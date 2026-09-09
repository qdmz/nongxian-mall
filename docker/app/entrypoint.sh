#!/bin/sh
# app 容器入口：等待DB → 引导校验/导入 → 启动 cron + php-fpm
set -e

echo "[entrypoint] 等待数据库就绪 ..."
i=0
until php /usr/local/bin/healthcheck.php >/dev/null 2>&1; do
  i=$((i + 1))
  if [ "$i" -ge 60 ]; then
    echo "[entrypoint] 数据库等待超时" >&2
    exit 1
  fi
  sleep 1
done
echo "[entrypoint] 数据库已就绪"

php /usr/local/bin/bootstrap.php

# crond（alpine busybox crond，日志进容器 stdout）
crond -b -l 8

echo "[entrypoint] 启动 php-fpm"
exec php-fpm
