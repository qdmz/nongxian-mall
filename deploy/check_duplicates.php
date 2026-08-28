<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=nongxian_mall;charset=utf8mb4', 'nongxian', 'nongxian123');

// 查找重复的key
$rows = $db->query("SELECT `group`, `key`, COUNT(*) as cnt, GROUP_CONCAT(id) as ids FROM system_configs GROUP BY `group`, `key` HAVING cnt > 1 ORDER BY `group`, `key`")->fetchAll(PDO::FETCH_ASSOC);

echo "=== Duplicates ===\n";
foreach ($rows as $r) {
    echo "{$r['group']}.{$r['key']} = {$r['cnt']} times (ids: {$r['ids']})\n";
}

echo "\n=== All IDs ===\n";
$rows = $db->query("SELECT id, `group`, `key`, `value` FROM system_configs ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $val = $r['value'] === '' ? '(empty)' : (strlen($r['value']) > 30 ? substr($r['value'], 0, 30) . '...' : $r['value']);
    echo "  [{$r['id']}] {$r['group']}.{$r['key']} = $val\n";
}
