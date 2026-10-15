<?php

namespace App\Services;

use App\Models\User;

/**
 * 钱包提现（2026-10-14b 新增）
 *
 * 资金流：申请时 可用余额 -> 冻结余额（wallet_transactions 记 withdraw 流水，金额为负）；
 *        通过（管理员线下打款后确认）：冻结余额扣除；
 *        驳回 / 用户撤销：冻结余额退回可用余额（记 withdraw_back 流水）。
 * 并发保护：申请与审核都在事务中对用户行加锁（SELECT ... FOR UPDATE），状态流转用条件更新 + 影响行数校验；
 *          客户端可传 request_id 做幂等（唯一索引 user_id+request_id），同一用户同时只能有 1 笔待审核。
 */
class WithdrawService
{
    public const ST_PENDING = 0;
    public const ST_APPROVED = 1;
    public const ST_REJECTED = 2;
    public const ST_CANCELLED = 3;

    public const STATUS_MAP = [0 => '待审核', 1 => '已打款', 2 => '已驳回', 3 => '已撤销'];
    public const ACCOUNT_TYPES = ['alipay' => '支付宝', 'wechat' => '微信', 'bank' => '银行卡'];

    public static function settings(): array
    {
        return [
            'enabled' => (string)config('app.withdraw_enabled', '1') === '1',
            'min' => (float)config('app.withdraw_min', '10'),
            'max' => (float)config('app.withdraw_max', '5000'),
            'daily_count' => (int)config('app.withdraw_daily_count', '3'),
            'account_types' => self::ACCOUNT_TYPES,
        ];
    }

