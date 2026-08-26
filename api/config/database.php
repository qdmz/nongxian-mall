<?php
/**
 * 数据库配置
 * 本地开发环境配置，部署时修改
 */
return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'nongxian_mall',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
    'ssl_ca' => getenv('DB_SSL_CA') ?: '',
];
