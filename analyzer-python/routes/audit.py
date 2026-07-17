from fastapi import APIRouter
from urllib.parse import urlparse
import redis
import json
from services.pageSpeed import get_pagespeed_data

router = APIRouter()
r = redis.Redis(host='redis', port=6379, db=0)

def is_valid_url(url: str):
    parsed = urlparse(url)
    return parsed.scheme in ("http", "https") and parsed.netloc

@router.get("/audit")
async def audit_url(url: str):
     # Validation URL
    if not is_valid_url(url):
        payload = {
            "status": "failed",
            "error_message": "Invalid URL",
            "url": url
        }

        key = f"audit:{url}"
        r.set(key, json.dumps(payload))
        r.expire(key, 3600)

        return payload
    
    full_data = await get_pagespeed_data(url)

    lcp = (
        full_data.get("metrics", {})
        .get("largest_contentful_paint", "0 s")
        .replace("\xa0", "")
        .replace("s", "")
        .strip()
    )

    page_load_time_ms = int(float(lcp) * 1000)

    payload = {
        "status": "completed",
        "global_score": full_data.get("performance_score"),
        "pagespeed_desktop_score": full_data.get("performance_score"),
        "pagespeed_mobile_score": full_data.get("performance_score"),
        "has_robots_txt": full_data.get("has_robots_txt"),
        "has_sitemap_xml": full_data.get("has_sitemap_xml"),
        "score_color":
            "green" if full_data.get("performance_score", 0) >= 90
            else "orange" if full_data.get("performance_score", 0) >= 50
            else "red",

        "is_https": url.startswith("https://"),
        "is_mobile_friendly": full_data.get("is_fast"),
        "page_load_time_ms": page_load_time_ms,

        "accessibility_score": full_data.get("accessibility_score"),
        "best_practices_score": full_data.get("best_practices_score"),
        "seo_score": full_data.get("seo_score"),

        "metrics": full_data.get("metrics"),
        "recommendations": full_data.get("recommendations"),

        "url": url
    }

    key = f"audit:{url}"
    r.set(key, json.dumps(payload))
    r.expire(key, 3600)

    return {
        "message": "Audit completed and saved in Redis",
        "status": "success"
    }
@router.get("/test")
async def test(url: str):
    full_data = await get_pagespeed_data(url)
    return full_data