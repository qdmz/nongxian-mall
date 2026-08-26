<?php

namespace App\Controllers\Admin;

use Core\Controller;
use App\Models\Category;

/**
 * 管理后台 - 商品分类
 */
class CategoryController extends Controller
{
    /**
     * GET /admin/categories
     */
    public function index(): void
    {
        $list = db()->all('SELECT * FROM categories ORDER BY sort DESC, id ASC');
        // 附带每个分类的商品数
        foreach ($list as &$item) {
            $item['product_count'] = (int)db()->value('SELECT COUNT(*) FROM products WHERE category_id = ?', [$item['id']]);
        }
        json_success($list);
    }

    /**
     * POST /admin/categories
     */
    public function store(): void
    {
        $data = $this->validate([
            'name' => 'required|max:20|label:分类名称',
        ]);
        $data['parent_id'] = $this->request->int('parent_id');
        $data['icon'] = $this->request->string('icon') ?: null;
        $data['image'] = $this->request->string('image') ?: null;
        $data['sort'] = $this->request->int('sort', 0);
        $data['status'] = $this->request->int('status', 1) ? 1 : 0;
        $data['is_red'] = $this->request->int('is_red') ? 1 : 0;

        // 校验父分类
        if ($data['parent_id'] > 0) {
            $parent = Category::find($data['parent_id']);
            if (!$parent) {
                json_error('父分类不存在');
            }
            if ($parent['parent_id'] > 0) {
                json_error('最多支持两级分类');
            }
        }

        $id = Category::create($data);
        json_success(Category::find($id), '分类已创建');
    }

    /**
     * PUT /admin/categories/{id}
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $category = Category::find($id);
        if (!$category) {
            json_error('分类不存在');
        }
        $data = $this->request->only(['name', 'parent_id', 'icon', 'image', 'sort', 'status', 'is_red']);
        if (empty($data)) {
            json_error('没有需要更新的内容');
        }
        if (isset($data['parent_id'])) {
            if ((int)$data['parent_id'] === $id) {
                json_error('不能将自己设为父分类');
            }
            if ($data['parent_id'] > 0) {
                $parent = Category::find((int)$data['parent_id']);
                if (!$parent || $parent['parent_id'] > 0) {
                    json_error('父分类无效或超过两级');
                }
                // 检查是否有子分类
                $childCount = Category::count(['parent_id' => $id]);
                if ($childCount > 0) {
                    json_error('该分类下有子分类，不能移动到二级');
                }
            }
        }
        if (isset($data['status'])) {
            $data['status'] = (int)$data['status'] ? 1 : 0;
        }
        $data['updated_at'] = time();
        db()->update('categories', $data, ['id' => $id]);
        json_success(Category::find($id), '更新成功');
    }

    /**
     * DELETE /admin/categories/{id}
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];
        $category = Category::find($id);
        if (!$category) {
            json_error('分类不存在');
        }
        $childCount = Category::count(['parent_id' => $id]);
        if ($childCount > 0) {
            json_error('请先删除该分类下的子分类');
        }
        $productCount = (int)db()->value('SELECT COUNT(*) FROM products WHERE category_id = ?', [$id]);
        if ($productCount > 0) {
            json_error('该分类下有 ' . $productCount . ' 个商品，请先移除或删除商品');
        }
        Category::deleteById($id);
        json_success(null, '分类已删除');
    }
}
