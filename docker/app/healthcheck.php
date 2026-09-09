<?php
/**
 * 容器健康检查
 * 验证 PHP 正常 + 数据库连通（走应用自身的 Database 配置）
 */
declare(strict_types=1);

// 最小引导：Database 走 config/database.php（读 DB_* 环境变量）
define('APP_ROOT', '/var/www/html/api');
require APP_ROOT . '/core/Helper.php';

try {
    $cfg = require APP_ROOT . '/config/database.php';
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], $cfg['database']),
        $cfg['username'],
        $cfg['password'],
        [PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->query('SELECT 1');
    http_response_code(200);
    echo 'ok';
} catch (Throwable $e) {
    http_response_code(503);
    echo 'db unavailable: ' . $e->getMessage();
}
