<?php

namespace Core;

/**
 * 简单的固定窗口限流（基于 MySQL 表 rate_limits）
 * 2026-10-14 新增：用于登录、注册、验证码校验防暴力破解
 */
class RateLimit
{
    /** 记一次命中，超过 $max 返回 false */
    public static function hit(string $key, int $max, int $window): bool
    {
        $now = time();
        $k = substr(hash('sha256', $key), 0, 64);
        try {
            db()->query(
                'INSERT INTO rate_limits (`k`, hits, reset_at) VALUES (?, 1, ?)
                 ON DUPLICATE KEY UPDATE hits = IF(reset_at <= ?, 1, hits + 1), reset_at = IF(reset_at <= ?, VALUES(reset_at), reset_at)',
                [$k, $now + $window, $now, $now]
            );
            $hits = (int)db()->value('SELECT hits FROM rate_limits WHERE `k` = ?', [$k]);
            return $hits <= $max;
        } catch (\Throwable $e) {
            error_log('[nongxian-mall] RateLimit error: ' . $e->getMessage());
            return true; // 限流故障不阻断业务
        }
    }

    /** 命中则直接返回错误 */
    public static function check(string $key, int $max, int $window, string $msg = '操作过于频繁，请稍后再试'): void
    {
        if (!self::hit($key, $max, $window)) {
            Response::error($msg, 429, null, 429);
        }
    }

    /** 成功后清除计数 */
    public static function clear(string $key): void
    {
        try {
            db()->query('DELETE FROM rate_limits WHERE `k` = ?', [substr(hash('sha256', $key), 0, 64)]);
        } catch (\Throwable $e) {
        }
    }
}
