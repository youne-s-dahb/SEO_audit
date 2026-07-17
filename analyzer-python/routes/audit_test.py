import asyncio

from fastapi import APIRouter
from fastapi.concurrency import run_in_threadpool

# Importi ga3 l-functions mn l-fichie dyalk
from services.pageSpeed import get_pagespeed_data, format_simple_report
from analyzer.seo_analyzer import analyze
from services.places import check_google_maps_presence
from services.serApi import get_ranking

router = APIRouter()



@router.get("/audit")
async def audit_url(url: str):

    # ----------------------------------
    # Validation basique de l'URL
    # (analyze() dir déjà validation/SSRF b3mق, hna ghi fail-fast bkri
    # bach ma-nkhelsouch appels API mkelfin b7al PageSpeed/SERP l blast)
    # ----------------------------------

    if not url or not url.strip():
        return {
            "seo": {"success": False, "error": "URL invalide."},
            "pagespeed": {"error": "URL invalide."},
            "google_maps": {"success": False, "error": "URL invalide."},
            "serp": {"success": False, "error": "URL invalide."}
        }

    # ----------------------------------
    # Analyse locale BeautifulSoup
    # (run_in_threadpool: analyze() synchrone/blocking (requests),
    # bach ma-tbloqich l-event loop dyal FastAPI)
    # ----------------------------------

    try:
        seo_result = await run_in_threadpool(analyze, url)
    except Exception as e:
        seo_result = {
            "success": False,
            "error": f"SEO analysis failed: {str(e)}"
        }

    # ----------------------------------
    # Google PageSpeed, Google Maps, SERP API
    # (asyncio.gather: 3 appels indépendants li kaymchiw f parallèle
    # bdl séquentiel, m3a return_exceptions bach chi failure
    # ma-ywa9afch les autres)
    # ----------------------------------

    pagespeed_data, google_maps_result, serp_result = await asyncio.gather(
        get_pagespeed_data(url),
        check_google_maps_presence(url),
        # get_ranking(url),
        get_ranking(
            keyword="seo",
            site_url=url
        ),
        return_exceptions=True
    )

    # ----------------------------------
    # Google PageSpeed
    # ----------------------------------

    if isinstance(pagespeed_data, Exception):

        pagespeed_result = {"error": f"PageSpeed analysis failed: {str(pagespeed_data)}"}

    elif "error" in pagespeed_data:

        pagespeed_result = pagespeed_data

    else:

        pagespeed_result = format_simple_report(
            pagespeed_data
        )

    # ----------------------------------
    # Google Maps
    # ----------------------------------

    if isinstance(google_maps_result, Exception):
        google_maps_result = {
            "success": False,
            "error": f"Google Maps check failed: {str(google_maps_result)}"
        }

    # ----------------------------------
    # SERP API
    # ----------------------------------

    if isinstance(serp_result, Exception):
        serp_result = {
            "success": False,
            "error": f"SERP ranking check failed: {str(serp_result)}"
        }

    # ----------------------------------
    # Fusion
    # ----------------------------------

    return {

        "seo": seo_result,

        "pagespeed": pagespeed_result,

        "google_maps": google_maps_result,

        "serp": serp_result

    }