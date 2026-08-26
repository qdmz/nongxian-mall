<?php

namespace Core;

use PDO;
use PDOStatement;

/**
 * 数据库 PDO 封装（单例）
 * 支持链式查询构造
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $cfg = require APP_ROOT . '/config/database.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['database'],
            $cfg['charset']
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$cfg['charset']}",
        ];
        if (!empty($cfg['ssl_ca'])) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $cfg['ssl_ca'];
        }
        $this->pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $options);
    }

    public static function instance(): static
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** 原生 SQL 执行 */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** 查询单行 */
    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** 查询多行 */
    public function all(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /** 查询单值 */
    public function value(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /** 插入，返回自增ID */
    public function insert(string $table, array $data): int
    {
        $fields = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(fn($f) => "`{$f}`", $fields)),
            implode(', ', array_fill(0, count($fields), '?'))
        );
        $this->query($sql, array_values($data));
        return (int)$this->pdo->lastInsertId();
    }

    /** 批量插入 */
    public function batchInsert(string $table, array $rows): int
    {
        if (empty($rows)) return 0;
        $fields = array_keys($rows[0]);
        $placeholder = '(' . implode(', ', array_fill(0, count($fields), '?')) . ')';
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES %s',
            $table,
            implode(', ', array_map(fn($f) => "`{$f}`", $fields)),
            implode(', ', array_fill(0, count($rows), $placeholder))
        );
        $params = [];
        foreach ($rows as $row) {
            foreach ($fields as $f) {
                $params[] = $row[$f] ?? null;
            }
        }
        $this->query($sql, $params);
        return count($rows);
    }

    /** 更新，返回影响行数 */
    public function update(string $table, array $data, array $where): int
    {
        $sets = [];
        $params = [];
        foreach ($data as $field => $value) {
            $sets[] = "`{$field}` = ?";
            $params[] = $value;
        }
        $whereSql = $this->buildWhere($where, $params);
        $sql = sprintf('UPDATE `%s` SET %s %s', $table, implode(', ', $sets), $whereSql);
        return $this->query($sql, $params)->rowCount();
    }

    /** 删除 */
    public function delete(string $table, array $where): int
    {
        $params = [];
        $whereSql = $this->buildWhere($where, $params);
        return $this->query(sprintf('DELETE FROM `%s` %s', $table, $whereSql), $params)->rowCount();
    }

    /** 事务 */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /** 构造 WHERE */
    private function buildWhere(array $where, array &$params): string
    {
        if (empty($where)) {
            return '';
        }
        $conditions = [];
        foreach ($where as $key => $value) {
            // 支持 ['id >' => 5] 这种操作符写法
            if (preg_match('/^(\w+)\s*(>=|<=|!=|>|<|LIKE|IN|NOT IN)$/i', (string)$key, $m)) {
                $field = $m[1];
                $op = strtoupper($m[2]);
                if ($op === 'IN' || $op === 'NOT IN') {
                    if (empty($value)) continue;
                    $placeholders = implode(', ', array_fill(0, count($value), '?'));
                    $conditions[] = sprintf('`%s` %s (%s)', $field, $op, $placeholders);
                    foreach ($value as $v) $params[] = $v;
                } else {
                    $conditions[] = sprintf('`%s` %s ?', $field, $op);
                    $params[] = $value;
                }
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
