<?php
// Test the actual controller's index method
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

echo "=== Simulating fixed controller index() for 'pay' ===\n";
$list = $db->all("SELECT * FROM system_configs WHERE `group` = ? ORDER BY `group`, sort, id", ['pay']);

// Password desensitization with reference + unset
foreach ($list as &$item) {
    if ($item['type'] === 'password') {
        $item['is_set'] = !empty($item['value']);
        $item['value'] = '';
    }
}
unset($item); // THE FIX

$grouped = [];
foreach ($list as $item) {
    $grouped[$item['group']][] = $item;
}

echo "Items in grouped['pay']: " . count($grouped['pay']) . "\n";
foreach ($grouped['pay'] as $item) {
    echo "  key={$item['key']} value={$item['value']}\n";
}

// Check smtp group
echo "\n=== Simulating fixed controller index() for 'smtp' ===\n";
$list2 = $db->all("SELECT * FROM system_configs WHERE `group` = ? ORDER BY `group`, sort, id", ['smtp']);
foreach ($list2 as &$item) {
    if ($item['type'] === 'password') {
        $item['is_set'] = !empty($item['value']);
        $item['value'] = '';
    }
}
unset($item);

$grouped2 = [];
foreach ($list2 as $item) {
    $grouped2[$item['group']][] = $item;
}
echo "Items in grouped['smtp']: " . count($grouped2['smtp']) . "\n";
foreach ($grouped2['smtp'] as $item) {
    echo "  key={$item['key']} value={$item['value']}\n";
}

// Check sms group
echo "\n=== Simulating fixed controller index() for 'sms' ===\n";
$list3 = $db->all("SELECT * FROM system_configs WHERE `group` = ? ORDER BY `group`, sort, id", ['sms']);
foreach ($list3 as &$item) {
    if ($item['type'] === 'password') {
        $item['is_set'] = !empty($item['value']);
        $item['value'] = '';
    }
}
unset($item);

$grouped3 = [];
foreach ($list3 as $item) {
    $grouped3[$item['group']][] = $item;
}
echo "Items in grouped['sms']: " . count($grouped3['sms']) . "\n";
foreach ($grouped3['sms'] as $item) {
    echo "  key={$item['key']} value={$item['value']}\n";
}
