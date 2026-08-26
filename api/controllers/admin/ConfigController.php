<?php

namespace App\Controllers\Admin;

use Core\Controller;

/**
 * 管理后台 - 系统配置（支付/SMTP/短信/应用）
 */
class ConfigController extends Controller
{
    /**
     * GET /admin/config
     * 按分组读取配置（密码类只返回是否已设置）
     */
    public function index(): void
    {
        $group = $this->request->string('group', '');
        $where = $group ? 'WHERE `group` = ?' : '';
        $params = $group ? [$group] : [];

        $list = db()->all("SELECT * FROM system_configs {$where} ORDER BY `group`, sort, id", $params);

        // 密码类脱敏
        foreach ($list as &$item) {
            if ($item['type'] === 'password') {
                $item['is_set'] = !empty($item['value']);
                $item['value'] = '';
            }
        }

        // 按分组组织
        $grouped = [];
        foreach ($list as $item) {
            $grouped[$item['group']][] = $item;
        }

        json_success($grouped);
    }

    /**
     * POST /admin/config
     * 批量保存配置
     * body: {"group": "pay", "configs": {"epay_pid": "1001", "epay_key": "xxx"}}
     */
    public function save(): void
    {
        $group = $this->request->string('group');
        $configs = $this->request->param('configs');
        $this->validate(['group' => 'required|label:配置分组']);
        if (!is_array($configs) || empty($configs)) {
            json_error('配置内容不能为空');
        }

        $allowedGroups = ['pay', 'smtp', 'sms', 'app', 'share'];
        if (!in_array($group, $allowedGroups, true)) {
            json_error('无效的配置分组');
        }

        // 该分组下所有合法 key
        $validKeys = db()->all('SELECT `key` FROM system_configs WHERE `group` = ?', [$group]);
        $validKeyMap = array_column($validKeys, 'key', 'key');

        $updated = 0;
        foreach ($configs as $key => $value) {
            if (!isset($validKeyMap[$key])) continue;
            // 密码类传空串表示不修改
            if ($value === '' || $value === null) {
                $isPassword = db()->value('SELECT type FROM system_configs WHERE `group` = ? AND `key` = ?', [$group, $key]);
                if ($isPassword === 'password') continue;
            }
            db()->query(
                'UPDATE system_configs SET `value` = ?, updated_at = ? WHERE `group` = ? AND `key` = ?',
                [(string)$value, time(), $group, $key]
            );
            $updated++;
        }

        json_success(['updated' => $updated], "配置已保存（{$updated} 项）");
    }

    /**
     * POST /admin/config/test-smtp
     * 测试 SMTP 配置（发送测试邮件）
     */
    public function testSmtp(): void
    {
        $to = $this->request->string('to');
        $this->validate(['to' => 'required|email|label:测试收件邮箱']);

        // 如果请求里带了临时配置，先保存
        $configs = $this->request->param('configs');
        if (is_array($configs) && !empty($configs)) {
            $this->saveConfigs('smtp', $configs);
        }

        $ok = \App\Services\EmailService::send(
            $to,
            '【测试】田冲助农商城 SMTP 配置测试',
            '<h2>SMTP 配置成功</h2><p>这是一封测试邮件，如果您收到了，说明邮件配置正确。</p><p>发送时间：' . date('Y-m-d H:i:s') . '</p>'
        );

        if ($ok) {
            json_success(null, '测试邮件已发送，请查收');
        } else {
            // 取最近错误
            $lastLog = db()->one('SELECT error FROM email_logs ORDER BY id DESC LIMIT 1');
            json_error('发送失败：' . ($lastLog['error'] ?? '请检查SMTP配置'));
        }
    }

    /**
     * POST /admin/config/test-sms
     * 测试短信配置
     */
    public function testSms(): void
    {
        $phone = $this->request->string('phone');
        $this->validate(['phone' => 'required|phone|label:测试手机号']);

        $configs = $this->request->param('configs');
        if (is_array($configs) && !empty($configs)) {
            $this->saveConfigs('sms', $configs);
        }

        $ok = \App\Services\SmsService::send($phone, generate_verify_code(6), 'test');
        if ($ok) {
            json_success(null, '测试短信已发送');
        } else {
            $lastLog = db()->one('SELECT error, response FROM sms_logs ORDER BY id DESC LIMIT 1');
            json_error('发送失败：' . ($lastLog['error'] ?? '请检查短信配置'));
        }
    }

    /**
     * POST /admin/config/test-pay
     * 测试支付配置（验证签名逻辑连通性）
     */
    public function testPay(): void
    {
        $configs = $this->request->param('configs');
        if (is_array($configs) && !empty($configs)) {
            $this->saveConfigs('pay', $configs);
        }

        $apiUrl = config('pay.epay_api_url', '');
        $pid = config('pay.epay_pid', '');
        $key = config('pay.epay_key', '');

        if (!$apiUrl || !$pid || !$key) {
            json_error('支付配置不完整：需要网关地址、商户ID、商户密钥');
        }

        // 尝试访问易支付网关（API 地址可达性检查）
        $ch = curl_init(rtrim($apiUrl, '/') . '/api.php?act=query&pid=' . $pid);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            json_error('网关无法访问：' . $error);
        }

        json_success([
            'http_code' => $httpCode,
            'response' => mb_substr((string)$response, 0, 500),
        ], '支付网关连通（HTTP ' . $httpCode . '），完整验证需实际支付一笔测试订单');
    }

    /**
     * GET /admin/config/payment-records
     * 支付记录
     */
    public function paymentRecords(): void
    {
        [$page, $pageSize] = $this->pageParams();
        $condition = [];
        $status = $this->request->param('status');
        if ($status !== null && $status !== '') {
            $condition['status'] = (int)$status;
        }
        $result = db()->query(
            'SELECT p.*, u.nickname FROM payments p LEFT JOIN users u ON u.id = p.user_id ' .
            ($condition ? 'WHERE p.status = ? ' : '') .
            'ORDER BY p.id DESC LIMIT ' . $pageSize . ' OFFSET ' . (($page - 1) * $pageSize),
            $condition ? [current($condition)] : []
        )->fetchAll();
        $total = (int)db()->value('SELECT COUNT(*) FROM payments' . ($condition ? ' WHERE status = ?' : ''), $condition ? [current($condition)] : []);
        json_success(['list' => $result, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * GET /admin/config/logs?type=sms|email
     * 短信/邮件发送日志
     */
    public function logs(): void
    {
        [$page, $pageSize] = $this->pageParams();
        $type = $this->request->string('type', 'sms');
        $table = $type === 'email' ? 'email_logs' : 'sms_logs';

        $list = db()->all("SELECT * FROM {$table} ORDER BY id DESC LIMIT {$pageSize} OFFSET " . (($page - 1) * $pageSize));
        $total = (int)db()->value("SELECT COUNT(*) FROM {$table}");
        json_success(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    private function saveConfigs(string $group, array $configs): void
    {
        foreach ($configs as $key => $value) {
            if ($value === '' || $value === null) {
                $type = db()->value('SELECT type FROM system_configs WHERE `group` = ? AND `key` = ?', [$group, $key]);
                if ($type === 'password') continue;
            }
            db()->query(
                'UPDATE system_configs SET `value` = ?, updated_at = ? WHERE `group` = ? AND `key` = ?',
                [(string)$value, time(), $group, $key]
            );
        }
    }
}
