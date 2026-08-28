#!/usr/bin/env python3
"""
Download product images for nongxian-mall from free image sources.
Uses Unsplash/Pixabay free images or generates colored placeholders.
"""

import json
import os
import sys
import urllib.request
import urllib.parse
import ssl
import time

# Product mapping: id -> (name, english_keyword, filename_base)
PRODUCTS = {
    1: ("遵义朝天椒", "chili pepper red", "chili"),
    2: ("安顺山药", "yam vegetable", "yam"),
    3: ("贵阳折耳根", "herbstoutia", "zheergen"),
    4: ("毕节大蒜", "garlic", "garlic"),
    5: ("镇宁樱桃", "cherry red", "cherry"),
    6: ("修文猕猴桃", "kiwi fruit", "kiwi"),
    7: ("从江香柚", "pomelo fruit", "pomelo"),
    8: ("罗甸火龙果", "dragon fruit", "dragonfruit"),
    9: ("湄潭翠芽", "green tea leaves", "tea"),
    10: ("从江香猪", "pig pork", "xiangzhu"),
    11: ("威宁洋芋", "potato", "potato"),
    12: ("贵州红米", "red rice", "redrice"),
    13: ("绿壳蛋", "green shell egg", "greenegg"),
    14: ("贵州黄牛", "yellow cattle beef", "cattle"),
    15: ("黔东南土鸡", "free range chicken", "chicken"),
    16: ("习酒鸡蛋", "egg", "xijiuegg"),
    17: ("织金竹荪", "bamboo mushroom", "zhusun"),
    18: ("野生黑木耳", "black wood ear mushroom", "woodear"),
    19: ("大方天麻", "gastrodia herb", "tianma"),
    20: ("贵州香菇", "shiitake mushroom", "mushroom"),
    21: ("贵州茅台镇酱香酒", "baijiu liquor bottle", "maotai"),
    22: ("老干妈辣椒酱", "chili sauce bottle", "laoganma"),
    23: ("刺梨干", "rosa roxburghii dried", "ciligan"),
    24: ("贵州蜡染", "batik fabric", "lalan"),
    25: ("红色助农·爱心礼盒A", "gift box red", "redbox1"),
    26: ("红色助农·爱心礼盒B", "gift box premium", "redbox2"),
    27: ("党员推荐·有机大米", "organic rice bag", "redrice_party"),
    28: ("助农山茶油", "tea oil bottle", "teaoil"),
}

OUTPUT_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "product_images")
os.makedirs(OUTPUT_DIR, exist_ok=True)

def download_with_urllib(url, timeout=15):
    """Download file using urllib with SSL context."""
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=timeout) as resp:
            return resp.read()
    except Exception as e:
        print(f"  urllib failed: {e}")
        return None

def search_unsplash_image(keyword):
    """Try to get an image URL from Unsplash source (free, no API key needed)."""
    # Unsplash source API - returns redirect to actual image
    encoded = urllib.parse.quote(keyword)
    url = f"https://source.unsplash.com/400x400/?{encoded}"
    return url

def search_pixabay_image(keyword):
    """Try to get an image from Pixabay (requires API key)."""
    # We'll use a direct search approach
    return None

