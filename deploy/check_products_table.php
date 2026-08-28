<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=nongxian_mall;charset=utf8mb4', 'nongxian', 'nongxian123');

// 查看products表结构
$cols = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_ASSOC);
echo "=== products table structure ===\n";
foreach ($cols as $c) {
    echo "  {$c['Field']} ({$c['Type']})\n";
}

echo "\n=== Sample data ===\n";
$rows = $db->query("SELECT * FROM products LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "\n--- Product {$r['id']} ---\n";
    foreach ($r as $k => $v) {
        if (is_string($v) && strlen($v) > 100) {
            $v = substr($v, 0, 100) . '...';
        }
        echo "  $k: $v\n";
    }
}
