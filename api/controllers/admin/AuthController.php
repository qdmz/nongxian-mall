<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Auth;
use App\Models\AdminUser;

/**
 * 管理后台 - 认证
 */
class AuthController extends Controller
{
    /**
     * POST /admin/auth/login
     */
    public function login(): void
    {
        $username = $this->request->string('username');
        $password = $this->request->string('password');
        $this->validate([
            'username' => 'required|label:用户名',
            'password' => 'required|label:密码',
        ]);

        // 2026-10-14：防暴力破解，同一 IP 15 分钟 20 次、同一账号 15 分钟 10 次失败上限
        $rlIp = 'admin_login_ip:' . client_ip();
        $rlUser = 'admin_login_user:' . strtolower($username);
        \Core\RateLimit::check($rlIp, 20, 900, '登录尝试过多，请 15 分钟后再试');
        \Core\RateLimit::check($rlUser, 10, 900, '该账号登录尝试过多，请 15 分钟后再试');

        $admin = AdminUser::findByUsername($username);
        if (!$admin || !password_verify($password, $admin['password'])) {
            json_error('用户名或密码错误');
        }
        \Core\RateLimit::clear($rlUser);
        if ($admin['status'] != 1) {
            json_error('账号已被禁用');
        }

        // 记录登录
        db()->update('admin_users', [
            'last_login_at' => time(),
            'last_login_ip' => client_ip(),
        ], ['id' => $admin['id']]);

        $token = Auth::issue((int)$admin['id'], 'admin', 86400 * 7); // 管理端7天有效
        $role = db()->one('SELECT * FROM admin_roles WHERE id = ?', [$admin['role_id']]);
        \Core\RateLimit::clear($rlIp);

        // 2026-10-14b：返回解析后的权限，供后台前端按角色隐藏菜单
        $perms = \Middleware\AdminAuth::parsePerms((string)($role['permissions'] ?? ''));
        $safe = AdminUser::safe($admin);
        $safe['permissions'] = $perms;
        $safe['role_slug'] = $role['slug'] ?? '';
        json_success([
            'token' => $token,
            'admin' => $safe,
            'role' => $role,
            'permissions' => $perms,
        ], '登录成功');
    }

    /**
     * GET /admin/auth/profile
     */
    public function profile(): void
    {
        $adminId = Auth::adminId();
        $admin = AdminUser::find($adminId);
        $role = db()->one('SELECT * FROM admin_roles WHERE id = ?', [$admin['role_id']]);
        $perms = \Middleware\AdminAuth::parsePerms((string)($role['permissions'] ?? ''));
        $safe = AdminUser::safe($admin);
        $safe['permissions'] = $perms;
        $safe['role_slug'] = $role['slug'] ?? '';
        json_success([
            'admin' => $safe,
            'role' => $role,
            'permissions' => $perms,
        ]);
    }

    /**
     * PUT /admin/auth/profile
     * 修改个人信息（姓名/头像/手机/邮箱）
     */
    public function updateProfile(): void
    {
        $adminId = Auth::adminId();
        $data = $this->request->only(['real_name', 'avatar', 'phone', 'email']);
        if (empty($data)) {
            json_error('没有需要更新的内容');
        }
        $data['updated_at'] = time();
        db()->update('admin_users', $data, ['id' => $adminId]);
        // 2026-10-14b：带上权限，避免后台前端更新资料后丢失权限信息
        $safe = AdminUser::safe(AdminUser::find($adminId));
        $role = db()->one('SELECT permissions, slug FROM admin_roles WHERE id = ?', [(int)$safe['role_id']]);
        $safe['permissions'] = \Middleware\AdminAuth::parsePerms((string)($role['permissions'] ?? ''));
        $safe['role_slug'] = $role['slug'] ?? '';
        json_success($safe, '更新成功');
    }

    /**
     * POST /admin/auth/change-password
     */
    public function changePassword(): void
    {
        $adminId = Auth::adminId();
        $admin = AdminUser::find($adminId);
        $oldPassword = $this->request->string('old_password');
        $newPassword = $this->request->string('new_password');
        $this->validate(['new_password' => 'required|min:6|label:新密码']);

        if (!password_verify($oldPassword, $admin['password'])) {
            json_error('原密码错误');
        }
        db()->update('admin_users', [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => time(),
        ], ['id' => $adminId]);
        json_success(null, '密码已修改，下次登录请使用新密码');
    }

    /**
     * POST /admin/auth/logout
     */
    public function logout(): void
    {
        // JWT 无状态，前端删除 token 即可。记录日志。
        json_success(null, '已退出登录');
    }
}
