# Endpoint /audit-onpage : lance l'analyse BeautifulSoup (analyzer.seo_analyzer.analyze)
# w kayrj3 resultat DIRECTEMENT f la reponse HTTP, groupé EXACTEMENT selon
# les entités Doctrine AuditPage, AuditPageHeading, AuditPageImage,
# AuditKeywordDensity.
# ----------------------------------------------------------------------------
 

import ipaddress
import logging
import socket
from urllib.parse import urlparse

from fastapi import APIRouter, HTTPException, status
from fastapi.concurrency import run_in_threadpool

from analyzer.seo_analyzer import analyze

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/audit-onpage", tags=["On-Page Audit"])


def is_valid_url(url: str) -> bool:
    """
    Vérifie la validité de l'URL ET bloque les IP privées/locales (Protection SSRF).
    """
    try:
        parsed = urlparse(url)
        if parsed.scheme not in ("http", "https") or not parsed.netloc:
            return False

        # Extraction du hostname (sans le port)
        hostname = parsed.hostname
        if not hostname:
            return False

        # Résolution DNS pour vérifier l'adresse IP sous-jacente
        ip_list = socket.getaddrinfo(hostname, None)
        for item in ip_list:
            ip_str = item[4][0]
            ip_obj = ipaddress.ip_address(ip_str)
            # Bloque 127.0.0.1, 10.x.x.x, 192.168.x.x, 169.254.x.x (AWS Metadata), etc.
            if ip_obj.is_private or ip_obj.is_loopback or ip_obj.is_link_local:
                logger.warning(f"Tentative SSRF bloquée pour le domaine/IP: {hostname} ({ip_str})")
                return False

        return True
    except Exception as e:
        logger.error(f"Erreur validation URL ({url}): {e}")
        return False


def flatten_headings(headings: dict) -> list:
    if not isinstance(headings, dict):
        return []

    rows = []
    for level, texts in headings.items():
        if isinstance(texts, list):
            for index, text in enumerate(texts, start=1):
                rows.append({
                    "heading_level": level,
                    "content": text,
                    "position": index,
                })
    return rows


def guess_image_type(image_url: str):
    if not image_url:
        return None
    try:
        path = urlparse(image_url).path
        if "." in path:
            ext = path.rsplit(".", 1)[-1].lower()
            return ext if len(ext) <= 10 else None
    except Exception:
        pass
    return None


def flatten_images(images_with_alt: list, images_without_alt: list) -> list:
    rows = []
    seen_urls = set()

    for img in (images_with_alt or []):
        if isinstance(img, dict):
            src = img.get("src", "")
            if src and src not in seen_urls:
                seen_urls.add(src)
                rows.append({
                    "image_url": src,
                    "has_alt": True,
                    "alt_text": img.get("alt", ""),
                    "file_size_kb": None,
                    "image_type": guess_image_type(src),
                })

    for src in (images_without_alt or []):
        if isinstance(src, str) and src and src not in seen_urls:
            seen_urls.add(src)
            rows.append({
                "image_url": src,
                "has_alt": False,
                "alt_text": None,
                "file_size_kb": None,
                "image_type": guess_image_type(src),
            })

    return rows


def build_response_payload(result: dict) -> dict:
    url = result.get("url") or ""
    images_without_alt = result.get("images_without_alt") or []
    response_time = result.get("response_time_ms")

    return {
        "status": "success",
        "url": url,
        "analysis_date": result.get("analysis_date"),
        "page": {
            "url": url,
            "status_code": result.get("status_code"),
            "title": result.get("title"),
            "title_length": result.get("title_length"),
            "meta_description": result.get("meta_description"),
            "meta_length": result.get("meta_length"),
            "canonical_url": result.get("canonical_url"),
            "meta_robots": result.get("meta_robots"),
            "lang_attribute": result.get("language"),
            "h1_count": result.get("h1_count"),
            "h1_is_unique": result.get("h1_is_unique"),
            "word_count": result.get("word_count"),
            "internal_links_count": result.get("internal_links"),
            "external_links_count": result.get("external_links"),
            "broken_links_count": None,
            "images_count": result.get("images_count") or 0,
            "images_without_alt_count": len(images_without_alt),
            "has_structured_data": bool(result.get("structured_data")),
            "viewport_meta": bool(result.get("viewport")),
            "is_https": url.startswith("https://"),
            "response_time_ms": int(response_time) if response_time is not None else None,
            "load_time_ms": None,
            "crawl_depth": 0,
            "created_at": result.get("analysis_date"),
        },
        "headings": flatten_headings(result.get("headings")),
        "images": flatten_images(
            result.get("images_with_alt"),
            result.get("images_without_alt"),
        ),
        "keyword_density": result.get("keyword_density", []),
    }


@router.get("")
async def audit_onpage(url: str):
    if not is_valid_url(url):
        return {
            "status": "failed",
            "error_message": "Invalid or restricted URL",
            "url": url,
        }

    # Utilisation de threadpool pour ne pas bloquer l'Event Loop de FastAPI
    result = await run_in_threadpool(analyze, url)

    if not result.get("success"):
        return {
            "status": "failed",
            "error_message": result.get("error", "Unknown error"),
            "url": url,
        }

    return build_response_payload(result)