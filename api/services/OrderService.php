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
        if (count($items) > 50) {
            json_error('单次最多下单 50 种商品');
        }

        db()->beginTransaction();
        try {
            $orderItems = [];
            $totalAmount = 0.0;
            $productCount = 0;

            foreach ($items as $item) {
                if (!is_array($item)) {
                    json_error('参数错误');
                }
                $productId = (int)($item['product_id'] ?? 0);
                $skuId = !empty($item['sku_id']) ? (int)$item['sku_id'] : null;
                $quantity = max(1, (int)($item['quantity'] ?? 1));
                if ($quantity > 9999) {
                    json_error('单个商品购买数量过大');
                }

                $product = db()->one('SELECT * FROM products WHERE id = ? AND status = 1', [$productId]);
                if (!$product) {
                    json_error('商品不存在或已下架');
                }

                // 确定价格
                $sku = null;
                if ($skuId) {
                    $sku = db()->one('SELECT * FROM product_skus WHERE id = ? AND product_id = ? AND status = 1', [$skuId, $productId]);
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

            // 2026-10-14b：运费按运费模板由服务端计算
            $freight = FreightService::calculate($address, $totalAmount, $productCount);
            $freightAmount = (float)$freight['fee'];
            $grandTotal = round($totalAmount + $freightAmount, 2);

            // 钱包抵扣（可抵扣商品金额 + 运费）
            $user = User::find($userId);
            $balance = (float)$user['wallet_balance'];
            $useBalance = min($useBalance, $balance, $grandTotal);
            $useBalance = round(max(0, $useBalance), 2);
            $payAmount = round($grandTotal - $useBalance, 2);

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
                'freight_amount' => $freightAmount,
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
                if ((int)$group['activity_id'] !== $activityId) {
                    json_error('拼团与活动不匹配');
                }
                // 2026-10-14：同一用户不能重复参加同一个团（含团长自己）
                $joined = db()->value('SELECT COUNT(*) FROM group_buy_members WHERE group_buy_id = ? AND user_id = ? AND status = 1', [$groupBuyId, $userId]);
                if ((int)$joined > 0) {
                    json_error('您已在该团中，邀请好友参团吧');
                }
                // 2026-10-14b：已有该团的待支付订单，不重复下单
                $pendingSame = db()->value('SELECT COUNT(*) FROM orders WHERE user_id = ? AND type = 2 AND status = 0 AND group_target_id = ?', [$userId, $groupBuyId]);
                if ((int)$pendingSame > 0) {
                    json_error('您已有该团的待支付订单，请先完成支付');
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
                'group_activity_id' => $activityId,
                'group_target_id' => (int)($groupBuyId ?: 0),
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

            // 2026-10-14b：拼团改为"付款成功后才入团/占名额"（见 afterGroupOrderPaid），
            // 下单时只记录意向（group_activity_id / group_target_id），未付款不占名额，超时取消也无需释放。

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
        if (!$order) {
            return;
        }
        // 2026-10-14：原子状态流转（仅 待支付 -> 已支付），防并发重复处理
        $affected = db()->query(
            'UPDATE orders SET status = ?, pay_method = ?, pay_no = ?, paid_at = ?, updated_at = ? WHERE id = ? AND status = ?',
            [Order::STATUS_PAID, (string)($payData['type'] ?? 'epay'), (string)($payData['trade_no'] ?? ''), time(), time(), (int)$order['id'], Order::STATUS_PENDING]
        )->rowCount();
        if ($affected === 0) {
            // 订单已不是待支付（如超时已取消）却收到第三方支付成功：把钱退到用户钱包，避免用户付款后"钱货两空"
            // （本支付单已在 PayService 中原子标记成功，走到这里说明是"订单已取消/已被其他支付单支付"后的重复/迟到支付）
            if (isset($payData['_amount']) && (float)$payData['_amount'] > 0) {
                // 2026-10-14b：易支付付款优先原路退回（cron 在 1 分钟内发起），否则退回钱包
                $dupPayment = !empty($payData['_payment_no']) ? db()->one('SELECT * FROM payments WHERE payment_no = ?', [$payData['_payment_no']]) : null;
                $viaEpay = $dupPayment && RefundService::createForDuplicatePayment($order, $dupPayment, '订单已取消或已支付后到账的重复支付');
                if (!$viaEpay) {
                    User::walletChange((int)$order['user_id'], (float)$payData['_amount'], 'refund', '订单已取消或已支付后到账的重复支付，自动退回余额', 'order', $order['order_no']);
                }
                db()->insert('order_logs', [
                    'order_id' => $order['id'],
                    'status' => (int)$order['status'],
                    'description' => '订单非待支付状态下收到支付（' . ($payData['trade_no'] ?? '') . '），金额 ' . $payData['_amount'] . ' 元' . ($viaEpay ? '将原路退回' : '已退回用户钱包'),
                    'operator_type' => 0,
                    'created_at' => time(),
                ]);
                db()->insert('notifications', [
                    'user_id' => (int)$order['user_id'],
                    'title' => '支付已退回余额',
                    'content' => '您的订单 ' . $order['order_no'] . ' 已取消或已支付，本次重复支付的 ' . $payData['_amount'] . ' 元' . ($viaEpay ? '将原路退回您的支付账户。' : '已退回钱包余额。'),
                    'type' => 'order',
                    'created_at' => time(),
                ]);
            }
            return; // 幂等
        }

        db()->insert('order_logs', [
            'order_id' => $order['id'],
            'status' => Order::STATUS_PAID,
            'description' => '支付成功',
            'operator_type' => 0,
            'created_at' => time(),
        ]);

        // 2026-10-14b：拼团订单付款后才入团
        if ((int)$order['type'] === 2) {
            self::afterGroupOrderPaid((int)$order['id']);
        }

        self::afterOrderPaid((int)$order['id'], $payData['type'] ?? 'epay');
    }

    /**
     * 拼团订单付款成功后入团（在支付事务中调用）
     *  - 开团订单：创建团，团长计 1 人；
     *  - 参团订单：原子 +1 占名额；若此时团已满/已结束/已过期/已在团中 → 自动为其开一个新团（保持拼团价，不额外收费）；
     *  - 达到成团人数立即成团。
     */
    public static function afterGroupOrderPaid(int $orderId): void
    {
        $order = Order::find($orderId);
        if (!$order || (int)$order['type'] !== 2) return;
        // 已入团（幂等 / 兼容旧数据：下单即入团的老订单）
        $member = db()->one('SELECT id FROM group_buy_members WHERE order_id = ? AND status = 1', [$orderId]);
        if ($member) return;

        $activityId = (int)$order['group_activity_id'];
        $activity = $activityId ? db()->one('SELECT * FROM group_buy_activities WHERE id = ?', [$activityId]) : null;
        if (!$activity) {
            error_log('[nongxian-mall] group order paid but activity missing, order#' . $orderId);
            return;
        }
        $userId = (int)$order['user_id'];
        $targetId = (int)$order['group_target_id'];
        $groupId = 0;
        if ($targetId > 0) {
            $already = db()->value('SELECT COUNT(*) FROM group_buy_members WHERE group_buy_id = ? AND user_id = ? AND status = 1', [$targetId, $userId]);
            $group = GroupBuy::find($targetId);
            if (!$already && $group && (int)$group['activity_id'] === $activityId && GroupBuy::join($targetId, $userId, $orderId, $order['order_no'])) {
                $groupId = $targetId;
            }
        }
        if ($groupId === 0) {
            $groupId = GroupBuy::start($activityId, $activity, $userId, $orderId, $order['order_no']);
            if ($targetId > 0) {
                db()->insert('order_logs', [
                    'order_id' => $orderId,
                    'status' => Order::STATUS_PAID,
                    'description' => '原拼团已满员或已结束，已自动为您开新团',
                    'operator_type' => 0,
                    'created_at' => time(),
                ]);
                db()->insert('notifications', [
                    'user_id' => $userId,
                    'title' => '已为您自动开团',
                    'content' => '您要参加的拼团在您付款时已满员或已结束，系统已按拼团价为您自动开了新团（订单 ' . $order['order_no'] . '），邀请好友参团吧。',
                    'type' => 'order',
                    'created_at' => time(),
                ]);
            }
        }
        db()->query('UPDATE orders SET group_target_id = ? WHERE id = ?', [$groupId, $orderId]);
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
            if (defined('CRON_MODE')) return false;
            json_error('当前状态不可确认收货');
        }

        // 2026-10-14：原子状态流转，防重复确认导致重复发放推荐奖励
        $affected = db()->query(
            'UPDATE orders SET status = ?, completed_at = ?, updated_at = ? WHERE id = ? AND status = ?',
            [Order::STATUS_COMPLETED, time(), time(), $orderId, Order::STATUS_DELIVERED]
        )->rowCount();
        if ($affected === 0) {
            return false;
        }
        db()->insert('order_logs', [
            'order_id' => $orderId,
            'status' => Order::STATUS_COMPLETED,
            'description' => '用户确认收货',
            'operator_type' => 1,
            'operator_id' => $userId,
            'created_at' => time(),
        ]);

        // 2026-10-14b：去掉"确认收货即强制成团"（会把人数不足的团标成成团）；成团只看已付款人数，发货前已校验成团

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
     * 退款处理（2026-10-14b 重写）
     *  - 易支付付款部分优先原路退回（RefundService，失败转钱包并记日志），余额部分退回钱包；
     *  - 归还库存；已发放的推荐奖励扣回；未成团的拼团释放名额。
     */
    public static function refund(int $orderId, string $reason): bool
    {
        $order = Order::find($orderId);
        if (!$order || !in_array((int)$order['status'], [Order::STATUS_PAID, Order::STATUS_DELIVERED, Order::STATUS_REFUNDING, Order::STATUS_COMPLETED], true)) {
            return false;
        }

        $db = db();
        $ownTx = !$db->pdo()->inTransaction();
        if ($ownTx) $db->beginTransaction();
        try {
            // 原子状态流转，防重复退款（管理员重复点击 / 并发）
            $affected = $db->query(
                'UPDATE orders SET status = ?, updated_at = ? WHERE id = ? AND status IN (?, ?, ?, ?)',
                [Order::STATUS_REFUNDED, time(), $orderId, Order::STATUS_PAID, Order::STATUS_DELIVERED, Order::STATUS_REFUNDING, Order::STATUS_COMPLETED]
            )->rowCount();
            if ($affected === 0) {
                if ($ownTx) $db->rollBack();
                return false;
            }

            $refundAmount = round((float)$order['pay_amount'] + (float)$order['use_balance'], 2);
            $split = ['epay' => 0.0, 'wallet' => 0.0, 'epay_record_ids' => []];
            if ($refundAmount > 0) {
                $split = RefundService::createForOrder($order, $refundAmount, $reason);
            }

            // 归还库存
            $items = $db->all('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
            foreach ($items as $item) {
                Product::restoreStock((int)$item['product_id'], $item['sku_id'] ? (int)$item['sku_id'] : null, (int)$item['quantity']);
            }

            // 拼团订单：团还在进行中 → 释放名额
            if ((int)$order['type'] === 2) {
                $member = $db->one('SELECT * FROM group_buy_members WHERE order_id = ? AND status = 1', [$orderId]);
                if ($member) {
                    $released = $db->query(
                        'UPDATE group_buys SET joined_count = GREATEST(joined_count - 1, 0), updated_at = ? WHERE id = ? AND status = 0',
                        [time(), $member['group_buy_id']]
                    )->rowCount();
                    if ($released) {
                        $db->query('UPDATE group_buy_members SET status = 0 WHERE id = ?', [$member['id']]);
                        $db->query('UPDATE group_buys SET status = 3, updated_at = ? WHERE id = ? AND status = 0 AND joined_count = 0', [time(), $member['group_buy_id']]);
                    }
                }
            }

            // 推荐奖励扣回
            \App\Models\Referral::clawback($orderId, '订单退款');

            // 同一订单尚未处理的退款申请一并关闭（如拼团失败自动退款时用户已申请过退款）
            $db->query('UPDATE refunds SET status = 3, updated_at = ? WHERE order_id = ? AND status = 0', [time(), $orderId]);

            $desc = '退款成功：' . $reason . '（共 ' . sprintf('%.2f', $refundAmount) . ' 元';
            if ($split['epay'] > 0) $desc .= '，原路退回 ' . sprintf('%.2f', $split['epay']) . ' 元';
            if ($split['wallet'] > 0) $desc .= '，退回钱包 ' . sprintf('%.2f', $split['wallet']) . ' 元';
            $desc .= '）';
            $db->insert('order_logs', [
                'order_id' => $orderId,
                'status' => Order::STATUS_REFUNDED,
                'description' => $desc,
                'operator_type' => 0,
                'created_at' => time(),
            ]);

            if ($ownTx) $db->commit();
        } catch (\Throwable $e) {
            if ($ownTx) $db->rollBack();
            error_log('[nongxian-mall] refund error order#' . $orderId . ': ' . $e->getMessage());
            if (!$ownTx) throw $e;
            return false;
        }

        // 通知
        $parts = [];
        if ($split['epay'] > 0) $parts[] = sprintf('%.2f', $split['epay']) . ' 元原路退回支付账户';
        if ($split['wallet'] > 0) $parts[] = sprintf('%.2f', $split['wallet']) . ' 元退至钱包余额';
        try {
            db()->insert('notifications', [
                'user_id' => (int)$order['user_id'],
                'title' => '退款成功',
                'content' => "您的订单 {$order['order_no']} 已退款 {$refundAmount} 元" . ($parts ? '（' . implode('，', $parts) . '）' : '') . "。原因：{$reason}",
                'type' => 'order',
                'created_at' => time(),
            ]);
        } catch (\Throwable $e) {
        }

        // 事务外立即发起原路退款（失败/不确定由 cron 继续处理）
        if ($ownTx) {
            foreach ($split['epay_record_ids'] as $rid) {
                try {
                    RefundService::process((int)$rid);
                } catch (\Throwable $e) {
                    error_log('[nongxian-mall] epay refund process error record#' . $rid . ': ' . $e->getMessage());
                }
            }
        }
        return true;
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
