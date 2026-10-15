<?php

namespace App\Services;

/**
 * 易支付服务
 * 对接标准易支付协议（彩虹易支付等兼容接口）
 * 商户号/密钥在管理后台「系统设置-支付配置」中填写
 *
 * 协议文档：提交方式 GET/POST 到 {api_url}/submit.php，MD5 签名
 * 异步通知：notify_url 接收 GET 参数，签名验证
 * 同步跳转：return_url
 */
class PayService
{
    /** 是否已启用 */
    public static function enabled(): bool
    {
        $enabled = config('pay.pay_enabled', '0');
        $url = config('pay.epay_api_url', '');
        $pid = config('pay.epay_pid', '');
        $key = config('pay.epay_key', '');
        return $enabled === '1' && $url && $pid && $key;
    }

    /**
     * 创建支付，返回支付跳转 URL
     * @param string $paymentNo 本地支付单号
     * @param string $name 商品名
     * @param float $amount 金额
     * @param string $notifyUrl 异步通知地址
     * @param string $returnUrl 支付后跳转地址
     * @param string $payType alipay/wxpay/qqpay
     */
    public static function createPay(string $paymentNo, string $name, float $amount, string $notifyUrl, string $returnUrl, string $payType = ''): string
    {
        if (!self::enabled()) {
            json_error('在线支付未配置，请联系管理员在后台完成支付配置');
        }

        $apiUrl = rtrim(config('pay.epay_api_url', ''), '/');
        $type = $payType ?: config('pay.epay_pay_type', 'wxpay');

        $data = [
            'pid' => config('pay.epay_pid', ''),
            'type' => $type,
            'out_trade_no' => $paymentNo,
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'name' => $name,
            'money' => sprintf('%.2f', $amount),
            'sitename' => config('app.app_name', '田冲助农商城'),
        ];
        $data['sign'] = self::sign($data);
        $data['sign_type'] = 'MD5';

        return $apiUrl . '/submit.php?' . http_build_query($data);
    }

    /**
     * 验证异步通知签名
     * 易支付 notify：GET 参数含 pid、trade_no、out_trade_no、type、name、money、trade_status=TRADE_SUCCESS、sign
     */
    public static function verifyNotify(array $params): bool
    {
        // 2026-10-14：密钥为空时 md5 签名可被任意伪造，直接拒绝；商户号必须一致；常量时间比较
        $key = (string)config('pay.epay_key', '');
        if ($key === '') return false;
        $sign = (string)($params['sign'] ?? '');
        if ($sign === '') return false;
        $pid = (string)config('pay.epay_pid', '');
        if (isset($params['pid']) && $pid !== '' && (string)$params['pid'] !== $pid) return false;
        unset($params['sign'], $params['sign_type']);
        return hash_equals(self::sign($params), strtolower($sign));
    }

    /** MD5 签名（按 key ASCII 升序，过滤空值，拼 url 参数 + key） */
    private static function sign(array $data): string
    {
        // 过滤空值和 sign
        $data = array_filter($data, fn($v) => $v !== '' && $v !== null);
        ksort($data);
        $str = urldecode(http_build_query($data));
        return md5($str . config('pay.epay_key', ''));
    }

    /**
     * 处理支付成功回调（订单 或 充值）
     * 由 PaymentCallback 控制器调用，幂等
     */
    public static function handlePaySuccess(string $paymentNo, array $notifyData): void
    {
        $paymentModel = new \App\Models\Payment();
        $payment = $paymentModel->findByPaymentNo($paymentNo);
        if (!$payment) {
            return; // 不存在的支付单，忽略
        }
        // 幂等：已成功直接返回
        if ((int)$payment['status'] === 1) {
            return;
        }
        // 2026-10-14：校验回调金额与支付单金额一致（防篡改/错单）
        if (isset($notifyData['money']) && abs((float)$notifyData['money'] - (float)$payment['amount']) > 0.001) {
            error_log('[nongxian-mall] pay notify amount mismatch: ' . $paymentNo . ' notify=' . $notifyData['money'] . ' local=' . $payment['amount']);
            throw new \RuntimeException('支付金额不一致');
        }

        db()->beginTransaction();
        try {
            // 标记支付单成功（原子条件更新，防并发重复回调导致重复入账）
            $affected = db()->query(
                'UPDATE payments SET status = 1, trade_no = ?, callback_data = ?, paid_at = ?, updated_at = ? WHERE id = ? AND status = 0',
                [
                    (string)($notifyData['trade_no'] ?? ''),
                    json_encode($notifyData, JSON_UNESCAPED_UNICODE),
                    time(), time(), (int)$payment['id'],
                ]
            )->rowCount();
            if ($affected === 0) {
                db()->rollBack();
                return; // 已被其他请求处理
            }
            $notifyData['type'] = $notifyData['type'] ?? $payment['method'];
            $notifyData['_amount'] = (float)$payment['amount'];
            $notifyData['_user_id'] = (int)$payment['user_id'];
            $notifyData['_payment_no'] = (string)$payment['payment_no'];

            if ($payment['biz_type'] === 'order') {
                OrderService::handleOrderPaid($payment['biz_no'], $notifyData);
            } elseif ($payment['biz_type'] === 'recharge') {
                self::handleRechargePaid($payment['biz_no']);
            }

            db()->commit();
        } catch (\Throwable $e) {
            db()->rollBack();
            throw $e;
        }
    }

