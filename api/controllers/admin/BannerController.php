<?php

namespace App\Controllers\Admin;

use Core\Controller;

/**
 * 管理后台 - 轮播图 + 上传
 */
class BannerController extends Controller
{
    /**
     * GET /admin/banners
     */
    public function index(): void
    {
        $list = db()->all('SELECT * FROM banners ORDER BY sort DESC, id DESC');
        json_success($list);
    }

    /**
     * POST /admin/banners
     */
    public function store(): void
    {
        $data = $this->validate([
            'image' => 'required|label:图片',
        ]);
        $linkType = $this->request->int('link_type');
        $data = array_merge($data, [
            'title' => $this->request->string('title') ?: null,
            'link_type' => $linkType,
            'link_value' => $this->request->string('link_value') ?: null,
            'position' => $this->request->string('position', 'home'),
            'sort' => $this->request->int('sort'),
            'status' => $this->request->int('status', 1) ? 1 : 0,
            'start_time' => $this->request->string('start_time') ? strtotime($this->request->string('start_time')) : 0,
            'end_time' => $this->request->string('end_time') ? strtotime($this->request->string('end_time')) : 0,
        ]);
        $id = db()->insert('banners', array_merge($data, ['created_at' => time(), 'updated_at' => time()]));
        json_success(db()->one('SELECT * FROM banners WHERE id = ?', [$id]), '轮播图已添加');
    }

    /**
     * PUT /admin/banners/{id}
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        if (!db()->one('SELECT id FROM banners WHERE id = ?', [$id])) {
            json_error('轮播图不存在');
        }
        $data = $this->request->only(['title', 'image', 'link_type', 'link_value', 'position', 'sort', 'status', 'start_time', 'end_time']);
        if (empty($data)) {
            json_error('没有需要更新的内容');
        }
        if (isset($data['start_time']) && is_string($data['start_time'])) {
            $data['start_time'] = $data['start_time'] ? strtotime($data['start_time']) : 0;
        }
        if (isset($data['end_time']) && is_string($data['end_time'])) {
            $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']) : 0;
        }
        $data['updated_at'] = time();
        db()->update('banners', $data, ['id' => $id]);
        json_success(null, '更新成功');
    }

    /**
     * DELETE /admin/banners/{id}
     */
    public function destroy(array $params): void
    {
        $deleted = db()->delete('banners', ['id' => (int)$params['id']]);
        if (!$deleted) json_error('轮播图不存在');
        json_success(null, '已删除');
    }
}
