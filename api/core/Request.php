<?php

namespace Core;

/**
 * 请求封装
 */
class Request
{
    private static ?Request $instance = null;
    private array $data;
    private array $query;

    public function __construct()
    {
        $this->data = $this->parseBody();
        $this->query = $_GET;
    }

    public static function instance(): static
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    private function parseBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            return is_array($data) ? $data : [];
        }
        if (str_contains($contentType, 'multipart/form-data') || str_contains($contentType, 'application/x-www-form-urlencoded')) {
            return $_POST;
        }
        // 兼容其他情况
        $raw = file_get_contents('php://input');
        if ($raw) {
            $data = json_decode($raw, true);
            if (is_array($data)) return $data;
        }
        return $_POST;
    }

    /** 取参数（body 优先，其次 query） */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $this->query[$key] ?? $default;
    }

    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->param($key);
            }
        }
        return $result;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->data);
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]) || isset($this->query[$key]);
    }

    public function string(string $key, string $default = ''): string
    {
        $v = $this->param($key, $default);
        return is_scalar($v) ? trim((string)$v) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->param($key, $default);
        return is_numeric($v) ? (int)$v : $default;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $v = $this->param($key, $default);
        return is_numeric($v) ? (float)$v : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $v = $this->param($key, $default);
        if (is_bool($v)) return $v;
        return in_array($v, [1, '1', 'true', 'on'], true);
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    /** Authorization Bearer token */
    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization');
        if ($auth && preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return $m[1];
        }
        // 兼容 query / header 传 token
        return $_GET['token'] ?? $this->header('Token');
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function ip(): string
    {
        return client_ip();
    }
}
