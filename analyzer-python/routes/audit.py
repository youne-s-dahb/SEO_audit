import asyncio

from fastapi import APIRouter
from urllib.parse import urlparse
import re       # <--- ضروري يكون هنا
import httpx    # <--- ضروري يكون هنا
from bs4 import BeautifulSoup # <--- ضروري يكون هنا
import json
from services.pageSpeed import get_pagespeed_data

router = APIRouter()


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



        return payload
    
    full_data = await get_pagespeed_data(url)
    desktop_data = await get_pagespeed_data(
        url,
        "desktop"
    )
    lcp = (
        full_data.get("metrics", {})
        .get("largest_contentful_paint", "0 s")
        .replace("\xa0", "")
        .replace("s", "")
        .strip()
    )
    language_code, country_code = extract_language_and_country(url)
    page_load_time_ms = int(float(lcp) * 1000)
    global_score = int(
        (
            full_data.get("performance_score", 0)
            + desktop_data.get("performance_score", 0)
            + full_data.get("accessibility_score", 0)
            + full_data.get("best_practices_score", 0)
            + full_data.get("seo_score", 0)
        ) / 5
    )
    payload = {
        "status": "completed",
        "global_score": global_score,
        "pagespeed_desktop_score": desktop_data.get("performance_score"),
        "pagespeed_mobile_score": full_data.get("performance_score"),
        "has_robots_txt": full_data.get("has_robots_txt"),
        "has_sitemap_xml": full_data.get("has_sitemap_xml"),
        "score_color":
            "green" if full_data.get("global_score", 0) >= 90
            else "orange" if full_data.get("global_score", 0) >= 50
            else "red",

        "is_https": url.startswith("https://"),
        "is_mobile_friendly": full_data.get("is_mobile_friendly"),
        "page_load_time_ms": page_load_time_ms,

        "accessibility_score": full_data.get("accessibility_score"),
        "best_practices_score": full_data.get("best_practices_score"),
        "seo_score": full_data.get("seo_score"),

        "metrics": full_data.get("metrics"),
        "recommendations": full_data.get("recommendations"),
        "language_code": language_code,
        "country_code": country_code,
        "url": url
    }

  

    return payload
def extract_language_and_country(url: str):
    """
    دالة كتجيب language_code و country_code من الموقع مباشرة
    """
    language_code = "fr"  # Default
    country_code = "MA"   # Default

    # 1. استخراج country_code من الامتداد ديال الدومين (TLD)
    parsed_url = urlparse(url)
    netloc = parsed_url.netloc.lower()
    
    # تحيد www إيلا كانت
    if netloc.startswith("www."):
        netloc = netloc[4:]
        
    # مطابقة الامتدادات الشهيرة بحال .ma, .fr, .com (إلا كانت شي حاجة بحال .co.uk الخ)
    tld_match = re.search(r'\.([a-z]{2})$', netloc)
    if tld_match:
        country_code = tld_match.group(1).upper()

    # 2. محاولة جلب اللغة الحقيقية من الصفحة (HTML lang attribute)
    try:
        # كنزيدو headers باش الموقع ما يبلغيش علينا كـ Bot
        headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"}
        with httpx.Client(timeout=5.0, follow_redirects=True, headers=headers) as client:
            response = client.get(url)
            if response.status_code == 200:
                soup = BeautifulSoup(response.text, 'html.parser')
                html_tag = soup.find('html')
                if html_tag and html_tag.has_attr('lang'):
                    lang = html_tag['lang'].strip()
                    # غالبا كيكون مكتوب بحال "fr-FR" أو "ar-MA" حنا بغينا غير أول جوج حروف مثلاً "fr" ولا "ar"
                    if len(lang) >= 2:
                        language_code = lang[:2].lower()
                        # وإلا بغيتي تاخد حتى البلد من lang إيلا كان مقسوم بـ dash بحال fr-MA
                        if '-' in lang or '_' in lang:
                            parts = re.split(r'[-_]', lang)
                            if len(parts) > 1 and len(parts[1]) == 2:
                                country_code = parts[1].upper()
    except Exception as e:
        # إيلا وقع شي مشكل فالكونيكسيو للموقع، كيبقى غادي بالقيم الافتراضية
        pass

    return language_code, country_code
@router.get("/test")
async def test(url: str):
    full_data = await get_pagespeed_data(url)
    print(full_data.get("recommendations"))
    return full_data