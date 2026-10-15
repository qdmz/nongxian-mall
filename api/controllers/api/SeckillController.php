<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use App\Models\Seckill;

/**
 * 用户端 - 秒杀（2026-10-15 v0.09 新增）
 */
class SeckillController extends Controller
{
    /**
     * GET /api/seckill/activities
     * 进行中 + 即将开始的秒杀活动
     */
    public function activities(): void
    {
        [$page, $pageSize] = $this->pageParams(50);
        $list = Seckill::listForShop($pageSize, ($page - 1) * $pageSize);
        json_success(['list' => $list, 'total' => Seckill::countForShop(), 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * GET /api/seckill/activities/{id}
     * 详情；带登录 token 时返回 my_bought（已购件数）
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $activity = Seckill::detailForShop($id);
        if (!$activity) {
            json_error('秒杀活动不存在或已结束');
        }
        $userId = Auth::userId();
        $activity['my_bought'] = $userId ? Seckill::boughtCount($id, (int)$userId) : 0;
        json_success($activity);
    }
}
