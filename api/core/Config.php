<?php

namespace Core;

/**
 * 配置加载器
 * 文件配置：config/*.php
 * 数据库配置：system_configs 表（后台可改）
 */
class Config
{
    private static array $fileCache = [];
    private static ?array $dbCache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key, 2);
        if (count($parts) < 2) {
            return $default;
        }
        [$file, $path] = $parts;

        // 文件配置
        $fileConfig = self::loadFile($file);
        if ($fileConfig !== null) {
            $value = self::dig($fileConfig, $path);
            if ($value !== null) {
                return $value;
            }
        }

        // 数据库配置（后台可动态修改的）
        $dbConfig = self::loadDb();
        if (isset($dbConfig[$file][$path])) {
            return $dbConfig[$file][$path];
        }

        return $default;
    }

    private static function loadFile(string $name): ?array
    {
        if (isset(self::$fileCache[$name])) {
            return self::$fileCache[$name];
        }
        $file = APP_ROOT . '/config/' . $name . '.php';
        if (is_file($file)) {
            self::$fileCache[$name] = require $file;
        } else {
            self::$fileCache[$name] = null;
        }
        return self::$fileCache[$name];
    }

    private static function loadDb(): array
    {
        if (self::$dbCache !== null) {
            return self::$dbCache;
        }
        self::$dbCache = [];
        try {
            $rows = Database::instance()->query('SELECT `group`, `key`, `value` FROM system_configs')->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                self::$dbCache[$row['group']][$row['key']] = $row['value'];
            }
        } catch (\Throwable $e) {
            // 数据库未就绪时忽略
        }
        return self::$dbCache;
    }

    private static function dig(array $config, string $path): mixed
    {
        $value = $config;
        foreach (explode('.', $path) as $seg) {
            if (is_array($value) && array_key_exists($seg, $value)) {
                $value = $value[$seg];
            } else {
                return null;
            }
        }
        return $value;
    }
}
