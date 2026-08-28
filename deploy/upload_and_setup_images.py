#!/usr/bin/env python3
"""Upload product images to server and set up all image references."""

import json
import os
import sys
import time
import paramiko

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PRODUCT_DIR = os.path.join(SCRIPT_DIR, "product_images")

# Load download results
with open(os.path.join(PRODUCT_DIR, "download_results.json"), "r", encoding="utf-8") as f:
    results = json.load(f)

# Server config
HOST = os.environ.get("DEPLOY_HOST", "qiniu.ypvps.com")
USER = os.environ.get("DEPLOY_USER", "root")
PWD = os.environ.get("DEPLOY_PWD")
REMOTE_BASE = "/var/www/nongxian-mall/api/public/uploads/products"

if not PWD:
    print("ERROR: Set DEPLOY_PWD env var!")
    sys.exit(1)

# Map: product_id -> (cover_filename_base, [additional filenames])
# Based on database query
PRODUCT_IMAGE_MAP = {
    1: ("chili", ["chili1.jpg", "chili2.jpg"]),
    2: ("yam", ["yam1.jpg", "yam2.jpg"]),
    3: ("zheergen", ["zheergen1.jpg"]),
    4: ("garlic", ["garlic1.jpg"]),
    5: ("cherry", ["cherry1.jpg", "cherry2.jpg"]),
    6: ("kiwi", ["kiwi1.jpg", "kiwi2.jpg"]),
    7: ("pomelo", ["pomelo1.jpg"]),
    8: ("dragonfruit", ["dragonfruit1.jpg"]),
    9: ("tea", ["tea1.jpg", "tea2.jpg"]),
    10: ("xiangzhu", ["xiangzhu1.jpg"]),
    11: ("potato", ["potato1.jpg"]),
    12: ("redrice", ["redrice1.jpg"]),
    13: ("greenegg", ["greenegg1.jpg"]),
    14: ("cattle", ["cattle1.jpg"]),
    15: ("chicken", ["chicken1.jpg"]),
    16: ("xijiuegg", ["xijiuegg1.jpg"]),
    17: ("zhusun", ["zhusun1.jpg"]),
    18: ("woodear", ["woodear1.jpg"]),
    19: ("tianma", ["tianma1.jpg"]),
    20: ("mushroom", ["mushroom1.jpg"]),
    21: ("maotai", ["maotai1.jpg", "maotai2.jpg"]),
    22: ("laoganma", ["laoganma1.jpg"]),
    23: ("ciligan", ["ciligan1.jpg"]),
    24: ("lalan", ["lalan1.jpg", "lalan2.jpg"]),
    25: ("redbox1", ["redbox1_1.jpg"]),
    26: ("redbox2", ["redbox2_1.jpg"]),
    27: ("redrice_party", ["redrice1.jpg"]),
    28: ("teaoil", ["teaoil1.jpg"]),
}

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
        pid = int(pid_str)
        filename_base = info["filename"]
        ext = info["ext"]
        local_path = info["local_path"]
        
        # Upload cover image
        cover_remote = f"{REMOTE_BASE}/{filename_base}.{ext}"
        try:
            print(f"  [{pid}] Uploading {filename_base}.{ext}...", end=" ")
            sftp.put(local_path, cover_remote)
            print("OK")
            uploaded += 1
        except Exception as e:
            print(f"FAIL: {e}")
            errors.append((filename_base, str(e)))
            continue
        
        time.sleep(0.2)
    
    sftp.close()
    
    # Now create copies for additional images on server
    print(f"\nCreating additional image copies on server...")
    for pid, (cover_base, additional_files) in PRODUCT_IMAGE_MAP.items():
        info = results.get(str(pid), {})
        ext = info.get("ext", "jpg")
        cover_file = f"{REMOTE_BASE}/{cover_base}.{ext}"
        
        for add_file in additional_files:
            add_path = f"{REMOTE_BASE}/{add_file}"
            # Copy cover to additional filename
            cmd = f"cp {cover_file} {add_path}"
            stdin, stdout, stderr = client.exec_command(cmd)
            err = stderr.read().decode().strip()
            if err:
                print(f"  [{pid}] Error copying to {add_file}: {err}")
            else:
                print(f"  [{pid}] {cover_base}.{ext} -> {add_file}")
    
    client.close()
    
    print(f"\n{'='*60}")
    print(f"Cover images uploaded: {uploaded}/{len(results)}")
    if errors:
        print(f"Errors: {len(errors)}")
        for name, err in errors:
            print(f"  {name}: {err}")
    else:
        print("All done! Images are live.")
    
    return uploaded

if __name__ == "__main__":
    main()
