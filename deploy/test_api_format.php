<?php
// Test the exact API response format
define('APP_ROOT', '/var/www/nongxian-mall/api');
chdir(APP_ROOT);

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

$db = \Core\Database::instance();

// Test all 4 groups
foreach (['pay', 'smtp', 'sms', 'app'] as $group) {
    echo "=== Group: $group ===\n";
    $list = $db->all("SELECT * FROM system_configs WHERE `group` = ? ORDER BY `group`, sort, id", [$group]);
    foreach ($list as &$item) {
        if ($item['type'] === 'password') {
            $item['is_set'] = !empty($item['value']);
            $item['value'] = '';
        }
    }
    $grouped = [];
    foreach ($list as $item) {
        $grouped[$item['group']][] = $item;
    }
    // Show just the keys and values
    foreach ($grouped[$group] as $item) {
        echo sprintf("  key=%-25s value=%-15s type=%-10s\n", $item['key'], $item['value'], $item['type']);
    }
    echo "\n";
}
