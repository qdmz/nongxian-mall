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
        $referral = $model->where(['user_id' => $userId, 'product_id' => null]);
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
            $enabled = config('share.share_reward_enabled', '0');
            if ($enabled != '1') return;

            $order = Order::find($orderId);
            if (!$order || !empty($order['referral_rewarded'])) return;

            // 查订单用户是否被推荐
            $buyer = User::find((int)$order['user_id']);
            if (!$buyer || empty($buyer['referred_by'])) return;

            $referrerId = (int)$buyer['referred_by'];
            $rate = (float)config('share.share_reward_rate', '0');
            $max = (float)config('share.share_reward_max', '0');
            if ($rate <= 0) return;

            $amount = round((float)$order['pay_amount'] * $rate / 100, 2);
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
            // 奖励失败不影响主流程
        }
    }
}
