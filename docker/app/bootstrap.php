<?php
/**
 * 首次启动引导
 * 1. 校验数据库表结构是否已导入（users/admin_users 是否存在）
 * 2. 表缺失时自动导入 sql/nongxian_mall.sql（幂等：只在空库执行）
 * 3. 应用 ADMIN_PASSWORD 环境变量（存在且非空时重置 admin 密码）
 */
declare(strict_types=1);

define('APP_ROOT', '/var/www/html/api');
require APP_ROOT . '/core/Helper.php';

$cfg = require APP_ROOT . '/config/database.php';
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], $cfg['database']),
    $cfg['username'],
    $cfg['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ---------- 1. 表结构校验 ----------
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if (count($tables) === 0) {
    echo "[bootstrap] 数据库为空，导入 sql/nongxian_mall.sql ...\n";
    $sql = file_get_contents('/var/www/html/sql/nongxian_mall.sql');
    if ($sql === false) {
        fwrite(STDERR, "[bootstrap] SQL 文件不存在\n");
        exit(1);
    }
    $pdo->exec($sql);
    echo "[bootstrap] 导入完成\n";
} else {
    echo "[bootstrap] 已有 " . count($tables) . " 张表，跳过导入\n";
}

// ---------- 2. 应用管理员密码 ----------
$adminPassword = getenv('ADMIN_PASSWORD');
if (is_string($adminPassword) && $adminPassword !== '') {
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE admin_users SET password = ?, updated_at = ? WHERE username = 'admin'");
    $stmt->execute([$hash, time()]);
    echo "[bootstrap] 已应用 ADMIN_PASSWORD 环境变量\n";
}

echo "[bootstrap] 完成\n";
