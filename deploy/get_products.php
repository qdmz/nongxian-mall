<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=nongxian_mall;charset=utf8mb4', 'nongxian', 'nongxian123');
$rows = $db->query("SELECT id, name, subtitle, image, price, original_price, category_id, origin, farmer, description FROM products ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

echo "=== Products (" . count($rows) . ") ===\n";
foreach ($rows as $r) {
    $img = $r['image'] ?: '(no image)';
    echo "[{$r['id']}] {$r['name']} - ¥{$r['price']} - img: {$img}\n";
    echo "    subtitle: {$r['subtitle']}\n";
    echo "    origin: {$r['origin']} | farmer: {$r['farmer']}\n";
    echo "    desc: " . substr($r['description'] ?? '', 0, 80) . "\n\n";
}
