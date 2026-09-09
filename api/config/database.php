<?php
/**
 * 数据库配置
 * 本地开发环境配置，部署时修改
 */
return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'nongxian_mall',
    // 本地/沙箱环境默认使用 nongxian 应用账号（生产环境请通过 DB_USER/DB_PASS 环境变量覆盖）
    'username' => getenv('DB_USER') ?: 'nongxian',
    'password' => getenv('DB_PASS') ?: 'nongxian_mall_2026',
    'charset' => 'utf8mb4',
    'ssl_ca' => getenv('DB_SSL_CA') ?: '',
];
