<?php

namespace App\Controllers\Admin;

use Core\Controller;
use App\Models\Order;
use App\Services\OrderService;

/**
 * 管理后台 - 订单管理 + 配送 + 退款
 */
class OrderController extends Controller
{
    /**
     * GET /admin/orders
     */
    public function index(): void
    {
        [$page, $pageSize] = $this->pageParams();
        $condition = [];

        $status = $this->request->param('status');
        if ($status !== null && $status !== '') {
            $condition['status'] = (int)$status;
        }
        $orderNo = $this->request->string('order_no');
        if ($orderNo) {
            $condition['order_no'] = $orderNo;
        }
        $userId = $this->request->int('user_id');
        if ($userId > 0) {
            $condition['user_id'] = $userId;
        }
        $type = $this->request->param('type');
        if ($type !== null && $type !== '') {
            $condition['type'] = (int)$type;
        }

        // 时间范围
        $startDate = $this->request->string('start_date');
        $endDate = $this->request->string('end_date');
        $timeWhere = '';
        $timeParams = [];
        if ($startDate) {
            $timeWhere .= ' AND created_at >= ?';
            $timeParams[] = strtotime($startDate);
        }
        if ($endDate) {
            $timeWhere .= ' AND created_at < ?';
            $timeParams[] = strtotime($endDate) + 86400;
        }

        $result = (new Order())->paginate($condition, '*', 'id DESC', $page, $pageSize);
        $total = $result['total'];
        $list = $result['list'];

        // 汇总
        $summaryWhere = '1=1';
        $summaryParams = [];
        if (!empty($condition['status'])) {
            $summaryWhere .= ' AND status = ?';
            $summaryParams[] = $condition['status'];
        }
        $summary = db()->one(
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS total_amount, COALESCE(SUM(pay_amount),0) AS pay_amount
             FROM orders WHERE {$summaryWhere}{$timeWhere}",
            array_merge($summaryParams, $timeParams)
        );

        foreach ($list as &$order) {
            $order['status_text'] = Order::STATUS_MAP[$order['status']] ?? '';
            $order['items'] = db()->all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);
            $order['user_info'] = db()->one('SELECT id, nickname, phone FROM users WHERE id = ?', [$order['user_id']]);
            $order['delivery'] = db()->one('SELECT id, company, tracking_no, status FROM deliveries WHERE order_no = ?', [$order['order_no']]);
        }

