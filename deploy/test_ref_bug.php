<?php
// Test if the &$item reference bug causes last item loss
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

echo "=== Test with reference (buggy) ===\n";
$list = $db->all("SELECT * FROM system_configs WHERE `group` = ? ORDER BY `group`, sort, id", ['pay']);
echo "Before: " . count($list) . " items\n";

// This is the buggy pattern from the controller
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
echo "After (with & ref): " . count($grouped['pay']) . " items\n";
foreach ($grouped['pay'] as $item) {
    echo "  key={$item['key']} value={$item['value']}\n";
}

echo "\n=== Test without reference (correct) ===\n";
$list2 = $db->all("SELECT * FROM system_configs WHERE `group` = ? ORDER BY `group`, sort, id", ['pay']);
$grouped2 = [];
foreach ($list2 as $item) {
    if ($item['type'] === 'password') {
        $item['is_set'] = !empty($item['value']);
        $item['value'] = '';
    }
    $grouped2[$item['group']][] = $item;
}
echo "After (without & ref): " . count($grouped2['pay']) . " items\n";
foreach ($grouped2['pay'] as $item) {
    echo "  key={$item['key']} value={$item['value']}\n";
}
