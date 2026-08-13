import os
import asyncio
import httpx
from bs4 import BeautifulSoup


async def check_robots_txt(url: str) -> bool:
    try:
        async with httpx.AsyncClient(follow_redirects=True, timeout=10) as client:
            response = await client.get(f"{url.rstrip('/')}/robots.txt")
            return response.status_code == 200
    except Exception as e:
        print("ROBOTS ERROR:", e, flush=True)
        return False


async def check_sitemap_xml(url: str) -> bool:
    try:
        async with httpx.AsyncClient(follow_redirects=True, timeout=10) as client:
            response = await client.get(f"{url.rstrip('/')}/sitemap.xml")
            return response.status_code == 200
    except Exception as e:
        print("SITEMAP ERROR:", e, flush=True)
        return False


async def check_mobile_friendly(url: str):
    try:
        async with httpx.AsyncClient(
            follow_redirects=True,
            headers={
                "User-Agent": (
                    "Mozilla/5.0 "
                    "(iPhone; CPU iPhone OS 17_0 like Mac OS X)"
                )
            }
        ) as client:
            response = await client.get(url, timeout=10)
            response.raise_for_status()

            html = response.text.lower()
            soup = BeautifulSoup(html, "html.parser")
            viewport = soup.find("meta", attrs={"name": "viewport"})

            if viewport:
                return {"mobile_score": 100, "is_mobile_friendly": True}

            return {"mobile_score": 0, "is_mobile_friendly": False}

    except Exception as e:
        print("MOBILE ERROR:", e, flush=True)
        return {"mobile_score": 0, "is_mobile_friendly": False}


async def _fetch_pagespeed(url: str, strategy: str, api_key: str):
    api_url = (
        "https://www.googleapis.com/"
        "pagespeedonline/v5/runPagespeed"
    )

    params = {
        "url": url,
        "key": api_key,
        "strategy": strategy,
        "category": [
            "performance",
            "accessibility",
            "best-practices",
            "seo",
        ],
    }

    async with httpx.AsyncClient(timeout=90) as client:
        response = await client.get(api_url, params=params)
        response.raise_for_status()
        return response.json()


def _extract_performance_score(pagespeed_json: dict) -> int:
    lighthouse = pagespeed_json.get("lighthouseResult", {})
    categories = lighthouse.get("categories", {})
    return int((categories.get("performance", {}).get("score", 0) or 0) * 100)


