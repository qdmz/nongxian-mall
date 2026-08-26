<?php

namespace App\Models;

use Core\Model;

class VerifyCode extends Model
{
    protected static string $table = 'verify_codes';

    /** 发送验证码（含频率限制） */
    public static function send(string $target, string $type, string $scene, string $code): bool
    {
        $now = time();
        // 60秒内不重发
        $recent = db()->one(
            'SELECT id FROM verify_codes WHERE target = ? AND type = ? AND scene = ? AND created_at > ?',
            [$target, $type, $scene, $now - 60]
        );
        if ($recent) {
            json_error('发送太频繁，请60秒后再试');
        }
        // 同一IP每天最多50条
        $ipCount = db()->value(
            'SELECT COUNT(*) FROM verify_codes WHERE ip = ? AND created_at > ?',
            [client_ip(), $now - 86400]
        );
        if ($ipCount >= 50) {
            json_error('今日发送次数已达上限');
        }

        db()->insert('verify_codes', [
            'target' => $target,
            'type' => $type,
            'scene' => $scene,
            'code' => $code,
            'expire_at' => $now + 600, // 10分钟有效
            'ip' => client_ip(),
            'created_at' => $now,
        ]);

        // 实际发送
        if ($type === 'sms') {
            return \App\Services\SmsService::send($target, $code, $scene);
        }
        return \App\Services\EmailService::sendVerifyCode($target, $code);
    }

    /** 校验验证码 */
    public static function check(string $target, string $type, string $scene, string $code): bool
    {
        if (empty($code)) return false;
        $row = db()->one(
            'SELECT * FROM verify_codes WHERE target = ? AND type = ? AND scene = ? AND used = 0 AND expire_at > ? ORDER BY id DESC LIMIT 1',
            [$target, $type, $scene, time()]
        );
        if (!$row || !hash_equals((string)$row['code'], (string)$code)) {
            return false;
        }
        // 标记已使用
        db()->update('verify_codes', ['used' => 1], ['id' => $row['id']]);
        return true;
    }
}
