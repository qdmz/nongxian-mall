<?php

namespace Middleware;

use Core\Database;

/**
 * 管理员操作日志
 * 2026-10-14：原中间件未挂到任何路由；同时改为记录 JSON 请求体并对敏感字段脱敏
 */
class AdminLog
{
    private const SENSITIVE = ['password', 'old_password', 'new_password', 'epay_key', 'smtp_password', 'sms_secret_key', 'secret', 'token'];

    public function handle(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, ['POST', 'PUT', 'DELETE'])) {
            return;
        }
        try {
            $adminId = \Core\Auth::adminId() ?? 0;
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $data = request()->all();
            array_walk_recursive($data, function (&$v, $k) {
                if (is_string($k) && in_array(strtolower($k), self::SENSITIVE, true)) {
                    $v = '***';
                }
            });
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            if (strlen((string)$json) > 4000) {
                $json = mb_substr((string)$json, 0, 4000) . '...';
            }
            Database::instance()->insert('admin_logs', [
                'admin_id' => $adminId,
                'module' => mb_substr(trim(explode('?', $uri)[0] ?? '', '/'), 0, 50),
                'action' => $method,
                'description' => mb_substr($uri, 0, 255),
                'ip' => client_ip(),
                'data' => $json,
                'created_at' => time(),
            ]);
        } catch (\Throwable $e) {
            // 日志失败不影响业务
        }
    }
}
