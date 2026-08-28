#!/usr/bin/env python3
"""Upload all admin assets with reconnection handling."""
import os
import sys
import paramiko
import time

HOST = "qiniu.ypvps.com"
USER = "root"
PWD = os.environ.get("DEPLOY_PWD")
REMOTE_BASE = "/var/www/nongxian-mall/admin/assets"
LOCAL_DIR = "dist/assets"

if not PWD:
    print("Set DEPLOY_PWD env var!")
    sys.exit(1)

def connect():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, username=USER, password=PWD, timeout=30)
    return client

def main():
    files = sorted(os.listdir(LOCAL_DIR))
    print(f"Uploading {len(files)} files...")
    
    uploaded = 0
    errors = []
    client = connect()
    sftp = client.open_sftp()
    
    for i, f in enumerate(files):
        local = os.path.join(LOCAL_DIR, f)
        remote = f"{REMOTE_BASE}/{f}"
        try:
            sftp.put(local, remote)
            uploaded += 1
            print(f"[{i+1}/{len(files)}] {f} OK")
        except Exception as e:
            print(f"[{i+1}/{len(files)}] {f} FAIL: {e} (retrying)")
            # Retry with new connection
            try:
                sftp.close()
                client.close()
            except:
                pass
            time.sleep(2)
            client = connect()
            sftp = client.open_sftp()
            try:
                sftp.put(local, remote)
                uploaded += 1
                print(f"[{i+1}/{len(files)}] {f} OK (retry)")
            except Exception as e2:
                print(f"[{i+1}/{len(files)}] {f} FAIL: {e2}")
                errors.append((f, str(e2)))
    
    sftp.close()
    client.close()
    
    print(f"\nUploaded: {uploaded}/{len(files)}")
    if errors:
        print(f"Errors: {len(errors)}")

if __name__ == "__main__":
    main()
