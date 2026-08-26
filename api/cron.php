<?php
/**
 * 定时任务入口
 * crontab: * * * * * php /path/to/api/cron.php >> /dev/null 2>&1
 */

declare(strict_types=1);

define('APP_ROOT', __DIR__);
define('CRON_MODE', true);

date_default_timezone_set('Asia/Shanghai');
ini_set('display_errors', '1');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// PSR-4 自动加载
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\'    => APP_ROOT . '/',
        'Core\\'   => APP_ROOT . '/core/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

require APP_ROOT . '/core/Helper.php';

try {
    $result = \App\Services\CronService::run();
    echo date('Y-m-d H:i:s') . ' cron: ' . json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
    echo date('Y-m-d H:i:s') . ' cron error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
