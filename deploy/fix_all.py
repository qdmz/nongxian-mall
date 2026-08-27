#!/usr/bin/env python3
"""Fix all server issues: Database.php config + upload h5/dist + nginx config"""
import paramiko
import os
import sys
import time

PASSWORD = os.environ.get('DEPLOY_PWD', 'thanks12A#')
SERVER = 'qiniu.ypvps.com'
LOCAL = os.path.dirname(os.path.abspath(__file__))
MALL_ROOT = os.path.dirname(LOCAL)

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(SERVER, username='root', password=PASSWORD, timeout=15)
sftp = ssh.open_sftp()

def run(cmd, label=''):
    if label:
        print(f"\n▶ {label}")
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode()
    err = stderr.read().decode()
    if out.strip():
        print(f"  stdout: {out.strip()[:200]}")
    if err.strip():
        print(f"  stderr: {err.strip()[:200]}")
    return out, err

# ── 1. Fix Database.php on server ──
print("\n" + "="*60)
print("1. 修复 Database.php 配置读取")
print("="*60)

db_php_content = r'''<?php

namespace Core;

use PDO;
use PDOStatement;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $cfgAll = require APP_ROOT . '/config/database.php';
        $cfg = $cfgAll['connections']['mysql'] ?? $cfgAll;

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

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function all(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function value(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

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

    public function delete(string $table, array $where): int
    {
        $params = [];
        $whereSql = $this->buildWhere($where, $params);
        return $this->query(sprintf('DELETE FROM `%s` %s', $table, $whereSql), $params)->rowCount();
    }

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

    private function buildWhere(array $where, array &$params): string
    {
        if (empty($where)) {
            return '';
        }
        $conditions = [];
        foreach ($where as $key => $value) {
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
'''

remote_db = '/var/www/nongxian-mall/api/core/Database.php'
with sftp.open(remote_db, 'w') as f:
    f.write(db_php_content)
print(f"  ✓ Database.php written to {remote_db}")

# ── 2. Test DB connection ──
print("\n" + "="*60)
print("2. 测试数据库连接")
print("="*60)
run('cd /var/www/nongxian-mall/api && php -r "require \"index.php\"; echo \"DB test ok\\n\";"', 'DB连接测试')

# ── 3. Upload h5/dist ──
print("\n" + "="*60)
print("3. 上传 H5 前端 dist")
print("="*60)
h5_dist = os.path.join(MALL_ROOT, 'h5', 'dist')
if os.path.isdir(h5_dist):
    tar_path = os.path.join(LOCAL, 'h5_dist.tar.gz')
    import tarfile
    with tarfile.open(tar_path, 'w:gz') as tar:
        tar.add(h5_dist, arcname='.')
    remote_tar = '/tmp/h5_dist.tar.gz'
    sftp.put(tar_path, remote_tar)
    run('mkdir -p /var/www/nongxian-mall/h5 && cd /var/www/nongxian-mall/h5 && tar xzf /tmp/h5_dist.tar.gz && rm /tmp/h5_dist.tar.gz && ls -la', '解压H5 dist')
    os.remove(tar_path)
else:
    print(f"  ✗ h5/dist not found at {h5_dist}")

# ── 4. Upload admin/dist ──
print("\n" + "="*60)
print("4. 上传 Admin 前端 dist")
print("="*60)
admin_dist = os.path.join(MALL_ROOT, 'admin', 'dist')
if os.path.isdir(admin_dist):
    tar_path = os.path.join(LOCAL, 'admin_dist.tar.gz')
    import tarfile
    with tarfile.open(tar_path, 'w:gz') as tar:
        tar.add(admin_dist, arcname='.')
    remote_tar = '/tmp/admin_dist.tar.gz'
    sftp.put(tar_path, remote_tar)
    run('mkdir -p /var/www/nongxian-mall/admin && cd /var/www/nongxian-mall/admin && tar xzf /tmp/admin_dist.tar.gz && rm /tmp/admin_dist.tar.gz && ls -la', '解压Admin dist')
    os.remove(tar_path)
else:
    print(f"  ✗ admin/dist not found at {admin_dist}")

# ── 5. Update Nginx config ──
print("\n" + "="*60)
print("5. 更新 Nginx 配置")
print("="*60)

nginx_conf = r'''server {
    listen 80;
    server_name qiniu.ypvps.com;
    
    # H5 前端 (用户端)
    location / {
        root /var/www/nongxian-mall/h5;
        index index.html;
        try_files $uri $uri/ /index.html;
    }
    
    # Admin 前端 (管理后台)
    location /manage/ {
        alias /var/www/nongxian-mall/admin/;
        index index.html;
        try_files $uri $uri/ /manage/index.html;
    }
    
    # API 路由
    location /api/ {
        alias /var/www/nongxian-mall/api/;
        try_files $uri $uri/ /api/index.php?$query_string;
        
        location ~ \.php$ {
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }
    
    # Admin API 路由
    location /admin/ {
        alias /var/www/nongxian-mall/api/;
        try_files $uri $uri/ /api/index.php?$query_string;
        
        location ~ \.php$ {
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }
    
    # 静态文件缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
'''

with sftp.open('/etc/nginx/sites-available/nongxian-mall', 'w') as f:
    f.write(nginx_conf)
print("  ✓ Nginx config written")

run('ln -sf /etc/nginx/sites-available/nongxian-mall /etc/nginx/sites-enabled/nongxian-mall', '启用站点')
run('nginx -t', '测试Nginx配置')
run('nginx -s reload', '重载Nginx')

# ── 6. Test API endpoints ──
print("\n" + "="*60)
print("6. 测试 API 端点")
print("="*60)
time.sleep(1)
run('curl -s -o /dev/null -w "%{http_code}" http://localhost/api/', 'GET /api/')
run('curl -s http://localhost/api/', 'GET /api/ body')
run('curl -s -o /dev/null -w "%{http_code}" http://localhost/api/products', 'GET /api/products')
run('curl -s http://localhost/api/products', 'GET /api/products body')

sftp.close()
ssh.close()
print("\n✅ 全部修复完成！")
