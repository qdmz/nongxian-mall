<?php

namespace App\Services;

/**
 * 定时任务入口
 * 建议配置 crontab：
 * * * * * * php /path/to/api/cron.php
 */
class CronService
{
    public static function run(): array
    {
        $results = [];
        // 取消超时订单
        $results['cancel_expired_orders'] = OrderService::cancelExpiredOrders();
        // 自动确认收货
        $results['auto_confirm'] = OrderService::autoConfirmReceive();
        // 处理过期拼团
        $results['group_buy_expired'] = \App\Models\GroupBuy::processExpired();
        // 2026-10-14b：原路退款（易支付）待处理/重试
        $results['epay_refunds'] = RefundService::processPending();
        // 每日凌晨汇总昨日统计
        if (date('H') == '00' && date('i') < 5) {
            StatisticsService::aggregateDaily();
            $results['aggregate_daily'] = 'done';
        }
        return $results;
    }
}
