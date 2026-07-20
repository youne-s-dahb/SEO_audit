#sert à re-télécharger une page avec un vrai navigateur headless (Playwright) qui exécute le JavaScript,
#pour récupérer le contenu réel des sites React/Vue/Next.js que le scraper classique (requests) ne peut
#pas voir puisqu'il ne charge que le HTML brut avant l'exécution du JS.

import time
from concurrent.futures import ThreadPoolExecutor
from urllib.parse import urlparse

from playwright.sync_api import sync_playwright, TimeoutError as PlaywrightTimeoutError

from analyzer.utils import validate_url, normalize_url

# Timeout maximum pour le chargement JS (en millisecondes)
JS_RENDER_TIMEOUT_MS = 15000

# Taille max du HTML rendu accepte (10 MB, coherent avec fetch_page())
MAX_CONTENT_SIZE = 10 * 1024 * 1024

USER_AGENT = "SEO Audit Bot/1.0"


def is_private_ip(hostname: str) -> bool:
    """
    Reprend la meme logique que seo_analyzer.is_private_ip() pour
    verifier l'IP AVANT de lancer le navigateur (verification bkri,
    fail-fast). Import local pour eviter une dependance circulaire.
    """
    from analyzer.seo_analyzer import is_private_ip as _check
    return _check(hostname)


def _render_page_sync(url: str) -> dict:
    """
    Fonction interne: fait le vrai travail Playwright (API sync).
    Doit TOUJOURS etre executee dans un thread separe (via
    render_page_with_js ci-dessous), jamais appelee directement
    depuis une route async FastAPI - sync_playwright() plante
    immediatement si une boucle asyncio est deja active dans le
    thread courant.
    """
    try:
        if not validate_url(url):
            return {
                "success": False,
                "error": "Invalid URL."
            }

        url = normalize_url(url)
        parsed = urlparse(url)

        if is_private_ip(parsed.hostname):
            return {
                "success": False,
                "error": "Private IPs are not allowed."
            }

        start = time.perf_counter()

        with sync_playwright() as p:
            # OPTIMISATION DOCKER: arguments necessaires pour la stabilite
            # de Chromium dans un container (sinon crash quasi systematique)
            browser = p.chromium.launch(
                headless=True,
                args=[
                    "--no-sandbox",
                    "--disable-dev-shm-usage",
                    "--disable-gpu",
                ]
            )

            try:
                context = browser.new_context(user_agent=USER_AGENT)
                page = context.new_page()

                page.set_default_timeout(JS_RENDER_TIMEOUT_MS)

                # OPTIMISATION PERFORMANCE: domcontentloaded au lieu de
                # networkidle, pour eviter les timeouts infinis sur des
                # sites avec des scripts analytics/chat en polling continu
                response = page.goto(
                    url,
                    wait_until="domcontentloaded",
                    timeout=JS_RENDER_TIMEOUT_MS,
                )

                if response is None:
                    return {
                        "success": False,
                        "error": "No response received from the page."
                    }

                # Petit delai pour laisser les frameworks JS (React/Vue)
                # hydrater le DOM apres le chargement initial
                page.wait_for_timeout(1000)

                status_code = response.status
                final_url = page.url

                html_content = page.content()

                if len(html_content.encode("utf-8")) > MAX_CONTENT_SIZE:
                    return {
                        "success": False,
                        "error": "Page too large."
                    }

            finally:
                browser.close()

        end = time.perf_counter()
        response_time = round((end - start) * 1000, 2)

        return {
            "success": True,
            "html": html_content,
            "status_code": status_code,
            "response_time_ms": response_time,
            "content_type": "text/html",
            "final_url": final_url,
            "rendered_with_js": True,
        }

    except PlaywrightTimeoutError:
        return {
            "success": False,
            "error": "JS rendering timeout (page took too long to load)."
        }

    except Exception as e:
        return {
            "success": False,
            "error": f"Unexpected error during JS rendering: {str(e)}"
        }


def render_page_with_js(url: str) -> dict:
    """
    Point d'entree public. Execute _render_page_sync() dans un thread
    separe, isole de toute boucle asyncio active (celle de FastAPI
    par exemple). C'est OBLIGATOIRE: appeler sync_playwright()
    directement depuis une route "async def" FastAPI leve une erreur
    immediate ("Playwright Sync API inside the asyncio loop").
    """
    try:
        with ThreadPoolExecutor(max_workers=1) as executor:
            future = executor.submit(_render_page_sync, url)
            # +5s de marge sur le timeout interne, pour laisser Playwright
            # lui-meme gerer son propre timeout proprement avant qu'on
            # coupe la main de notre cote.
            return future.result(timeout=(JS_RENDER_TIMEOUT_MS / 1000) + 5)

    except Exception as e:
        return {
            "success": False,
            "error": f"Thread execution error during JS rendering: {str(e)}"
        }


def looks_like_empty_shell(analysis_result: dict) -> bool:
    """
    Heuristique simple pour detecter une page "coquille vide" typique
    d'un site JS non rendu: tres peu de mots ET aucun heading ET
    aucune image ET aucun lien. Si ces 4 signaux sont reunis, on
    suppose que c'est un site React/Vue/Next.js cote client.
    """
    try:
        word_count = analysis_result.get("word_count", 0) or 0
        headings_count = analysis_result.get("headings_count", 0) or 0
        images_count = analysis_result.get("images_count", 0) or 0
        links_count = analysis_result.get("links_count", 0) or 0

        return (
            word_count < 50
            and headings_count == 0
            and images_count == 0
            and links_count == 0
        )

    except Exception:
        return False