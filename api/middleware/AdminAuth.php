<?php

namespace Middleware;

use Core\Auth;
use Core\Response;

/**
 * 管理后台登录校验
 */
class AdminAuth
{
    public function handle(): void
    {
        $adminId = Auth::adminId();
        if (!$adminId) {
            Response::error('请先登录', 401, null, 401);
        }
        $admin = \App\Models\AdminUser::find($adminId);
        if (!$admin || $admin['status'] != 1) {
            Response::error('账号已被禁用', 403, null, 403);
        }
    }
}
