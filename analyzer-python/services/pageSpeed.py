import os
import httpx


async def get_pagespeed_data(url: str):
    # Jib API Key mn variables d'environnement
    api_key = os.getenv("PAGESPEED_API_KEY")

    if not api_key:
        return {
            "error": "PAGESPEED_API_KEY n'est pas configurée."
        }

    print(
        f"DEBUG: Key loaded successfully! Starts with: {api_key[:6]}...",
        flush=True
    )

    api_url = (
        "https://www.googleapis.com/pagespeedonline/v5/runPagespeed"
        f"?url={url}"
        f"&key={api_key}"
        f"&strategy=mobile"
        f"&category=performance"
        f"&category=accessibility"
        f"&category=best-practices"
        f"&category=seo"
    )
    robots_exists = False
    sitemap_exists = False
    try:
        async with httpx.AsyncClient() as client:
            robots_response = await client.get(
                f"{url.rstrip('/')}/robots.txt",
                timeout=10,
                follow_redirects=True
            )
            robots_exists = robots_response.status_code == 200
    except:
        pass
    try:
        async with httpx.AsyncClient() as client:
            sitemap_response = await client.get(
                f"{url.rstrip('/')}/sitemap.xml",
                timeout=10,
                follow_redirects=True
            )
        sitemap_exists = sitemap_response.status_code == 200
    except:
        pass
    try:
        async with httpx.AsyncClient() as client:
            response = await client.get(api_url, timeout=30.0)
            response.raise_for_status()
            data = response.json()

        lighthouse = data.get("lighthouseResult", {})
        categories = lighthouse.get("categories", {})
        audits = lighthouse.get("audits", {})
        print("CATEGORIES =", categories, flush=True)
        performance_score = int(
            categories.get("performance", {}).get("score", 0) * 100
        )
        accessibility_score = int(
            categories.get("accessibility", {}).get("score", 0) * 100
        )
        best_practices_score = int(
            categories.get("best-practices", {}).get("score", 0) * 100
        )
        seo_score = int(
            categories.get("seo", {}).get("score", 0) * 100
        )

        return {
            "performance_score": performance_score,
            "accessibility_score": accessibility_score,
            "best_practices_score": best_practices_score,
            "has_robots_txt": robots_exists,
            "has_sitemap_xml": sitemap_exists,
            "seo_score": seo_score,
            "metrics": {
                "first_contentful_paint": audits.get(
                    "first-contentful-paint", {}
                ).get("displayValue", "N/A"),

                "largest_contentful_paint": audits.get(
                    "largest-contentful-paint", {}
                ).get("displayValue", "N/A"),

                "speed_index": audits.get(
                    "speed-index", {}
                ).get("displayValue", "N/A"),

                "total_blocking_time": audits.get(
                    "total-blocking-time", {}
                ).get("displayValue", "N/A"),

                "cumulative_layout_shift": audits.get(
                    "cumulative-layout-shift", {}
                ).get("displayValue", "N/A"),

                "time_to_interactive": audits.get(
                    "interactive", {}
                ).get("displayValue", "N/A"),
            },
            "is_fast": performance_score >= 90,
            "recommendations": (
                [
                    "Compresser les images.",
                    "Minifier les fichiers CSS et JavaScript.",
                    "Activer la mise en cache du navigateur.",
                    "Réduire les scripts inutiles.",
                    "Optimiser les polices web."
                ]
                if performance_score < 90
                else ["Le site est très performant 🚀"]
            )
        }

    except httpx.HTTPStatusError as e:
        return {
            "error": f"Erreur HTTP {e.response.status_code}",
            "details": e.response.text
        }

    except Exception as e:
        return {
            "error": str(e)
        }


def format_simple_report(data):
    perf_score = data.get("performance_score", 0)
    perf_emoji = "🟢" if perf_score >= 80 else ("🟡" if perf_score >= 50 else "🔴")

    metrics = data.get("metrics", {})
    return {
        "status_global": f"{perf_emoji} Performance: {perf_score}/100",
        "scores_detailles": {
            "performance": perf_score,
            "seo": data.get("seo_score", 0),
            "accessibilite": data.get("accessibility_score", 0),
            "bonnes_pratiques": data.get("best_practices_score", 0)
        },
        "resume_technique": {
            "lcp": metrics.get("largest_contentful_paint", "N/A"),
            "cls": metrics.get("cumulative_layout_shift", "N/A"),
            "speed_index": metrics.get("speed_index", "N/A"),
            "total_blocking_time": metrics.get("total_blocking_time", "N/A")
        },
        "conseils_rapides": data.get("recommendations", [])
    }