# syntax=docker/dockerfile:1
# =====================================================================
# 田冲助农商城 - 生产级多阶段构建
#   target=assets : Node 构建前端（h5/dist + admin/dist）
#   target=app    : PHP-FPM 运行镜像（后端 + 前端产物 + cron + 健康检查）
#   target=web    : Nginx 静态/反代镜像（H5、/manage 管理后台、uploads）
#
#   docker build --target app -t nongxian-app .
#   docker build --target web -t nongxian-web .
# =====================================================================

########## 1. 前端构建（Node） ##########
FROM node:20-alpine AS assets

WORKDIR /build

# 先拷贝依赖清单，利用层缓存
COPY h5/package.json h5/package-lock.json ./h5/
RUN npm ci --prefix ./h5 --no-audit --no-fund

COPY h5/ ./h5/
RUN npm run build --prefix ./h5

COPY admin/package.json admin/package-lock.json ./admin/
RUN npm ci --prefix ./admin --no-audit --no-fund

COPY admin/ ./admin/
RUN npm run build --prefix ./admin


########## 2. PHP 应用镜像（php-fpm + cron） ##########
FROM php:8.2-fpm-alpine AS app

# pdo_mysql 必需；opcache 生产加速
RUN docker-php-ext-install pdo_mysql opcache

# PHP 运行配置
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# 后端源码（生产镜像移除调试锁）
WORKDIR /var/www/html
COPY api/ ./api/
COPY sql/ ./sql/
RUN rm -f api/config/debug.lock \
 && mkdir -p api/runtime api/public/uploads \
 && chown -R www-data:www-data api/runtime api/public/uploads

# 前端构建产物（随镜像分发，nginx/web 镜像单独 COPY）
COPY --from=assets /build/h5/dist/ ./h5/dist/
COPY --from=assets /build/admin/dist/ ./admin/dist/

# 定时任务、健康检查、首次引导、入口脚本
COPY docker/app/crontab /etc/crontabs/root
COPY docker/app/healthcheck.php /usr/local/bin/healthcheck.php
COPY docker/app/bootstrap.php /usr/local/bin/bootstrap.php
COPY docker/app/entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN chmod 644 /etc/crontabs/root \
 && chmod 755 /usr/local/bin/app-entrypoint.sh \
 && chmod 644 /usr/local/bin/healthcheck.php /usr/local/bin/bootstrap.php

# 容器启动：等待DB → 校验表结构 → 应用 ADMIN_PASSWORD → cron + php-fpm
CMD ["sh", "/usr/local/bin/app-entrypoint.sh"]


########## 3. Nginx 镜像（静态托管 + FastCGI 反代） ##########
FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# 前端构建产物（uploads 通过共享卷挂载，见 docker-compose.yml）
COPY --from=assets /build/h5/dist/ /var/www/html/h5/dist/
COPY --from=assets /build/admin/dist/ /var/www/html/admin/dist/
