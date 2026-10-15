<?php

namespace App\Models;

use Core\Model;

class Order extends Model
{
    protected static string $table = 'orders';

    public const STATUS_PENDING = 0;   // 待支付
    public const STATUS_PAID = 1;      // 已支付待发货
    public const STATUS_DELIVERED = 2; // 已发货
    public const STATUS_COMPLETED = 3; // 已完成
    public const STATUS_CANCELLED = 4; // 已取消
    public const STATUS_CLOSED = 5;    // 已关闭
    public const STATUS_REFUNDING = 6; // 退款中
    public const STATUS_REFUNDED = 7;  // 已退款

    public const STATUS_MAP = [
        self::STATUS_PENDING => '待支付',
        self::STATUS_PAID => '待发货',
        self::STATUS_DELIVERED => '已发货',
        self::STATUS_COMPLETED => '已完成',
        self::STATUS_CANCELLED => '已取消',
        self::STATUS_CLOSED => '已关闭',
        self::STATUS_REFUNDING => '退款中',
        self::STATUS_REFUNDED => '已退款',
    ];

    /** 创建订单（含明细，需在事务中调用） */
    public static function createOrder(array $order, array $items): int
    {
        $model = new static();
        $orderId = $model->create($order);
        foreach ($items as &$item) {
            $item['order_id'] = $orderId;
            $item['created_at'] = time();
        }
        db()->batchInsert('order_items', $items);
        // 订单日志
        db()->insert('order_logs', [
            'order_id' => $orderId,
            'status' => $order['status'],
            'description' => '订单创建',
            'operator_type' => 1,
            'operator_id' => $order['user_id'],
            'created_at' => time(),
        ]);
        return $orderId;
    }

    /** 带商品明细查详情 */
    public static function detailWithItems(int $orderId): ?array
    {
        $model = new static();
        $order = $model->find($orderId);
        if (!$order) return null;
        $order['items'] = db()->all('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
        $order['status_text'] = self::STATUS_MAP[$order['status']] ?? '未知';
        $order['logs'] = db()->all('SELECT * FROM order_logs WHERE order_id = ? ORDER BY id ASC', [$orderId]);
        return $order;
    }

    /** 按订单号查 */
    public static function findByNo(string $orderNo): ?array
    {
        return (new static())->where(['order_no' => $orderNo]);
    }

    /** 取消订单并归还库存（2026-10-14 重写：事务 + 原子状态流转，防并发重复归还库存/余额） */
    public static function cancel(int $orderId, string $reason, int $operatorType = 0, ?int $operatorId = null): bool
    {
        $order = static::find($orderId);
        if (!$order || $order['status'] != self::STATUS_PENDING) {
            return false;
        }
        $db = db();
        $ownTx = !$db->pdo()->inTransaction();
        if ($ownTx) $db->beginTransaction();
        try {
            $affected = $db->query(
                'UPDATE orders SET status = ?, cancelled_at = ?, cancel_reason = ?, updated_at = ? WHERE id = ? AND status = ?',
                [self::STATUS_CANCELLED, time(), $reason, time(), $orderId, self::STATUS_PENDING]
            )->rowCount();
            if ($affected === 0) {
                if ($ownTx) $db->rollBack();
                return false;
            }
            // 归还库存
            $items = $db->all('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
            foreach ($items as $item) {
                Product::restoreStock((int)$item['product_id'], $item['sku_id'] ? (int)$item['sku_id'] : null, (int)$item['quantity']);
            }
            // 退还钱包抵扣
            if ((float)$order['use_balance'] > 0) {
                User::walletChange((int)$order['user_id'], (float)$order['use_balance'], 'refund', '订单取消退回余额', 'order', $order['order_no']);
            }
            // 2026-10-15：秒杀订单取消回补秒杀库存
            if ((int)$order['type'] === 3) {
                Seckill::restoreStock((int)($order['seckill_activity_id'] ?? 0), (int)$order['product_count']);
            }
            // 拼团订单：未支付取消后释放团内名额，避免未付款订单把团"占满"
            if ((int)$order['type'] === 2) {
                $member = $db->one('SELECT * FROM group_buy_members WHERE order_no = ? AND status = 1', [$order['order_no']]);
                if ($member) {
                    $db->query('UPDATE group_buy_members SET status = 0 WHERE id = ?', [$member['id']]);
                    $db->query(
                        'UPDATE group_buys SET joined_count = GREATEST(joined_count - 1, 0), updated_at = ? WHERE id = ? AND status = 0',
                        [time(), $member['group_buy_id']]
                    );
                    // 团长取消且团内已无有效成员：关闭该团
                    $db->query(
                        'UPDATE group_buys SET status = 3, updated_at = ? WHERE id = ? AND status = 0 AND joined_count = 0',
                        [time(), $member['group_buy_id']]
                    );
                }
            }
            $db->insert('order_logs', [
                'order_id' => $orderId,
                'status' => self::STATUS_CANCELLED,
                'description' => '订单取消：' . $reason,
                'operator_type' => $operatorType,
                'operator_id' => $operatorId,
                'created_at' => time(),
            ]);
            if ($ownTx) $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($ownTx) $db->rollBack();
            throw $e;
        }
    }
}
