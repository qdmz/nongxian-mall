<?php
// Final test - check what the API actually returns
header('Content-Type: application/json; charset=utf-8');

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

define('APP_ROOT', '/var/www/nongxian-mall/api');
chdir(APP_ROOT);

// PSR-4 autoloader
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\' => APP_ROOT . '/',
        'Core\\' => APP_ROOT . '/core/',
        'Middleware\\' => APP_ROOT . '/middleware/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) { require $file; return; }
            $fileLower = strtolower($file);
            if (is_file($fileLower)) { require $fileLower; return; }
        }
    }
});

require APP_ROOT . '/core/Helper.php';

// Mock admin auth
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test_admin_token_12345';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['group'] = 'pay';

// Run the app
$app = new Core\App();
$app->run();
