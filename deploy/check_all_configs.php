<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=nongxian_mall;charset=utf8mb4', 'nongxian', 'nongxian123');
$rows = $db->query("SELECT `group`, `key`, `value`, `type` FROM system_configs ORDER BY `group`, `sort`, `id`")->fetchAll(PDO::FETCH_ASSOC);

$grouped = [];
foreach ($rows as $r) {
    $grouped[$r['group']][] = $r;
}

foreach ($grouped as $group => $items) {
    echo "=== $group (count=" . count($items) . ") ===\n";
    foreach ($items as $item) {
        $val = $item['value'] === '' ? '(empty)' : $item['value'];
        echo "  {$item['key']} = $val (type={$item['type']})\n";
    }
    echo "\n";
}
