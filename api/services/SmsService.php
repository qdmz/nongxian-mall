<?php

namespace App\Services;

/**
 * 短信服务
 * 支持阿里云短信 / 腾讯云短信
 * 配置在管理后台「系统设置-短信配置」中填写
 * 未配置时进入开发模式：验证码直接写入 sms_logs，可在后台查看（方便本地调试）
 */
class SmsService
{
    public static function enabled(): bool
    {
        return config('sms.sms_enabled', '0') === '1'
            && config('sms.sms_access_key', '')
            && config('sms.sms_secret_key', '');
    }

    /**
     * 发送短信验证码
     */
    public static function send(string $phone, string $code, string $scene = 'verify'): bool
    {
        $content = "您的验证码是{$code}，10分钟内有效。如非本人操作请忽略。【助农商城】";
        $logId = self::log($phone, 'verify_code', $content, $scene, $code);

        try {
            if (!self::enabled()) {
                // 开发模式：不真正发送，记录日志即可，验证码可从后台短信日志查看
                self::updateLog($logId, 1, '', 'dev_mode');
                return true;
            }

            $provider = config('sms.sms_provider', 'aliyun');
            if ($provider === 'tencent') {
                $result = self::sendByTencent($phone, $code);
            } else {
                $result = self::sendByAliyun($phone, $code);
            }

            self::updateLog($logId, $result['ok'] ? 1 : 2, $result['error'] ?? '', $result['response'] ?? '');
            return $result['ok'];
        } catch (\Throwable $e) {
            self::updateLog($logId, 2, $e->getMessage());
            return false;
        }
    }

    /**
     * 发送通知短信（订单发货等）
     */
    public static function sendNotify(string $phone, string $templateParam, string $scene = 'notify'): bool
    {
        if (!self::enabled()) return false;
        return self::send($phone, $templateParam, $scene);
    }

    /**
     * 阿里云短信 API（签名 V3）
     */
    private static function sendByAliyun(string $phone, string $code): array
    {
        $accessKeyId = config('sms.sms_access_key', '');
        $accessKeySecret = config('sms.sms_secret_key', '');
        $signName = config('sms.sms_sign', '');
        $templateCode = config('sms.sms_template_code', '');

        // 使用阿里云短信 API（dysmsapi.aliyuncs.com）
        // 公共参数
        $params = [
            'AccessKeyId' => $accessKeyId,
            'Action' => 'SendSms',
            'Format' => 'JSON',
            'PhoneNumbers' => $phone,
            'SignName' => $signName,
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => bin2hex(random_bytes(16)),
            'SignatureVersion' => '1.0',
            'TemplateCode' => $templateCode,
            'TemplateParam' => json_encode(['code' => $code], JSON_UNESCAPED_UNICODE),
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'Version' => '2017-05-25',
        ];

        // 计算签名
        ksort($params);
        $sortedQuery = '';
        foreach ($params as $k => $v) {
            $sortedQuery .= '&' . self::aliEncode($k) . '=' . self::aliEncode((string)$v);
        }
        $stringToSign = 'GET&' . self::aliEncode('/') . '&' . self::aliEncode(substr($sortedQuery, 1));
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', true));
        $params['Signature'] = $signature;

        $url = 'https://dysmsapi.aliyuncs.com/?' . http_build_query($params);
        $response = self::httpGet($url);
        $data = json_decode($response, true);

        $ok = isset($data['Code']) && $data['Code'] === 'OK';
        return ['ok' => $ok, 'error' => $ok ? '' : ($data['Message'] ?? '发送失败'), 'response' => $response];
    }

    /**
     * 腾讯云短信 API
     */
    private static function sendByTencent(string $phone, string $code): array
    {
        $secretId = config('sms.sms_access_key', '');
        $secretKey = config('sms.sms_secret_key', '');
        $signName = config('sms.sms_sign', '');
        $templateId = config('sms.sms_template_code', '');
        $sdkAppId = config('sms.sms_sdk_app_id', '');

        $host = 'sms.tencentcloudapi.com';
        $service = 'sms';
        $version = '2021-01-11';
        $action = 'SendSms';
        $timestamp = time();

        $payload = json_encode([
            'PhoneNumberSet' => ['+86' . $phone],
            'SmsSdkAppId' => $sdkAppId,
            'SignName' => $signName,
            'TemplateId' => $templateId,
            'TemplateParamSet' => [$code],
        ], JSON_UNESCAPED_UNICODE);

        // TC3-HMAC-SHA256 签名
        $hashedPayload = hash('sha256', $payload);
        $canonicalRequest = "POST\n/\n\ncontent-type:application/json\nhost:{$host}\n\ncontent-type;host\n{$hashedPayload}";
        $date = gmdate('Y-m-d', $timestamp);
        $credentialScope = "{$date}/{$service}/tc3_request";
        $stringToSign = "TC3-HMAC-SHA256\n{$timestamp}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);
        $secretDate = hash_hmac('sha256', $date, 'TC3' . $secretKey, true);
        $secretService = hash_hmac('sha256', $service, $secretDate, true);
        $secretSigning = hash_hmac('sha256', 'tc3_request', $secretService, true);
        $signature = hash_hmac('sha256', $stringToSign, $secretSigning);

        $authorization = "TC3-HMAC-SHA256 Credential={$secretId}/{$credentialScope}, SignedHeaders=content-type;host, Signature={$signature}";

        $response = self::httpPost("https://{$host}/", $payload, [
            "Content-Type: application/json",
            "Authorization: {$authorization}",
            "X-TC-Action: {$action}",
            "X-TC-Version: {$version}",
            "X-TC-Timestamp: {$timestamp}",
        ]);

        $data = json_decode($response, true);
        $ok = isset($data['Response']['SendStatusSet'][0]['Code']) && $data['Response']['SendStatusSet'][0]['Code'] === 'Ok';
        return ['ok' => $ok, 'error' => $ok ? '' : ($data['Response']['SendStatusSet'][0]['Message'] ?? '发送失败'), 'response' => $response];
    }

    private static function aliEncode(string $str): string
    {
        $encoded = rawurlencode($str);
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], $encoded);
    }

    private static function httpGet(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($response === false) {
            throw new \RuntimeException('HTTP请求失败: ' . $error);
        }
        return $response;
    }

    private static function httpPost(string $url, string $body, array $headers): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($response === false) {
            throw new \RuntimeException('HTTP请求失败: ' . $error);
        }
        return $response;
    }

    private static function log(string $phone, string $template, string $content, string $scene, string $code = ''): int
    {
        try {
            return db()->insert('sms_logs', [
                'phone' => $phone,
                'template' => $template,
                'content' => $content,
                'scene' => $scene,
                'code' => $code,
                'provider' => config('sms.sms_provider', 'aliyun'),
                'status' => 0,
                'created_at' => time(),
            ]);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private static function updateLog(int $id, int $status, string $error = '', string $response = ''): void
    {
        if ($id <= 0) return;
        try {
            db()->update('sms_logs', ['status' => $status, 'error' => $error, 'response' => mb_substr($response, 0, 2000)], ['id' => $id]);
        } catch (\Throwable $e) {
        }
    }
}
