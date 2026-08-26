<?php

namespace App\Controllers\Admin;

use Core\Controller;
use App\Models\User;

/**
 * 管理后台 - 用户管理
 */
class UserController extends Controller
{
    /**
     * GET /admin/users
     */
    public function index(): void
    {
        [$page, $pageSize] = $this->pageParams();
        $condition = [];

        $keyword = $this->request->string('keyword');
        if ($keyword) {
            $condition['phone LIKE'] = "%{$keyword}%";
            // 用 OR 条件复杂，这里直接 SQL
        }
        $status = $this->request->param('status');
        if ($status !== null && $status !== '') {
            $condition['status'] = (int)$status;
        }

        // 关键词搜索（昵称/手机号/邮箱/ID）
        if ($keyword) {
            $where = '(phone LIKE ? OR email LIKE ? OR nickname LIKE ? OR id = ?)';
            $params = ["%{$keyword}%", "%{$keyword}%", "%{$keyword}%", is_numeric($keyword) ? (int)$keyword : 0];
            if ($status !== null && $status !== '') {
                $where .= ' AND status = ?';
                $params[] = (int)$status;
            }
            $total = (int)db()->value("SELECT COUNT(*) FROM users WHERE {$where}", $params);
            $list = db()->all("SELECT * FROM users WHERE {$where} ORDER BY id DESC LIMIT {$pageSize} OFFSET " . (($page - 1) * $pageSize), $params);
        } else {
            $result = (new User())->paginate($condition, '*', 'id DESC', $page, $pageSize);
            $list = $result['list'];
            $total = $result['total'];
        }

        foreach ($list as &$user) {
            unset($user['password']);
            $user['order_count'] = (int)db()->value('SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN (1,2,3)', [$user['id']]);
            $user['total_consumption'] = (float)db()->value('SELECT COALESCE(SUM(pay_amount),0) FROM orders WHERE user_id = ? AND status IN (1,2,3)', [$user['id']]);
            $user['referrer_name'] = $user['referred_by'] ? db()->value('SELECT nickname FROM users WHERE id = ?', [$user['referred_by']]) : null;
        }

        json_success(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * GET /admin/users/{id}
     */
    public function show(array $params): void
    {
        $user = User::find((int)$params['id']);
        if (!$user) {
            json_error('用户不存在');
        }
        unset($user['password']);
        // 最近订单
        $user['recent_orders'] = db()->all('SELECT id, order_no, pay_amount, status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 10', [$user['id']]);
        // 钱包流水
        $user['recent_transactions'] = db()->all('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 10', [$user['id']]);
        json_success($user);
    }

    /**
     * PUT /admin/users/{id}
     * 编辑（状态/昵称/真实姓名）
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $user = User::find($id);
        if (!$user) {
            json_error('用户不存在');
        }
        $data = $this->request->only(['nickname', 'real_name', 'status', 'phone', 'email']);
        if (empty($data)) {
            json_error('没有需要更新的内容');
        }
        if (isset($data['status'])) {
            $data['status'] = (int)$data['status'] ? 1 : 0;
        }
        $data['updated_at'] = time();
        db()->update('users', $data, ['id' => $id]);
        json_success(null, '更新成功');
    }

    /**
     * POST /admin/users/{id}/adjust-balance
     * 管理员调整余额
     */
    public function adjustBalance(array $params): void
    {
        $id = (int)$params['id'];
        $user = User::find($id);
        if (!$user) {
            json_error('用户不存在');
        }
        $amount = $this->request->float('amount');
        $remark = $this->request->string('remark', '管理员调整');
        if ($amount == 0) {
            json_error('调整金额不能为0');
        }

        db()->beginTransaction();
        try {
            $ok = User::walletChange($id, $amount, 'adjust', $remark, 'admin', (string)$id);
            if (!$ok) {
                db()->rollBack();
                json_error('调整失败：余额不足');
            }
            db()->commit();
        } catch (\Throwable $e) {
            db()->rollBack();
            throw $e;
        }

        json_success(['new_balance' => User::find($id)['wallet_balance']], '调整成功');
    }

    /**
     * GET /admin/users/{id}/orders
     */
    public function orders(array $params): void
    {
        [$page, $pageSize] = $this->pageParams();
        $result = db()->query(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?',
            [(int)$params['id'], $pageSize, ($page - 1) * $pageSize]
        )->fetchAll();
        $total = (int)db()->value('SELECT COUNT(*) FROM orders WHERE user_id = ?', [(int)$params['id']]);
        json_success(['list' => $result, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * POST /admin/users/send-notification
     * 给用户发站内消息
     */
    public function sendNotification(): void
    {
        $userId = $this->request->int('user_id');
        $title = $this->request->string('title');
        $content = $this->request->string('content');
        $this->validate([
            'title' => 'required|max:50|label:标题',
            'content' => 'required|max:1000|label:内容',
        ]);
        if ($userId > 0 && !User::find($userId)) {
            json_error('用户不存在');
        }
        db()->insert('notifications', [
            'user_id' => $userId,
            'title' => $title,
            'content' => $content,
            'type' => 'system',
            'created_at' => time(),
        ]);
        json_success(null, '消息已发送');
    }
}
