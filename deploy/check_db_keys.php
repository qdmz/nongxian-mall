<?php
// 登录获取token
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

// 获取所有配置
$ch = curl_init('https://127.0.0.1/admin/config');
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$resp = curl_exec($ch);
curl_close($ch);

$result = json_decode($resp, true);
$groups = $result['data'];

foreach ($groups as $groupName => $items) {
    echo "=== $groupName ===\n";
    foreach ($items as $item) {
        $isset = isset($item['is_set']) ? " (is_set={$item['is_set']})" : "";
        echo "  {$item['key']} = '{$item['value']}' type={$item['type']}{$isset}\n";
    }
    echo "\n";
}
