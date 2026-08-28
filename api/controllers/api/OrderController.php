<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use App\Models\User;
use App\Models\Order;
use App\Models\UserAddress;
use App\Services\OrderService;
use App\Services\PayService;

/**
 * 用户端 - 订单
 */
class OrderController extends Controller
{
    /**
     * POST /api/orders
     * 创建订单（来自购物车或直接购买）
     */
    public function store(): void
    {
        $userId = Auth::userId();
        $addressId = $this->request->int('address_id');
        $remark = $this->request->string('remark');
        $useBalance = $this->request->float('use_balance');
        $fromCart = $this->request->bool('from_cart', false);

        $address = UserAddress::where(['id' => $addressId, 'user_id' => $userId]);
        if (!$address) {
            json_error('收货地址不存在，请先添加地址');
        }

        // 商品项
        $items = $this->request->param('items');
        if (empty($items)) {
            json_error('请选择要购买的商品');
        }
        if (!is_array($items)) {
            json_error('参数错误');
        }

        $order = OrderService::createOrder((int)$userId, $items, $address, $remark, $useBalance);

        // 从购物车下单，移除对应购物车项
        if ($fromCart) {
            foreach ($items as $item) {
                db()->query(
                    'DELETE FROM carts WHERE user_id = ? AND product_id = ? AND IFNULL(sku_id,0) = IFNULL(?,0)',
                    [$userId, (int)$item['product_id'], isset($item['sku_id']) ? (int)$item['sku_id'] : null]
                );
            }
        }

        json_success($order, '下单成功');
    }

    /**
     * GET /api/orders
     * 订单列表（支持状态筛选）
     */
    public function index(): void
    {
        $userId = Auth::userId();
        [$page, $pageSize] = $this->pageParams(20);
        $status = $this->request->param('status'); // 不传查全部

        $condition = ['user_id' => $userId];
        if ($status !== null && $status !== '') {
            $condition['status'] = (int)$status;
        }

        $result = (new Order())->paginate($condition, '*', 'id DESC', $page, $pageSize);
        foreach ($result['list'] as &$order) {
            $order['items'] = db()->all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);
            $order['status_text'] = Order::STATUS_MAP[$order['status']] ?? '';
            // 拼团信息
            if ($order['type'] == 2) {
                $member = db()->one('SELECT gb.*, gbm.is_leader FROM group_buy_members gbm INNER JOIN group_buys gb ON gb.id = gbm.group_buy_id WHERE gbm.order_no = ?', [$order['order_no']]);
                if ($member) {
                    $order['group_buy'] = [
                        'group_no' => $member['group_no'],
                        'status' => (int)$member['status'],
                        'required_count' => (int)$member['required_count'],
                        'joined_count' => (int)$member['joined_count'],
                        'expire_at' => (int)$member['expire_at'],
                        'is_leader' => (int)$member['is_leader'],
                    ];
                }
            }
            // 配送信息
            $delivery = db()->one('SELECT delivery_no, company, tracking_no, status FROM deliveries WHERE order_no = ?', [$order['order_no']]);
            $order['delivery'] = $delivery ?: null;
        }

