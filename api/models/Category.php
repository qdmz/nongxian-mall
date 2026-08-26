<?php

namespace App\Models;

use Core\Model;

class Category extends Model
{
    protected static string $table = 'categories';

    /** 全部分类树 */
    public static function tree(int $status = 1): array
    {
        $model = new static();
        $condition = [];
        if ($status >= 0) {
            $condition['status'] = $status;
        }
        $all = $model->select($condition, '*', 'sort DESC, id ASC');
        return self::buildTree($all, 0);
    }

    private static function buildTree(array $items, int $parentId): array
    {
        $tree = [];
        foreach ($items as $item) {
            if ((int)$item['parent_id'] === $parentId) {
                $item['children'] = self::buildTree($items, (int)$item['id']);
                $tree[] = $item;
            }
        }
        return $tree;
    }
}
