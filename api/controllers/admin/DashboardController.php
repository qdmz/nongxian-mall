<?php

namespace App\Controllers\Admin;

use Core\Controller;
use App\Services\StatisticsService;

/**
 * 管理后台 - 仪表盘/统计
 */
class DashboardController extends Controller
{
    /**
     * GET /admin/dashboard
     */
    public function index(): void
    {
        json_success(StatisticsService::dashboard());
    }

    /**
     * GET /admin/dashboard/sales-trend?days=30
     */
    public function salesTrend(): void
    {
        $days = min(90, max(7, $this->request->int('days', 30)));
        json_success(StatisticsService::salesTrend($days));
    }

    /**
     * GET /admin/dashboard/product-rank?days=30
     */
    public function productRank(): void
    {
        $days = min(365, max(1, $this->request->int('days', 30)));
        $limit = min(100, max(5, $this->request->int('limit', 20)));
        json_success(StatisticsService::productRank($days, $limit));
    }

    /**
     * GET /admin/dashboard/category-sales?days=30
     */
    public function categorySales(): void
    {
        $days = min(365, max(1, $this->request->int('days', 30)));
        json_success(StatisticsService::categorySales($days));
    }

    /**
     * GET /admin/dashboard/latest-orders
     * 最新订单（仪表盘展示）
     */
    public function latestOrders(): void
    {
        $list = db()->all(
            'SELECT o.id, o.order_no, o.pay_amount, o.status, o.created_at, u.nickname, u.phone
             FROM orders o LEFT JOIN users u ON u.id = o.user_id
             ORDER BY o.id DESC LIMIT 10'
        );
        foreach ($list as &$order) {
            $order['status_text'] = \App\Models\Order::STATUS_MAP[$order['status']] ?? '';
        }
        json_success($list);
    }

    /**
     * GET /admin/dashboard/low-stock
     * 库存预警
     */
    public function lowStock(): void
    {
        $list = db()->all(
            'SELECT id, name, cover_image, stock, stock_warn FROM products
             WHERE status = 1 AND stock <= stock_warn ORDER BY stock ASC LIMIT 20'
        );
        json_success($list);
    }
}
