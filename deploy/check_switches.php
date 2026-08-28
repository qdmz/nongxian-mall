<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=nongxian_mall;charset=utf8mb4', 'nongxian', 'nongxian123');
$rows = $db->query("SELECT `group`, `key`, `value`, `type` FROM system_configs WHERE `key` LIKE '%enabled%' OR `key` LIKE '%ssl%' ORDER BY `group`, `key`")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "{$r['group']}.{$r['key']} = '{$r['value']}' (type={$r['type']})\n";
}
