#!/bin/sh
# 田冲助农商城 - 本地一键启动（Linux / macOS）
# 1. 启动 MariaDB/MySQL（如未运行）
# 2. 确保应用数据库账号存在（nongxian / nongxian_mall_2026，可用环境变量覆盖）
# 3. 启动 PHP 后端（127.0.0.1:8000）
# 4. 启动单端口整合预览（H5 + /manage 管理后台 + API 代理）
#
# 用法：sh ./scripts/dev-start.sh   （Ctrl+C 退出时自动清理子进程）
set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

DB_USER="${DB_USER:-nongxian}"
DB_PASS="${DB_PASS:-nongxian_mall_2026}"

# ---------- 1. 数据库 ----------
if ! mysqladmin ping --silent 2>/dev/null; then
  echo "[start] 启动 MariaDB..."
  mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld 2>/dev/null || true
  (mariadbd --user=mysql >/tmp/mariadb.log 2>&1 &) || (mysqld_safe >/tmp/mariadb.log 2>&1 &)
  for i in $(seq 1 15); do
    mysqladmin ping --silent 2>/dev/null && break
    sleep 1
  done
  mysqladmin ping --silent 2>/dev/null || { echo "[start] 数据库启动失败，查看 /tmp/mariadb.log"; exit 1; }
fi
echo "[start] 数据库已就绪"

# ---------- 2. 应用数据库账号 ----------
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS nongxian_mall DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON nongxian_mall.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON nongxian_mall.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
echo "[start] 数据库账号 ${DB_USER} 已就绪"
# 如库为空，可手动导入：mysql -u root nongxian_mall < sql/nongxian_mall.sql

# ---------- 3. PHP 后端 ----------
PHP_PORT="${PHP_PORT:-8000}"
cleanup() { kill ${PHP_PID:-} >/dev/null 2>&1 || true; }
trap cleanup EXIT INT TERM

if curl -s -o /dev/null "http://127.0.0.1:${PHP_PORT}"; then
  echo "[start] 端口 ${PHP_PORT} 已有 PHP 后端在运行，跳过启动"
  PHP_PID=""
else
  echo "[start] 启动 PHP 后端 (127.0.0.1:${PHP_PORT})..."
  (cd api && php -S "127.0.0.1:${PHP_PORT}" -t public router.php >/tmp/php-api.log 2>&1 &)
  PHP_PID=$!
  sleep 2
fi

# ---------- 4. 单端口整合预览 ----------
PORT="${PORT:-8080}"
echo "[start] 启动整合预览 (0.0.0.0:${PORT})..."
echo "[start]   H5 用户端:  http://localhost:${PORT}/"
echo "[start]   管理后台:   http://localhost:${PORT}/manage/  (admin / admin123456)"
PORT="$PORT" PHP_BACKEND="http://127.0.0.1:${PHP_PORT}" node preview-server.mjs
