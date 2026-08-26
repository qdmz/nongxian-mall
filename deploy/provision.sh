#!/bin/bash
# 田冲助农商城 - 服务器端部署配置脚本
# 在 qiniu.ypvps.com 上执行
set -e

DB_NAME="nongxian_mall"
DB_USER="nongxian"
DB_PASS="NxMall2026Secure"
SITE_ROOT="/var/www/nongxian"

echo "=========================================="
echo "  田冲助农商城 - 生产部署配置"
echo "=========================================="

echo ""
echo "=== [1/8] 确认环境 ==="
nginx -v 2>&1
php -v 2>&1 | head -1
mysql --version 2>&1
echo "PHP-FPM socket:" && ls -la /run/php/php8.3-fpm.sock 2>/dev/null || echo "WARN: php8.3-fpm.sock not found"

echo ""
echo "=== [2/8] 配置 MySQL ==="
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;
DROP USER IF EXISTS '${DB_USER}'@'localhost';
CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
echo "MySQL 用户 ${DB_USER} 已创建"

echo ""
echo "=== [3/8] 导入数据库 ==="
mysql -u root ${DB_NAME} < ${SITE_ROOT}/sql/nongxian_mall.sql
echo "SQL 导入完成"
mysql -u root ${DB_NAME} -e "SHOW TABLES;" | wc -l
echo "张表"

echo ""
echo "=== [4/8] 更新管理员密码 ==="
ADMIN_HASH=$(php -r "echo password_hash('admin123456', PASSWORD_BCRYPT);")
mysql -u root ${DB_NAME} -e "UPDATE admin_users SET password='${ADMIN_HASH}' WHERE username='admin';"
echo "管理员密码已更新 (admin / admin123456)"

echo ""
echo "=== [5/8] 覆盖生产配置 ==="
cp ${SITE_ROOT}/deploy/database.production.php ${SITE_ROOT}/api/config/database.php
echo "database.php 已覆盖为生产配置"
# 关闭调试模式
rm -f ${SITE_ROOT}/api/config/debug.lock
echo "调试模式已关闭"

echo ""
echo "=== [6/8] 配置 Nginx ==="
cp ${SITE_ROOT}/deploy/nginx-nongxian.conf /etc/nginx/sites-available/nongxian
ln -sf /etc/nginx/sites-available/nongxian /etc/nginx/sites-enabled/nongxian
rm -f /etc/nginx/sites-enabled/default
nginx -t 2>&1
echo "Nginx 配置测试通过"

echo ""
echo "=== [7/8] 权限配置 ==="
mkdir -p ${SITE_ROOT}/api/public/uploads
chown -R www-data:www-data ${SITE_ROOT}/api/public/uploads
chmod -R 755 ${SITE_ROOT}
chmod -R 775 ${SITE_ROOT}/api/public/uploads
echo "权限配置完成"

echo ""
echo "=== [8/8] 重启服务 ==="
systemctl restart php8.3-fpm
systemctl restart nginx
systemctl enable nginx php8.3-fpm 2>/dev/null
echo "服务已重启"

echo ""
echo "=========================================="
echo "  部署配置完成！"
echo "=========================================="
echo ""
echo "验证："
echo "  H5:    https://qiniu.ypvps.com/"
echo "  管理:  https://qiniu.ypvps.com/manage/"
echo "  API:   https://qiniu.ypvps.com/api/products"
echo "  账号:  admin / admin123456"
