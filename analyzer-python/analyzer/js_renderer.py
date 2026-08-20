# Sert à re-télécharger une page avec un vrai navigateur headless (Playwright)
# qui exécute le JavaScript, pour récupérer le contenu réel des sites
# React/Vue/Next.js que requests ne peut pas toujours voir.

import time
from concurrent.futures import ThreadPoolExecutor, TimeoutError as FuturesTimeoutError
from urllib.parse import urlparse

from playwright.sync_api import (
    sync_playwright,
    TimeoutError as PlaywrightTimeoutError
)

from analyzer.utils import (
    validate_url,
    normalize_url
)


# =========================================================
# TIMEOUT
# =========================================================

# Timeout maximum pour le chargement JS
# 10 secondes au lieu de 15
JS_RENDER_TIMEOUT_MS = 10000

# Taille max du HTML rendu : 10 MB
MAX_CONTENT_SIZE = 10 * 1024 * 1024

USER_AGENT = "SEO Audit Bot/1.0"


# =========================================================
# SSRF PROTECTION
# =========================================================

def is_private_ip(hostname: str) -> bool:
    """
    Vérifie l'IP avant de lancer Playwright.
    Import local pour éviter une dépendance circulaire.
    """

    from analyzer.seo_analyzer import is_private_ip as _check

    return _check(hostname)


# =========================================================
# PLAYWRIGHT RENDER
# =========================================================

def _render_page_sync(url: str) -> dict:

    try:

        # -------------------------------------------------
        # VALIDATION URL
        # -------------------------------------------------

        if not validate_url(url):

            return {
                "success": False,
                "error": "Invalid URL."
            }

        # -------------------------------------------------
        # NORMALIZE URL
        # -------------------------------------------------

        url = normalize_url(url)

        parsed = urlparse(url)

        # -------------------------------------------------
        # SSRF
        # -------------------------------------------------

        if is_private_ip(parsed.hostname):

            return {
                "success": False,
                "error": "Private IPs are not allowed."
            }

        # -------------------------------------------------
        # TIMER
        # -------------------------------------------------

        start = time.perf_counter()

        # -------------------------------------------------
        # PLAYWRIGHT
        # -------------------------------------------------

        with sync_playwright() as p:

            browser = p.chromium.launch(
                headless=True,
                args=[
                    "--no-sandbox",
                    "--disable-dev-shm-usage",
                    "--disable-gpu",
                ]
            )

            try:

                context = browser.new_context(
                    user_agent=USER_AGENT
                )

                page = context.new_page()

                # Timeout global Playwright
                page.set_default_timeout(
                    JS_RENDER_TIMEOUT_MS
                )

                # -------------------------------------------------
                # LOAD PAGE
                # -------------------------------------------------

                response = page.goto(
                    url,
                    wait_until="domcontentloaded",
                    timeout=JS_RENDER_TIMEOUT_MS
                )

                # -------------------------------------------------
                # NO RESPONSE
                # -------------------------------------------------

                if response is None:

                    return {
                        "success": False,
                        "error":
                            "No response received from the page."
                    }

                # -------------------------------------------------
                # WAIT FOR JS HYDRATION
                # -------------------------------------------------

                # Petit délai seulement.
                # On ne fait PAS networkidle.
                page.wait_for_timeout(500)

                # -------------------------------------------------
                # RESPONSE DATA
                # -------------------------------------------------

                status_code = response.status

                final_url = page.url

                html_content = page.content()

                # -------------------------------------------------
                # SIZE LIMIT
                # -------------------------------------------------

                if (
                    len(
                        html_content.encode("utf-8")
                    )
                    > MAX_CONTENT_SIZE
                ):

                    return {
                        "success": False,
                        "error": "Page too large."
                    }

            finally:

                browser.close()

        # -------------------------------------------------
        # RESPONSE TIME
        # -------------------------------------------------

        end = time.perf_counter()

        response_time = round(
            (end - start) * 1000,
            2
        )

        # -------------------------------------------------
        # SUCCESS
        # -------------------------------------------------

        return {

            "success": True,

            "html":
                html_content,

            "status_code":
                status_code,

            "response_time_ms":
                response_time,

            "content_type":
                "text/html",

            "final_url":
                final_url,

            "rendered_with_js":
                True

        }

    # =====================================================
    # PLAYWRIGHT TIMEOUT
    # =====================================================

    except PlaywrightTimeoutError:

        return {

            "success": False,

            "error":
                "JS rendering timeout."

        }

    # =====================================================
    # OTHER ERROR
    # =====================================================

    except Exception as e:

        return {

            "success": False,

            "error":
                f"Unexpected error during JS rendering: {str(e)}"

        }


# =========================================================
# PUBLIC FUNCTION
# =========================================================

def render_page_with_js(url: str) -> dict:

    try:

        # -------------------------------------------------
        # Thread séparé
        # -------------------------------------------------

        with ThreadPoolExecutor(
            max_workers=1
        ) as executor:

            future = executor.submit(
                _render_page_sync,
                url
            )

            # -------------------------------------------------
            # Maximum 12 secondes
            # -------------------------------------------------

            return future.result(
                timeout=12
            )

    except FuturesTimeoutError:

        return {

            "success": False,

            "error":
                "JS rendering timeout."

        }

    except Exception as e:

        return {

            "success": False,

            "error":
                f"Thread execution error during JS rendering: {str(e)}"

        }


# =========================================================
# DETECT EMPTY JS SHELL
# =========================================================

def looks_like_empty_shell(
    analysis_result: dict
) -> bool:

    try:

        word_count = (
            analysis_result.get(
                "word_count",
                0
            )
            or 0
        )

        headings_count = (
            analysis_result.get(
                "headings_count",
                0
            )
            or 0
        )

        images_count = (
            analysis_result.get(
                "images_count",
                0
            )
            or 0
        )

        links_count = (
            analysis_result.get(
                "links_count",
                0
            )
            or 0
        )

        return (

            word_count < 50

            and headings_count == 0

            and images_count == 0

            and links_count == 0

        )

    except Exception:

        return False