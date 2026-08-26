<?php

namespace Core;

/**
 * 路由器
 * 支持中间件、动态参数
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    public function loadFile(string $file): void
    {
        $router = $this;
        require $file;
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $this->routes[$method][] = [
            'pattern' => $this->compile($path),
            'raw' => $path,
            'handler' => $handler,   // [ControllerClass, method]
            'middleware' => $middleware,
        ];
    }

    /** 把 /api/user/{id} 编译成正则 */
    private function compile(string $path): array
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^\/]+)', $path);
        return ['#^' . $regex . '$#', $path];
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // CORS 预检
        if ($method === 'OPTIONS') {
            Response::cors();
            http_response_code(204);
            exit;
        }

        Response::cors();
        Response::jsonHeader();

        // 去掉 base path（如果 API 部署在子目录）
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }
        $uri = '/' . trim($uri, '/');

        $routes = $this->routes[$method] ?? [];
        foreach ($routes as $route) {
            [$regex] = $route['pattern'];
            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->execute($route, $params);
                return;
            }
        }

        Response::error('接口不存在: ' . $method . ' ' . $uri, 404, null, 404);
    }

    private function execute(array $route, array $params): void
    {
        // 执行中间件
        foreach ($route['middleware'] as $middleware) {
            $class = 'Middleware\\' . $middleware;
            if (!class_exists($class)) {
                throw new \RuntimeException("中间件不存在: {$middleware}");
            }
            $instance = new $class();
            $instance->handle();
        }

        [$controllerClass, $method] = $route['handler'];
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("控制器不存在: {$controllerClass}");
        }
        $controller = new $controllerClass();
        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("方法不存在: {$controllerClass}::{$method}");
        }
        $controller->$method($params);
    }
}
