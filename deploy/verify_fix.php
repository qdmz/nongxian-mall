<?php
define('APP_ROOT', '/var/www/nongxian-mall/api');
chdir(APP_ROOT);
$spl = function (string $class): void {
    $prefixes = ['App\\' => APP_ROOT . '/', 'Core\\' => APP_ROOT . '/core/', 'Middleware\\' => APP_ROOT . '/middleware/'];
    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $rel = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $rel) . '.php';
            if (is_file($file)) { require $file; return; }
            $l = strtolower($file);
            if (is_file($l)) { require $l; return; }
        }
    }
};
spl_autoload_register($spl);
require APP_ROOT . '/core/Helper.php';
$db = \Core\Database::instance();

$group = 'app';
$list = $db->all("SELECT * FROM system_configs WHERE `group` = ? ORDER BY `group`, sort, id", [$group]);
foreach ($list as &$item) {
    if ($item['type'] === 'password') {
        $item['is_set'] = !empty($item['value']);
        $item['value'] = '';
    }
}
unset($item);
$grouped = [];
foreach ($list as $item) {
    $grouped[$item['group']][] = $item;
}

echo json_encode($grouped, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
