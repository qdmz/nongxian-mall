<?php

namespace App\Services;

/**
 * 统计服务
 */
class StatisticsService
{
    /** 仪表盘概览数据 */
    public static function dashboard(): array
    {
        $today = date('Y-m-d');
        $todayStart = strtotime($today);
        $yesterdayStart = $todayStart - 86400;
        $monthStart = strtotime(date('Y-m-01'));

        return [
            'today' => [
                'new_users' => (int)db()->value('SELECT COUNT(*) FROM users WHERE created_at >= ?', [$todayStart]),
                'order_count' => (int)db()->value('SELECT COUNT(*) FROM orders WHERE created_at >= ?', [$todayStart]),
                'pay_amount' => (float)db()->value('SELECT COALESCE(SUM(pay_amount),0) FROM orders WHERE paid_at >= ? AND status IN (1,2,3)', [$todayStart]),
                'recharge_amount' => (float)db()->value('SELECT COALESCE(SUM(amount+give_amount),0) FROM recharge_orders WHERE paid_at >= ?', [$todayStart]),
            ],
            'yesterday' => [
                'new_users' => (int)db()->value('SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at < ?', [$yesterdayStart, $todayStart]),
                'order_count' => (int)db()->value('SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at < ?', [$yesterdayStart, $todayStart]),
                'pay_amount' => (float)db()->value('SELECT COALESCE(SUM(pay_amount),0) FROM orders WHERE paid_at >= ? AND paid_at < ? AND status IN (1,2,3)', [$yesterdayStart, $todayStart]),
            ],
            'month' => [
                'new_users' => (int)db()->value('SELECT COUNT(*) FROM users WHERE created_at >= ?', [$monthStart]),
                'order_count' => (int)db()->value('SELECT COUNT(*) FROM orders WHERE created_at >= ?', [$monthStart]),
                'pay_amount' => (float)db()->value('SELECT COALESCE(SUM(pay_amount),0) FROM orders WHERE paid_at >= ? AND status IN (1,2,3)', [$monthStart]),
            ],
            'total' => [
                'users' => (int)db()->value('SELECT COUNT(*) FROM users'),
                'products' => (int)db()->value('SELECT COUNT(*) FROM products WHERE status = 1'),
                'orders' => (int)db()->value('SELECT COUNT(*) FROM orders'),
                'pay_amount' => (float)db()->value('SELECT COALESCE(SUM(pay_amount),0) FROM orders WHERE status IN (1,2,3)'),
                'pending_deliver' => (int)db()->value('SELECT COUNT(*) FROM orders WHERE status = 1'),
                'pending_refund' => (int)db()->value('SELECT COUNT(*) FROM refunds WHERE status = 0'),
                'group_buying' => (int)db()->value('SELECT COUNT(*) FROM group_buys WHERE status = 0'),
            ],
        ];
    }

    /** 近 N 天销售趋势 */
    public static function salesTrend(int $days = 30): array
    {
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $start = strtotime($date);
            $end = $start + 86400;
            $result[] = [
                'date' => $date,
                'order_count' => (int)db()->value('SELECT COUNT(*) FROM orders WHERE paid_at >= ? AND paid_at < ? AND status IN (1,2,3,7)', [$start, $end]),
                'pay_amount' => (float)db()->value('SELECT COALESCE(SUM(pay_amount),0) FROM orders WHERE paid_at >= ? AND paid_at < ? AND status IN (1,2,3,7)', [$start, $end]),
            ];
        }
        return $result;
    }

    /** 商品销量排行 */
    public static function productRank(int $days = 30, int $limit = 20): array
    {
        $startTime = time() - $days * 86400;
        return db()->all(
            'SELECT oi.product_id, oi.name, oi.image, SUM(oi.quantity) AS sales_count, SUM(oi.subtotal) AS sales_amount
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id AND o.status IN (1,2,3)
             WHERE o.paid_at >= ?
             GROUP BY oi.product_id, oi.name, oi.image
             ORDER BY sales_count DESC
             LIMIT ?',
            [$startTime, $limit]
        );
    }

    /** 商品分类销售占比 */
    public static function categorySales(int $days = 30): array
    {
        $startTime = time() - $days * 86400;
        return db()->all(
            'SELECT c.name AS category_name, SUM(oi.subtotal) AS sales_amount
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id AND o.status IN (1,2,3) AND o.paid_at >= ?
             INNER JOIN products p ON p.id = oi.product_id
             INNER JOIN categories c ON c.id = p.category_id
             GROUP BY c.id, c.name
             ORDER BY sales_amount DESC',
            [$startTime]
        );
    }

    /** 汇总每日统计（定时任务） */
    public static function aggregateDaily(): void
    {
        $date = date('Y-m-d', strtotime('-1 day'));
        $start = strtotime($date);
        $end = $start + 86400;

        $newUsers = (int)db()->value('SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at < ?', [$start, $end]);
        $totalUsers = (int)db()->value('SELECT COUNT(*) FROM users WHERE created_at < ?', [$end]);
        $orderCount = (int)db()->value('SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at < ?', [$start, $end]);
        $orderAmount = (float)db()->value('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE created_at >= ? AND created_at < ?', [$start, $end]);
        $payCount = (int)db()->value('SELECT COUNT(*) FROM orders WHERE paid_at >= ? AND paid_at < ? AND status IN (1,2,3)', [$start, $end]);
        $payAmount = (float)db()->value('SELECT COALESCE(SUM(pay_amount),0) FROM orders WHERE paid_at >= ? AND paid_at < ? AND status IN (1,2,3)', [$start, $end]);
        $rechargeAmount = (float)db()->value('SELECT COALESCE(SUM(amount+give_amount),0) FROM recharge_orders WHERE paid_at >= ? AND paid_at < ?', [$start, $end]);
        $groupBuyCount = (int)db()->value('SELECT COUNT(*) FROM group_buys WHERE success_at >= ? AND success_at < ?', [$start, $end]);

        $exists = db()->value('SELECT id FROM statistics_daily WHERE date = ?', [$date]);
        $data = [
            'new_users' => $newUsers,
            'total_users' => $totalUsers,
            'order_count' => $orderCount,
            'order_amount' => $orderAmount,
            'pay_count' => $payCount,
            'pay_amount' => $payAmount,
            'recharge_amount' => $rechargeAmount,
            'group_buy_count' => $groupBuyCount,
            'updated_at' => time(),
        ];
        if ($exists) {
            db()->update('statistics_daily', $data, ['date' => $date]);
        } else {
            db()->insert('statistics_daily', array_merge($data, ['date' => $date, 'created_at' => time()]));
        }
    }
}