        json_success($result);
    }

    /**
     * GET /api/orders/{id}
     */
    public function show(array $params): void
    {
        $userId = Auth::userId();
        $order = Order::where(['id' => (int)$params['id'], 'user_id' => $userId]);
        if (!$order) {
            json_error('订单不存在');
        }
        $order = Order::detailWithItems((int)$params['id']);
        $order['use_balance'] = (float)$order['use_balance'];

        // 配送详情
        $delivery = db()->one('SELECT * FROM deliveries WHERE order_no = ?', [$order['order_no']]);
        if ($delivery) {
            $delivery['tracks'] = db()->all('SELECT * FROM delivery_tracks WHERE delivery_id = ? ORDER BY id DESC', [$delivery['id']]);
            $order['delivery'] = $delivery;
        }

        json_success($order);
    }

    /**
     * POST /api/orders/{id}/cancel
     * 取消订单（仅待支付）
     */
    public function cancel(array $params): void
    {
        $userId = Auth::userId();
        $order = Order::where(['id' => (int)$params['id'], 'user_id' => $userId]);
        if (!$order) {
            json_error('订单不存在');
        }
        if (!Order::cancel((int)$order['id'], '用户取消', 1, (int)$userId)) {
            json_error('当前状态不可取消');
        }
        json_success(null, '订单已取消');
    }

    /**
     * POST /api/orders/{id}/confirm
     * 确认收货
     */
    public function confirm(array $params): void
    {
        $userId = Auth::userId();
        if (!OrderService::confirmReceive((int)$params['id'], (int)$userId)) {
            json_error('确认收货失败');
        }
        json_success(null, '确认收货成功');
    }

    /**
     * POST /api/orders/{id}/apply-refund
     * 申请退款
     */
    public function applyRefund(array $params): void
    {
        $userId = Auth::userId();
        $order = Order::where(['id' => (int)$params['id'], 'user_id' => $userId]);
        if (!$order) {
            json_error('订单不存在');
        }
        if (!in_array($order['status'], [Order::STATUS_PAID, Order::STATUS_DELIVERED])) {
            json_error('当前状态不可申请退款');
        }
        // 已有处理中的退款
        $exists = db()->one('SELECT id FROM refunds WHERE order_id = ? AND status = 0', [$order['id']]);
        if ($exists) {
            json_error('已提交退款申请，请耐心等待处理');
        }

        $refundNo = order_no('R');
        db()->insert('refunds', [
            'refund_no' => $refundNo,
            'order_id' => $order['id'],
            'order_no' => $order['order_no'],
            'user_id' => $userId,
            'amount' => round((float)$order['pay_amount'] + (float)$order['use_balance'], 2),
            'reason' => $this->request->string('reason'),
            'status' => 0,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        Order::updateById((int)$order['id'], ['status' => Order::STATUS_REFUNDING]);

        json_success(null, '退款申请已提交，请等待管理员处理');
    }

    /**
     * POST /api/orders/{id}/pay
     * 发起支付（balance/wxpay/alipay/qqpay）
     */
    public function pay(array $params): void
    {
        $userId = Auth::userId();
        $order = Order::where(['id' => (int)$params['id'], 'user_id' => $userId]);
        if (!$order) {
            json_error('订单不存在');
        }
        if ($order['status'] != Order::STATUS_PENDING) {
            json_error('订单状态不可支付');
        }

        $payType = $this->request->string('pay_type'); // balance/alipay/wxpay/qqpay

        // 余额支付
        if ($payType === 'balance') {
            $payAmount = (float)$order['pay_amount'];
            $user = User::find($userId);
            if ((float)$user['wallet_balance'] < $payAmount) {
                json_error('余额不足，请先充值');
            }
            // 创建支付单（标记为已支付）
            $paymentNo = order_no('P');
            db()->insert('payments', [
                'payment_no' => $paymentNo,
                'biz_type' => 'order',
                'biz_no' => $order['order_no'],
                'user_id' => $userId,
                'amount' => $payAmount,
                'method' => 'balance',
                'channel' => 'balance',
                'status' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            // 扣余额
            if (!User::walletChange($userId, -$payAmount, 'consume', '余额支付订单', 'order', $order['order_no'])) {
                json_error('余额扣款失败');
            }
            // 触发支付成功处理
            OrderService::handleOrderPaid($order['order_no'], ['type' => 'balance', 'trade_no' => $paymentNo]);
            json_success(['success' => true, 'pay_url' => ''], '支付成功');
            return;
        }

        $baseUrl = $this->getBaseUrl();

        // 创建支付单
        $paymentNo = order_no('P');
        db()->insert('payments', [
            'payment_no' => $paymentNo,
            'biz_type' => 'order',
            'biz_no' => $order['order_no'],
            'user_id' => $userId,
            'amount' => $order['pay_amount'],
            'method' => $payType ?: config('pay.epay_pay_type', 'wxpay'),
            'channel' => 'epay',
            'status' => 0,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $payUrl = PayService::createPay(
            $paymentNo,
            '农产品订单-' . $order['order_no'],
            (float)$order['pay_amount'],
            $baseUrl . '/api/pay/notify',
            $baseUrl . '/h5/#/order/detail?id=' . $order['id'] . '&paid=1',
            $payType
        );

        json_success([
            'payment_no' => $paymentNo,
            'pay_url' => $payUrl,
            'order_no' => $order['order_no'],
            'amount' => $order['pay_amount'],
        ], '支付单已创建');
    }

    /**
     * GET /api/pay/notify
     * 易支付异步通知（GET 参数）
     */
    public function payNotify(): void
    {
        $params = $_GET;
        if (empty($params['out_trade_no'])) {
            echo 'fail';
            exit;
        }

        // 验签
        if (!PayService::verifyNotify($params)) {
            echo 'sign error';
            exit;
        }

        $tradeStatus = $params['trade_status'] ?? '';
        if ($tradeStatus !== 'TRADE_SUCCESS') {
            echo 'success'; // 非成功状态直接应答
            exit;
        }

        try {
            PayService::handlePaySuccess($params['out_trade_no'], $params);
            echo 'success';
        } catch (\Throwable $e) {
            echo 'fail';
        }
        exit;
    }

    /** 获取当前站点基础URL */
    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}
