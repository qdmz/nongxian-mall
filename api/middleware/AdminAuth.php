<?php

namespace Middleware;

use Core\Auth;
use Core\Response;

/**
 * 管理后台登录校验 + 角色权限校验
 * 2026-10-14：原实现只校验登录，admin_roles.permissions 未生效（运营/客服可改支付配置、调余额）。
 */
class AdminAuth
{
    /** URI 模块 => 所需权限（未列出的模块对所有已登录管理员开放） */
    private const MODULE_PERMS = [
        'users' => 'users',
        'categories' => 'products',
        'products' => 'products',
        'orders' => 'orders',
        'refunds' => 'orders',
        'deliveries' => 'orders',
        'group-buy' => 'group_buy',
        'seckill' => 'group_buy', // 2026-10-15：秒杀与拼团同属营销活动
        'banners' => 'banners',
        'config' => 'config',
        'finance' => 'finance', // 2026-10-14b：提现审核/退款打款/对账，默认仅超级管理员
    ];

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

        $role = db()->one('SELECT slug, permissions, status FROM admin_roles WHERE id = ?', [(int)$admin['role_id']]);
        $perms = self::parsePerms($role['permissions'] ?? '');
        if (in_array('*', $perms, true)) {
            return; // 超级管理员
        }
        if (!$role || (int)$role['status'] !== 1) {
            Response::error('角色已停用或不存在，请联系超级管理员', 403, null, 403);
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $parts = explode('/', trim((string)$uri, '/')); // admin/{module}/...
        $module = $parts[1] ?? '';
        // 站内消息群发单独权限
        if ($module === 'users' && ($parts[2] ?? '') === 'send-notification') {
            if (in_array('notifications', $perms, true) || in_array('users', $perms, true)) return;
            Response::error('无权限执行该操作', 403, null, 403);
        }
        // 调整余额只允许超级管理员
        if ($module === 'users' && ($parts[3] ?? '') === 'adjust-balance') {
            Response::error('仅超级管理员可调整用户余额', 403, null, 403);
        }
        $need = self::MODULE_PERMS[$module] ?? null;
        if ($need !== null && !in_array($need, $perms, true)) {
            Response::error('无权限访问该模块', 403, null, 403);
        }
    }

    /** permissions 兼容 "*"、JSON 数组、逗号分隔 三种格式 */
    public static function parsePerms(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return [];
        if ($raw === '*') return ['*'];
        $json = json_decode($raw, true);
        if (is_array($json)) return array_map('strval', $json);
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
