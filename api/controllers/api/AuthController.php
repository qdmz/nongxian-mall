<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use App\Models\User;
use App\Models\VerifyCode;

/**
 * 用户端 - 认证
 */
class AuthController extends Controller
{
    /**
     * POST /api/auth/register
     * 支持三种方式：手机号+验证码 / 邮箱+验证码 / 手机号+密码
     */
    public function register(): void
    {
        $registerType = $this->request->string('register_type', 'phone'); // phone_code / email_code / password

        if ($registerType === 'email_code') {
            $email = $this->request->string('email');
            $code = $this->request->string('code');
            $this->validate(['email' => 'required|email|label:邮箱']);
            if (!VerifyCode::check($email, 'email', 'register', $code)) {
                json_error('验证码错误或已过期');
            }
            if (User::findByEmail($email)) {
                json_error('该邮箱已注册');
            }
            $data = [
                'email' => $email,
                'email_verified' => 1,
                'nickname' => $this->request->string('nickname') ?: ('用户' . substr(md5($email), 0, 6)),
            ];
        } else {
            $phone = $this->request->string('phone');
            $this->validate(['phone' => 'required|phone|label:手机号']);
            if (User::findByPhone($phone)) {
                json_error('该手机号已注册');
            }
            if ($registerType === 'phone_code') {
                $code = $this->request->string('code');
                if (!VerifyCode::check($phone, 'sms', 'register', $code)) {
                    json_error('验证码错误或已过期');
                }
            } else {
                $password = $this->request->string('password');
                $this->validate(['password' => 'required|min:6|label:密码']);
            }
            $data = [
                'phone' => $phone,
                'phone_verified' => $registerType === 'phone_code' ? 1 : 0,
                'nickname' => $this->request->string('nickname') ?: ('用户' . substr($phone, -4)),
            ];
            if (isset($password)) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        // 处理邀请码
        $inviteCode = $this->request->string('invite_code');
        if ($inviteCode) {
            $referral = db()->one('SELECT * FROM referrals WHERE code = ?', [$inviteCode]);
            if ($referral) {
                $data['referred_by'] = $referral['user_id'];
            }
        }

        $user = User::register($data);
        $token = Auth::issue((int)$user['id'], 'user');

        json_success([
            'token' => $token,
            'user' => User::safe($user),
        ], '注册成功');
    }

    /**
     * POST /api/auth/login
     * 手机号+密码 / 邮箱+密码 / 手机号+验证码
     */
    public function login(): void
    {
        $loginType = $this->request->string('login_type', 'password');
        $account = $this->request->string('account');

        if ($loginType === 'sms') {
            $phone = $this->request->string('phone');
            $code = $this->request->string('code');
            $this->validate(['phone' => 'required|phone|label:手机号']);
            if (!VerifyCode::check($phone, 'sms', 'login', $code)) {
                json_error('验证码错误或已过期');
            }
            $user = User::findByPhone($phone);
            if (!$user) {
                // 验证码登录，未注册自动注册
                $user = User::register(['phone' => $phone, 'phone_verified' => 1]);
            }
        } else {
            $this->validate(['account' => 'required|label:账号']);
            $password = $this->request->string('password');
            $this->validate(['password' => 'required|label:密码']);
            $user = filter_var($account, FILTER_VALIDATE_EMAIL) ? User::findByEmail($account) : User::findByPhone($account);
            if (!$user || !password_verify($password, $user['password'] ?? '')) {
                json_error('账号或密码错误');
            }
        }

        if ($user['status'] != 1) {
            json_error('账号已被禁用，请联系客服');
        }

        db()->update('users', ['last_login_at' => time()], ['id' => $user['id']]);
        $token = Auth::issue((int)$user['id'], 'user');

        json_success([
            'token' => $token,
            'user' => User::safe($user),
        ], '登录成功');
    }

    /**
     * POST /api/auth/send-code
     * 发送验证码（短信或邮件）
     */
    public function sendCode(): void
    {
        $type = $this->request->string('type', 'sms'); // sms / email
        $target = $this->request->string('target');
        $scene = $this->request->string('scene', 'register'); // register/login/verify/reset

        $this->validate(['target' => 'required|label:' . ($type === 'sms' ? '手机号' : '邮箱')]);

        if ($type === 'sms') {
            $this->validate(['target' => 'phone|label:手机号']);
        } else {
            $this->validate(['target' => 'email|label:邮箱']);
            if (!in_array($scene, ['register', 'verify'])) {
                json_error('不支持的邮件验证场景');
            }
        }

        // 注册场景检查是否已注册
        if ($scene === 'register') {
            $exists = $type === 'sms' ? User::findByPhone($target) : User::findByEmail($target);
            if ($exists) {
                json_error('该' . ($type === 'sms' ? '手机号' : '邮箱') . '已注册，请直接登录');
            }
        }

        $code = generate_verify_code(6);
        $ok = VerifyCode::send($target, $type, $scene, $code);

        if (!$ok) {
            json_error('验证码发送失败，请稍后重试');
        }

        // 开发模式提示（短信未配置时验证码直接进日志）
        $hint = '';
        if ($type === 'sms' && !\App\Services\SmsService::enabled()) {
            $hint = '（当前为开发模式，验证码可在后台短信日志中查看）';
        }

        json_success(null, '验证码已发送' . $hint);
    }

    /**
     * GET /api/auth/check-token
     */
    public function checkToken(): void
    {
        $userId = Auth::userId();
        if (!$userId) {
            json_error('token无效', 401);
        }
        $user = User::find($userId);
        if (!$user || $user['status'] != 1) {
            json_error('账号不可用', 403);
        }
        json_success(['user' => User::safe($user)]);
    }
}