        json_success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'summary' => [
                'count' => (int)$summary['cnt'],
                'total_amount' => (float)$summary['total_amount'],
                'pay_amount' => (float)$summary['pay_amount'],
            ],
        ]);
    }

    /**
     * GET /admin/orders/{id}
     */
    public function show(array $params): void
    {
        $order = Order::detailWithItems((int)$params['id']);
        if (!$order) {
            json_error('订单不存在');
        }
        $order['user_info'] = db()->one('SELECT id, nickname, phone, email, avatar FROM users WHERE id = ?', [$order['user_id']]);
        $delivery = db()->one('SELECT * FROM deliveries WHERE order_no = ?', [$order['order_no']]);
        if ($delivery) {
            $delivery['tracks'] = db()->all('SELECT * FROM delivery_tracks WHERE delivery_id = ? ORDER BY id DESC', [$delivery['id']]);
            $order['delivery'] = $delivery;
        }
        $order['refund'] = db()->one('SELECT * FROM refunds WHERE order_id = ?', [$order['id']]);
        // 拼团信息
        if ($order['type'] == 2) {
            $order['group_buy'] = db()->one(
                'SELECT gb.* FROM group_buys gb INNER JOIN group_buy_members gbm ON gbm.group_buy_id = gb.id WHERE gbm.order_no = ?',
                [$order['order_no']]
            );
        }
        json_success($order);
    }

    /**
     * POST /admin/orders/{id}/deliver
     * 发货（创建配送单，支持快递和自配送）
     */
    public function deliver(array $params): void
    {
        $id = (int)$params['id'];
        $order = Order::find($id);
        if (!$order) {
            json_error('订单不存在');
        }
        if ($order['status'] != Order::STATUS_PAID) {
            json_error('只有已支付待发货的订单才能发货');
        }

        $company = $this->request->string('company');
        $trackingNo = $this->request->string('tracking_no');
        $courierName = $this->request->string('courier_name');
        $courierPhone = $this->request->string('courier_phone');
        $remark = $this->request->string('remark');

        if (!$company && !$courierName) {
            json_error('请填写快递公司或配送员信息');
        }

        db()->beginTransaction();
        try {
            $deliveryNo = order_no('D');
            $deliveryId = db()->insert('deliveries', [
                'delivery_no' => $deliveryNo,
                'order_id' => $id,
                'order_no' => $order['order_no'],
                'user_id' => $order['user_id'],
                'company' => $company ?: null,
                'tracking_no' => $trackingNo ?: null,
                'courier_name' => $courierName ?: null,
                'courier_phone' => $courierPhone ?: null,
                'status' => 1, // 配送中
                'sender_name' => config('app.app_name'),
                'receiver_name' => $order['consignee'],
                'receiver_phone' => $order['phone'],
                'receiver_address' => $order['address'],
                'remark' => $remark ?: null,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            // 初始轨迹
            db()->insert('delivery_tracks', [
                'delivery_id' => $deliveryId,
                'description' => $courierName ? ('配送员 ' . $courierName . ' 已接单') : ('商品已交由 ' . $company . ' 承运，运单号：' . $trackingNo),
                'operator' => '系统',
                'created_at' => time(),
            ]);

            // 更新订单状态
            Order::updateById($id, [
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => time(),
            ]);
            db()->insert('order_logs', [
                'order_id' => $id,
                'status' => Order::STATUS_DELIVERED,
                'description' => $courierName ? '商家配送：' . $courierName . ' ' . $courierPhone : '已发货：' . $company . ' ' . $trackingNo,
                'operator_type' => 2,
                'operator_id' => \Core\Auth::adminId(),
                'created_at' => time(),
            ]);
            db()->commit();
        } catch (\Throwable $e) {
            db()->rollBack();
            throw $e;
        }

        // 通知用户（事务外）
        $user = \App\Models\User::find((int)$order['user_id']);
        $notifyContent = $courierName
            ? "您的订单 {$order['order_no']} 已安排配送，配送员：{$courierName}（{$courierPhone}）"
            : "您的订单 {$order['order_no']} 已发货，快递公司：{$company}，运单号：{$trackingNo}";
        db()->insert('notifications', [
            'user_id' => (int)$order['user_id'],
            'title' => '订单已发货',
            'content' => $notifyContent,
            'type' => 'order',
            'created_at' => time(),
        ]);
        if ($user && $user['phone'] && \App\Services\SmsService::enabled()) {
            \App\Services\SmsService::send($user['phone'], $notifyContent, 'order_delivered');
        }

        json_success(null, '发货成功');
    }

    /**
     * POST /admin/orders/{id}/complete
     * 管理员确认完成
     */
    public function complete(array $params): void
    {
        $id = (int)$params['id'];
        $order = Order::find($id);
        if (!$order || $order['status'] != Order::STATUS_DELIVERED) {
            json_error('只有已发货的订单才能完成');
        }
        if (!OrderService::confirmReceive($id, (int)$order['user_id'])) {
            json_error('操作失败');
        }
        json_success(null, '订单已完成');
    }

    /**
     * POST /admin/orders/{id}/cancel
     * 管理员取消订单（已支付订单退款到钱包）
     */
    public function cancel(array $params): void
    {
        $id = (int)$params['id'];
        $order = Order::find($id);
        if (!$order) {
            json_error('订单不存在');
        }
        $reason = $this->request->string('reason', '管理员取消');

        if ($order['status'] == Order::STATUS_PENDING) {
            if (!Order::cancel($id, $reason, 2, \Core\Auth::adminId())) {
                json_error('取消失败');
            }
        } elseif (in_array($order['status'], [Order::STATUS_PAID, Order::STATUS_DELIVERED])) {
            if (!OrderService::refund($id, $reason)) {
                json_error('取消失败');
            }
        } else {
            json_error('当前状态不可取消');
        }

        json_success(null, '订单已取消');
    }

    /**
     * GET /admin/refunds
     * 退款申请列表
     */
    public function refunds(): void
    {
        [$page, $pageSize] = $this->pageParams();
        $status = $this->request->param('status');

        $condition = [];
        if ($status !== null && $status !== '') {
            $condition['status'] = (int)$status;
        }
        $result = db()->query(
            'SELECT r.*, u.nickname, u.phone FROM refunds r LEFT JOIN users u ON u.id = r.user_id ' .
            ($condition ? 'WHERE r.status = ? ' : '') .
            'ORDER BY r.id DESC LIMIT ' . $pageSize . ' OFFSET ' . (($page - 1) * $pageSize),
            $condition ? [current($condition)] : []
        )->fetchAll();
        $total = (int)db()->value('SELECT COUNT(*) FROM refunds' . ($condition ? ' WHERE status = ?' : ''), $condition ? [current($condition)] : []);

        json_success(['list' => $result, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * POST /admin/refunds/{id}/handle
     * 处理退款申请：同意（退款到用户钱包）/ 拒绝
     */
    public function handleRefund(array $params): void
    {
        $id = (int)$params['id'];
        $refund = db()->one('SELECT * FROM refunds WHERE id = ?', [$id]);
        if (!$refund || $refund['status'] != 0) {
            json_error('退款申请不存在或已处理');
        }

        $action = $this->request->string('action'); // approve / reject
        if ($action === 'approve') {
            if (!OrderService::refund((int)$refund['order_id'], '管理员同意退款')) {
                json_error('退款处理失败');
            }
            db()->update('refunds', [
                'status' => 3,
                'handled_by' => \Core\Auth::adminId(),
                'updated_at' => time(),
            ], ['id' => $id]);
            json_success(null, '已同意退款，退款金额已退到用户钱包');
        } elseif ($action === 'reject') {
            $rejectReason = $this->request->string('reject_reason', '不符合退款条件');
            db()->update('refunds', [
                'status' => 2,
                'reject_reason' => $rejectReason,
                'handled_by' => \Core\Auth::adminId(),
                'updated_at' => time(),
            ], ['id' => $id]);
            // 订单恢复到已支付状态
            Order::updateById((int)$refund['order_id'], ['status' => Order::STATUS_PAID]);
            db()->insert('notifications', [
                'user_id' => (int)$refund['user_id'],
                'title' => '退款申请未通过',
                'content' => "您的订单 {$refund['order_no']} 退款申请未通过。原因：{$rejectReason}",
                'type' => 'order',
                'created_at' => time(),
            ]);
            json_success(null, '已拒绝退款');
        } else {
            json_error('无效的操作');
        }
    }

    /**
     * GET /admin/deliveries
     * 配送单列表
     */
    public function deliveries(): void
    {
        [$page, $pageSize] = $this->pageParams();
        $status = $this->request->param('status');

        $where = '';
        $params = [];
        if ($status !== null && $status !== '') {
            $where = 'WHERE d.status = ?';
            $params[] = (int)$status;
        }
        $list = db()->all(
            "SELECT d.*, o.pay_amount, o.status AS order_status, u.nickname, u.phone AS user_phone
             FROM deliveries d
             LEFT JOIN orders o ON o.order_no = d.order_no
             LEFT JOIN users u ON u.id = d.user_id
             {$where} ORDER BY d.id DESC LIMIT {$pageSize} OFFSET " . (($page - 1) * $pageSize),
            $params
        );
        $total = (int)db()->value("SELECT COUNT(*) FROM deliveries d {$where}", $params);

        json_success(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * POST /admin/deliveries/{id}/track
     * 添加配送轨迹
     */
    public function addTrack(array $params): void
    {
        $id = (int)$params['id'];
        $delivery = db()->one('SELECT * FROM deliveries WHERE id = ?', [$id]);
        if (!$delivery) {
            json_error('配送单不存在');
        }
        $description = $this->request->string('description');
        $this->validate(['description' => 'required|max:200|label:轨迹描述']);

        db()->insert('delivery_tracks', [
            'delivery_id' => $id,
            'description' => $description,
            'location' => $this->request->string('location') ?: null,
            'operator' => '管理员',
            'created_at' => time(),
        ]);

        // 可选：更新配送状态
        $status = $this->request->int('status');
        if (in_array($status, [0, 1, 2, 3], true)) {
            $update = ['status' => $status, 'updated_at' => time()];
            if ($status == 2) $update['delivered_at'] = time();
            if ($status == 3) $update['received_at'] = time();
            db()->update('deliveries', $update, ['id' => $id]);
        }

        json_success(null, '轨迹已添加');
    }
}
