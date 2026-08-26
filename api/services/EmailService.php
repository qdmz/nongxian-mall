<?php

namespace App\Services;

/**
 * SMTP 邮件服务
 * 纯 PHP SMTP 实现（支持 SSL），无需 Composer 依赖
 * SMTP 配置在管理后台「系统设置-邮件配置」中填写
 */
class EmailService
{
    /** 是否已启用 */
    public static function enabled(): bool
    {
        return config('smtp.smtp_enabled', '0') === '1'
            && config('smtp.smtp_host', '')
            && config('smtp.smtp_username', '');
    }

    /** 发送邮件 */
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        $logId = self::log($to, $subject, $htmlBody);
        try {
            if (!self::enabled()) {
                self::updateLog($logId, 2, '邮件服务未配置');
                return false;
            }
            $ok = self::smtpSend($to, $subject, $htmlBody);
            self::updateLog($logId, $ok ? 1 : 2, $ok ? '' : '发送失败');
            return $ok;
        } catch (\Throwable $e) {
            self::updateLog($logId, 2, $e->getMessage());
            return false;
        }
    }

    /** 发送验证码邮件 */
    public static function sendVerifyCode(string $to, string $code): bool
    {
        $appName = config('app.app_name', '田冲助农商城');
        $minutes = 10;
        $html = <<<HTML
<!DOCTYPE html>
<html>
<body style="margin:0;padding:20px;background:#f5f5f5;font-family:'Helvetica Neue',Arial,'PingFang SC','Microsoft YaHei',sans-serif;">
<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;">
  <div style="background:#e63946;padding:24px;text-align:center;">
    <h1 style="margin:0;color:#fff;font-size:20px;">{$appName}</h1>
  </div>
  <div style="padding:32px 24px;">
    <p style="color:#333;font-size:15px;">您好！</p>
    <p style="color:#555;font-size:14px;line-height:1.8;">您正在使用邮箱验证功能，验证码为：</p>
    <div style="text-align:center;margin:24px 0;">
      <span style="display:inline-block;padding:12px 32px;background:#fff3f3;border:1px dashed #e63946;border-radius:6px;color:#e63946;font-size:28px;font-weight:bold;letter-spacing:8px;">{$code}</span>
    </div>
    <p style="color:#999;font-size:13px;">验证码 {$minutes} 分钟内有效，请勿泄露给他人。</p>
    <p style="color:#999;font-size:13px;">如果这不是您的操作，请忽略此邮件。</p>
  </div>
  <div style="background:#fafafa;padding:16px 24px;text-align:center;color:#bbb;font-size:12px;">
    强村富民 · 助农电商 —— 田冲红色美丽乡村工坊
  </div>
</div>
</body>
</html>
HTML;
        return self::send($to, "【{$appName}】邮箱验证码", $html);
    }

    /** SMTP 协议发送 */
    private static function smtpSend(string $to, string $subject, string $htmlBody): bool
    {
        $host = config('smtp.smtp_host', '');
        $port = (int)config('smtp.smtp_port', '465');
        $ssl = config('smtp.smtp_ssl', '1') === '1';
        $username = config('smtp.smtp_username', '');
        $password = config('smtp.smtp_password', '');
        $fromName = config('smtp.smtp_from_name', '田冲助农商城');

        $remote = ($ssl ? 'ssl://' : '') . $host . ':' . $port;
        $timeout = 15;
        $socket = @stream_socket_client($remote, $errno, $errstr, $timeout);
        if (!$socket) {
            throw new \RuntimeException("SMTP连接失败: {$errstr}");
        }
        stream_set_timeout($socket, $timeout);

        $read = function () use ($socket): string {
            $data = '';
            while ($line = fgets($socket, 515)) {
                $data .= $line;
                if (strlen($line) < 4 || $line[3] === ' ') break;
            }
            return $data;
        };
        $write = function (string $cmd) use ($socket): void {
            fwrite($socket, $cmd . "\r\n");
        };

        $read(); // banner

        $write('HELO ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $read();

        // 尝试 STARTTLS（587 端口）
        if (!$ssl && $port == 587) {
            $write('STARTTLS');
            $response = $read();
            if (str_starts_with($response, '220')) {
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $write('HELO ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                $read();
            }
        }

        $write('AUTH LOGIN');
        $read();
        $write(base64_encode($username));
        $read();
        $write(base64_encode($password));
        $authResp = $read();
        if (!str_starts_with($authResp, '235')) {
            throw new \RuntimeException('SMTP认证失败: ' . trim($authResp));
        }

        $write("MAIL FROM:<{$username}>");
        $fromResp = $read();
        if (!str_starts_with($fromResp, '250')) {
            throw new \RuntimeException('发件人被拒绝: ' . trim($fromResp));
        }

        $write("RCPT TO:<{$to}>");
        $toResp = $read();
        if (!str_starts_with($toResp, '250') && !str_starts_with($toResp, '251')) {
            throw new \RuntimeException('收件人被拒绝: ' . trim($toResp));
        }

        $write('DATA');
        $read();

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'From: ' . self::encodeName($fromName) . " <{$username}>",
            'To: <' . $to . '>',
            'Subject: ' . self::encodeName($subject),
            'Date: ' . date('r'),
        ];
        $body = base64_encode($htmlBody);
        // 按行切分 base64 防止超长
        $body = chunk_split($body, 76, "\r\n");
        $write(implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.");
        $sendResp = $read();
        if (!str_starts_with($sendResp, '250')) {
            throw new \RuntimeException('邮件发送失败: ' . trim($sendResp));
        }

        $write('QUIT');
        $read();
        fclose($socket);
        return true;
    }

    private static function encodeName(string $name): string
    {
        if (preg_match('/[\x80-\xff]/', $name)) {
            return '=?UTF-8?B?' . base64_encode($name) . '?=';
        }
        return $name;
    }

    private static function log(string $to, string $subject, string $content): int
    {
        try {
            return db()->insert('email_logs', [
                'email' => $to,
                'subject' => $subject,
                'content' => mb_substr($content, 0, 5000),
                'status' => 0,
                'created_at' => time(),
            ]);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private static function updateLog(int $id, int $status, string $error = ''): void
    {
        if ($id <= 0) return;
        try {
            db()->update('email_logs', ['status' => $status, 'error' => $error], ['id' => $id]);
        } catch (\Throwable $e) {
        }
    }
}
