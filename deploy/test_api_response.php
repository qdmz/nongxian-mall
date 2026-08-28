<?php
/**
 * Test script - place on server to check API response
 */
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

// Load helper
require APP_ROOT . '/core/Helper.php';

// Use the DB directly to check what's in the database
echo "=== Direct DB query ===\n";
$db = \Core\Database::instance();

// Check all configs for pay group
$rows = $db->all("SELECT `key`, `value`, `type` FROM system_configs WHERE `group` = 'pay' ORDER BY sort");
foreach ($rows as $row) {
    echo sprintf("  key=%-20s value=%-30s type=%s\n", $row['key'], $row['value'], $row['type']);
}

echo "\n=== Full row data ===\n";
$fullRows = $db->all("SELECT * FROM system_configs WHERE `group` = ? ORDER BY sort", ['pay']);
foreach ($fullRows as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== Test controller index() ===\n";
// Simulate what the controller does
$list = $db->all("SELECT * FROM system_configs WHERE `group` = ? ORDER BY `group`, sort, id", ['pay']);
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
echo json_encode($grouped, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
