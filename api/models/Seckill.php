<?php

namespace App\Models;

use Core\Model;

/**
 * 秒杀活动（2026-10-15 v0.09 新增）
 * 秒杀库存独立于商品库存：下单时秒杀库存与商品/规格库存同时原子扣减，取消/退款同时回补。
 */
class Seckill extends Model
{
    protected static string $table = 'seckill_activities';

    /** 前台展示字段（活动 + 商品） */
    public const SHOP_FIELDS = 'a.id, a.product_id, a.sku_id, a.title, a.seckill_price, a.stock, a.sold, a.limit_per_user,
        a.start_time, a.end_time, a.status, p.name, p.subtitle, p.cover_image, p.unit,
        COALESCE(s.price, p.price) AS original_price, p.original_price AS market_price, s.specs AS sku_specs';

    /** 进行中（含即将开始）的活动列表 */
    public static function listForShop(int $limit = 20, int $offset = 0, bool $includeUpcoming = true): array
    {
        $now = time();
        $timeCond = $includeUpcoming ? '(a.end_time = 0 OR a.end_time >= ?)' : '(a.start_time = 0 OR a.start_time <= ?) AND (a.end_time = 0 OR a.end_time >= ?)';
        $params = $includeUpcoming ? [$now] : [$now, $now];
        $list = db()->all(
            'SELECT ' . self::SHOP_FIELDS . '
             FROM seckill_activities a
             INNER JOIN products p ON p.id = a.product_id AND p.status = 1
             LEFT JOIN product_skus s ON s.id = a.sku_id AND a.sku_id > 0
             WHERE a.status = 1 AND ' . $timeCond . '
             ORDER BY (a.start_time > ' . $now . ') ASC, a.sort DESC, a.id DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            $params
        );
        return array_map([self::class, 'decorate'], $list);
    }

    public static function countForShop(): int
    {
        return (int)db()->value(
            'SELECT COUNT(*) FROM seckill_activities a INNER JOIN products p ON p.id = a.product_id AND p.status = 1
             WHERE a.status = 1 AND (a.end_time = 0 OR a.end_time >= ?)',
            [time()]
        );
    }

    /** 前台详情（停用/商品下架返回 null；已结束的仍返回，由 state 标识） */
    public static function detailForShop(int $id): ?array
    {
        $row = db()->one(
            'SELECT ' . self::SHOP_FIELDS . '
             FROM seckill_activities a
             INNER JOIN products p ON p.id = a.product_id AND p.status = 1
             LEFT JOIN product_skus s ON s.id = a.sku_id AND a.sku_id > 0
             WHERE a.id = ? AND a.status = 1',
            [$id]
        );
        return $row ? self::decorate($row) : null;
    }

    /** 某商品当前可用的秒杀活动（进行中优先） */
    public static function activeForProduct(int $productId): ?array
    {
        $now = time();
        $row = db()->one(
            'SELECT ' . self::SHOP_FIELDS . '
             FROM seckill_activities a
             INNER JOIN products p ON p.id = a.product_id AND p.status = 1
             LEFT JOIN product_skus s ON s.id = a.sku_id AND a.sku_id > 0
             WHERE a.product_id = ? AND a.status = 1 AND (a.end_time = 0 OR a.end_time >= ?)
             ORDER BY (a.start_time > ?) ASC, a.sort DESC, a.id DESC LIMIT 1',
            [$productId, $now, $now]
        );
        return $row ? self::decorate($row) : null;
    }

    /** 补充状态字段：state = upcoming / ongoing / ended / sold_out */
    public static function decorate(array $a): array
    {
        $now = time();
        $start = (int)$a['start_time'];
        $end = (int)$a['end_time'];
        if ($end > 0 && $end < $now) {
            $state = 'ended';
        } elseif ($start > $now) {
            $state = 'upcoming';
        } elseif ((int)$a['stock'] <= 0) {
            $state = 'sold_out';
        } else {
            $state = 'ongoing';
        }
        $a['state'] = $state;
        $a['server_time'] = $now;
        $a['remaining_seconds'] = $state === 'upcoming' ? $start - $now : ($end > 0 ? max(0, $end - $now) : 0);
        $total = (int)$a['stock'] + (int)$a['sold'];
        $a['sold_percent'] = $total > 0 ? (int)round((int)$a['sold'] / $total * 100) : 0;
        $orig = (float)$a['original_price'];
        $a['discount'] = $orig > 0 ? round((float)$a['seckill_price'] / $orig * 10, 1) : 10;
        return $a;
    }

    /** 原子扣减秒杀库存（仅进行中的启用活动） */
    public static function reduceStock(int $id, int $quantity): bool
    {
        $now = time();
        return db()->query(
            'UPDATE seckill_activities SET stock = stock - ?, sold = sold + ?, updated_at = ?
             WHERE id = ? AND status = 1 AND stock >= ?
               AND (start_time = 0 OR start_time <= ?) AND (end_time = 0 OR end_time >= ?)',
            [$quantity, $quantity, $now, $id, $quantity, $now, $now]
        )->rowCount() > 0;
    }

    /** 回补秒杀库存（订单取消/退款） */
    public static function restoreStock(int $id, int $quantity): void
    {
        if ($id <= 0 || $quantity <= 0) return;
        db()->query(
            'UPDATE seckill_activities SET stock = stock + ?, sold = GREATEST(sold - ?, 0), updated_at = ? WHERE id = ?',
            [$quantity, $quantity, time(), $id]
        );
    }

    /** 用户在该活动已购（未取消/未退款）件数 */
    public static function boughtCount(int $id, int $userId): int
    {
        return (int)db()->value(
            'SELECT COALESCE(SUM(product_count), 0) FROM orders
             WHERE user_id = ? AND type = 3 AND seckill_activity_id = ? AND status NOT IN (?, ?, ?)',
            [$userId, $id, Order::STATUS_CANCELLED, Order::STATUS_CLOSED, Order::STATUS_REFUNDED]
        );
    }
}
