<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=nongxian_mall;charset=utf8mb4', 'nongxian', 'nongxian123');
$rows = $db->query("SELECT id, name, cover_image, images FROM products ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
