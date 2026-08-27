#!/usr/bin/env python3
"""部署 index.php 到服务器"""
import sys
sys.path.insert(0, 'C:/Users/admin/WorkBuddy/2026-08-25-16-30-21/nongxian-mall/deploy')
from ssh_tool import connect

CONTENT = """<?php
/**
 * 农产品在线商城系统 - API 入口
 * 田冲红色美丽乡村强村富民工坊
 */

declare(strict_types=1);

define('APP_ROOT', __DIR__);
define('APP_START', microtime(true));

// 错误处理
$debug = is_file(APP_ROOT . '/config/debug.lock');
if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

date_default_timezone_set('Asia/Shanghai');

// PSR-4 自动加载（支持大小写不敏感）
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\\\' => APP_ROOT . '/',
        'Core\\\\' => APP_ROOT . '/core/',
        'Middleware\\\\' => APP_ROOT . '/middleware/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
                return;
            }
            $fileLower = strtolower($file);
            if (is_file($fileLower)) {
                require $fileLower;
                return;
            }
        }
    }
});

require APP_ROOT . '/core/Helper.php';

try {
    $app = new Core\\App();
    $app->run();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => 500,
        'msg' => $debug ? $e->getMessage() : '服务器内部错误',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
"""

c = connect()
sftp = c.open_sftp()
with sftp.open('/var/www/nongxian-mall/api/index.php', 'w') as f:
    f.write(CONTENT)
sftp.close()
c.close()
print("index.php written successfully")
