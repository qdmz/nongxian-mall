<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use App\Models\GroupBuy;
use App\Models\UserAddress;
use App\Services\OrderService;

/**
 * 用户端 - 拼团
 */
class GroupBuyController extends Controller
{
    /**
     * GET /api/group-buy/activities
     * 拼团活动列表
     */
    public function activities(): void
    {
        [$page, $pageSize] = $this->pageParams(20);
        $now = time();
        $offset = ($page - 1) * $pageSize;
        $list = db()->all(
            'SELECT a.*, p.name, p.cover_image, p.price AS original_price, p.unit,
                    (SELECT COUNT(*) FROM group_buys gb WHERE gb.activity_id = a.id AND gb.status IN (0,1)) AS group_count
             FROM group_buy_activities a
             INNER JOIN products p ON p.id = a.product_id AND p.status = 1
             WHERE a.status = 1 AND (a.start_time = 0 OR a.start_time <= ?) AND (a.end_time = 0 OR a.end_time >= ?)
             ORDER BY a.id DESC
             LIMIT ? OFFSET ?',
            [$now, $now, $pageSize, $offset]
        );
        $total = (int)db()->value(
            'SELECT COUNT(*) FROM group_buy_activities a
             INNER JOIN products p ON p.id = a.product_id AND p.status = 1
             WHERE a.status = 1 AND (a.start_time = 0 OR a.start_time <= ?) AND (a.end_time = 0 OR a.end_time >= ?)',
            [$now, $now]
        );

        foreach ($list as &$item) {
            $item['discount'] = $item['original_price'] > 0 ? round($item['group_price'] / $item['original_price'] * 10, 1) : 10;
        }

        json_success(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * GET /api/group-buy/activities/{id}
     * 活动详情（含进行中的团列表）
     */
    public function activityDetail(array $params): void
    {
        $activityId = (int)$params['id'];
        $activity = db()->one(
            'SELECT a.*, p.name, p.cover_image, p.price AS original_price, p.unit, p.subtitle
             FROM group_buy_activities a
             INNER JOIN products p ON p.id = a.product_id AND p.status = 1
             WHERE a.id = ? AND a.status = 1',
            [$activityId]
        );
        if (!$activity) {
            json_error('拼团活动不存在或已结束');
        }

        // 进行中的团（可加入）
        $groups = db()->all(
            'SELECT gb.*, gbm.nickname AS leader_nickname, gbm.avatar AS leader_avatar
             FROM group_buys gb
             LEFT JOIN group_buy_members gbm ON gbm.group_buy_id = gb.id AND gbm.is_leader = 1
             WHERE gb.activity_id = ? AND gb.status = 0 AND gb.expire_at > ?
             ORDER BY gb.id DESC LIMIT 10',
            [$activityId, time()]
        );
        foreach ($groups as &$group) {
            $group['remaining_count'] = max(0, (int)$group['required_count'] - (int)$group['joined_count']);
            $group['remaining_seconds'] = max(0, (int)$group['expire_at'] - time());
        }
        $activity['groups'] = $groups;

        json_success($activity);
    }

    /**
     * POST /api/group-buy/orders
     * 创建拼团订单（开团 or 参团）
     */
    public function createOrder(): void
    {
        $userId = Auth::userId();
        $activityId = $this->request->int('activity_id');
        $groupBuyId = $this->request->int('group_buy_id') ?: null; // 传了是参团，不传是开团
        $quantity = max(1, $this->request->int('quantity', 1));
        $addressId = $this->request->int('address_id');
        $remark = $this->request->string('remark');

        $address = UserAddress::where(['id' => $addressId, 'user_id' => $userId]);
        if (!$address) {
            json_error('收货地址不存在，请先添加地址');
        }

        $order = OrderService::createGroupBuyOrder((int)$userId, $activityId, $groupBuyId, $quantity, $address, $remark);

        json_success($order, $groupBuyId ? '参团成功' : '开团成功');
    }

    /**
     * GET /api/group-buy/groups/{id}
     * 拼团详情（分享给好友看的页面）
     */
    public function groupDetail(array $params): void
    {
        $group = GroupBuy::detailWithMembers((int)$params['id']);
        if (!$group) {
            json_error('拼团不存在');
        }
        // 活动信息
        $group['activity'] = db()->one(
            'SELECT a.*, p.name AS product_name FROM group_buy_activities a WHERE a.id = ?',
            [$group['activity_id']]
        );
        json_success($group);
    }

    /**
     * GET /api/group-buy/my-groups
     * 我参与/发起的拼团
     */
    public function myGroups(): void
    {
        $userId = Auth::userId();
        [$page, $pageSize] = $this->pageParams(20);
        $offset = ($page - 1) * $pageSize;

        $list = db()->all(
            'SELECT gb.*, gbm.is_leader, gbm.order_no AS my_order_no, p.name AS product_name, p.cover_image
             FROM group_buy_members gbm
             INNER JOIN group_buys gb ON gb.id = gbm.group_buy_id
             INNER JOIN products p ON p.id = gb.product_id
             WHERE gbm.user_id = ? AND gbm.status = 1
             ORDER BY gbm.id DESC
             LIMIT ? OFFSET ?',
            [$userId, $pageSize, $offset]
        );
        $total = (int)db()->value('SELECT COUNT(*) FROM group_buy_members WHERE user_id = ? AND status = 1', [$userId]);

        foreach ($list as &$group) {
            $group['remaining_count'] = max(0, (int)$group['required_count'] - (int)$group['joined_count']);
            $group['remaining_seconds'] = max(0, (int)$group['expire_at'] - time());
        }

        json_success(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }
}
