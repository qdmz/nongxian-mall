<?php

namespace App\Controllers\Admin;

use Core\Controller;
use App\Models\Product;

/**
 * 管理后台 - 商品管理
 */
class ProductController extends Controller
{
    /**
     * GET /admin/products
     */
    public function index(): void
    {
        [$page, $pageSize] = $this->pageParams();
        $condition = [];

        $keyword = $this->request->string('keyword');
        $categoryId = $this->request->int('category_id');
        $status = $this->request->param('status');

        if ($categoryId > 0) {
            $condition['category_id'] = $categoryId;
        }
        if ($status !== null && $status !== '') {
            $condition['status'] = (int)$status;
        }

        if ($keyword) {
            $where = 'name LIKE ?';
            $params = ["%{$keyword}%"];
            foreach ($condition as $k => $v) {
                $where .= " AND {$k} = ?";
                $params[] = $v;
            }
            $total = (int)db()->value("SELECT COUNT(*) FROM products WHERE {$where}", $params);
            $list = db()->all("SELECT * FROM products WHERE {$where} ORDER BY id DESC LIMIT {$pageSize} OFFSET " . (($page - 1) * $pageSize), $params);
        } else {
            $result = (new Product())->paginate($condition, '*', 'id DESC', $page, $pageSize);
            $list = $result['list'];
            $total = $result['total'];
        }

        $categories = db()->all('SELECT id, name FROM categories');
        $catMap = array_column($categories, 'name', 'id');
        foreach ($list as &$item) {
            $item['category_name'] = $catMap[$item['category_id']] ?? '';
            $item['images_arr'] = $item['images'] ? json_decode($item['images'], true) : [];
        }

        json_success(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * GET /admin/products/{id}
     */
    public function show(array $params): void
    {
        $product = Product::find((int)$params['id']);
        if (!$product) {
            json_error('商品不存在');
        }
        $product['images_arr'] = $product['images'] ? json_decode($product['images'], true) : [];
        $product['skus'] = db()->all('SELECT * FROM product_skus WHERE product_id = ? ORDER BY id ASC', [$product['id']]);
        $product['category_name'] = db()->value('SELECT name FROM categories WHERE id = ?', [$product['category_id']]);
        // 销量统计
        $product['total_sales_amount'] = (float)db()->value(
            'SELECT COALESCE(SUM(oi.subtotal),0) FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id AND o.status IN (1,2,3) WHERE oi.product_id = ?',
            [$product['id']]
        );
        json_success($product);
    }

    /**
     * POST /admin/products
     */
    public function store(): void
    {
        $data = $this->validate([
            'name' => 'required|max:100|label:商品名称',
            'category_id' => 'required|int|label:分类',
            'price' => 'required|float|label:售价',
        ]);

        $data = array_merge($data, [
            'subtitle' => $this->request->string('subtitle') ?: null,
            'description' => $this->request->string('description') ?: null,
            'cover_image' => $this->request->string('cover_image') ?: null,
            'images' => $this->request->param('images') ? json_encode($this->request->param('images'), JSON_UNESCAPED_UNICODE) : null,
            'unit' => $this->request->string('unit', '件'),
            'original_price' => $this->request->float('original_price') ?: null,
            'cost_price' => $this->request->float('cost_price') ?: null,
            'stock' => $this->request->int('stock'),
            'stock_warn' => $this->request->int('stock_warn', 10),
            'virtual_sales' => $this->request->int('virtual_sales'),
            'status' => $this->request->int('status', 0),
            'sort' => $this->request->int('sort', 0),
            'is_hot' => $this->request->int('is_hot') ? 1 : 0,
            'is_new' => $this->request->int('is_new') ? 1 : 0,
            'is_recommend' => $this->request->int('is_recommend') ? 1 : 0,
            'is_red' => $this->request->int('is_red') ? 1 : 0,
            'origin' => $this->request->string('origin') ?: null,
            'farmer' => $this->request->string('farmer') ?: null,
        ]);

        // 分类校验
        if (!db()->one('SELECT id FROM categories WHERE id = ?', [$data['category_id']])) {
            json_error('分类不存在');
        }

        db()->beginTransaction();
        try {
            $id = Product::create($data);
            // 保存 SKU
            $skus = $this->request->param('skus');
            if (is_array($skus) && !empty($skus)) {
                foreach ($skus as $sku) {
                    if (empty($sku['specs'])) continue;
                    db()->insert('product_skus', [
                        'product_id' => $id,
                        'specs' => is_string($sku['specs']) ? $sku['specs'] : json_encode($sku['specs'], JSON_UNESCAPED_UNICODE),
                        'price' => (float)$sku['price'],
                        'stock' => (int)($sku['stock'] ?? 0),
                        'status' => 1,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }
            }
            db()->commit();
        } catch (\Throwable $e) {
            db()->rollBack();
            throw $e;
        }

        json_success(Product::find($id), '商品已创建');
    }

    /**
     * PUT /admin/products/{id}
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $product = Product::find($id);
        if (!$product) {
            json_error('商品不存在');
        }

        $data = $this->request->only([
            'name', 'subtitle', 'description', 'cover_image', 'unit',
            'original_price', 'cost_price', 'stock', 'stock_warn', 'virtual_sales',
            'status', 'sort', 'is_hot', 'is_new', 'is_recommend', 'is_red', 'origin', 'farmer', 'category_id', 'price'
        ]);

        if (isset($data['images'])) {
            $data['images'] = is_array($data['images']) ? json_encode($data['images'], JSON_UNESCAPED_UNICODE) : $data['images'];
        }
        foreach (['is_hot', 'is_new', 'is_recommend', 'is_red'] as $flag) {
            if (isset($data[$flag])) {
                $data[$flag] = (int)$data[$flag] ? 1 : 0;
            }
        }
        if (isset($data['price']) && (float)$data['price'] <= 0) {
            json_error('售价必须大于0');
        }
        $data['updated_at'] = time();

        db()->beginTransaction();
        try {
            db()->update('products', $data, ['id' => $id]);

            // SKU 处理：传了 skus 数组则全量替换
            $skus = $this->request->param('skus');
            if (is_array($skus)) {
                db()->delete('product_skus', ['product_id' => $id]);
                foreach ($skus as $sku) {
                    if (empty($sku['specs'])) continue;
                    db()->insert('product_skus', [
                        'product_id' => $id,
                        'specs' => is_string($sku['specs']) ? $sku['specs'] : json_encode($sku['specs'], JSON_UNESCAPED_UNICODE),
                        'price' => (float)$sku['price'],
                        'stock' => (int)($sku['stock'] ?? 0),
                        'status' => isset($sku['status']) ? (int)$sku['status'] : 1,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                }
            }
            db()->commit();
        } catch (\Throwable $e) {
            db()->rollBack();
            throw $e;
        }

        json_success(Product::find($id), '更新成功');
    }

    /**
     * POST /admin/products/{id}/toggle-status
     * 快速上下架
     */
    public function toggleStatus(array $params): void
    {
        $id = (int)$params['id'];
        $product = Product::find($id);
        if (!$product) {
            json_error('商品不存在');
        }
        $newStatus = $product['status'] == 1 ? 0 : 1;
        db()->update('products', ['status' => $newStatus, 'updated_at' => time()], ['id' => $id]);
        json_success(['status' => $newStatus], $newStatus == 1 ? '已上架' : '已下架');
    }

    /**
     * POST /admin/products/{id}/update-stock
     * 快速改库存
     */
    public function updateStock(array $params): void
    {
        $id = (int)$params['id'];
        $stock = $this->request->int('stock');
        if ($stock < 0) {
            json_error('库存不能为负数');
        }
        $updated = db()->update('products', ['stock' => $stock, 'updated_at' => time()], ['id' => $id]);
        if (!$updated) {
            json_error('商品不存在');
        }
        json_success(null, '库存已更新');
    }

    /**
     * DELETE /admin/products/{id}
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];
        $product = Product::find($id);
        if (!$product) {
            json_error('商品不存在');
        }
        // 有订单的商品不能物理删除，改为下架
        $orderCount = (int)db()->value('SELECT COUNT(*) FROM order_items WHERE product_id = ?', [$id]);
        if ($orderCount > 0) {
            db()->update('products', ['status' => 0, 'updated_at' => time()], ['id' => $id]);
            json_success(null, '该商品已有订单记录，已自动下架（保留历史数据）');
        }
        db()->delete('product_skus', ['product_id' => $id]);
        Product::deleteById($id);
        json_success(null, '商品已删除');
    }
}