async def get_pagespeed_data(url: str, strategy: str = "mobile"):
    """
    - get_pagespeed_data(url)              -> appel KAML: pagespeed (strategy, default
                                               "mobile") + robots.txt + sitemap.xml +
                                               mobile-friendly + metrics + recommendations.
    - get_pagespeed_data(url, "desktop")   -> appel LGER: ghi performance_score dyal
                                               desktop, bla robots/sitemap/mobile-friendly
                                               (bach ma nkerrrouch nafss les checks jouj mrat).
    """
    api_key = os.getenv("PAGESPEED_API_KEY")

    if not api_key:
        return {"error": "PAGESPEED_API_KEY n'est pas configurée."}

    print(
        f"DEBUG: Key loaded successfully! Starts with: {api_key[:6]}...",
        flush=True
    )

    try:
        # ==========================================
        # Appel léger: ghi performance_score
        # (khddam mn audit.py bach yjib desktop_score)
        # ==========================================
        if strategy == "desktop":
            desktop_data = await _fetch_pagespeed(url, "desktop", api_key)
            return {
                "performance_score": _extract_performance_score(desktop_data)
            }

        # ==========================================
        # Appel KAML: kolla les checks bla 3la9a
        # binathom kaydiro f WA9T WAHED (asyncio.gather)
        # ==========================================
        (
            robots_exists,
            sitemap_exists,
            pagespeed_data,
            mobile_result,
        ) = await asyncio.gather(
            check_robots_txt(url),
            check_sitemap_xml(url),
            _fetch_pagespeed(url, strategy, api_key),
            check_mobile_friendly(url),
        )

        lighthouse = pagespeed_data.get("lighthouseResult", {})
        categories = lighthouse.get("categories", {})
        audits = lighthouse.get("audits", {})

        performance_score = int(
            (categories.get("performance", {}).get("score", 0) or 0) * 100
        )
        accessibility_score = int(
            (categories.get("accessibility", {}).get("score", 0) or 0) * 100
        )
        best_practices_score = int(
            (categories.get("best-practices", {}).get("score", 0) or 0) * 100
        )
        seo_score = int(
            (categories.get("seo", {}).get("score", 0) or 0) * 100
        )

        global_score = int(
            (
                performance_score
                + accessibility_score
                + best_practices_score
                + seo_score
            ) / 4
        )

        is_mobile_friendly = mobile_result["is_mobile_friendly"]

        metrics = {
            "first_contentful_paint": (
                audits.get("first-contentful-paint", {}).get("displayValue", "N/A")
            ),
            "largest_contentful_paint": (
                audits.get("largest-contentful-paint", {}).get("displayValue", "N/A")
            ),
            "speed_index": (
                audits.get("speed-index", {}).get("displayValue", "N/A")
            ),
            "total_blocking_time": (
                audits.get("total-blocking-time", {}).get("displayValue", "N/A")
            ),
            "cumulative_layout_shift": (
                audits.get("cumulative-layout-shift", {}).get("displayValue", "N/A")
            ),
            "time_to_interactive": (
                audits.get("interactive", {}).get("displayValue", "N/A")
            ),
        }

        recommendations = []

        if performance_score < 90:
            recommendations.append("Compresser et optimiser les images.")
            recommendations.append("Minifier les fichiers CSS et JavaScript.")

        if metrics["largest_contentful_paint"] != "N/A":
            recommendations.append("Optimiser le Largest Contentful Paint (LCP).")

        if not robots_exists:
            recommendations.append("Ajouter un fichier robots.txt.")

        if not sitemap_exists:
            recommendations.append("Ajouter un sitemap.xml.")

        if not is_mobile_friendly:
            recommendations.append("Améliorer l'expérience mobile.")

        if not recommendations:
            recommendations.append("Le site est très performant 🚀")

        return {
            "global_score": global_score,
            "performance_score": performance_score,
            "accessibility_score": accessibility_score,
            "best_practices_score": best_practices_score,
            "seo_score": seo_score,
            "is_mobile_friendly": is_mobile_friendly,
            "mobile_score": mobile_result["mobile_score"],
            "has_robots_txt": robots_exists,
            "has_sitemap_xml": sitemap_exists,
            "metrics": metrics,
            "recommendations": recommendations,
        }

    except httpx.HTTPStatusError as e:
        return {
            "error": f"Erreur HTTP {e.response.status_code}",
            "details": e.response.text,
        }

    except Exception as e:
        return {"error": str(e)}


def format_simple_report(data):
    perf_score = data.get("performance_score", 0)

    if perf_score >= 80:
        perf_emoji = "🟢"
    elif perf_score >= 50:
        perf_emoji = "🟡"
    else:
        perf_emoji = "🔴"

    metrics = data.get("metrics", {})

    return {
        "status_global": f"{perf_emoji} Performance: {perf_score}/100",
        "scores_detailles": {
            "performance": perf_score,
            "seo": data.get("seo_score", 0),
            "accessibilite": data.get("accessibility_score", 0),
            "bonnes_pratiques": data.get("best_practices_score", 0),
        },
        "resume_technique": {
            "lcp": metrics.get("largest_contentful_paint", "N/A"),
            "cls": metrics.get("cumulative_layout_shift", "N/A"),
            "speed_index": metrics.get("speed_index", "N/A"),
            "total_blocking_time": metrics.get("total_blocking_time", "N/A"),
        },
        "conseils_rapides": data.get("recommendations", []),
    }