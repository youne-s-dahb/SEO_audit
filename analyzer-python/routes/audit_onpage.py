
# Endpoint /audit-onpage : lance l'analyse BeautifulSoup (analyzer.seo_analyzer.analyze)
# w kaysauvegardi resultat f Redis, groupé EXACTEMENT selon les entités Doctrine
# AuditPage, AuditPageHeading, AuditPageImage, AuditKeywordDensity.

# ----------------------------------------------------------------------------

import json
from urllib.parse import urlparse

import redis
from fastapi import APIRouter

from analyzer.seo_analyzer import analyze

router = APIRouter(prefix="/audit-onpage", tags=["On-Page Audit"])

# Meme connexion Redis que Personne 1 (meme host/port/db, service "redis" du docker-compose)
r = redis.Redis(host="redis", port=6379, db=0)

# Duree de vie du cache (1h, comme le cache PageSpeed de Personne 1)
CACHE_TTL_SECONDS = 3600


def is_valid_url(url: str) -> bool:
    """
    Katverifi wach URL valide (http/https + domaine).
    """
    try:
        parsed = urlparse(url)
        return parsed.scheme in ("http", "https") and bool(parsed.netloc)
    except Exception:
        return False


# --------------------------------------------------
# Flatten headings: dict {h1: [...], h2: [...]} -> liste de lignes
# pretes pour AuditPageHeading (headingLevel, content, position)
# --------------------------------------------------

def flatten_headings(headings: dict) -> list:
    """
    Kay7awel dict dyal headings l liste dyal dict, wa7ed par ligne DB.
    position = ordre dyal heading DAKHEL nefss l-level (h1 #1, h1 #2, h2 #1...).
    """
    try:
        if not headings or not isinstance(headings, dict):
            return []

        rows = []

        for level, texts in headings.items():
            if not isinstance(texts, list):
                continue

            for index, text in enumerate(texts, start=1):
                rows.append({
                    "heading_level": level,
                    "content": text,
                    "position": index,
                })

        return rows

    except Exception:
        return []


# --------------------------------------------------
# Deviner le type de fichier image depuis l'URL (extension)
# --------------------------------------------------

def guess_image_type(image_url: str):
    """
    Kaychouf extension dyal image mn URL dyalha (jpg, png, webp...).
    """
    try:
        if not image_url:
            return None

        path = urlparse(image_url).path

        if "." not in path:
            return None

        extension = path.rsplit(".", 1)[-1].lower()

        # Securite: extension trop longue = probablement pas une vraie extension
        if len(extension) > 10:
            return None

        return extension

    except Exception:
        return None


# --------------------------------------------------
# Flatten images: fusionne images_with_alt + images_without_alt
# en une seule liste prete pour AuditPageImage
# --------------------------------------------------

def flatten_images(images_with_alt: list, images_without_alt: list) -> list:
    """
    Kayjma3 images (avec ALT + sans ALT) f liste wa7da coherente
    avec les colonnes dyal AuditPageImage.
    """
    try:
        rows = []

        for img in (images_with_alt or []):
            src = img.get("src", "") if isinstance(img, dict) else ""
            alt = img.get("alt", "") if isinstance(img, dict) else ""

            rows.append({
                "image_url": src,
                "has_alt": True,
                "alt_text": alt,
                "file_size_kb": None,   # pas calcule ici (necessite download image)
                "image_type": guess_image_type(src),
            })

        for src in (images_without_alt or []):
            rows.append({
                "image_url": src,
                "has_alt": False,
                "alt_text": None,
                "file_size_kb": None,
                "image_type": guess_image_type(src),
            })

        return rows

    except Exception:
        return []


# --------------------------------------------------
# Construire le payload Redis, groupe EXACTEMENT selon les entites Doctrine
# --------------------------------------------------

def build_redis_payload(result: dict) -> dict:
    """
    Kat-organisi resultat dyal analyze() f des groupes, un groupe par
    table Doctrine cible. Les cles JSON sont en snake_case pour lisibilite,
    mais c'est le code Symfony qui va mapper chaque cle vers le setter
    PHP correspondant (setTitle(), setWordCount()...) - donc PAS besoin
    que les noms JSON soient identiques aux noms camelCase des entites.
    """

    url = result.get("url") or ""
    images_without_alt = result.get("images_without_alt") or []
    response_time = result.get("response_time_ms")

    return {
        "status": "success",
        "url": url,
        "analysis_date": result.get("analysis_date"),

        # -----------------------------
        # Table: audit_pages (AuditPage)
        # -----------------------------
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
            # broken_links_count: pas calcule (demanderait de tester chaque
            # lien un par un -> lent). A ajouter plus tard si besoin.
            "broken_links_count": None,
            "images_count": result.get("images_count") or 0,
            "images_without_alt_count": len(images_without_alt),
            "has_structured_data": bool(result.get("structured_data")),
            "viewport_meta": bool(result.get("viewport")) if result.get("viewport") else False,
            "is_https": url.startswith("https://"),
            "response_time_ms": int(response_time) if response_time is not None else None,
            # load_time_ms: c'est une metrique PageSpeed (Personne 1), pas dispo ici
            "load_time_ms": None,
            # crawl_depth: audit d'une seule page (pas de crawl multi-pages)
            "crawl_depth": 0,
            "created_at": result.get("analysis_date"),
        },

        # -----------------------------
        # Table: audit_page_headings (AuditPageHeading) - liste
        # -----------------------------
        "headings": flatten_headings(result.get("headings")),

        # -----------------------------
        # Table: audit_page_images (AuditPageImage) - liste
        # -----------------------------
        "images": flatten_images(
            result.get("images_with_alt"),
            result.get("images_without_alt"),
        ),

        # -----------------------------
        # Table: audit_keyword_density (AuditKeywordDensity) - liste
        # -----------------------------
        "keyword_density": result.get("keyword_density", []),
    }


@router.get("")
async def audit_onpage(url: str):
    """
    Lance l'analyse on-page complete (BeautifulSoup) w kaysauvegardiha f Redis.

    Cle Redis: audit:onpage:{url}  (differente de audit:{url} li kayst3ml
    Personne 1 bach ma-tt9ta3ch m3a son cache PageSpeed).
    """

    key = f"audit:onpage:{url}"

    if not is_valid_url(url):
        payload = {
            "status": "failed",
            "error_message": "Invalid URL",
            "url": url,
        }
        r.set(key, json.dumps(payload))
        r.expire(key, CACHE_TTL_SECONDS)
        return payload

    result = analyze(url)

    if not result.get("success"):
        payload = {
            "status": "failed",
            "error_message": result.get("error", "Unknown error"),
            "url": url,
        }
        r.set(key, json.dumps(payload))
        r.expire(key, CACHE_TTL_SECONDS)
        return payload

    payload = build_redis_payload(result)

    r.set(key, json.dumps(payload))
    r.expire(key, CACHE_TTL_SECONDS)

    return {
        "message": "On-page audit completed and saved in Redis",
        "status": "success",
        "key": key,
    }


@router.get("/debug")
async def audit_onpage_debug(url: str):
    """
    Route de debug: rj3 direct le payload structure complet (sans passer
    par Redis), utile bach ttesti rapidement le mapping vers les tables.
    """
    result = analyze(url)

    if not result.get("success"):
        return result

    return build_redis_payload(result)