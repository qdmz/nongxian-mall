<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Auth;
use App\Services\WithdrawService;
use App\Services\RefundService;

/**
 * 管理后台 - 财务：提现审核、退款打款记录、对账差异（2026-10-14b 新增；权限 finance，默认仅超级管理员）
 */
class FinanceController extends Controller
{
    /** GET /admin/finance/withdrawals?status=&keyword= */
    public function withdrawals(): void
    {
        [$page, $pageSize] = $this->pageParams();
        $where = [];
        $args = [];
        $status = $this->request->param('status');
        if ($status !== null && $status !== '') {
            $where[] = 'w.status = ?';
            $args[] = (int)$status;
        }
        $kw = trim($this->request->string('keyword'));
        if ($kw !== '') {
            $where[] = '(w.withdraw_no = ? OR u.phone = ? OR w.account_name = ?)';
            array_push($args, $kw, $kw, $kw);
        }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $rows = db()->all(
            "SELECT w.*, u.nickname, u.phone, u.wallet_balance, u.frozen_balance, a.username AS handled_by_name
             FROM withdrawals w LEFT JOIN users u ON u.id = w.user_id LEFT JOIN admin_users a ON a.id = w.handled_by
             {$sqlWhere} ORDER BY w.id DESC LIMIT " . (int)$pageSize . ' OFFSET ' . (int)(($page - 1) * $pageSize),
            $args
        );
        $total = (int)db()->value("SELECT COUNT(*) FROM withdrawals w LEFT JOIN users u ON u.id = w.user_id {$sqlWhere}", $args);
        $summary = db()->one('SELECT COUNT(*) AS pending_count, COALESCE(SUM(amount),0) AS pending_amount FROM withdrawals WHERE status = 0');
        json_success([
            'list' => array_map(fn($w) => WithdrawService::present($w, true), $rows),
            'total' => $total, 'page' => $page, 'page_size' => $pageSize,
            'summary' => ['pending_count' => (int)$summary['pending_count'], 'pending_amount' => (float)$summary['pending_amount']],
        ]);
    }

    /** POST /admin/finance/withdrawals/{id}/handle  {action: approve|reject, transfer_no?, reason?} */
    public function handleWithdrawal(array $params): void
    {
        $action = $this->request->string('action');
        if (!in_array($action, ['approve', 'reject'], true)) {
            json_error('无效的操作');
        }
        $w = WithdrawService::handle((int)$params['id'], $action, [
            'admin_id' => Auth::adminId(),
            'transfer_no' => $this->request->string('transfer_no'),
            'reason' => $this->request->string('reason', '不符合提现条件'),
        ]);
        json_success(WithdrawService::present($w, true), $action === 'approve' ? '已确认打款' : '已驳回，金额已退回用户余额');
    }

    /** GET /admin/finance/refund-records?status= */
    public function refundRecords(): void
    {
        [$page, $pageSize] = $this->pageParams();
        $status = $this->request->param('status');
        $where = '';
        $args = [];
        if ($status !== null && $status !== '') {
            $where = 'WHERE r.status = ?';
            $args[] = (int)$status;
        }
        $rows = db()->all("SELECT r.*, u.nickname, u.phone FROM refund_records r LEFT JOIN users u ON u.id = r.user_id {$where} ORDER BY r.id DESC LIMIT " . (int)$pageSize . ' OFFSET ' . (int)(($page - 1) * $pageSize), $args);
        $total = (int)db()->value("SELECT COUNT(*) FROM refund_records r {$where}", $args);
        json_success(['list' => $rows, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * POST /admin/finance/refund-records/{id}/resolve  {action: retry|to_wallet|mark_success}
     * 只用于 status=3（待人工）的记录：管理员到易支付后台核实后处理。
     */
    public function resolveRefundRecord(array $params): void
    {
        $id = (int)$params['id'];
        $action = $this->request->string('action');
        $rec = db()->one('SELECT * FROM refund_records WHERE id = ?', [$id]);
        if (!$rec || (int)$rec['status'] !== RefundService::ST_MANUAL || $rec['channel'] !== 'epay') {
            json_error('只能处理"待人工"的原路退款记录');
        }
        $note = '管理员#' . Auth::adminId() . ' ';
        if ($action === 'retry') {
            db()->query('UPDATE refund_records SET status = 0, last_error = ?, updated_at = ? WHERE id = ? AND status = 3', [$note . '手动重试', time(), $id]);
            $st = RefundService::process($id);
            json_success(['status' => $st], '已重试');
        } elseif ($action === 'mark_success') {
            db()->query('UPDATE refund_records SET status = 1, last_error = ?, updated_at = ? WHERE id = ? AND status = 3', [$note . '核实平台已退款，标记成功', time(), $id]);
            json_success(null, '已标记为原路退款成功');
        } elseif ($action === 'to_wallet') {
            db()->beginTransaction();
            try {
                $ok = db()->query('UPDATE refund_records SET status = 2, last_error = ?, updated_at = ? WHERE id = ? AND status = 3', [$note . '核实平台未退款，转退钱包', time(), $id])->rowCount();
                if (!$ok) {
                    db()->rollBack();
                    json_error('记录状态已变化，请刷新');
                }
                if (!\App\Models\User::walletChange((int)$rec['user_id'], (float)$rec['amount'], 'refund', '订单退款（原路退回失败，转入钱包）', 'order', $rec['order_no'])) {
                    throw new \RuntimeException('wallet');
                }
                db()->commit();
            } catch (\Throwable $e) {
                db()->rollBack();
                json_error('处理失败');
            }
            json_success(null, '已转退至用户钱包');
        } else {
            json_error('无效的操作');
        }
    }

    /** GET /admin/finance/reconcile-diffs?date= */
    public function reconcileDiffs(): void
    {
        [$page, $pageSize] = $this->pageParams();
        $date = $this->request->string('date');
        $where = '';
        $args = [];
        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $where = 'WHERE bill_date = ?';
            $args[] = $date;
        }
        $rows = db()->all("SELECT * FROM pay_reconcile_diffs {$where} ORDER BY id DESC LIMIT " . (int)$pageSize . ' OFFSET ' . (int)(($page - 1) * $pageSize), $args);
        $total = (int)db()->value("SELECT COUNT(*) FROM pay_reconcile_diffs {$where}", $args);
        json_success(['list' => $rows, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }
}
