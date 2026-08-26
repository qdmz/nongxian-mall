<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\RechargeOrder;
use App\Services\PayService;

/**
 * 用户端 - 用户中心
 */
class UserController extends Controller
{
    /**
     * GET /api/user/profile
     */
    public function profile(): void
    {
        $userId = Auth::userId();
        $user = User::find($userId);
        json_success(User::safe($user));
    }

    /**
     * PUT /api/user/profile
     * 更新资料
     */
    public function updateProfile(): void
    {
        $userId = Auth::userId();
        $data = $this->request->only(['nickname', 'avatar', 'gender', 'real_name', 'birthday']);
        if (empty($data)) {
            json_error('没有需要更新的内容');
        }
        if (isset($data['nickname']) && mb_strlen($data['nickname']) > 20) {
            json_error('昵称最多20个字符');
        }
        $data['updated_at'] = time();
        db()->update('users', $data, ['id' => $userId]);
        json_success(User::safe(User::find($userId)), '更新成功');
    }

    /**
     * POST /api/user/change-password
     */
    public function changePassword(): void
    {
        $userId = Auth::userId();
        $user = User::find($userId);
        $oldPassword = $this->request->string('old_password');
        $newPassword = $this->request->string('new_password');
        $this->validate(['new_password' => 'required|min:6|label:新密码']);

        if (!empty($user['password'])) {
            if (!password_verify($oldPassword, $user['password'])) {
                json_error('原密码错误');
            }
        }
        db()->update('users', [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => time(),
        ], ['id' => $userId]);
        json_success(null, '密码已修改');
    }

    /**
     * GET /api/user/addresses
     */
    public function addresses(): void
    {
        $userId = Auth::userId();
        $list = UserAddress::select(['user_id' => $userId], '*', 'is_default DESC, id DESC');
        json_success($list);
    }

    /**
     * POST /api/user/addresses
     */
    public function storeAddress(): void
    {
        $userId = Auth::userId();
        $data = $this->validate([
            'consignee' => 'required|max:20|label:收货人',
            'phone' => 'required|phone|label:手机号',
            'province' => 'required|max:30|label:省份',
            'city' => 'required|max:30|label:城市',
            'district' => 'required|max:30|label:区县',
            'detail' => 'required|max:200|label:详细地址',
        ]);
        $data['user_id'] = $userId;
        $data['is_default'] = $this->request->int('is_default') ? 1 : 0;

        $id = UserAddress::create($data);
        if ($data['is_default']) {
            UserAddress::setDefault((int)$userId, $id);
        }
        json_success(UserAddress::find($id), '地址已添加');
    }

    /**
     * PUT /api/user/addresses/{id}
     */
    public function updateAddress(array $params): void
    {
        $userId = Auth::userId();
        $id = (int)$params['id'];
        $address = UserAddress::where(['id' => $id, 'user_id' => $userId]);
        if (!$address) {
            json_error('地址不存在');
        }
        $data = $this->request->only(['consignee', 'phone', 'province', 'city', 'district', 'detail', 'is_default']);
        if (empty($data)) {
            json_error('没有需要更新的内容');
        }
        if (isset($data['is_default'])) {
            $data['is_default'] = $data['is_default'] ? 1 : 0;
        }
        $data['updated_at'] = time();
        db()->update('user_addresses', $data, ['id' => $id, 'user_id' => $userId]);
        if (!empty($data['is_default'])) {
            UserAddress::setDefault((int)$userId, $id);
        }
        json_success(UserAddress::find($id), '地址已更新');
    }

    /**
     * DELETE /api/user/addresses/{id}
     */
    public function destroyAddress(array $params): void
    {
        $userId = Auth::userId();
        $deleted = db()->delete('user_addresses', ['id' => (int)$params['id'], 'user_id' => $userId]);
        if (!$deleted) json_error('地址不存在');
        json_success(null, '地址已删除');
    }

    /**
     * GET /api/user/wallet
     * 钱包信息 + 流水
     */
    public function wallet(): void
    {
        $userId = Auth::userId();
        $user = User::find($userId);
        [$page, $pageSize] = $this->pageParams(20);
        $offset = ($page - 1) * $pageSize;

        $transactions = db()->all('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?', [$userId, $pageSize, $offset]);
        $total = (int)db()->value('SELECT COUNT(*) FROM wallet_transactions WHERE user_id = ?', [$userId]);

        json_success([
            'balance' => (float)$user['wallet_balance'],
            'total_recharge' => (float)$user['total_recharge'],
            'transactions' => ['list' => $transactions, 'total' => $total, 'page' => $page, 'page_size' => $pageSize],
        ]);
    }

