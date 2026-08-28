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

foreach (['pay', 'smtp', 'sms', 'app'] as $group) {
    $ch = curl_init("https://127.0.0.1/admin/config?group=$group");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $resp = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($resp, true);
    $items = $result['data'][$group] ?? [];
    echo "=== $group (count=" . count($items) . ") ===\n";
    foreach ($items as $item) {
        $val = $item['value'] === '' ? "(empty)" : $item['value'];
        echo "  {$item['key']} = $val\n";
    }
    echo "\n";
}
