<?php

namespace Middleware;

use Core\Database;

/**
 * 管理员操作日志
 */
class AdminLog
{
    public function handle(): void
    {
        // 记录写操作日志
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            try {
                $adminId = \Core\Auth::adminId() ?? 0;
                $uri = $_SERVER['REQUEST_URI'] ?? '';
                Database::instance()->insert('admin_logs', [
                    'admin_id' => $adminId,
                    'module' => trim(explode('?', $uri)[0] ?? '', '/'),
                    'action' => $method,
                    'description' => $uri,
                    'ip' => client_ip(),
                    'data' => json_encode($_POST ?: [], JSON_UNESCAPED_UNICODE),
                    'created_at' => time(),
                ]);
            } catch (\Throwable $e) {
                // 日志失败不影响业务
            }
        }
    }
}
