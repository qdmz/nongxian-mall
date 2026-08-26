<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\GroupBuy;

/**
 * 订单服务（核心业务逻辑）
 */
class OrderService
{
    /**
     * 创建普通订单
     * @param int $userId
     * @param array $items [['product_id'=>1,'sku_id'=>null,'quantity'=>2], ...]
     * @param array $address 收货地址（来自 user_addresses 或快照）
     * @param string $remark
     * @param float $useBalance 使用的钱包余额
     * @return array 订单
     */
    public static function createOrder(int $userId, array $items, array $address, string $remark = '', float $useBalance = 0.0): array
    {
        if (empty($items)) {
            json_error('商品不能为空');
        }

        db()->beginTransaction();
        try {
            $orderItems = [];
            $totalAmount = 0.0;
            $productCount = 0;

            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $skuId = !empty($item['sku_id']) ? (int)$item['sku_id'] : null;
                $quantity = max(1, (int)($item['quantity'] ?? 1));

                $product = db()->one('SELECT * FROM products WHERE id = ? AND status = 1', [$productId]);
                if (!$product) {
                    json_error('商品不存在或已下架');
                }

                // 确定价格
                $sku = null;
                if ($skuId) {
                    $sku = db()->one('SELECT * FROM product_skus WHERE id = ? AND product_id = ?', [$skuId, $productId]);
                    if (!$sku) {
                        json_error('商品规格不存在');
                    }
                    $price = (float)$sku['price'];
                } else {
                    $price = (float)$product['price'];
                }

                // 检查并扣库存（原子操作）
                if (!Product::reduceStock($productId, $skuId, $quantity)) {
                    json_error('商品「' . $product['name'] . '」库存不足');
                }

                $subtotal = round($price * $quantity, 2);
                $totalAmount += $subtotal;
                $productCount += $quantity;

                $orderItems[] = [
                    'product_id' => $productId,
                    'sku_id' => $skuId,
                    'name' => $product['name'],
                    'image' => $product['cover_image'],
                    'specs' => $sku ? $sku['specs'] : null,
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
            }

            $totalAmount = round($totalAmount, 2);

            // 钱包抵扣
            $user = User::find($userId);
            $balance = (float)$user['wallet_balance'];
            $useBalance = min($useBalance, $balance, $totalAmount);
            $useBalance = round(max(0, $useBalance), 2);
            $payAmount = round($totalAmount - $useBalance, 2);

            // 扣余额
            if ($useBalance > 0) {
                if (!User::walletChange($userId, -$useBalance, 'consume', '订单消费（余额抵扣）', 'order', '')) {
                    json_error('余额不足');
                }
            }

            $orderNo = order_no();
            $addressStr = $address['province'] . $address['city'] . $address['district'] . ' ' . $address['detail'];

            $orderId = Order::createOrder([
                'order_no' => $orderNo,
                'user_id' => $userId,
                'type' => 1,
                'status' => $payAmount > 0 ? Order::STATUS_PENDING : Order::STATUS_PAID,
                'total_amount' => $totalAmount,
                'pay_amount' => $payAmount,
                'discount_amount' => 0,
                'use_balance' => $useBalance,
                'product_count' => $productCount,
                'consignee' => $address['consignee'] ?? '',
                'phone' => $address['phone'] ?? '',
                'address' => $addressStr,
                'remark' => $remark ?: null,
                'pay_method' => $payAmount == 0 ? 'balance' : null,
                'paid_at' => $payAmount == 0 ? time() : 0,
            ], $orderItems);

            // 全额余额支付，直接进入已支付状态
            if ($payAmount == 0) {
                self::afterOrderPaid($orderId, 'balance');
            }

            db()->commit();
            return Order::detailWithItems($orderId);
        } catch (\Throwable $e) {
            db()->rollBack();
            throw $e;
        }
    }

