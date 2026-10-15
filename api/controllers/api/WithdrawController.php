<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use App\Services\WithdrawService;

/**
 * 用户端 - 钱包提现（2026-10-14b 新增）
 */
class WithdrawController extends Controller
{
    /** GET /api/user/withdraw/config  提现规则 + 当前余额 */
    public function config(): void
    {
        $userId = (int)Auth::userId();
        $user = db()->one('SELECT wallet_balance, frozen_balance FROM users WHERE id = ?', [$userId]);
        $cfg = WithdrawService::settings();
        $cfg['balance'] = (float)($user['wallet_balance'] ?? 0);
        $cfg['frozen_balance'] = (float)($user['frozen_balance'] ?? 0);
        json_success($cfg);
    }

    /** POST /api/user/withdraw  申请提现 */
    public function store(): void
    {
        $userId = (int)Auth::userId();
        \Core\RateLimit::check('withdraw_apply:' . $userId, 10, 3600, '操作过于频繁，请稍后再试');
        $w = WithdrawService::apply($userId, [
            'amount' => $this->request->float('amount'),
            'account_type' => $this->request->string('account_type'),
            'account_name' => $this->request->string('account_name'),
            'account_no' => $this->request->string('account_no'),
            'bank_name' => $this->request->string('bank_name'),
            'remark' => $this->request->string('remark'),
            'request_id' => $this->request->string('request_id'),
        ]);
        json_success($w, '提现申请已提交，等待审核');
    }

    /** GET /api/user/withdrawals  我的提现记录 */
    public function index(): void
    {
        $userId = (int)Auth::userId();
        [$page, $pageSize] = $this->pageParams(50);
        $offset = ($page - 1) * $pageSize;
        $rows = db()->all('SELECT * FROM withdrawals WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int)$pageSize . ' OFFSET ' . (int)$offset, [$userId]);
        $total = (int)db()->value('SELECT COUNT(*) FROM withdrawals WHERE user_id = ?', [$userId]);
        json_success(['list' => array_map(fn($w) => WithdrawService::present($w), $rows), 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /** POST /api/user/withdrawals/{id}/cancel  撤销待审核的提现 */
    public function cancel(array $params): void
    {
        $userId = (int)Auth::userId();
        $w = WithdrawService::handle((int)$params['id'], 'cancel', ['user_id' => $userId]);
        json_success($w, '已撤销，金额已退回余额');
    }
}
