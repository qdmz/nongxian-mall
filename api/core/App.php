<?php

namespace Core;

/**
 * 应用引导
 */
class App
{
    public function run(): void
    {
        $router = new Router();
        // 加载路由定义
        $routeFiles = glob(APP_ROOT . '/routes/*.php');
        foreach ($routeFiles as $file) {
            $router->loadFile($file);
        }
        $router->dispatch();
    }
}
