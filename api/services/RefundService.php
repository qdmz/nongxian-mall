<?php

namespace App\Services;

use App\Models\User;

/**
 * 退款打款（2026-10-14b 新增）
 *
 * 规则：
 *  - 订单中通过易支付付款的部分：优先调用易支付退款接口原路退回（pay.epay_refund_enabled=1 时）；
 *  - 余额支付 / 余额抵扣部分：直接退回钱包；
 *  - 原路退款首次调用就被平台明确拒绝（code!=1）→ 自动改退钱包，并记录日志；
 *  - 网络超时等"结果不确定"的情况 → 保持待处理，由 cron 先查单再重试，绝不直接退钱包（防重复退款）；
 *    之前出现过不确定结果、之后又被平台拒绝的 → 标记"待人工"（status=3），由管理员核实。
 *
 * refund_records.status：0=待处理 1=成功 2=原路失败已转钱包 3=失败待人工 4=处理中（临时占用）
 */
class RefundService
{
    public const ST_PENDING = 0;
    public const ST_SUCCESS = 1;
    public const ST_TO_WALLET = 2;
    public const ST_MANUAL = 3;
    public const ST_PROCESSING = 4;

    public static function epayRefundEnabled(): bool
    {
        return (string)config('pay.epay_refund_enabled', '1') === '1';
    }

    /** 找到实际支付了该订单的易支付支付单 */
    public static function findOrderEpayPayment(array $order): ?array
    {
        if (empty($order['pay_no'])) return null;
        return db()->one(
            "SELECT * FROM payments WHERE biz_type = 'order' AND biz_no = ? AND status = 1 AND channel = 'epay' AND trade_no = ? ORDER BY id ASC LIMIT 1",
            [$order['order_no'], $order['pay_no']]
        );
    }

    /**
     * 为订单退款生成退款记录（必须在事务中调用）。钱包部分立即入账，易支付部分生成待处理记录。
     * @return array{epay: float, wallet: float, epay_record_ids: int[]}
     */
    public static function createForOrder(array $order, float $totalAmount, string $reason): array
    {
        $totalAmount = round($totalAmount, 2);
        $epayAmount = 0.0;
        $payment = null;
        if (self::epayRefundEnabled()) {
            $payment = self::findOrderEpayPayment($order);
            if ($payment) {
                $epayAmount = min(round((float)$payment['amount'], 2), $totalAmount);
            }
        }
        $walletAmount = round($totalAmount - $epayAmount, 2);
        $ids = [];

        if ($walletAmount > 0) {
            if (!User::walletChange((int)$order['user_id'], $walletAmount, 'refund', '订单退款 - ' . $reason, 'order', $order['order_no'])) {
                throw new \RuntimeException('退款入账钱包失败');
            }
            self::insertRecord($order, null, null, 'wallet', $walletAmount, self::ST_SUCCESS, $reason);
        }
        if ($epayAmount > 0 && $payment) {
            $ids[] = self::insertRecord($order, $payment['payment_no'], $payment['trade_no'], 'epay', $epayAmount, self::ST_PENDING, $reason);
        }
        return ['epay' => $epayAmount, 'wallet' => $walletAmount, 'epay_record_ids' => $ids];
    }

    /** 迟到/重复的易支付付款：整笔原路退回（在支付回调事务中调用，cron 稍后实际发起） */
    public static function createForDuplicatePayment(array $order, array $payment, string $reason): bool
    {
        if (!self::epayRefundEnabled() || ($payment['channel'] ?? '') !== 'epay') {
            return false;
        }
        $exists = db()->value('SELECT id FROM refund_records WHERE payment_no = ?', [$payment['payment_no']]);
        if ($exists) return true;
        self::insertRecord($order, $payment['payment_no'], $payment['trade_no'] ?? null, 'epay', (float)$payment['amount'], self::ST_PENDING, $reason);
        return true;
    }