    public static function apply(int $userId, array $in): array
    {
        $cfg = self::settings();
        if (!$cfg['enabled']) json_error('提现功能暂未开放');

        $amount = round((float)($in['amount'] ?? 0), 2);
        $type = (string)($in['account_type'] ?? '');
        $name = trim((string)($in['account_name'] ?? ''));
        $no = trim((string)($in['account_no'] ?? ''));
        $bank = trim((string)($in['bank_name'] ?? ''));
        $remark = trim((string)($in['remark'] ?? ''));
        $requestId = trim((string)($in['request_id'] ?? ''));

        if ($amount <= 0 || $amount < $cfg['min']) json_error('单笔最低提现 ' . $cfg['min'] . ' 元');
        if ($cfg['max'] > 0 && $amount > $cfg['max']) json_error('单笔最高提现 ' . $cfg['max'] . ' 元');
        if (!isset(self::ACCOUNT_TYPES[$type])) json_error('请选择收款方式');
        if ($name === '' || mb_strlen($name) > 50) json_error('请填写收款人姓名');
        if ($no === '' || mb_strlen($no) > 64 || !preg_match('/^[\w@.\-+ ]+$/u', $no)) json_error('收款账号格式不正确');
        if ($type === 'bank' && ($bank === '' || mb_strlen($bank) > 100)) json_error('请填写开户银行');
        if ($requestId !== '' && !preg_match('/^[A-Za-z0-9_\-]{8,64}$/', $requestId)) json_error('request_id 格式不正确');
        if (mb_strlen($remark) > 200) $remark = mb_substr($remark, 0, 200);

        // 幂等：同一 request_id 直接返回原记录
        if ($requestId !== '') {
            $dup = db()->one('SELECT * FROM withdrawals WHERE user_id = ? AND request_id = ?', [$userId, $requestId]);
            if ($dup) return self::present($dup);
        }

        $db = db();
        $db->beginTransaction();
        try {
            // 用户行锁：同一用户的提现申请串行化
            $user = $db->one('SELECT id, wallet_balance, frozen_balance, status FROM users WHERE id = ? FOR UPDATE', [$userId]);
            if (!$user || (int)$user['status'] !== 1) {
                $db->rollBack();
                json_error('账号状态异常');
            }
            $pending = (int)$db->value('SELECT COUNT(*) FROM withdrawals WHERE user_id = ? AND status = 0', [$userId]);
            if ($pending > 0) {
                $db->rollBack();
                json_error('您有一笔提现正在审核中，请等待处理后再申请');
            }
            $today = (int)$db->value('SELECT COUNT(*) FROM withdrawals WHERE user_id = ? AND created_at >= ? AND status <> 3', [$userId, strtotime('today')]);
            if ($cfg['daily_count'] > 0 && $today >= $cfg['daily_count']) {
                $db->rollBack();
                json_error('今日提现次数已达上限');
            }
            if ((float)$user['wallet_balance'] + 0.0001 < $amount) {
                $db->rollBack();
                json_error('可提现余额不足');
            }
            $withdrawNo = order_no('W');
            if (!User::walletChange($userId, -$amount, 'withdraw', '提现申请（冻结）' . $withdrawNo, 'withdraw', $withdrawNo)) {
                $db->rollBack();
                json_error('可提现余额不足');
            }
            $db->query('UPDATE users SET frozen_balance = frozen_balance + ? WHERE id = ?', [$amount, $userId]);
            $id = $db->insert('withdrawals', [
                'withdraw_no' => $withdrawNo,
                'user_id' => $userId,
                'amount' => $amount,
                'account_type' => $type,
                'account_name' => $name,
                'account_no' => $no,
                'bank_name' => $type === 'bank' ? $bank : null,
                'request_id' => $requestId !== '' ? $requestId : null,
                'status' => self::ST_PENDING,
                'remark' => $remark !== '' ? $remark : null,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            $db->commit();
        } catch (\PDOException $e) {
            $db->rollBack();
            if ($requestId !== '' && str_contains($e->getMessage(), 'uk_user_request')) {
                $dup = db()->one('SELECT * FROM withdrawals WHERE user_id = ? AND request_id = ?', [$userId, $requestId]);
                if ($dup) return self::present($dup);
            }
            throw $e;
        }
        return self::present(db()->one('SELECT * FROM withdrawals WHERE id = ?', [$id]));
    }

    /** 审核/撤销：$action = approve | reject | cancel */
    public static function handle(int $id, string $action, array $opt = []): array
    {
        $map = ['approve' => self::ST_APPROVED, 'reject' => self::ST_REJECTED, 'cancel' => self::ST_CANCELLED];
        if (!isset($map[$action])) json_error('无效的操作');
        $db = db();
        $db->beginTransaction();
        try {
            $w = $db->one('SELECT * FROM withdrawals WHERE id = ? FOR UPDATE', [$id]);
            if (!$w) {
                $db->rollBack();
                json_error('提现申请不存在');
            }
            if ($action === 'cancel' && (int)$w['user_id'] !== (int)($opt['user_id'] ?? 0)) {
                $db->rollBack();
                json_error('提现申请不存在');
            }
            $ok = $db->query(
                'UPDATE withdrawals SET status = ?, reject_reason = ?, transfer_no = ?, handled_by = ?, handled_at = ?, updated_at = ? WHERE id = ? AND status = 0',
                [
                    $map[$action],
                    $action === 'reject' ? mb_substr((string)($opt['reason'] ?? '不符合提现条件'), 0, 250) : null,
                    $action === 'approve' ? (mb_substr((string)($opt['transfer_no'] ?? ''), 0, 100) ?: null) : null,
                    $action === 'cancel' ? null : ($opt['admin_id'] ?? null),
                    time(), time(), $id,
                ]
            )->rowCount();
            if ($ok === 0) {
                $db->rollBack();
                json_error('该提现申请已处理，请刷新');
            }
            $amount = (float)$w['amount'];
            $uid = (int)$w['user_id'];
            $db->one('SELECT id FROM users WHERE id = ? FOR UPDATE', [$uid]);
            $aff = $db->query('UPDATE users SET frozen_balance = frozen_balance - ? WHERE id = ? AND frozen_balance >= ?', [$amount, $uid, $amount])->rowCount();
            if ($aff === 0) {
                throw new \RuntimeException('冻结余额不足，数据异常');
            }
            if ($action !== 'approve') {
                $desc = $action === 'reject' ? ('提现驳回退回 ' . $w['withdraw_no']) : ('提现撤销退回 ' . $w['withdraw_no']);
                if (!User::walletChange($uid, $amount, 'withdraw_back', $desc, 'withdraw', $w['withdraw_no'])) {
                    throw new \RuntimeException('退回余额失败');
                }
            }
            if ($action !== 'cancel') {
                $db->insert('notifications', [
                    'user_id' => $uid,
                    'title' => $action === 'approve' ? '提现已打款' : '提现申请被驳回',
                    'content' => $action === 'approve'
                        ? ('您的提现 ' . $w['withdraw_no'] . '（' . $w['amount'] . ' 元）已打款到您的' . (self::ACCOUNT_TYPES[$w['account_type']] ?? '') . '账户，请留意到账。')
                        : ('您的提现 ' . $w['withdraw_no'] . '（' . $w['amount'] . ' 元）未通过审核，金额已退回钱包余额。原因：' . ($opt['reason'] ?? '不符合提现条件')),
                    'type' => 'system',
                    'created_at' => time(),
                ]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[nongxian-mall] withdraw handle error #' . $id . ': ' . $e->getMessage());
            json_error('处理失败：' . ($e instanceof \RuntimeException ? $e->getMessage() : '服务器错误'));
        }
        return self::present(db()->one('SELECT * FROM withdrawals WHERE id = ?', [$id]));
    }

    public static function present(array $w, bool $admin = false): array
    {
        $w['amount'] = (float)$w['amount'];
        $w['status'] = (int)$w['status'];
        $w['status_text'] = self::STATUS_MAP[$w['status']] ?? '';
        $w['account_type_text'] = self::ACCOUNT_TYPES[$w['account_type']] ?? $w['account_type'];
        if (!$admin) {
            // 用户端账号脱敏
            $no = (string)$w['account_no'];
            $w['account_no'] = mb_strlen($no) > 6 ? mb_substr($no, 0, 3) . str_repeat('*', max(3, mb_strlen($no) - 7)) . mb_substr($no, -4) : $no;
            unset($w['handled_by'], $w['request_id']);
        }
        return $w;
    }
}
