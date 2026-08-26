<?php
/**
 * 本地开发用：PHP 内置服务器路由重写
 * 启动：php -S localhost:8000 -t public router.php
 * 作用：把 /api/xxx 和 /admin/xxx 都转发到 index.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 静态资源直接返回（上传的图片等）
$staticFile = __DIR__ . '/public' . $uri;
if ($uri !== '/' && is_file($staticFile)) {
    return false; // PHP 内置服务器会自动返回文件
}

// 其他请求交给 index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
require __DIR__ . '/public/index.php';
