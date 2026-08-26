<?php

namespace App\Controllers\Api;

use Core\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;

/**
 * 用户端 - 商品
 */
class ProductController extends Controller
{
    /**
     * GET /api/products
     * 分类筛选、关键词搜索、排序、分页
     */
    public function index(): void
    {
        [$page, $pageSize] = $this->pageParams(50);
        $filters = [
            'category_id' => $this->request->int('category_id'),
            'keyword' => $this->request->string('keyword'),
            'is_red' => $this->request->has('is_red') ? $this->request->int('is_red') : null,
            'is_hot' => $this->request->has('is_hot') ? $this->request->int('is_hot') : null,
            'is_recommend' => $this->request->has('is_recommend') ? $this->request->int('is_recommend') : null,
            'is_new' => $this->request->has('is_new') ? $this->request->int('is_new') : null,
            'order_by' => $this->request->string('order_by'),
        ];
        $result = Product::listForShop(array_filter($filters, fn($v) => $v !== null && $v !== ''), $page, $pageSize);

        // 处理图片 URL、隐藏成本价
        foreach ($result['list'] as &$item) {
            unset($item['cost_price']);
            $item['images_arr'] = $item['images'] ? json_decode($item['images'], true) : [];
            $item['display_sales'] = (int)$item['sales'] + (int)$item['virtual_sales'];
        }

        json_success($result);
    }

    /**
     * GET /api/products/{id}
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $product = db()->one('SELECT * FROM products WHERE id = ? AND status = 1', [$id]);
        if (!$product) {
            json_error('商品不存在或已下架');
        }

        // 浏览量+1
        db()->query('UPDATE products SET view_count = view_count + 1 WHERE id = ?', [$id]);

        $product['images_arr'] = $product['images'] ? json_decode($product['images'], true) : [];
        $product['display_sales'] = (int)$product['sales'] + (int)$product['virtual_sales'];
        $product['skus'] = db()->all('SELECT id, specs, price, stock FROM product_skus WHERE product_id = ? AND status = 1', [$id]);
        $product['category'] = db()->one('SELECT id, name FROM categories WHERE id = ?', [$product['category_id']]);
        unset($product['cost_price']);

        // 拼团活动信息
        $activity = db()->one(
            'SELECT * FROM group_buy_activities WHERE product_id = ? AND status = 1
             AND (start_time = 0 OR start_time <= ?) AND (end_time = 0 OR end_time >= ?)',
            [$id, time(), time()]
        );
        $product['group_buy_activity'] = $activity ?: null;
        // 进行中的拼团数
        if ($activity) {
            $product['group_buying_count'] = (int)db()->value(
                'SELECT COUNT(*) FROM group_buys WHERE activity_id = ? AND status = 0 AND expire_at > ?',
                [$activity['id'], time()]
            );
        }

        // 推荐商品（同类）
        $product['related'] = db()->all(
            'SELECT id, name, cover_image, price, unit FROM products WHERE category_id = ? AND id != ? AND status = 1 ORDER BY sales DESC LIMIT 6',
            [$product['category_id'], $id]
        );

        json_success($product);
    }

    /**
     * GET /api/categories
     */
    public function categories(): void
    {
        json_success(Category::tree());
    }

    /**
     * GET /api/home
     * 首页聚合数据：轮播图 + 分类 + 热销 + 推荐 + 新品 + 红色助农
     */
    public function home(): void
    {
        $banners = Banner::listByPosition('home');

        $categories = db()->all('SELECT id, name, icon, image FROM categories WHERE status = 1 AND parent_id = 0 ORDER BY sort DESC, id ASC LIMIT 10');

        $hot = db()->all('SELECT id, name, subtitle, cover_image, price, original_price, unit, sales, virtual_sales FROM products WHERE status = 1 AND is_hot = 1 ORDER BY sort DESC, sales DESC LIMIT 8');
        $recommend = db()->all('SELECT id, name, subtitle, cover_image, price, original_price, unit, sales, virtual_sales FROM products WHERE status = 1 AND is_recommend = 1 ORDER BY sort DESC, sales DESC LIMIT 8');
        $newProducts = db()->all('SELECT id, name, subtitle, cover_image, price, original_price, unit, sales, virtual_sales FROM products WHERE status = 1 AND is_new = 1 ORDER BY id DESC LIMIT 8');
        $red = db()->all('SELECT id, name, subtitle, cover_image, price, original_price, unit, sales, virtual_sales FROM products WHERE status = 1 AND is_red = 1 ORDER BY sort DESC LIMIT 8');

        // 拼团进行中的商品
        $groupBuyProducts = db()->all(
            'SELECT p.id, p.name, p.cover_image, p.unit, a.group_price, a.required_count,
                    (SELECT COUNT(*) FROM group_buys gb WHERE gb.activity_id = a.id AND gb.status = 0 AND gb.expire_at > ?) AS group_count
             FROM group_buy_activities a
             INNER JOIN products p ON p.id = a.product_id AND p.status = 1
             WHERE a.status = 1 AND (a.start_time = 0 OR a.start_time <= ?) AND (a.end_time = 0 OR a.end_time >= ?)
             ORDER BY a.id DESC LIMIT 6',
            [time(), time(), time()]
        );

        json_success([
            'banners' => $banners,
            'categories' => $categories,
            'hot' => $hot,
            'recommend' => $recommend,
            'new' => $newProducts,
            'red' => $red,
            'group_buy' => $groupBuyProducts,
        ]);
    }

    /**
     * GET /api/search/hot-keywords
     */
    public function hotKeywords(): void
    {
        $keywords = db()->all(
            'SELECT name FROM products WHERE status = 1 ORDER BY sales DESC, view_count DESC LIMIT 10'
        );
        json_success(array_column($keywords, 'name'));
    }
}
