import asyncio
import re
import time

import httpx

from urllib.parse import urlparse

from fastapi import APIRouter
from bs4 import BeautifulSoup

from services.pageSpeed import get_pagespeed_data


router = APIRouter()


# =========================================================
# HTTP SETTINGS
# =========================================================

USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
    "AppleWebKit/537.36 "
    "(KHTML, like Gecko) "
    "Chrome/151.0 Safari/537.36"
)

HTTP_TIMEOUT = httpx.Timeout(
    4.0,
    connect=2.0,
    read=4.0,
    write=4.0,
    pool=2.0
)


# =========================================================
# VALIDATE URL
# =========================================================

def is_valid_url(url: str) -> bool:

    try:

        parsed = urlparse(url)

        return (
            parsed.scheme in ("http", "https")
            and bool(parsed.netloc)
        )

    except Exception:

        return False


# =========================================================
# EXTRACT LANGUAGE + COUNTRY
# =========================================================

async def extract_language_and_country(url: str):

    language_code = "fr"
    country_code = "MA"

    try:

        parsed_url = urlparse(url)

        netloc = parsed_url.netloc.lower()

        # Remove port
        netloc = netloc.split(":")[0]

        # Remove www
        if netloc.startswith("www."):
            netloc = netloc[4:]

        # -------------------------------------------------
        # COUNTRY FROM DOMAIN
        # -------------------------------------------------

        tld_match = re.search(
            r"\.([a-z]{2})$",
            netloc
        )

        if tld_match:

            country_code = (
                tld_match.group(1).upper()
            )

        # -------------------------------------------------
        # REQUEST HTML
        # -------------------------------------------------

        headers = {
            "User-Agent": USER_AGENT,
            "Accept": "text/html,application/xhtml+xml",
        }

        async with httpx.AsyncClient(
            timeout=HTTP_TIMEOUT,
            follow_redirects=True,
            headers=headers
        ) as client:

            response = await client.get(url)

        # -------------------------------------------------
        # STATUS
        # -------------------------------------------------

        if response.status_code != 200:

            return (
                language_code,
                country_code
            )

        # -------------------------------------------------
        # PARSE HTML
        # -------------------------------------------------

        soup = BeautifulSoup(
            response.text,
            "html.parser"
        )

        html_tag = soup.find("html")

        if not html_tag:

            return (
                language_code,
                country_code
            )

        lang = html_tag.get("lang")

        if not lang:

            return (
                language_code,
                country_code
            )

        lang = lang.strip()

        # -------------------------------------------------
        # LANGUAGE
        # -------------------------------------------------

        if len(lang) >= 2:

            language_code = (
                lang[:2].lower()
            )

        # -------------------------------------------------
        # COUNTRY FROM LANG
        # -------------------------------------------------

        if "-" in lang:

            parts = lang.split("-")

            if (
                len(parts) > 1
                and len(parts[1]) == 2
            ):

                country_code = (
                    parts[1].upper()
                )

        elif "_" in lang:

            parts = lang.split("_")

            if (
                len(parts) > 1
                and len(parts[1]) == 2
            ):

                country_code = (
                    parts[1].upper()
                )

    except Exception as e:

        print(
            "LANGUAGE DETECTION ERROR:",
            str(e)
        )

    return (
        language_code,
        country_code
    )


# =========================================================
# SAFE SCORE
# =========================================================

def safe_score(data, key):

    try:

        if not isinstance(data, dict):

            return 0

        value = data.get(
            key,
            0
        )

        if value is None:

            return 0

        return int(
            round(
                float(value)
            )
        )

    except (
        ValueError,
        TypeError
    ):

        return 0


# =========================================================
# SCORE COLOR
# =========================================================

def get_score_color(score: int):

    if score >= 90:

        return "green"

    if score >= 50:

        return "orange"

    return "red"


# =========================================================
# LCP CONVERSION
# =========================================================

