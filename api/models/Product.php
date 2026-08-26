<?php

namespace App\Models;

use Core\Model;

class Product extends Model
{
    protected static string $table = 'products';

    /** 前台商品列表（上架状态） */
    public static function listForShop(array $filters = [], int $page = 1, int $pageSize = 15): array
    {
        $model = new static();
        $condition = ['status' => 1];
        if (!empty($filters['category_id'])) {
            $condition['category_id'] = (int)$filters['category_id'];
        }
        if (isset($filters['is_red'])) {
            $condition['is_red'] = (int)$filters['is_red'];
        }
        if (isset($filters['is_hot'])) {
            $condition['is_hot'] = (int)$filters['is_hot'];
        }
        if (isset($filters['is_recommend'])) {
            $condition['is_recommend'] = (int)$filters['is_recommend'];
        }
        if (isset($filters['is_new'])) {
            $condition['is_new'] = (int)$filters['is_new'];
        }
        if (!empty($filters['keyword'])) {
            $condition['name LIKE'] = '%' . $filters['keyword'] . '%';
        }

        $orderBy = 'sort DESC, id DESC';
        if (!empty($filters['order_by'])) {
            $map = [
                'sales' => 'sales DESC',
                'price_asc' => 'price ASC',
                'price_desc' => 'price DESC',
                'newest' => 'id DESC',
            ];
            $orderBy = $map[$filters['order_by']] ?? $orderBy;
        }

        return $model->paginate($condition, '*', $orderBy, $page, $pageSize);
    }

    /** 扣库存（原子操作防超卖） */
    public static function reduceStock(int $productId, ?int $skuId, int $quantity): bool
    {
        // 检查并扣减 SKU 库存
        if ($skuId) {
            $affected = db()->query(
                'UPDATE product_skus SET stock = stock - ?, sales = sales + ? WHERE id = ? AND stock >= ?',
                [$quantity, $quantity, $skuId, $quantity]
            )->rowCount();
            if ($affected === 0) return false;
        }
        // 扣减商品总库存
        $affected = db()->query(
            'UPDATE products SET stock = stock - ?, sales = sales + ? WHERE id = ? AND stock >= ?',
            [$quantity, $quantity, $productId, $quantity]
        )->rowCount();
        if ($affected === 0) {
            // 回滚 SKU
            if ($skuId) {
                db()->query('UPDATE product_skus SET stock = stock + ?, sales = sales - ? WHERE id = ?', [$quantity, $quantity, $skuId]);
            }
            return false;
        }
        return true;
    }

    /** 归还库存 */
    public static function restoreStock(int $productId, ?int $skuId, int $quantity): void
    {
        if ($skuId) {
            db()->query('UPDATE product_skus SET stock = stock + ?, sales = GREATEST(sales - ?, 0) WHERE id = ?', [$quantity, $quantity, $skuId]);
        }
        db()->query('UPDATE products SET stock = stock + ?, sales = GREATEST(sales - ?, 0) WHERE id = ?', [$quantity, $quantity, $productId]);
    }
}
