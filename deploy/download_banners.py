#!/usr/bin/env python3
"""Download banner images for nongxian-mall."""
import os
import urllib.request
import ssl
import time

BANNERS = [
    ("banner1", "farmers market fresh produce"),
    ("banner2", "rural revitalization agriculture"),
    ("banner3", "party member recommended products"),
    ("banner4", "guizhou specialty products"),
    ("banner5", "group buy discount agriculture"),
]

OUTPUT_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "banner_images")
os.makedirs(OUTPUT_DIR, exist_ok=True)

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

def download(url, timeout=15):
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=timeout) as resp:
            return resp.read()
    except Exception as e:
        print(f"  Error: {e}")
        return None

for name, keyword in BANNERS:
    print(f"Downloading {name} ({keyword})...")
    encoded = urllib.parse.quote(keyword)
    data = download(f"https://loremflickr.com/800/300/{encoded},food/all")
    if data and len(data) > 1000:
        ext = "jpg"
        if b"PNG" in data[:20]: ext = "png"
        path = os.path.join(OUTPUT_DIR, f"{name}.{ext}")
        with open(path, "wb") as f:
            f.write(data)
        print(f"  Saved {path} ({len(data)} bytes)")
    else:
        # Fallback to picsum
        data = download(f"https://picsum.photos/seed/{name}/800/300")
        if data and len(data) > 1000:
            path = os.path.join(OUTPUT_DIR, f"{name}.jpg")
            with open(path, "wb") as f:
                f.write(data)
            print(f"  Saved {path} ({len(data)} bytes) [fallback]")
        else:
            print(f"  FAILED")
    time.sleep(0.5)

print("\nDone!")
