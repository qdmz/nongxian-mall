#!/usr/bin/env python3
"""Fix Nginx config: proper alias + PHP routing"""
import paramiko
import os
import time

PASSWORD = os.environ.get('DEPLOY_PWD', 'thanks12A#')
SERVER = 'qiniu.ypvps.com'

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
        print(f"  {out.strip()[:300]}")
    if err.strip():
        print(f"  ERR: {err.strip()[:300]}")
    return out, err

# Remove old conflicting config
run('rm -f /etc/nginx/sites-enabled/nongxian-mall.conf', '删除旧配置')

# Write proper nginx config
nginx_conf = """server {
    listen 80;
    server_name qiniu.ypvps.com;

    # H5 前端 (用户端)
    root /var/www/nongxian-mall/h5;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Admin 前端 (管理后台)
    location /manage/ {
        alias /var/www/nongxian-mall/admin/;
        if (!-f $request_filename) {
            rewrite ^ /manage/index.html last;
        }
    }

    # API 路由
    location /api/ {
        alias /var/www/nongxian-mall/api/;
        if (!-f $request_filename) {
            rewrite ^ /api/index.php last;
        }
        location ~ \\.php$ {
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }

    # Admin API 路由
    location /admin/ {
        alias /var/www/nongxian-mall/api/;
        if (!-f $request_filename) {
            rewrite ^ /admin/index.php last;
        }
        location ~ \\.php$ {
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }

    # 静态文件缓存
    location ~* \\.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
"""

with sftp.open('/etc/nginx/sites-available/nongxian-mall', 'w') as f:
    f.write(nginx_conf)
print("✓ Nginx config written")

run('ln -sf /etc/nginx/sites-available/nongxian-mall /etc/nginx/sites-enabled/nongxian-mall', '启用站点')
run('nginx -t 2>&1', '测试配置')
run('nginx -s reload 2>&1', '重载Nginx')

time.sleep(1)

# Test
print("\n" + "="*50)
print("测试结果")
print("="*50)

run('curl -s -o /dev/null -w "状态码: %{http_code}" http://localhost/', 'GET / (H5前端)')
run('curl -s http://localhost/ | head -5', 'GET / body')

run('curl -s -o /dev/null -w "状态码: %{http_code}" http://localhost/api/', 'GET /api/')
run('curl -s http://localhost/api/', 'GET /api/ body')

run('curl -s -o /dev/null -w "状态码: %{http_code}" http://localhost/api/products', 'GET /api/products')
run('curl -s http://localhost/api/products', 'GET /api/products body')

run('curl -s -o /dev/null -w "状态码: %{http_code}" http://localhost/manage/', 'GET /manage/')
run('curl -s http://localhost/manage/ | head -5', 'GET /manage/ body')

run('curl -s -o /dev/null -w "状态码: %{http_code}" http://localhost/admin/auth/login', 'GET /admin/auth/login')

sftp.close()
ssh.close()
print("\n✅ Nginx修复完成！")
