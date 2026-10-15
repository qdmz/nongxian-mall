<?php

namespace App\Models;

use Core\Model;

class Referral extends Model
{
    protected static string $table = 'referrals';

    /** 获取或生成用户推广码 */
    public static function ensureCode(int $userId): array
    {
        $model = new static();
        // 2026-10-14：原写法 `product_id = NULL` 永远不成立，导致每次调用都新建推广码
        $referral = db()->one('SELECT * FROM referrals WHERE user_id = ? AND product_id IS NULL ORDER BY id ASC LIMIT 1', [$userId]);
        if ($referral) return $referral;

        $code = generate_code(8);
        while ($model->where(['code' => $code])) {
            $code = generate_code(8);
        }
        $id = $model->create([
            'user_id' => $userId,
            'code' => $code,
        ]);
        return $model->find($id);
    }

    /** 记录点击 */
    public static function trackClick(string $code): void
    {
        try {
            db()->query('UPDATE referrals SET click_count = click_count + 1, updated_at = ? WHERE code = ?', [time(), $code]);
        } catch (\Throwable $e) {
            // 忽略
        }
    }

    /** 发放推荐奖励（订单确认收货后调用） */
    public static function grantReward(int $orderId): void
    {
        try {
            $enabled = config('app.share_reward_enabled', config('share.share_reward_enabled', '0'));
            if ($enabled != '1') return;

            $order = Order::find($orderId);
            if (!$order || !empty($order['referral_rewarded'])) return;
            // 2026-10-14：原子占位，防重复发放
            $claimed = db()->query('UPDATE orders SET referral_rewarded = 1 WHERE id = ? AND referral_rewarded = 0', [$orderId])->rowCount();
            if ($claimed === 0) return;

            // 只有已完成（确认收货）的订单发放
            if ((int)$order['status'] !== Order::STATUS_COMPLETED) return;
            // 查订单用户是否被推荐
            $buyer = User::find((int)$order['user_id']);
            if (!$buyer || empty($buyer['referred_by'])) return;

            $referrerId = (int)$buyer['referred_by'];
            if ($referrerId === (int)$order['user_id']) return; // 防自荐
            $rate = (float)config('app.share_reward_rate', config('share.share_reward_rate', '0'));
            $max = (float)config('app.share_reward_max', config('share.share_reward_max', '0'));
            if ($rate <= 0) return;

            // 2026-10-14b：返佣基数 = 商品金额（total_amount，含余额抵扣部分，不含运费）；原来按 pay_amount 算，
            // 余额抵扣的订单返佣偏少、全额余额支付的订单返佣为 0，且会把运费算进去
            $amount = round((float)$order['total_amount'] * $rate / 100, 2);
            if ($max > 0) {
                $amount = min($amount, $max);
            }
            if ($amount <= 0) return;

            $referral = self::ensureCode($referrerId);
            db()->insert('referral_rewards', [
                'referral_id' => $referral['id'],
                'from_user_id' => $order['user_id'],
                'to_user_id' => $referrerId,
                'order_id' => $orderId,
                'order_no' => $order['order_no'],
                'amount' => $amount,
                'rate' => $rate,
                'status' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            // 加钱包
            User::walletChange($referrerId, $amount, 'reward', '推荐奖励 - 订单 ' . $order['order_no'], 'referral', (string)$orderId);
            // 更新推广统计
            db()->query('UPDATE referrals SET order_count = order_count + 1, earnings = earnings + ?, updated_at = ? WHERE id = ?', [$amount, time(), $referral['id']]);
            // 标记订单已发奖励
            db()->update('orders', ['referral_rewarded' => 1], ['id' => $orderId]);
        } catch (\Throwable $e) {
            // 奖励失败不影响主流程，但记录日志
            error_log('[nongxian-mall] referral reward error order#' . $orderId . ': ' . $e->getMessage());
        }
    }

    /**
     * 2026-10-14b：订单退款时扣回已发放的推荐奖励（在退款事务中调用）。
     * 推荐人余额不足时扣到 0 为止，差额记入 error_log 和奖励记录，由管理员线下处理。
     */
    public static function clawback(int $orderId, string $reason): void
    {
        $reward = db()->one('SELECT * FROM referral_rewards WHERE order_id = ? AND status = 1', [$orderId]);
        if (!$reward) {
            // 还没发放：占位，防止之后再发放
            db()->query('UPDATE orders SET referral_rewarded = 1 WHERE id = ? AND referral_rewarded = 0', [$orderId]);
            return;
        }
        // 原子占用（1=已发放 -> 2=已扣回）
        $claimed = db()->query('UPDATE referral_rewards SET status = 2, clawback_at = ?, updated_at = ? WHERE id = ? AND status = 1', [time(), time(), $reward['id']])->rowCount();
        if (!$claimed) return;

        $amount = round((float)$reward['amount'], 2);
        $toUser = (int)$reward['to_user_id'];
        $balance = (float)db()->value('SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE', [$toUser]);
        $deduct = round(min($amount, max(0, $balance)), 2);
        if ($deduct > 0) {
            User::walletChange($toUser, -$deduct, 'reward_back', '推荐奖励扣回 - 订单 ' . $reward['order_no'] . '（' . $reason . '）', 'referral', (string)$orderId);
        }
        db()->query('UPDATE referral_rewards SET clawback_amount = ? WHERE id = ?', [$deduct, $reward['id']]);
        db()->query('UPDATE referrals SET order_count = GREATEST(order_count - 1, 0), earnings = GREATEST(earnings - ?, 0), updated_at = ? WHERE id = ?', [$amount, time(), $reward['referral_id']]);
        if ($deduct + 0.001 < $amount) {
            error_log('[nongxian-mall] referral clawback shortfall order#' . $orderId . ' user#' . $toUser . ' need=' . $amount . ' deducted=' . $deduct);
        }
    }
}
