#!/usr/bin/env python3
"""Check frontend API config and fix issues"""
import paramiko
import os
import time
import re

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
        print(f"  {out.strip()[:500]}")
    if err.strip():
        print(f"  ERR: {err.strip()[:300]}")
    return out, err

# 1. Check H5 API config in JS
print("="*60)
print("1. 检查 H5 前端 API 配置")
print("="*60)

# Read the main JS file and search for API config
stdin, stdout, stderr = ssh.exec_command('cat /var/www/nongxian-mall/h5/assets/index-ChgVIL5V.js | head -c 50000')
h5_js = stdout.read().decode()

# Search for common API patterns
patterns = [
    r'baseURL\s*[:=]\s*["\']([^"\']+)["\']',
    r'apiBase\s*[:=]\s*["\']([^"\']+)["\']',
    r'prefix\s*[:=]\s*["\']([^"\']+)["\']',
    r'axios\.create\(\{[^}]*baseURL\s*:\s*["\']([^"\']+)["\']',
    r'fetch\(["\']([^"\']+/api)',
    r'url\s*:\s*["\']([^"\']+/api)',
]

for pat in patterns:
    matches = re.findall(pat, h5_js)
    if matches:
        print(f"  Pattern '{pat[:40]}...': {matches[:3]}")

# Also check for hardcoded URLs
url_matches = re.findall(r'["\']([^"\']*?/api/[^"\']*)["\']', h5_js)
if url_matches:
    print(f"  API URLs found: {list(set(url_matches))[:5]}")

# 2. Check Admin API config
print("\n" + "="*60)
print("2. 检查 Admin 前端 API 配置")
print("="*60)

stdin, stdout, stderr = ssh.exec_command('cat /var/www/nongxian-mall/admin/assets/index-hXTHyTMy.js | head -c 50000')
admin_js = stdout.read().decode()

for pat in patterns:
    matches = re.findall(pat, admin_js)
    if matches:
        print(f"  Pattern '{pat[:40]}...': {matches[:3]}")

url_matches = re.findall(r'["\']([^"\']*?/admin/[^"\']*)["\']', admin_js)
if url_matches:
    print(f"  Admin API URLs: {list(set(url_matches))[:5]}")

# 3. Check if there's a config file
print("\n" + "="*60)
print("3. 检查前端配置文件")
print("="*60)
run('ls /var/www/nongxian-mall/h5/assets/*.js | head -5', 'H5 JS files')
run('grep -l "baseURL\\|apiBase\\|VITE_API" /var/www/nongxian-mall/h5/assets/*.js 2>/dev/null | head -3', 'H5 config files')

# 4. Check if the issue is CORS or network
print("\n" + "="*60)
print("4. 测试 API 直接访问")
print("="*60)
run('curl -s http://localhost/api/home', 'H5首页API')
run('curl -s http://localhost/api/categories', '分类API')

# 5. Check if there's a vite.config that sets base
print("\n" + "="*60)
print("5. 检查 Vite 构建配置")
print("="*60)
stdin, stdout, stderr = ssh.exec_command('cat /var/www/nongxian-mall/h5/assets/index.html 2>/dev/null || echo "no index.html"')
print(f"  {stdout.read().decode()[:200]}")

sftp.close()
ssh.close()
