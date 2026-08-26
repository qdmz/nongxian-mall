<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use App\Models\Referral;

/**
 * 用户端 - 推荐分享
 */
class ShareController extends Controller
{
    /**
     * GET /api/share/code
     * 获取我的推广码/推广链接
     */
    public function code(): void
    {
        $userId = Auth::userId();
        $referral = Referral::ensureCode((int)$userId);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host . '/h5';

        json_success([
            'code' => $referral['code'],
            'url' => $baseUrl . '/#/register?invite=' . $referral['code'],
            'click_count' => (int)$referral['click_count'],
            'order_count' => (int)$referral['order_count'],
            'earnings' => (float)$referral['earnings'],
            'reward_rate' => (float)config('share.share_reward_rate', '0'),
            'reward_enabled' => config('share.share_reward_enabled', '0') === '1',
        ]);
    }

    /**
     * GET /api/share/product/{id}
     * 获取商品分享卡片信息
     */
    public function productShare(array $params): void
    {
        $userId = Auth::userId();
        $productId = (int)$params['id'];
        $product = db()->one('SELECT id, name, subtitle, cover_image, price, original_price, unit FROM products WHERE id = ? AND status = 1', [$productId]);
        if (!$product) {
            json_error('商品不存在');
        }

        $referral = Referral::ensureCode((int)$userId);
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        json_success([
            'product' => $product,
            'share_url' => $scheme . '://' . $host . '/h5/#/product/' . $productId . '?invite=' . $referral['code'],
            'share_title' => $product['name'] . ' 只需 ¥' . $product['price'],
            'share_desc' => $product['subtitle'] ?: '来自田冲助农商城的新鲜农产品，强村富民，一起助力乡村振兴！',
        ]);
    }

    /**
     * GET /api/share/rewards
     * 我的推荐奖励明细
     */
    public function rewards(): void
    {
        $userId = Auth::userId();
        [$page, $pageSize] = $this->pageParams(20);
        $offset = ($page - 1) * $pageSize;
        $list = db()->all(
            'SELECT rr.*, u.nickname AS from_nickname, u.avatar AS from_avatar
             FROM referral_rewards rr
             LEFT JOIN users u ON u.id = rr.from_user_id
             WHERE rr.to_user_id = ?
             ORDER BY rr.id DESC LIMIT ? OFFSET ?',
            [$userId, $pageSize, $offset]
        );
        $total = (int)db()->value('SELECT COUNT(*) FROM referral_rewards WHERE to_user_id = ?', [$userId]);
        $totalEarnings = (float)db()->value('SELECT COALESCE(SUM(amount),0) FROM referral_rewards WHERE to_user_id = ? AND status = 1', [$userId]);

        json_success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_earnings' => $totalEarnings,
        ]);
    }

    /**
     * GET /api/share/my-team
     * 我推荐的用户
     */
    public function myTeam(): void
    {
        $userId = Auth::userId();
        [$page, $pageSize] = $this->pageParams(20);
        $offset = ($page - 1) * $pageSize;
        $list = db()->all(
            'SELECT id, nickname, avatar, created_at,
                    (SELECT COALESCE(SUM(pay_amount),0) FROM orders WHERE user_id = users.id AND status IN (1,2,3)) AS total_consumption
             FROM users WHERE referred_by = ?
             ORDER BY id DESC LIMIT ? OFFSET ?',
            [$userId, $pageSize, $offset]
        );
        $total = (int)db()->value('SELECT COUNT(*) FROM users WHERE referred_by = ?', [$userId]);
        foreach ($list as &$item) {
            unset($item['id']);
            $item['total_consumption'] = (float)$item['total_consumption'];
        }
        json_success(['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]);
    }

    /**
     * POST /api/share/track
     * 记录分享链接点击（H5 页面加载时调用）
     */
    public function track(): void
    {
        $code = $this->request->string('code');
        if ($code) {
            Referral::trackClick($code);
        }
        json_success(null);
    }
}