    /**
     * 创建拼团订单
     */
    public static function createGroupBuyOrder(int $userId, int $activityId, ?int $groupBuyId, int $quantity, array $address, string $remark = ''): array
    {
        db()->beginTransaction();
        try {
            $activity = db()->one('SELECT * FROM group_buy_activities WHERE id = ? AND status = 1', [$activityId]);
            if (!$activity) {
                json_error('拼团活动不存在或已结束');
            }
            $now = time();
            if ($activity['start_time'] > 0 && $activity['start_time'] > $now) {
                json_error('拼团活动尚未开始');
            }
            if ($activity['end_time'] > 0 && $activity['end_time'] < $now) {
                json_error('拼团活动已结束');
            }
            if ($activity['max_count'] > 0 && $quantity > $activity['max_count']) {
                json_error('该商品限购 ' . $activity['max_count'] . ' 件');
            }

            $product = db()->one('SELECT * FROM products WHERE id = ? AND status = 1', [$activity['product_id']]);
            if (!$product) {
                json_error('商品不存在或已下架');
            }

            // 参团：校验团状态
            if ($groupBuyId) {
                $group = GroupBuy::find($groupBuyId);
                if (!$group || $group['status'] != GroupBuy::STATUS_PENDING) {
                    json_error('该拼团已结束');
                }
                if ($group['expire_at'] <= time()) {
                    json_error('该拼团已过期');
                }
                if ($group['joined_count'] >= $group['required_count']) {
                    json_error('该拼团已满员');
                }
            }

            // 扣库存
            if (!Product::reduceStock((int)$product['id'], null, $quantity)) {
                json_error('商品库存不足');
            }

            $price = (float)$activity['group_price'];
            $totalAmount = round($price * $quantity, 2);
            $orderNo = order_no();
            $addressStr = $address['province'] . $address['city'] . $address['district'] . ' ' . $address['detail'];

            $orderId = Order::createOrder([
                'order_no' => $orderNo,
                'user_id' => $userId,
                'type' => 2,
                'status' => Order::STATUS_PENDING,
                'total_amount' => $totalAmount,
                'pay_amount' => $totalAmount,
                'discount_amount' => round((float)$product['price'] * $quantity - $totalAmount, 2),
                'use_balance' => 0,
                'product_count' => $quantity,
                'consignee' => $address['consignee'] ?? '',
                'phone' => $address['phone'] ?? '',
                'address' => $addressStr,
                'remark' => $remark ?: null,
            ], [[
                'product_id' => $product['id'],
                'sku_id' => null,
                'name' => $product['name'],
                'image' => $product['cover_image'],
                'specs' => null,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $totalAmount,
            ]]);

            // 关联拼团：开团 or 参团
            if ($groupBuyId) {
                if (!GroupBuy::join($groupBuyId, $userId, $orderId, $orderNo)) {
                    json_error('参团失败，团已满或已结束');
                }
            } else {
                GroupBuy::start($activityId, $activity, $userId, $orderId, $orderNo);
            }

            db()->commit();
            return Order::detailWithItems($orderId);
        } catch (\Throwable $e) {
            db()->rollBack();
            throw $e;
        }
    }

    /**
     * 支付成功后处理（由 PayService 调用，在事务中）
     */
    public static function handleOrderPaid(string $orderNo, array $payData): void
    {
        $order = Order::findByNo($orderNo);
        if (!$order || $order['status'] != Order::STATUS_PENDING) {
            return; // 幂等
        }

        Order::updateById((int)$order['id'], [
            'status' => Order::STATUS_PAID,
            'pay_method' => $payData['type'] ?? 'epay',
            'pay_no' => $payData['trade_no'] ?? '',
            'paid_at' => time(),
        ]);

        db()->insert('order_logs', [
            'order_id' => $order['id'],
            'status' => Order::STATUS_PAID,
            'description' => '支付成功',
            'operator_type' => 0,
            'created_at' => time(),
        ]);

        self::afterOrderPaid((int)$order['id'], $payData['type'] ?? 'epay');
    }

    /**
     * 支付完成后的动作：通知（短信/邮件）
     */
    private static function afterOrderPaid(int $orderId, string $payMethod): void
    {
        $order = Order::find($orderId);
        if (!$order) return;

        // 站内通知
        db()->insert('notifications', [
            'user_id' => (int)$order['user_id'],
            'title' => '支付成功',
            'content' => '您的订单 ' . $order['order_no'] . ' 已支付成功，我们会尽快为您安排发货。',
            'type' => 'order',
            'created_at' => time(),
        ]);

        // 邮件通知
        $user = User::find((int)$order['user_id']);
        if ($user && $user['email']) {
            EmailService::send(
                $user['email'],
                '订单支付成功',
                "您好，您的订单 {$order['order_no']} 已支付成功，金额 {$order['pay_amount']} 元。我们会尽快为您安排发货。感谢您对助农事业的支持！"
            );
        }
        // 短信通知
        if ($user && $user['phone'] && SmsService::enabled()) {
            SmsService::send($user['phone'], "您的订单已支付成功，我们将尽快发货。", 'order_paid');
        }
    }

