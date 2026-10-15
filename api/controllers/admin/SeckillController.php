<?php

namespace App\Controllers\Admin;

use Core\Controller;

/**
 * 管理后台 - 秒杀管理（2026-10-15 v0.09 新增，权限同拼团：group_buy）
 */
class SeckillController extends Controller
{
    /**
     * GET /admin/seckill/activities
     */
    public function index(): void
    {
        [$page, $pageSize] = $this->pageParams();
        $status = $this->request->param('status');
        $where = '';
        $params = [];
        if ($status !== null && $status !== '') {
            $where = 'WHERE a.status = ?';
            $params[] = (int)$status;
        }
        $list = db()->all(
            "SELECT a.*, p.name AS product_name, p.cover_image, p.price AS product_price, s.specs AS sku_specs, s.price AS sku_price
             FROM seckill_activities a
             LEFT JOIN products p ON p.id = a.product_id
             LEFT JOIN product_skus s ON s.id = a.sku_id AND a.sku_id > 0
             {$where} ORDER BY a.sort DESC, a.id DESC LIMIT {$pageSize} OFFSET " . (($page - 1) * $pageSize),
            $params
        );
        $total = (int)db()->value("SELECT COUNT(*) FROM seckill_activities a {$where}", $params);
        json_success(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * POST /admin/seckill/activities
     */
    public function store(): void
    {
        $data = $this->collect(null);
        $data['created_at'] = time();
        $data['updated_at'] = time();
        $id = db()->insert('seckill_activities', $data);
        json_success(db()->one('SELECT * FROM seckill_activities WHERE id = ?', [$id]), '秒杀活动已创建');
    }

    /**
     * PUT /admin/seckill/activities/{id}
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $activity = db()->one('SELECT * FROM seckill_activities WHERE id = ?', [$id]);
        if (!$activity) {
            json_error('秒杀活动不存在');
        }
        $data = $this->collect($activity);
        $data['updated_at'] = time();
        db()->update('seckill_activities', $data, ['id' => $id]);
        json_success(null, '秒杀活动已更新');
    }

    /**
     * DELETE /admin/seckill/activities/{id}  —— 停用（保留订单关联）
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];
        if (!db()->one('SELECT id FROM seckill_activities WHERE id = ?', [$id])) {
            json_error('秒杀活动不存在');
        }
        db()->update('seckill_activities', ['status' => 0, 'updated_at' => time()], ['id' => $id]);
        json_success(null, '秒杀活动已停用');
    }

    /** 校验并整理表单（新建 $old=null；编辑时未传字段沿用原值） */
    private function collect(?array $old): array
    {
        $r = $this->request;
        $productId = $r->has('product_id') ? $r->int('product_id') : (int)($old['product_id'] ?? 0);
        $product = db()->one('SELECT * FROM products WHERE id = ?', [$productId]);
        if (!$product) {
            json_error('商品不存在');
        }
        $skuId = $r->has('sku_id') ? $r->int('sku_id') : (int)($old['sku_id'] ?? 0);
        $basePrice = (float)$product['price'];
        if ($skuId > 0) {
            $sku = db()->one('SELECT * FROM product_skus WHERE id = ? AND product_id = ?', [$skuId, $productId]);
            if (!$sku) {
                json_error('规格不属于该商品');
            }
            $basePrice = (float)$sku['price'];
        }
        $price = $r->has('seckill_price') ? $r->float('seckill_price') : (float)($old['seckill_price'] ?? 0);
        if ($price <= 0 || $price >= $basePrice) {
            json_error('秒杀价需大于 0 且低于单买价 ' . sprintf('%.2f', $basePrice));
        }
        $stock = $r->has('stock') ? $r->int('stock') : (int)($old['stock'] ?? 0);
        if ($stock < 0 || $stock > 1000000) {
            json_error('秒杀库存不合法');
        }
        $limit = $r->has('limit_per_user') ? $r->int('limit_per_user') : (int)($old['limit_per_user'] ?? 1);
        if ($limit < 0 || $limit > 999) {
            json_error('每人限购需在 0-999 之间（0=不限）');
        }
        $toTs = function (string $key) use ($r, $old): int {
            if (!$r->has($key)) return (int)($old[$key] ?? 0);
            $v = $r->param($key);
            if (is_numeric($v)) return (int)$v > 9999999999 ? intdiv((int)$v, 1000) : (int)$v;
            return $v ? (int)strtotime((string)$v) : 0;
        };
        $start = $toTs('start_time');
        $end = $toTs('end_time');
        if ($start <= 0 || $end <= 0 || $end <= $start) {
            json_error('请设置正确的开始/结束时间');
        }
        $title = $r->has('title') ? $r->string('title') : (string)($old['title'] ?? '');
        return [
            'product_id' => $productId,
            'sku_id' => max(0, $skuId),
            'title' => mb_substr($title !== '' ? $title : ($product['name'] . ' 限时秒杀'), 0, 120),
            'seckill_price' => round($price, 2),
            'stock' => $stock,
            'limit_per_user' => $limit,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $r->has('status') ? ($r->int('status') ? 1 : 0) : (int)($old['status'] ?? 1),
            'sort' => $r->has('sort') ? $r->int('sort') : (int)($old['sort'] ?? 0),
        ];
    }
}