    /** 充值到账 */
    private static function handleRechargePaid(string $rechargeNo): void
    {
        $rechargeModel = new \App\Models\RechargeOrder();
        $recharge = $rechargeModel->findByNo($rechargeNo);
        if (!$recharge || (int)$recharge['status'] !== 0) {
            return;
        }
        // 2026-10-14：原子条件更新，防重复到账
        $affected = db()->query(
            'UPDATE recharge_orders SET status = 1, paid_at = ?, updated_at = ? WHERE id = ? AND status = 0',
            [time(), time(), (int)$recharge['id']]
        )->rowCount();
        if ($affected === 0) {
            return;
        }
        // 充值 + 赠送到钱包
        $total = round((float)$recharge['amount'] + (float)$recharge['give_amount'], 2);
        \App\Models\User::walletChange(
            (int)$recharge['user_id'],
            $total,
            'recharge',
            '钱包充值 ' . sprintf('%.2f', $total) . ' 元',
            'recharge',
            $rechargeNo
        );
        // 站内通知
        db()->insert('notifications', [
            'user_id' => (int)$recharge['user_id'],
            'title' => '充值成功',
            'content' => '您的充值 ' . sprintf('%.2f', $total) . ' 元已到账',
            'type' => 'system',
            'created_at' => time(),
        ]);
        // 邮件通知（如果配置了）
        $user = \App\Models\User::find((int)$recharge['user_id']);
        if ($user && $user['email']) {
            EmailService::send($user['email'], '充值成功通知', "您好，您的充值 {$total} 元已到账。感谢您对助农事业的支持！");
        }
    }
    // ===================== 2026-10-14b：易支付商户 API（退款 / 查单 / 订单列表） =====================

    /** 商户 API 调用（彩虹易支付兼容 /api.php?act=xxx），返回 [ok(bool), data(array|null), err(string)] */
    private static function merchantApi(string $act, array $params, bool $post = false): array
    {
        $apiUrl = rtrim((string)config('pay.epay_api_url', ''), '/');
        $pid = (string)config('pay.epay_pid', '');
        $key = (string)config('pay.epay_key', '');
        if ($apiUrl === '' || $pid === '' || $key === '') {
            return [false, null, '易支付未配置'];
        }
        $params = array_merge(['pid' => $pid, 'key' => $key], $params);
        $url = $apiUrl . '/api.php?act=' . rawurlencode($act);
        $ch = curl_init();
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'nongxian-mall/1.0',
        ];
        if ($post) {
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($params);
        } else {
            $opts[CURLOPT_URL] = $url . '&' . http_build_query($params);
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno || $body === false) {
            return [false, null, 'network:' . $errno];
        }
        $data = json_decode((string)$body, true);
        if (!is_array($data)) {
            return [false, null, 'bad_response:http' . $http . ':' . mb_substr(strip_tags((string)$body), 0, 120)];
        }
        return [true, $data, ''];
    }

    /**
     * 易支付退款：返回 ['result' => 'success'|'fail'|'unknown', 'msg' => string]
     * success=平台明确退款成功；fail=平台明确拒绝（可安全转退钱包）；unknown=网络/响应异常（不能确定，需稍后查单）
     */
    public static function epayRefund(string $outTradeNo, ?string $tradeNo, float $amount): array
    {
        $params = ['money' => sprintf('%.2f', $amount)];
        if ($tradeNo) {
            $params['trade_no'] = $tradeNo;
        } else {
            $params['out_trade_no'] = $outTradeNo;
        }
        [$ok, $data, $err] = self::merchantApi('refund', $params, true);
        if (!$ok) {
            return ['result' => 'unknown', 'msg' => $err];
        }
        $code = (int)($data['code'] ?? -99);
        $msg = (string)($data['msg'] ?? '');
        if ($code === 1) {
            return ['result' => 'success', 'msg' => $msg ?: '退款成功'];
        }
        return ['result' => 'fail', 'msg' => 'code=' . $code . ' ' . $msg];
    }

    /** 查询单笔订单：返回平台订单数组或 null（不存在/失败） */
    public static function epayQueryOrder(string $outTradeNo): ?array
    {
        [$ok, $data] = self::merchantApi('order', ['out_trade_no' => $outTradeNo]);
        if (!$ok || (int)($data['code'] ?? 0) !== 1) {
            return null;
        }
        return $data;
    }

    /** 订单列表（分页），返回 [ok, list] */
    public static function epayListOrders(int $page = 1, int $limit = 50): array
    {
        [$ok, $data, $err] = self::merchantApi('orders', ['limit' => $limit, 'page' => $page]);
        if (!$ok || (int)($data['code'] ?? 0) !== 1) {
            return [false, [], $err ?: (string)($data['msg'] ?? '')];
        }
        return [true, is_array($data['data'] ?? null) ? $data['data'] : [], ''];
    }
}
