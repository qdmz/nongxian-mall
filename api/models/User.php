<?php

namespace App\Models;

use Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    /** 按手机号查 */
    public static function findByPhone(string $phone): ?array
    {
        return (new static())->where(['phone' => $phone]);
    }

    /** 按邮箱查 */
    public static function findByEmail(string $email): ?array
    {
        return (new static())->where(['email' => $email]);
    }

    /** 注册用户 */
    public static function register(array $data): array
    {
        $model = new static();
        $now = time();
        $code = generate_code(8);
        // 保证推荐码唯一
        while ($model->where(['referral_code' => $code])) {
            $code = generate_code(8);
        }
        $userId = $model->create(array_merge([
            'nickname' => '用户' . substr($code, 0, 6),
            'wallet_balance' => 0,
            'status' => 1,
            'referral_code' => $code,
        ], $data, ['created_at' => $now, 'updated_at' => $now]));

        return $model->find($userId);
    }

    /** 钱包变动（需在事务中调用） */
    public static function walletChange(int $userId, float $amount, string $type, string $description, ?string $relatedType = null, ?string $relatedId = null): bool
    {
        $model = new static();
        $user = $model->find($userId);
        if (!$user) return false;

        $balanceBefore = (float)$user['wallet_balance'];
        $balanceAfter = round($balanceBefore + $amount, 2);
        if ($balanceAfter < 0) return false;

        // 原子更新余额，防并发
        $affected = db()->query(
            'UPDATE users SET wallet_balance = wallet_balance + ?, updated_at = ? WHERE id = ? AND wallet_balance + ? >= 0',
            [$amount, time(), $userId, $amount]
        )->rowCount();
        if ($affected === 0) return false;

        // 记流水
        db()->insert('wallet_transactions', [
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'description' => $description,
            'created_at' => time(),
        ]);

        // 累计充值/消费
        if ($type === 'recharge' && $amount > 0) {
            db()->query('UPDATE users SET total_recharge = total_recharge + ? WHERE id = ?', [$amount, $userId]);
        }

        return true;
    }

    /** 安全输出（隐藏敏感字段） */
    public static function safe(array $user): array
    {
        unset($user['password']);
        return $user;
    }
}
