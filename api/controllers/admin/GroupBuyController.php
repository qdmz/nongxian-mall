<?php

namespace App\Controllers\Admin;

use Core\Controller;
use App\Models\GroupBuy;

/**
 * 管理后台 - 拼团管理
 */
class GroupBuyController extends Controller
{
    /**
     * GET /admin/group-buy/activities
     */
    public function activities(): void
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
            "SELECT a.*, p.name AS product_name, p.cover_image,
                    (SELECT COUNT(*) FROM group_buys gb WHERE gb.activity_id = a.id) AS total_groups,
                    (SELECT COUNT(*) FROM group_buys gb WHERE gb.activity_id = a.id AND gb.status = 1) AS success_groups
             FROM group_buy_activities a
             LEFT JOIN products p ON p.id = a.product_id
             {$where} ORDER BY a.id DESC LIMIT {$pageSize} OFFSET " . (($page - 1) * $pageSize),
            $params
        );
        $total = (int)db()->value("SELECT COUNT(*) FROM group_buy_activities a {$where}", $params);

        json_success(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * POST /admin/group-buy/activities
     */
    public function storeActivity(): void
    {
        $data = $this->validate([
            'product_id' => 'required|int|label:商品ID',
            'group_price' => 'required|float|label:拼团价',
            'required_count' => 'required|int|label:成团人数',
        ]);

        $product = db()->one('SELECT * FROM products WHERE id = ?', [$data['product_id']]);
        if (!$product) {
            json_error('商品不存在');
        }
        if ($data['group_price'] >= (float)$product['price']) {
            json_error('拼团价应低于商品原价');
        }
        if ($data['required_count'] < 2 || $data['required_count'] > 100) {
            json_error('成团人数需在 2-100 之间');
        }

        $data = array_merge($data, [
            'title' => $this->request->string('title') ?: ($product['name'] . ' ' . $data['required_count'] . '人团'),
            'valid_hours' => max(1, $this->request->int('valid_hours', 24)),
            'max_count' => $this->request->int('max_count'),
            'stock' => $this->request->int('stock', (int)$product['stock']),
            'status' => $this->request->int('status', 1),
            'start_time' => $this->request->string('start_time') ? strtotime($this->request->string('start_time')) : 0,
            'end_time' => $this->request->string('end_time') ? strtotime($this->request->string('end_time')) : 0,
        ]);

        $id = db()->insert('group_buy_activities', array_merge($data, ['created_at' => time(), 'updated_at' => time()]));
        // 标记商品支持拼团
        db()->update('products', ['is_group_buy' => 1, 'updated_at' => time()], ['id' => $data['product_id']]);

        json_success(db()->one('SELECT * FROM group_buy_activities WHERE id = ?', [$id]), '拼团活动已创建');
    }

    /**
     * PUT /admin/group-buy/activities/{id}
     */
    public function updateActivity(array $params): void
    {
        $id = (int)$params['id'];
        $activity = db()->one('SELECT * FROM group_buy_activities WHERE id = ?', [$id]);
        if (!$activity) {
            json_error('拼团活动不存在');
        }

        $data = $this->request->only(['title', 'group_price', 'required_count', 'valid_hours', 'max_count', 'stock', 'status', 'start_time', 'end_time']);
        if (empty($data)) {
            json_error('没有需要更新的内容');
        }
        if (isset($data['group_price'])) {
            $price = (float)db()->value('SELECT price FROM products WHERE id = ?', [$activity['product_id']]);
            if ($data['group_price'] >= $price) {
                json_error('拼团价应低于商品原价');
            }
        }
        if (isset($data['required_count']) && ($data['required_count'] < 2 || $data['required_count'] > 100)) {
            json_error('成团人数需在 2-100 之间');
        }
        if (isset($data['start_time']) && is_string($data['start_time'])) {
            $data['start_time'] = $data['start_time'] ? strtotime($data['start_time']) : 0;
        }
        if (isset($data['end_time']) && is_string($data['end_time'])) {
            $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']) : 0;
        }
        $data['updated_at'] = time();

        db()->update('group_buy_activities', $data, ['id' => $id]);
        json_success(null, '活动已更新');
    }

    /**
     * DELETE /admin/group-buy/activities/{id}
     * 删除活动（进行中的团不受影响，只是不能再开新团）
     */
    public function destroyActivity(array $params): void
    {
        $id = (int)$params['id'];
        $activity = db()->one('SELECT * FROM group_buy_activities WHERE id = ?', [$id]);
        if (!$activity) {
            json_error('拼团活动不存在');
        }
        $pendingGroups = (int)db()->value('SELECT COUNT(*) FROM group_buys WHERE activity_id = ? AND status = 0', [$id]);
        db()->update('group_buy_activities', ['status' => 0, 'updated_at' => time()], ['id' => $id]);
        json_success(null, $pendingGroups > 0 ? '活动已停用（还有 ' . $pendingGroups . ' 个进行中的团，到期自动处理）' : '活动已停用');
    }

    /**
     * GET /admin/group-buy/groups
     * 拼团单列表
     */
    public function groups(): void
    {
        [$page, $pageSize] = $this->pageParams();
        $status = $this->request->param('status');

        $where = '';
        $params = [];
        if ($status !== null && $status !== '') {
            $where = 'WHERE gb.status = ?';
            $params[] = (int)$status;
        }
        $list = db()->all(
            "SELECT gb.*, p.name AS product_name, p.cover_image,
                    u.nickname AS leader_nickname, u.phone AS leader_phone
             FROM group_buys gb
             LEFT JOIN products p ON p.id = gb.product_id
             LEFT JOIN users u ON u.id = gb.leader_user_id
             {$where} ORDER BY gb.id DESC LIMIT {$pageSize} OFFSET " . (($page - 1) * $pageSize),
            $params
        );
        $total = (int)db()->value("SELECT COUNT(*) FROM group_buys gb {$where}", $params);

        foreach ($list as &$group) {
            $group['remaining_count'] = max(0, (int)$group['required_count'] - (int)$group['joined_count']);
        }

        json_success(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * GET /admin/group-buy/groups/{id}
     * 拼团详情（含成员）
     */
    public function groupDetail(array $params): void
    {
        $group = GroupBuy::detailWithMembers((int)$params['id']);
        if (!$group) {
            json_error('拼团不存在');
        }
        json_success($group);
    }
}