def convert_lcp_to_ms(value):

    if value is None:

        return 0

    try:

        lcp_string = str(value)

        lcp_string = (
            lcp_string
            .replace("\xa0", "")
            .strip()
            .lower()
        )

        # Example: 2500ms
        if lcp_string.endswith("ms"):

            lcp_string = (
                lcp_string[:-2]
                .strip()
            )

            return int(
                float(lcp_string)
            )

        # Example: 2.5s
        if lcp_string.endswith("s"):

            lcp_string = (
                lcp_string[:-1]
                .strip()
            )

            return int(
                float(lcp_string) * 1000
            )

        # Unknown numeric format
        return int(
            float(lcp_string) * 1000
        )

    except (
        ValueError,
        TypeError
    ):

        return 0


# =========================================================
# MAIN AUDIT
#
# GET /audit?url=https://example.com
# =========================================================

@router.get("/audit")
async def audit_url(url: str):

    # =====================================================
    # START TIMER
    # =====================================================

    total_start = time.perf_counter()

    # =====================================================
    # VALIDATE URL
    # =====================================================

    if not is_valid_url(url):

        return {
            "status": "failed",
            "error_message": "Invalid URL",
            "url": url
        }

    try:

        # =================================================
        # START PARALLEL TASKS
        # =================================================
        #
        # IMPORTANT:
        #
        # PageSpeed Mobile
        # PageSpeed Desktop
        # Language detection
        #
        # run at the same time.
        #
        # =================================================

        page_start = time.perf_counter()

        mobile_task = get_pagespeed_data(
            url
        )

        desktop_task = get_pagespeed_data(
            url,
            "desktop"
        )

        language_task = (
            extract_language_and_country(
                url
            )
        )

        (
            mobile_data,
            desktop_data,
            language_result
        ) = await asyncio.gather(
            mobile_task,
            desktop_task,
            language_task
        )

        page_time = (
            time.perf_counter()
            - page_start
        )

        print(
            f"PAGE SPEED + LANGUAGE TIME: "
            f"{page_time:.2f}s"
        )

    except Exception as e:

        print(
            "AUDIT ERROR:",
            str(e)
        )

        return {
            "status": "failed",
            "error_message":
                "Impossible de récupérer les données PageSpeed.",
            "details": str(e),
            "url": url
        }

    # =====================================================
    # SAFETY
    # =====================================================

    if not isinstance(
        mobile_data,
        dict
    ):

        mobile_data = {}

    if not isinstance(
        desktop_data,
        dict
    ):

        desktop_data = {}

    # =====================================================
    # LANGUAGE + COUNTRY
    # =====================================================

    try:

        language_code, country_code = (
            language_result
        )

    except Exception:

        language_code = "fr"
        country_code = "MA"

    # =====================================================
    # SCORES
    # =====================================================

    mobile_performance = safe_score(
        mobile_data,
        "performance_score"
    )

    desktop_performance = safe_score(
        desktop_data,
        "performance_score"
    )

    accessibility_score = safe_score(
        mobile_data,
        "accessibility_score"
    )

    best_practices_score = safe_score(
        mobile_data,
        "best_practices_score"
    )

    seo_score = safe_score(
        mobile_data,
        "seo_score"
    )

    # =====================================================
    # GLOBAL SCORE
    # =====================================================

    global_score = int(
        round(
            (
                desktop_performance
                + mobile_performance
                + accessibility_score
                + best_practices_score
                + seo_score
            ) / 5
        )
    )

    # =====================================================
    # SCORE COLOR
    # =====================================================

    score_color = get_score_color(
        global_score
    )

    # =====================================================
    # METRICS
    # =====================================================

    metrics = (
        mobile_data.get(
            "metrics"
        )
        or {}
    )

    if not isinstance(
        metrics,
        dict
    ):

        metrics = {}

    # =====================================================
    # LCP
    # =====================================================

    lcp_value = metrics.get(
        "largest_contentful_paint"
    )

    page_load_time_ms = (
        convert_lcp_to_ms(
            lcp_value
        )
    )

    # =====================================================
    # HTTPS
    # =====================================================

    is_https = (
        url.lower().startswith(
            "https://"
        )
    )

    # =====================================================
    # MOBILE FRIENDLY
    # =====================================================

    is_mobile_friendly = (
        mobile_data.get(
            "is_mobile_friendly"
        )
    )

    # =====================================================
    # ROBOTS
    # =====================================================

    has_robots_txt = (
        mobile_data.get(
            "has_robots_txt"
        )
    )

    # =====================================================
    # SITEMAP
    # =====================================================

    has_sitemap_xml = (
        mobile_data.get(
            "has_sitemap_xml"
        )
    )

    # =====================================================
    # RECOMMENDATIONS
    # =====================================================

    recommendations = (
        mobile_data.get(
            "recommendations"
        )
        or []
    )

    if not isinstance(
        recommendations,
        list
    ):

        recommendations = []

    # =====================================================
    # TOTAL TIME
    # =====================================================

    total_time = (
        time.perf_counter()
        - total_start
    )

    print(
        f"AUDIT TOTAL TIME: "
        f"{total_time:.2f}s"
    )

    # =====================================================
    # RESPONSE
    # =====================================================

    payload = {

        "status": "completed",

        "url": url,

        # -----------------------------------------------
        # GLOBAL
        # -----------------------------------------------

        "global_score":
            global_score,

        "score_color":
            score_color,

        # -----------------------------------------------
        # PAGESPEED
        # -----------------------------------------------

        "pagespeed_desktop_score":
            desktop_performance,

        "pagespeed_mobile_score":
            mobile_performance,

        # -----------------------------------------------
        # SEO
        # -----------------------------------------------

        "seo_score":
            seo_score,

        # -----------------------------------------------
        # ACCESSIBILITY
        # -----------------------------------------------

        "accessibility_score":
            accessibility_score,

        # -----------------------------------------------
        # BEST PRACTICES
        # -----------------------------------------------

        "best_practices_score":
            best_practices_score,

        # -----------------------------------------------
        # TECHNICAL
        # -----------------------------------------------

        "has_robots_txt":
            has_robots_txt,

        "has_sitemap_xml":
            has_sitemap_xml,

        "is_https":
            is_https,

        "is_mobile_friendly":
            is_mobile_friendly,

        "page_load_time_ms":
            page_load_time_ms,

        # -----------------------------------------------
        # LANGUAGE
        # -----------------------------------------------

        "language_code":
            language_code,

        "country_code":
            country_code,

        # -----------------------------------------------
        # METRICS
        # -----------------------------------------------

        "metrics":
            metrics,

        # -----------------------------------------------
        # RECOMMENDATIONS
        # -----------------------------------------------

        "recommendations":
            recommendations,

        # -----------------------------------------------
        # DEBUG
        # -----------------------------------------------

        "audit_time_seconds":
            round(total_time, 2)
    }

    return payload


# =========================================================
# TEST PAGE SPEED
#
# GET /test?url=https://example.com
# =========================================================

@router.get("/test")
async def test(url: str):

    if not is_valid_url(url):

        return {
            "status": "failed",
            "error_message": "Invalid URL",
            "url": url
        }

    start = time.perf_counter()

    try:

        data = await get_pagespeed_data(
            url
        )

        elapsed = (
            time.perf_counter()
            - start
        )

        print(
            f"TEST PAGESPEED TIME: "
            f"{elapsed:.2f}s"
        )

        print(
            "PAGESPEED DATA:"
        )

        print(data)

        print(
            "RECOMMENDATIONS:"
        )

        print(
            data.get(
                "recommendations"
            )
        )

        return {
            **data,
            "test_time_seconds":
                round(elapsed, 2)
        }

    except Exception as e:

        elapsed = (
            time.perf_counter()
            - start
        )

        return {
            "status": "failed",
            "error_message":
                "PageSpeed error",
            "details": str(e),
            "test_time_seconds":
                round(elapsed, 2)
        }