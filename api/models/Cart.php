<?php

namespace App\Models;

use Core\Model;

class Cart extends Model
{
    protected static string $table = 'carts';

    /** 购物车带商品信息 */
    public static function listWithProduct(int $userId): array
    {
        $sql = 'SELECT c.*, p.name, p.cover_image, p.price AS product_price, p.stock, p.status AS product_status,
                       p.unit, p.is_group_buy, s.price AS sku_price, s.specs AS sku_specs, s.stock AS sku_stock
                FROM carts c
                LEFT JOIN products p ON p.id = c.product_id
                LEFT JOIN product_skus s ON s.id = c.sku_id
                WHERE c.user_id = ?
                ORDER BY c.updated_at DESC';
        $list = db()->all($sql, [$userId]);
        foreach ($list as &$item) {
            $item['real_price'] = $item['sku_price'] !== null ? (float)$item['sku_price'] : (float)$item['product_price'];
            $item['real_stock'] = $item['sku_stock'] !== null ? (int)$item['sku_stock'] : (int)$item['stock'];
            $item['invalid'] = ($item['product_status'] != 1) || $item['real_stock'] < $item['quantity'] ? 1 : 0;
        }
        return $list;
    }
}
