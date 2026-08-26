<?php

namespace Middleware;

use Core\Auth;
use Core\Response;

/**
 * 用户端登录校验
 */
class UserAuth
{
    public function handle(): void
    {
        $userId = Auth::userId();
        if (!$userId) {
            Response::error('请先登录', 401, null, 401);
        }
        // 校验用户状态
        $user = \App\Models\User::find($userId);
        if (!$user || $user['status'] != 1) {
            Response::error('账号已被禁用', 403, null, 403);
        }
    }
}
