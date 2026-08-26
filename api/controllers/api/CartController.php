<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use App\Models\Cart;

/**
 * 用户端 - 购物车
 */
class CartController extends Controller
{
    /**
     * GET /api/cart
     */
    public function index(): void
    {
        $userId = Auth::userId();
        $list = Cart::listWithProduct((int)$userId);

        // 统计
        $validItems = array_values(array_filter($list, fn($item) => !$item['invalid']));
        $selectedAmount = 0;
        $selectedCount = 0;
        foreach ($validItems as $item) {
            if ($item['selected']) {
                $selectedAmount += $item['real_price'] * $item['quantity'];
                $selectedCount += $item['quantity'];
            }
        }

        json_success([
            'list' => $list,
            'selected_count' => $selectedCount,
            'selected_amount' => round($selectedAmount, 2),
        ]);
    }

    /**
     * POST /api/cart
     * 添加商品到购物车
     */
    public function store(): void
    {
        $userId = Auth::userId();
        $productId = $this->request->int('product_id');
        $skuId = $this->request->int('sku_id') ?: null;
        $quantity = max(1, $this->request->int('quantity', 1));

        $product = db()->one('SELECT * FROM products WHERE id = ? AND status = 1', [$productId]);
        if (!$product) {
            json_error('商品不存在或已下架');
        }
        if ($skuId) {
            $sku = db()->one('SELECT * FROM product_skus WHERE id = ? AND product_id = ? AND status = 1', [$skuId, $productId]);
            if (!$sku) json_error('商品规格不存在');
            if ($sku['stock'] < $quantity) json_error('库存不足');
        } else {
            if ($product['stock'] < $quantity) json_error('库存不足');
        }

        // 已存在则累加数量
        $existing = db()->one(
            'SELECT * FROM carts WHERE user_id = ? AND product_id = ? AND IFNULL(sku_id, 0) = IFNULL(?, 0)',
            [$userId, $productId, $skuId]
        );
        if ($existing) {
            $newQty = (int)$existing['quantity'] + $quantity;
            db()->update('carts', ['quantity' => $newQty, 'selected' => 1, 'updated_at' => time()], ['id' => $existing['id']]);
        } else {
            db()->insert('carts', [
                'user_id' => $userId,
                'product_id' => $productId,
                'sku_id' => $skuId,
                'quantity' => $quantity,
                'selected' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        json_success(null, '已加入购物车');
    }

    /**
     * PUT /api/cart
     * 更新数量/选中状态
     */
    public function update(): void
    {
        $userId = Auth::userId();
        $id = $this->request->int('id');
        $data = [];

        $cart = db()->one('SELECT * FROM carts WHERE id = ? AND user_id = ?', [$id, $userId]);
        if (!$cart) {
            json_error('购物车项不存在');
        }

        if ($this->request->has('quantity')) {
            $quantity = max(1, $this->request->int('quantity'));
            // 检查库存
            $stock = $cart['sku_id']
                ? (int)db()->value('SELECT stock FROM product_skus WHERE id = ?', [$cart['sku_id']])
                : (int)db()->value('SELECT stock FROM products WHERE id = ?', [$cart['product_id']]);
            if ($quantity > $stock) {
                json_error('库存不足，最多购买 ' . $stock . ' 件');
            }
            $data['quantity'] = $quantity;
        }
        if ($this->request->has('selected')) {
            $data['selected'] = $this->request->int('selected') ? 1 : 0;
        }
        if ($data) {
            $data['updated_at'] = time();
            db()->update('carts', $data, ['id' => $id]);
        }

        json_success(null, '更新成功');
    }

    /**
     * DELETE /api/cart/{id}
     */
    public function destroy(array $params): void
    {
        $userId = Auth::userId();
        $id = (int)$params['id'];
        $deleted = db()->delete('carts', ['id' => $id, 'user_id' => $userId]);
        if (!$deleted) {
            json_error('删除失败');
        }
        json_success(null, '已删除');
    }

    /**
     * POST /api/cart/clear-invalid
     * 清空失效商品
     */
    public function clearInvalid(): void
    {
        $userId = Auth::userId();
        $list = Cart::listWithProduct((int)$userId);
        foreach ($list as $item) {
            if ($item['invalid']) {
                db()->delete('carts', ['id' => $item['id']]);
            }
        }
        json_success(null, '已清空失效商品');
    }
}
