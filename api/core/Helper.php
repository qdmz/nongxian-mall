<?php
/**
 * 核心助手函数
 */

use Core\Response;

if (!function_exists('config')) {
    /** 读取配置 */
    function config(string $key, mixed $default = null): mixed
    {
        return Core\Config::get($key, $default);
    }
}

if (!function_exists('db')) {
    /** 获取数据库连接 */
    function db(): Core\Database
    {
        return Core\Database::instance();
    }
}

if (!function_exists('request')) {
    /** 当前请求 */
    function request(): Core\Request
    {
        return Core\Request::instance();
    }
}

if (!function_exists('json_success')) {
    function json_success(mixed $data = null, string $msg = 'success', int $code = 0): void
    {
        Response::success($data, $msg, $code);
    }
}

if (!function_exists('json_error')) {
    function json_error(string $msg = 'error', int $code = 1, mixed $data = null): void
    {
        Response::error($msg, $code, $data);
    }
}

if (!function_exists('now')) {
    function now(): int
    {
        return time();
    }
}

if (!function_exists('order_no')) {
    /** 生成订单号：年月日时分秒 + 微秒 + 随机数 */
    function order_no(string $prefix = ''): string
    {
        return $prefix . date('YmdHis') . substr((string)microtime(true), -4, 3) . str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generate_code')) {
    /** 生成推荐码 */
    function generate_code(int $length = 8): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $str;
    }
}

if (!function_exists('generate_verify_code')) {
    /** 生成数字验证码 */
    function generate_verify_code(int $length = 6): string
    {
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= (string)random_int(0, 9);
        }
        return $str;
    }
}

if (!function_exists('client_ip')) {
    function client_ip(): string
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
