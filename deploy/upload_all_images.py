#!/usr/bin/env python3
"""Upload all product images to server via SSH (paramiko)."""

import json
import os
import sys
import time
import paramiko

# Load download results
RESULTS_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), "product_images", "download_results.json")
with open(RESULTS_PATH, "r", encoding="utf-8") as f:
    results = json.load(f)

# Server config
HOST = os.environ.get("DEPLOY_HOST", "qiniu.ypvps.com")
USER = os.environ.get("DEPLOY_USER", "root")
PWD = os.environ.get("DEPLOY_PWD")
REMOTE_BASE = "/var/www/nongxian-mall/api/public/uploads/products"

if not PWD:
    print("Set DEPLOY_PWD env var!")
    sys.exit(1)

def main():
    # Connect SSH
    print(f"Connecting to {HOST}...")
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, username=USER, password=PWD, timeout=30)
    
    # Ensure remote directory exists
    client.exec_command(f"mkdir -p {REMOTE_BASE}")
    
    # Open SFTP
    sftp = client.open_sftp()
    
    uploaded = 0
    errors = []
    
    for pid_str, info in results.items():
        local_path = info["local_path"]
        filename = info["filename"]
        ext = info["ext"]
        
        # Upload cover image
        remote_path = f"{REMOTE_BASE}/{filename}.{ext}"
        try:
            print(f"  Uploading {filename}.{ext}...", end=" ")
            sftp.put(local_path, remote_path)
            print("OK")
            uploaded += 1
        except Exception as e:
            print(f"FAIL: {e}")
            errors.append((filename, str(e)))
        
        time.sleep(0.2)
    
    sftp.close()
    client.close()
    
    print(f"\n{'='*60}")
    print(f"Uploaded: {uploaded}/{len(results)}")
    if errors:
        print(f"Errors: {len(errors)}")
        for name, err in errors:
            print(f"  {name}: {err}")
    else:
        print("All uploads successful!")
    
    return uploaded

if __name__ == "__main__":
    main()
