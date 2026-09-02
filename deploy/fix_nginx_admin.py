#!/usr/bin/env python3
"""修复管理后台 Nginx 配置"""
import os
import sys
import time
import paramiko

HOST = os.environ.get("DEPLOY_HOST", "qiniu.ypvps.com")
USER = os.environ.get("DEPLOY_USER", "root")
PWD = os.environ.get("DEPLOY_PWD", "thanks12A#")


def connect():
    for attempt in range(5):
        try:
            c = paramiko.SSHClient()
            c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            c.connect(HOST, username=USER, password=PWD, timeout=30,
                      allow_agent=False, look_for_keys=False)
            return c
        except Exception as e:
            print(f"Attempt {attempt+1} failed: {e}")
            time.sleep(5)
    return None


def main():
    c = connect()
    if not c:
        print("Failed to connect")
        sys.exit(1)

    # 写入正确的 nginx 配置
    nginx_config = """server {
    listen 80;
    server_name qiniu.ypvps.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name qiniu.ypvps.com;

    ssl_certificate /game/certs/cert.pem;
    ssl_certificate_key /game/certs/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root /var/www/nongxian-mall/h5;
    index index.html;

    # 全局 CORS 头
    add_header Access-Control-Allow-Origin * always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Content-Type, Authorization, Token, X-Requested-With" always;

    # 上传文件
    location /uploads/ {
        alias /var/www/nongxian-mall/api/public/uploads/;
        expires 30d;
        add_header Cache-Control "public, immutable";
        add_header Access-Control-Allow-Origin *;
    }

    # H5 前端 (用户端)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # H5 静态资源缓存
    location ~* \\.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        add_header Access-Control-Allow-Origin *;
    }

    # Admin 前端 (管理后台)
    location /manage {
        alias /var/www/nongxian-mall/admin;
        try_files $uri $uri/ /manage/index.html;

        location ~* \\.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            alias /var/www/nongxian-mall/admin;
            expires 30d;
            add_header Cache-Control "public, immutable";
            add_header Access-Control-Allow-Origin *;
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
}
"""

    sftp = c.open_sftp()
    with sftp.file('/etc/nginx/sites-enabled/nongxian-mall', 'w') as f:
        f.write(nginx_config)
    print("nginx config written")

    # 测试配置
    stdin, stdout, stderr = c.exec_command('nginx -t 2>&1', timeout=15)
    out = stdout.read().decode()
    err = stderr.read().decode()
    print(f"nginx -t: {out} {err}")

    if 'syntax is ok' in out:
        stdin, stdout, stderr = c.exec_command('systemctl reload nginx && echo RELOADED', timeout=15)
        print(f"reload: {stdout.read().decode()}")
    else:
        print("nginx config test failed!")

    sftp.close()
    c.close()


if __name__ == "__main__":
    main()