def generate_placeholder(filename, product_name, keyword):
    """Generate a colored SVG placeholder with product name."""
    # Create a simple SVG with product initial
    colors = {
        "chili": "#e74c3c", "yam": "#d4a574", "zheergen": "#27ae60", "garlic": "#f5f5dc",
        "cherry": "#c0392b", "kiwi": "#2ecc71", "pomelo": "#f39c12", "dragonfruit": "#e91e63",
        "tea": "#27ae60", "xiangzhu": "#f8c9c9", "potato": "#d4a574", "redrice": "#c0392b",
        "greenegg": "#a8d8a8", "cattle": "#d4a574", "chicken": "#f5deb3", "xijiuegg": "#fff8dc",
        "zhusun": "#d4a574", "woodear": "#2c3e50", "tianma": "#d4a574", "mushroom": "#8b4513",
        "maotai": "#8b0000", "laoganma": "#c0392b", "ciligan": "#f39c12", "lalan": "#1a5276",
        "redbox1": "#c0392b", "redbox2": "#8b0000", "redrice_party": "#c0392b", "teaoil": "#27ae60",
    }
    color = colors.get(keyword, "#7f8c8d")
    initial = product_name[0] if product_name else "?"
    
    svg = f'''<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400">
  <rect width="400" height="400" fill="{color}"/>
  <text x="200" y="220" font-family="Arial, sans-serif" font-size="120" fill="white" text-anchor="middle" font-weight="bold">{initial}</text>
  <text x="200" y="320" font-family="Arial, sans-serif" font-size="24" fill="white" text-anchor="middle">{product_name}</text>
</svg>'''
    
    svg_path = os.path.join(OUTPUT_DIR, f"{filename}.svg")
    with open(svg_path, "w", encoding="utf-8") as f:
        f.write(svg)
    return svg_path

def download_product_image(product_id, product_name, keyword, filename):
    """Download a product image from various sources."""
    print(f"\n[{product_id}] {product_name} ({keyword})")
    
    # Try multiple image sources
    image_urls = []
    
    # Source 1: Try Unsplash (may not work in all regions)
    image_urls.append(("unsplash", f"https://source.unsplash.com/400x400/?{urllib.parse.quote(keyword)}"))
    
    # Source 2: Try loremflickr (free, reliable)
    image_urls.append(("loremflickr", f"https://loremflickr.com/400/400/{urllib.parse.quote(keyword)},food/all"))
    
    # Source 3: Try picsum with seed (reliable placeholder)
    image_urls.append(("picsum", f"https://picsum.photos/seed/{filename}/400/400"))
    
    for source_name, url in image_urls:
        print(f"  Trying {source_name}: {url[:80]}...")
        data = download_with_urllib(url)
        if data and len(data) > 1000:  # At least 1KB
            # Determine content type
            ext = "jpg"
            if b"PNG" in data[:20]:
                ext = "png"
            elif b"GIF" in data[:20]:
                ext = "gif"
            elif b"WEBP" in data[:30]:
                ext = "webp"
            
            filepath = os.path.join(OUTPUT_DIR, f"{filename}.{ext}")
            with open(filepath, "wb") as f:
                f.write(data)
            print(f"  ✓ Saved {filepath} ({len(data)} bytes)")
            return filepath, ext
        else:
            print(f"  ✗ Failed or too small ({len(data) if data else 0} bytes)")
    
    # Fallback: generate placeholder
    print(f"  Falling back to placeholder SVG")
    svg_path = generate_placeholder(filename, product_name, keyword)
    return svg_path, "svg"

def main():
    results = {}
    
    for pid, (name, keyword, filename) in PRODUCTS.items():
        filepath, ext = download_product_image(pid, name, keyword, filename)
        results[pid] = {
            "name": name,
            "keyword": keyword,
            "filename": filename,
            "local_path": filepath,
            "ext": ext,
        }
        time.sleep(0.5)  # Be polite
    
    # Save results
    results_path = os.path.join(OUTPUT_DIR, "download_results.json")
    with open(results_path, "w", encoding="utf-8") as f:
        json.dump(results, f, ensure_ascii=False, indent=2)
    
    print(f"\n{'='*60}")
    print(f"Download complete! Results saved to {results_path}")
    print(f"Images in: {OUTPUT_DIR}")
    
    # Print summary
    success = sum(1 for r in results.values() if r["ext"] != "svg")
    print(f"Successfully downloaded: {success}/{len(PRODUCTS)}")
    print(f"Placeholders: {len(PRODUCTS) - success}/{len(PRODUCTS)}")
    
    return results

if __name__ == "__main__":
    main()