    /**
     * 确认收货（触发推荐奖励）
     */
    public static function confirmReceive(int $orderId, int $userId): bool
    {
        $order = Order::find($orderId);
        if (!$order || (int)$order['user_id'] !== $userId) {
            return false;
        }
        if ($order['status'] != Order::STATUS_DELIVERED) {
            json_error('当前状态不可确认收货');
        }

        Order::updateById($orderId, [
            'status' => Order::STATUS_COMPLETED,
            'completed_at' => time(),
        ]);
        db()->insert('order_logs', [
            'order_id' => $orderId,
            'status' => Order::STATUS_COMPLETED,
            'description' => '用户确认收货',
            'operator_type' => 1,
            'operator_id' => $userId,
            'created_at' => time(),
        ]);

        // 拼团订单：标记成团（如果还没成团）
        if ($order['type'] == 2) {
            $member = db()->one('SELECT * FROM group_buy_members WHERE order_no = ?', [$order['order_no']]);
            if ($member) {
                GroupBuy::markSuccess((int)$member['group_buy_id']);
            }
        }

        // 发放推荐奖励
        \App\Models\Referral::grantReward($orderId);

        return true;
    }

    /**
     * 订单退款（管理员同意 / 拼团失败自动退）
     */
    public static function refundByOrderNo(string $orderNo, string $reason): bool
    {
        $order = Order::findByNo($orderNo);
        if (!$order) return false;
        return self::refund((int)$order['id'], $reason);
    }

    /**
     * 退款处理：原路退回第三方支付（易支付通常无退款API，退到钱包余额）+ 余额抵扣部分退回
     */
    public static function refund(int $orderId, string $reason): bool
    {
        $order = Order::find($orderId);
        if (!$order || !in_array($order['status'], [Order::STATUS_PAID, Order::STATUS_DELIVERED, Order::STATUS_REFUNDING])) {
            return false;
        }

        db()->beginTransaction();
        try {
            Order::updateById($orderId, [
                'status' => Order::STATUS_REFUNDED,
            ]);

            // 退款金额 = 实付金额 + 余额抵扣，全部退到钱包
            $refundAmount = round((float)$order['pay_amount'] + (float)$order['use_balance'], 2);
            if ($refundAmount > 0) {
                User::walletChange((int)$order['user_id'], $refundAmount, 'refund', '订单退款 - ' . $reason, 'order', $order['order_no']);
            }

            // 归还库存
            $items = db()->all('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
            foreach ($items as $item) {
                Product::restoreStock((int)$item['product_id'], $item['sku_id'] ? (int)$item['sku_id'] : null, (int)$item['quantity']);
            }

            db()->insert('order_logs', [
                'order_id' => $orderId,
                'status' => Order::STATUS_REFUNDED,
                'description' => '退款成功：' . $reason,
                'operator_type' => 0,
                'created_at' => time(),
            ]);

            db()->commit();

            // 通知
            db()->insert('notifications', [
                'user_id' => (int)$order['user_id'],
                'title' => '退款成功',
                'content' => "您的订单 {$order['order_no']} 已退款 {$refundAmount} 元至钱包余额。原因：{$reason}",
                'type' => 'order',
                'created_at' => time(),
            ]);
            return true;
        } catch (\Throwable $e) {
            db()->rollBack();
            return false;
        }
    }

    /**
     * 定时任务：取消超时未支付订单
     */
    public static function cancelExpiredOrders(): int
    {
        $minutes = (int)config('app.order_auto_cancel_minutes', '30');
        $deadline = time() - $minutes * 60;
        $expired = db()->all('SELECT id FROM orders WHERE status = 0 AND created_at < ? LIMIT 100', [$deadline]);
        $count = 0;
        foreach ($expired as $order) {
            if (Order::cancel((int)$order['id'], '超时未支付自动取消')) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 定时任务：自动确认收货
     */
    public static function autoConfirmReceive(): int
    {
        $days = (int)config('app.order_auto_confirm_days', '7');
        $deadline = time() - $days * 86400;
        $orders = db()->all('SELECT id FROM orders WHERE status = 2 AND delivered_at < ? AND delivered_at > 0 LIMIT 100', [$deadline]);
        $count = 0;
        foreach ($orders as $order) {
            $orderFull = Order::find((int)$order['id']);
            if ($orderFull && self::confirmReceive((int)$order['id'], (int)$orderFull['user_id'])) {
                $count++;
            }
        }
        return $count;
    }
}
