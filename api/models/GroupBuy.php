<?php

namespace App\Models;

use Core\Model;

class GroupBuy extends Model
{
    protected static string $table = 'group_buys';

    public const STATUS_PENDING = 0; // 拼团中
    public const STATUS_SUCCESS = 1; // 已成团
    public const STATUS_FAILED = 2;  // 拼团失败
    public const STATUS_CANCELLED = 3;

    /** 开团 */
    public static function start(int $activityId, array $activity, int $leaderUserId, int $orderId, string $orderNo): int
    {
        $groupNo = order_no('G');
        $expireAt = time() + (int)$activity['valid_hours'] * 3600;
        $groupId = (new static())->create([
            'group_no' => $groupNo,
            'activity_id' => $activityId,
            'product_id' => $activity['product_id'],
            'leader_user_id' => $leaderUserId,
            'required_count' => $activity['required_count'],
            'joined_count' => 1,
            'group_price' => $activity['group_price'],
            'status' => self::STATUS_PENDING,
            'expire_at' => $expireAt,
        ]);
        // 团长入成员表
        $user = User::find($leaderUserId);
        db()->insert('group_buy_members', [
            'group_buy_id' => $groupId,
            'user_id' => $leaderUserId,
            'order_id' => $orderId,
            'order_no' => $orderNo,
            'is_leader' => 1,
            'status' => 1,
            'avatar' => $user['avatar'] ?? null,
            'nickname' => $user['nickname'] ?? null,
            'joined_at' => time(),
        ]);
        return $groupId;
    }

    /** 参团 */
    public static function join(int $groupBuyId, int $userId, int $orderId, string $orderNo): bool
    {
        $model = new static();
        // 原子更新人数（防超额参团）
        $affected = db()->query(
            'UPDATE group_buys SET joined_count = joined_count + 1, updated_at = ? WHERE id = ? AND status = 0 AND joined_count < required_count AND expire_at > ?',
            [time(), $groupBuyId, time()]
        )->rowCount();
        if ($affected === 0) return false;

        $user = User::find($userId);
        db()->insert('group_buy_members', [
            'group_buy_id' => $groupBuyId,
            'user_id' => $userId,
            'order_id' => $orderId,
            'order_no' => $orderNo,
            'is_leader' => 0,
            'status' => 1,
            'avatar' => $user['avatar'] ?? null,
            'nickname' => $user['nickname'] ?? null,
            'joined_at' => time(),
        ]);

        // 检查是否成团
        $group = $model->find($groupBuyId);
        if ($group && (int)$group['joined_count'] >= (int)$group['required_count']) {
            self::markSuccess($groupBuyId);
        }
        return true;
    }

    /** 标记成团并触发发货流程 */
    public static function markSuccess(int $groupBuyId): void
    {
        db()->query(
            'UPDATE group_buys SET status = 1, success_at = ?, updated_at = ? WHERE id = ? AND status = 0',
            [time(), time(), $groupBuyId]
        );
    }

    /** 处理过期拼团（失败退款） */
    public static function processExpired(): int
    {
        $expired = db()->all(
            'SELECT * FROM group_buys WHERE status = 0 AND expire_at <= ? LIMIT 100',
            [time()]
        );
        $count = 0;
        foreach ($expired as $group) {
            // 全部退款
            $members = db()->all('SELECT * FROM group_buy_members WHERE group_buy_id = ? AND status = 1', [$group['id']]);
            foreach ($members as $member) {
                if ($member['order_no']) {
                    \App\Services\OrderService::refundByOrderNo($member['order_no'], '拼团超时未成团，自动退款');
                }
            }
            db()->query('UPDATE group_buys SET status = 2, updated_at = ? WHERE id = ? AND status = 0', [time(), $group['id']]);
            $count++;
        }
        return $count;
    }

    /** 拼团详情（含成员） */
    public static function detailWithMembers(int $groupBuyId): ?array
    {
        $group = (new static())->find($groupBuyId);
        if (!$group) return null;
        $group['members'] = db()->all('SELECT * FROM group_buy_members WHERE group_buy_id = ? AND status = 1 ORDER BY is_leader DESC, joined_at ASC', [$groupBuyId]);
        $group['remaining_count'] = max(0, (int)$group['required_count'] - (int)$group['joined_count']);
        $group['remaining_seconds'] = max(0, (int)$group['expire_at'] - time());
        $group['product'] = db()->one('SELECT id, name, cover_image, unit FROM products WHERE id = ?', [$group['product_id']]);
        return $group;
    }
}
