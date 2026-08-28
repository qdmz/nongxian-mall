<?php
$ch = curl_init('https://127.0.0.1/admin/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{"username":"admin","password":"admin123"}');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$resp = curl_exec($ch);
curl_close($ch);
$data = json_decode($resp, true);
$token = $data['data']['token'];

// 获取原始 JSON
$ch = curl_init('https://127.0.0.1/admin/config?group=pay');
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$resp = curl_exec($ch);
curl_close($ch);

echo "Raw JSON:\n$resp\n\n";

// 统计 pay 数组中的元素个数
$result = json_decode($resp, true);
$payItems = $result['data']['pay'] ?? [];
echo "Decoded count: " . count($payItems) . "\n";

// 检查是否有重复的 key
$keys = array_column($payItems, 'key');
$uniqueKeys = array_unique($keys);
echo "Unique keys: " . count($uniqueKeys) . "\n";
if (count($keys) !== count($uniqueKeys)) {
    $diff = array_diff_assoc($keys, $uniqueKeys);
    echo "Duplicate keys: " . implode(', ', $diff) . "\n";
}
