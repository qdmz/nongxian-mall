<?php

namespace Core;

/**
 * 基础模型（Active Record 风格，静态调用）
 * User::find(1) => 自身静态调用，无需实例化
 */
class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    private static function db(): Database
    {
        return Database::instance();
    }

    private static function tableName(): string
    {
        return static::$table;
    }

    private static function pkName(): string
    {
        return static::$primaryKey;
    }

    /** 按主键查 */
    public static function find(int $id): ?array
    {
        $t = static::tableName();
        $pk = static::pkName();
        return self::db()->one("SELECT * FROM `{$t}` WHERE `{$pk}` = ?", [$id]);
    }

    /** 按条件查单条 */
    public static function where(array $condition): ?array
    {
        $params = [];
        $whereSql = self::buildWhere($condition, $params);
        $t = static::tableName();
        return self::db()->one("SELECT * FROM `{$t}` {$whereSql} LIMIT 1", $params);
    }

    /** 按条件查列表 */
    public static function select(array $condition = [], string $fields = '*', string $orderBy = '', int $limit = 0): array
    {
        $params = [];
        $whereSql = self::buildWhere($condition, $params);
        $t = static::tableName();
        $orderSql = $orderBy ? "ORDER BY {$orderBy}" : '';
        $limitSql = $limit > 0 ? "LIMIT {$limit}" : '';
        return self::db()->all("SELECT {$fields} FROM `{$t}` {$whereSql} {$orderSql} {$limitSql}", $params);
    }

    /** 分页查询 */
    public static function paginate(array $condition = [], string $fields = '*', string $orderBy = 'id DESC', int $page = 1, int $pageSize = 15): array
    {
        $params = [];
        $whereSql = self::buildWhere($condition, $params);
        $t = static::tableName();
        $total = (int)self::db()->value("SELECT COUNT(*) FROM `{$t}` {$whereSql}", $params);
        $offset = ($page - 1) * $pageSize;
        $list = self::db()->all(
            "SELECT {$fields} FROM `{$t}` {$whereSql} ORDER BY {$orderBy} LIMIT {$pageSize} OFFSET {$offset}",
            $params
        );
        return ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize];
    }

    /** 插入 */
    public static function create(array $data): int
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = time();
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = time();
        }
        return self::db()->insert(static::tableName(), $data);
    }

    /** 按主键更新 */
    public static function updateById(int $id, array $data): bool
    {
        if (isset($data['created_at'])) unset($data['created_at']);
        $data['updated_at'] = time();
        $pk = static::pkName();
        return self::db()->update(static::tableName(), $data, [$pk => $id]) >= 0;
    }

    /** 按条件更新 */
    public static function updateWhere(array $data, array $condition): int
    {
        if (isset($data['created_at'])) unset($data['created_at']);
        $data['updated_at'] = time();
        return self::db()->update(static::tableName(), $data, $condition);
    }

    /** 按主键删除 */
    public static function deleteById(int $id): bool
    {
        $pk = static::pkName();
        return self::db()->delete(static::tableName(), [$pk => $id]) > 0;
    }

    /** 统计 */
    public static function count(array $condition = []): int
    {
        $params = [];
        $whereSql = self::buildWhere($condition, $params);
        $t = static::tableName();
        return (int)self::db()->value("SELECT COUNT(*) FROM `{$t}` {$whereSql}", $params);
    }

    /** 求和 */
    public static function sum(string $field, array $condition = []): float
    {
        $params = [];
        $whereSql = self::buildWhere($condition, $params);
        $t = static::tableName();
        return (float)self::db()->value("SELECT COALESCE(SUM(`{$field}`),0) FROM `{$t}` {$whereSql}", $params);
    }

    /** 自增/自减（原子操作，防并发超卖） */
    public static function increment(string $field, int $amount, array $condition): int
    {
        $params = [];
        $whereSql = self::buildWhere($condition, $params);
        $t = static::tableName();
        $sign = $amount >= 0 ? '+' : '-';
        $abs = abs($amount);
        return self::db()->query(
            "UPDATE `{$t}` SET `{$field}` = `{$field}` {$sign} {$abs} {$whereSql}",
            $params
        )->rowCount();
    }

    /** 原始查询（子类用） */
    protected static function query(string $sql, array $params = []): \PDOStatement
    {
        return self::db()->query($sql, $params);
    }

    /** 获取表名（供外部使用） */
    public static function table(): string
    {
        return static::$table;
    }

    private static function buildWhere(array $where, array &$params): string
    {
        if (empty($where)) return '';
        $conditions = [];
        foreach ($where as $key => $value) {
            if (preg_match('/^(\w+)\s*(>=|<=|!=|>|<)$/i', (string)$key, $m)) {
                $conditions[] = sprintf('`%s` %s ?', $m[1], strtoupper($m[2]));
                $params[] = $value;
            } elseif (is_array($value)) {
                if (empty($value)) continue;
                $placeholders = implode(', ', array_fill(0, count($value), '?'));
                $conditions[] = sprintf('`%s` IN (%s)', $key, $placeholders);
                foreach ($value as $v) $params[] = $v;
            } elseif (str_contains($key, ' LIKE')) {
                $field = trim(str_replace(' LIKE', '', $key));
                $conditions[] = "`{$field}` LIKE ?";
                $params[] = $value;
            } elseif (str_contains($key, ' RAW')) {
                $conditions[] = $value;
            } else {
                $conditions[] = "`{$key}` = ?";
                $params[] = $value;
            }
        }
        return empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
    }
}
