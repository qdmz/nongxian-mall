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
        $sign = $params['sign'] ?? '';
        unset($params['sign'], $params['sign_type']);
        return $sign === self::sign($params);
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

        db()->beginTransaction();
        try {
            // 标记支付单成功
            $paymentModel->updateById((int)$payment['id'], [
                'status' => 1,
                'trade_no' => $notifyData['trade_no'] ?? '',
                'callback_data' => json_encode($notifyData, JSON_UNESCAPED_UNICODE),
                'paid_at' => time(),
            ]);

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
        $rechargeModel->updateById((int)$recharge['id'], [
            'status' => 1,
            'paid_at' => time(),
        ]);
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
}