    private static function insertRecord(array $order, ?string $paymentNo, ?string $tradeNo, string $channel, float $amount, int $status, string $reason): int
    {
        return db()->insert('refund_records', [
            'record_no' => order_no('RF'),
            'order_id' => (int)$order['id'],
            'order_no' => (string)$order['order_no'],
            'user_id' => (int)$order['user_id'],
            'payment_no' => $paymentNo,
            'trade_no' => $tradeNo,
            'channel' => $channel,
            'amount' => round($amount, 2),
            'status' => $status,
            'attempts' => 0,
            'reason' => mb_substr($reason, 0, 250),
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    /**
     * 处理一条待处理的易支付退款记录（事务外调用：会访问外部接口）
     * @return int 处理后的状态
     */
    public static function process(int $recordId): int
    {
        // 原子占用，防 cron 与请求并发重复发起退款
        $claimed = db()->query(
            'UPDATE refund_records SET status = ?, attempts = attempts + 1, updated_at = ? WHERE id = ? AND status = ? AND channel = ?',
            [self::ST_PROCESSING, time(), $recordId, self::ST_PENDING, 'epay']
        )->rowCount();
        $rec = db()->one('SELECT * FROM refund_records WHERE id = ?', [$recordId]);
        if (!$claimed || !$rec) {
            return $rec ? (int)$rec['status'] : -1;
        }
        $firstAttempt = (int)$rec['attempts'] === 1;

        // 之前有过不确定结果：先查单，平台已退款则直接认定成功
        if (!$firstAttempt) {
            $remote = PayService::epayQueryOrder((string)$rec['payment_no']);
            if ($remote && self::remoteRefunded($remote, (float)$rec['amount'])) {
                self::finish($rec, self::ST_SUCCESS, '查单确认已退款');
                return self::ST_SUCCESS;
            }
        }

        $res = PayService::epayRefund((string)$rec['payment_no'], $rec['trade_no'] ?: null, (float)$rec['amount']);
        if ($res['result'] === 'success') {
            self::finish($rec, self::ST_SUCCESS, $res['msg']);
            self::notify($rec, '退款已原路退回', '您的订单 ' . $rec['order_no'] . ' 退款 ' . $rec['amount'] . ' 元已原路退回支付账户，请留意到账（一般 1-3 个工作日）。');
            return self::ST_SUCCESS;
        }
        if ($res['result'] === 'fail' && $firstAttempt) {
            // 平台明确拒绝且此前没有任何不确定的请求 → 安全地改退钱包
            db()->beginTransaction();
            try {
                $ok = db()->query(
                    'UPDATE refund_records SET status = ?, last_error = ?, updated_at = ? WHERE id = ? AND status = ?',
                    [self::ST_TO_WALLET, mb_substr('原路退款失败，已转退钱包：' . $res['msg'], 0, 490), time(), $rec['id'], self::ST_PROCESSING]
                )->rowCount();
                if ($ok) {
                    if (!User::walletChange((int)$rec['user_id'], (float)$rec['amount'], 'refund', '订单退款（原路退回失败，转入钱包）', 'order', $rec['order_no'])) {
                        throw new \RuntimeException('wallet credit failed');
                    }
                }
                db()->commit();
            } catch (\Throwable $e) {
                db()->rollBack();
                db()->query('UPDATE refund_records SET status = ?, last_error = ?, updated_at = ? WHERE id = ? AND status = ?',
                    [self::ST_MANUAL, mb_substr('转钱包失败：' . $e->getMessage(), 0, 490), time(), $rec['id'], self::ST_PROCESSING]);
                error_log('[nongxian-mall] refund to wallet fallback failed record#' . $rec['id'] . ': ' . $e->getMessage());
                return self::ST_MANUAL;
            }
            error_log('[nongxian-mall] epay refund rejected, credited to wallet: record#' . $rec['id'] . ' ' . $res['msg']);
            self::notify($rec, '退款已到账钱包', '您的订单 ' . $rec['order_no'] . ' 退款 ' . $rec['amount'] . ' 元原路退回未成功，已退至钱包余额，可在钱包申请提现。');
            return self::ST_TO_WALLET;
        }
        if ($res['result'] === 'fail') {
            // 之前有过不确定结果，现在又被拒（可能其实已退过）→ 人工核查，绝不自动退钱包
            self::finish($rec, self::ST_MANUAL, '需人工核实（此前请求结果不确定）：' . $res['msg']);
            error_log('[nongxian-mall] epay refund needs manual check record#' . $rec['id'] . ': ' . $res['msg']);
            return self::ST_MANUAL;
        }
        // unknown：放回待处理，cron 稍后查单重试；超过 6 次转人工
        $next = (int)$rec['attempts'] >= 6 ? self::ST_MANUAL : self::ST_PENDING;
        self::finish($rec, $next, '结果不确定：' . $res['msg']);
        error_log('[nongxian-mall] epay refund uncertain record#' . $rec['id'] . ': ' . $res['msg']);
        return $next;
    }

    private static function remoteRefunded(array $remote, float $amount): bool
    {
        if ((string)($remote['status'] ?? '') === '2') return true; // 彩虹易支付：2=已退款
        if (isset($remote['refundmoney']) && (float)$remote['refundmoney'] + 0.001 >= $amount) return true;
        return false;
    }

    private static function finish(array $rec, int $status, string $msg): void
    {
        db()->query(
            'UPDATE refund_records SET status = ?, last_error = ?, updated_at = ? WHERE id = ? AND status = ?',
            [$status, mb_substr($msg, 0, 490), time(), $rec['id'], self::ST_PROCESSING]
        );
    }

    private static function notify(array $rec, string $title, string $content): void
    {
        try {
            db()->insert('notifications', [
                'user_id' => (int)$rec['user_id'],
                'title' => $title,
                'content' => $content,
                'type' => 'order',
                'created_at' => time(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    /** cron：处理待处理记录；处理中超过 10 分钟的（进程崩溃）放回待处理 */
    public static function processPending(int $limit = 20): int
    {
        db()->query('UPDATE refund_records SET status = ?, updated_at = ? WHERE status = ? AND updated_at < ?',
            [self::ST_PENDING, time(), self::ST_PROCESSING, time() - 600]);
        // 首次立即处理；重试的间隔至少 2 分钟
        $rows = db()->all(
            'SELECT id FROM refund_records WHERE status = ? AND channel = ? AND (attempts = 0 OR updated_at < ?) ORDER BY id ASC LIMIT ' . (int)$limit,
            [self::ST_PENDING, 'epay', time() - 120]
        );
        $n = 0;
        foreach ($rows as $r) {
            self::process((int)$r['id']);
            $n++;
        }
        return $n;
    }
}
