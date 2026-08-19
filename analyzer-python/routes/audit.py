import re
import httpx

from urllib.parse import urlparse

from fastapi import APIRouter
from bs4 import BeautifulSoup

from services.pageSpeed import get_pagespeed_data


router = APIRouter()


# =========================================================
# VALIDATE URL
# =========================================================

def is_valid_url(url: str):
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

def extract_language_and_country(url: str):

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
                tld_match.group(1)
                .upper()
            )

        # -------------------------------------------------
        # LANGUAGE FROM HTML
        # -------------------------------------------------

        headers = {
            "User-Agent":
                "Mozilla/5.0 "
                "(Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 "
                "(KHTML, like Gecko) "
                "Chrome/151.0 Safari/537.36"
        }

        with httpx.Client(
            timeout=10.0,
            follow_redirects=True,
            headers=headers
        ) as client:

            response = client.get(url)

            if response.status_code == 200:

                soup = BeautifulSoup(
                    response.text,
                    "html.parser"
                )

                html_tag = soup.find("html")

                if html_tag:

                    lang = html_tag.get("lang")

                    if lang:

                        lang = lang.strip()

                        # Example:
                        # fr
                        # fr-FR
                        # fr_FR
                        # ar-MA

                        if len(lang) >= 2:

                            language_code = (
                                lang[:2].lower()
                            )

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

    except Exception:

        # Keep defaults
        pass

    return (
        language_code,
        country_code
    )


# =========================================================
# SAFE SCORE
# =========================================================

def safe_score(data, key):

    try:

        value = data.get(key, 0)

        if value is None:
            return 0

        return int(round(float(value)))

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
# MAIN AUDIT
#
# GET /audit?url=https://example.com
# =========================================================

@router.get("/audit")
async def audit_url(url: str):

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
        # MOBILE
        # =================================================

        mobile_data = await get_pagespeed_data(
            url
        )

        # =================================================
        # DESKTOP
        # =================================================

        desktop_data = await get_pagespeed_data(
            url,
            "desktop"
        )

    except Exception as e:

        return {
            "status": "failed",
            "error_message":
                "Impossible de récupérer les données PageSpeed.",
            "details": str(e),
            "url": url
        }

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
    #
    # 5 critères:
    #
    # Desktop Performance
    # Mobile Performance
    # Accessibility
    # Best Practices
    # SEO
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
    # LCP
    # =====================================================

    metrics = (
        mobile_data.get("metrics")
        or {}
    )

    lcp_value = (
        metrics.get(
            "largest_contentful_paint"
        )
    )

    page_load_time_ms = 0

    if lcp_value is not None:

        try:

            # Convert to string
            lcp_string = str(
                lcp_value
            )

            lcp_string = (
                lcp_string
                .replace("\xa0", "")
                .replace("ms", "")
                .replace("s", "")
                .strip()
            )

            page_load_time_ms = int(
                float(lcp_string) * 1000
            )

        except (
            ValueError,
            TypeError
        ):

            page_load_time_ms = 0

    # =====================================================
    # LANGUAGE / COUNTRY
    # =====================================================

    language_code, country_code = (
        extract_language_and_country(url)
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
    # ROBOTS / SITEMAP
    # =====================================================

    has_robots_txt = (
        mobile_data.get(
            "has_robots_txt"
        )
    )

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
            recommendations
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

    try:

        data = await get_pagespeed_data(
            url
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

        return data

    except Exception as e:

        return {
            "status": "failed",
            "error_message":
                "PageSpeed error",
            "details": str(e)
        }