    /**
     * POST /api/user/recharge
     * 充值（易支付）
     */
    public function recharge(): void
    {
        $userId = Auth::userId();
        $amount = $this->request->float('amount');
        if ($amount < 0.01 || $amount > 100000) {
            json_error('充值金额需在 0.01 - 100000 元之间');
        }
        // 赠送规则可后续扩展
        $giveAmount = 0.0;

        $rechargeNo = order_no('C');
        db()->insert('recharge_orders', [
            'recharge_no' => $rechargeNo,
            'user_id' => $userId,
            'amount' => $amount,
            'give_amount' => $giveAmount,
            'status' => 0,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        // 创建支付单
        $paymentNo = order_no('P');
        db()->insert('payments', [
            'payment_no' => $paymentNo,
            'biz_type' => 'recharge',
            'biz_no' => $rechargeNo,
            'user_id' => $userId,
            'amount' => $amount,
            'method' => config('pay.epay_pay_type', 'wxpay'),
            'channel' => 'epay',
            'status' => 0,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $payUrl = PayService::createPay(
            $paymentNo,
            '钱包充值',
            $amount,
            $baseUrl . '/api/pay/notify',
            $baseUrl . '/h5/#/user/wallet?recharged=1',
            $this->request->string('pay_type')
        );

        json_success([
            'recharge_no' => $rechargeNo,
            'payment_no' => $paymentNo,
            'pay_url' => $payUrl,
            'amount' => $amount,
        ], '充值单已创建');
    }

    /**
     * GET /api/user/recharge-orders
     */
    public function rechargeOrders(): void
    {
        $userId = Auth::userId();
        [$page, $pageSize] = $this->pageParams(20);
        $result = (new RechargeOrder())->paginate(['user_id' => $userId], '*', 'id DESC', $page, $pageSize);
        json_success($result);
    }

    /**
     * GET /api/user/notifications
     * 站内消息
     */
    public function notifications(): void
    {
        $userId = Auth::userId();
        [$page, $pageSize] = $this->pageParams(20);
        $result = db()->query(
            'SELECT * FROM notifications WHERE user_id = ? OR user_id = 0 ORDER BY id DESC LIMIT ? OFFSET ?',
            [$userId, $pageSize, ($page - 1) * $pageSize]
        )->fetchAll();
        $total = (int)db()->value('SELECT COUNT(*) FROM notifications WHERE user_id = ? OR user_id = 0', [$userId]);
        json_success(['list' => $result, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * POST /api/user/notifications/read
     */
    public function readNotifications(): void
    {
        $userId = Auth::userId();
        db()->query('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$userId]);
        json_success(null, '已全部标记为已读');
    }

    /**
     * GET /api/user/bind-info
     * 绑定信息（手机/邮箱状态）
     */
    public function bindInfo(): void
    {
        $userId = Auth::userId();
        $user = User::find($userId);
        json_success([
            'phone' => $user['phone'],
            'phone_verified' => (int)$user['phone_verified'],
            'email' => $user['email'],
            'email_verified' => (int)$user['email_verified'],
        ]);
    }

    /**
     * POST /api/user/bind-phone
     * 绑定手机号
     */
    public function bindPhone(): void
    {
        $userId = Auth::userId();
        $phone = $this->request->string('phone');
        $code = $this->request->string('code');
        $this->validate(['phone' => 'required|phone|label:手机号']);

        if (!\App\Models\VerifyCode::check($phone, 'sms', 'verify', $code)) {
            json_error('验证码错误或已过期');
        }
        $exists = User::findByPhone($phone);
        if ($exists && (int)$exists['id'] !== (int)$userId) {
            json_error('该手机号已被其他账号绑定');
        }
        db()->update('users', ['phone' => $phone, 'phone_verified' => 1, 'updated_at' => time()], ['id' => $userId]);
        json_success(null, '手机号绑定成功');
    }

    /**
     * POST /api/user/bind-email
     * 绑定邮箱
     */
    public function bindEmail(): void
    {
        $userId = Auth::userId();
        $email = $this->request->string('email');
        $code = $this->request->string('code');
        $this->validate(['email' => 'required|email|label:邮箱']);

        if (!\App\Models\VerifyCode::check($email, 'email', 'verify', $code)) {
            json_error('验证码错误或已过期');
        }
        $exists = User::findByEmail($email);
        if ($exists && (int)$exists['id'] !== (int)$userId) {
            json_error('该邮箱已被其他账号绑定');
        }
        db()->update('users', ['email' => $email, 'email_verified' => 1, 'updated_at' => time()], ['id' => $userId]);
        json_success(null, '邮箱绑定成功');
    }
}
