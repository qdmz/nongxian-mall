<?php

namespace Core;

/**
 * JWT 认证（HS256，纯 PHP 实现，无依赖）
 */
class Auth
{
    private static string $secret;

    private static function secret(): string
    {
        if (!isset(self::$secret)) {
            self::$secret = config('app.jwt_secret', 'nongxian-mall-secret-change-me');
        }
        return self::$secret;
    }

    /** 签发 token */
    public static function issue(int $uid, string $type = 'user', int $ttl = 86400 * 30): string
    {
        $header = self::b64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::b64url(json_encode([
            'uid' => $uid,
            'type' => $type,
            'iat' => time(),
            'exp' => time() + $ttl,
        ]));
        $sig = self::b64url(hash_hmac('sha256', "{$header}.{$payload}", self::secret(), true));
        return "{$header}.{$payload}.{$sig}";
    }

    /** 验证 token，返回 payload 或 null */
    public static function verify(?string $token): ?array
    {
        if (!$token) return null;
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$header, $payload, $sig] = $parts;
        $expected = self::b64url(hash_hmac('sha256', "{$header}.{$payload}", self::secret(), true));
        if (!hash_equals($expected, $sig)) return null;
        $data = json_decode(self::b64urlDecode($payload), true);
        if (!is_array($data)) return null;
        if (isset($data['exp']) && $data['exp'] < time()) return null;
        return $data;
    }

    /** 从请求中获取当前用户ID（用户端） */
    public static function userId(): ?int
    {
        $payload = self::verify(request()->bearerToken());
        if (!$payload || ($payload['type'] ?? '') !== 'user') return null;
        return isset($payload['uid']) ? (int)$payload['uid'] : null;
    }

    /** 从请求中获取当前管理员ID */
    public static function adminId(): ?int
    {
        $payload = self::verify(request()->bearerToken());
        if (!$payload || ($payload['type'] ?? '') !== 'admin') return null;
        return isset($payload['uid']) ? (int)$payload['uid'] : null;
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64urlDecode(string $data): string|false
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
