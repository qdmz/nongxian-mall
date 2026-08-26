<?php

namespace Core;

/**
 * JSON 响应
 */
class Response
{
    public static function jsonHeader(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    public static function cors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Token, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }

    public static function success(mixed $data = null, string $msg = 'success', int $code = 0, int $httpStatus = 200): void
    {
        self::output([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ], $httpStatus);
    }

    public static function error(string $msg = 'error', int $code = 1, mixed $data = null, int $httpStatus = 200): void
    {
        self::output([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ], $httpStatus);
    }

    public static function page(array $list, int $total, int $page, int $pageSize): void
    {
        self::success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => (int)ceil($total / max(1, $pageSize)),
        ]);
    }

    private static function output(array $data, int $httpStatus): void
    {
        http_response_code($httpStatus);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
