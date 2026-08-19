# ============================================================
# ON-PAGE AUDIT
# ============================================================
# Lance l'analyse BeautifulSoup:
# analyzer.seo_analyzer.analyze
#
# IMPORTANT:
# - Aucun Redis
# - Aucun cache
# - Python ne sauvegarde rien
# - Python analyse uniquement et retourne le résultat
#
# Symfony recevra ce payload et pourra ensuite sauvegarder
# les données dans PostgreSQL via les entités Doctrine:
#
#   AuditPage
#   AuditPageHeading
#   AuditPageImage
#   AuditKeywordDensity
# ============================================================

from urllib.parse import urlparse

from fastapi import APIRouter

from analyzer.seo_analyzer import analyze


router = APIRouter(
    prefix="/audit-onpage",
    tags=["On-Page Audit"]
)


# ============================================================
# VALIDATION URL
# ============================================================

def is_valid_url(url: str) -> bool:
    """
    Vérifie si l'URL est valide:
    - http://
    - https://
    - domaine présent
    """

    try:
        parsed = urlparse(url)

        return (
            parsed.scheme in ("http", "https")
            and bool(parsed.netloc)
        )

    except Exception:
        return False


# ============================================================
# FLATTEN HEADINGS
# ============================================================

def flatten_headings(headings: dict) -> list:
    """
    Transforme:

    {
        "h1": ["Titre 1", "Titre 2"],
        "h2": ["Sous titre"]
    }

    en:

    [
        {
            "heading_level": "h1",
            "content": "Titre 1",
            "position": 1
        },
        {
            "heading_level": "h1",
            "content": "Titre 2",
            "position": 2
        },
        {
            "heading_level": "h2",
            "content": "Sous titre",
            "position": 1
        }
    ]
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


# ============================================================
# IMAGE TYPE
# ============================================================

def guess_image_type(image_url: str):
    """
    Devine le type de fichier image depuis son URL.

    Exemples:
        .jpg  -> jpg
        .png  -> png
        .webp -> webp
    """

    try:

        if not image_url:
            return None

        path = urlparse(image_url).path

        if "." not in path:
            return None

        extension = path.rsplit(".", 1)[-1].lower()

        # Sécurité
        if len(extension) > 10:
            return None

        return extension

    except Exception:

        return None


# ============================================================
# FLATTEN IMAGES
# ============================================================

def flatten_images(
    images_with_alt: list,
    images_without_alt: list
) -> list:

    """
    Fusionne:

        images_with_alt

    et:

        images_without_alt

    dans une seule liste.

    Structure compatible avec AuditPageImage.
    """

    try:

        rows = []

        # ----------------------------------------------------
        # Images avec ALT
        # ----------------------------------------------------

        for img in (images_with_alt or []):

            if isinstance(img, dict):

                src = img.get("src", "")
                alt = img.get("alt", "")

            else:

                src = ""
                alt = ""

            rows.append({
                "image_url": src,
                "has_alt": True,
                "alt_text": alt,
                "file_size_kb": None,
                "image_type": guess_image_type(src),
            })

        # ----------------------------------------------------
        # Images sans ALT
        # ----------------------------------------------------

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


# ============================================================
# BUILD ON-PAGE PAYLOAD
# ============================================================

def build_onpage_payload(result: dict) -> dict:
    """
    Organise le résultat de analyze().

    IMPORTANT:
    Cette fonction ne sauvegarde rien.

    Elle prépare simplement les données pour Symfony.

    Groupes:

        page
        headings
        images
        keyword_density
    """

    url = result.get("url") or ""

    images_without_alt = (
        result.get("images_without_alt") or []
    )

    response_time = result.get(
        "response_time_ms"
    )

    return {

        # ====================================================
        # GLOBAL
        # ====================================================

        "status": "success",

        "url": url,

        "analysis_date": result.get(
            "analysis_date"
        ),

        # ====================================================
        # AUDIT PAGE
        # ====================================================

        "page": {

            "url": url,

            "status_code": result.get(
                "status_code"
            ),

            # TITLE
            "title": result.get(
                "title"
            ),

            "title_length": result.get(
                "title_length"
            ),

            # META DESCRIPTION
            "meta_description": result.get(
                "meta_description"
            ),

            "meta_length": result.get(
                "meta_length"
            ),

            # CANONICAL
            "canonical_url": result.get(
                "canonical_url"
            ),

            # ROBOTS
            "meta_robots": result.get(
                "meta_robots"
            ),

            # LANGUAGE
            "lang_attribute": result.get(
                "language"
            ),

            # H1
            "h1_count": result.get(
                "h1_count"
            ),

            "h1_is_unique": result.get(
                "h1_is_unique"
            ),

            # CONTENT
            "word_count": result.get(
                "word_count"
            ),

            # LINKS
            "internal_links_count": result.get(
                "internal_links"
            ),

            "external_links_count": result.get(
                "external_links"
            ),

            # Broken links
            #
            # Pas encore calculé.
            "broken_links_count": None,

            # IMAGES
            "images_count": result.get(
                "images_count"
            ) or 0,

            "images_without_alt_count": len(
                images_without_alt
            ),

            # STRUCTURED DATA
            "has_structured_data": bool(
                result.get(
                    "structured_data"
                )
            ),

            # MOBILE
            "viewport_meta": (
                bool(result.get("viewport"))
                if result.get("viewport")
                else False
            ),

            # HTTPS
            "is_https": url.startswith(
                "https://"
            ),

            # RESPONSE TIME
            "response_time_ms": (
                int(response_time)
                if response_time is not None
                else None
            ),

            # PageSpeed metric
            #
            # Non disponible dans cette analyse.
            "load_time_ms": None,

            # Crawl depth
            #
            # Analyse d'une seule page.
            "crawl_depth": 0,

            # DATE
            "created_at": result.get(
                "analysis_date"
            ),
        },

        # ====================================================
        # HEADINGS
        # ====================================================

        "headings": flatten_headings(
            result.get("headings")
        ),

        # ====================================================
        # IMAGES
        # ====================================================

        "images": flatten_images(
            result.get("images_with_alt"),
            result.get("images_without_alt"),
        ),

        # ====================================================
        # KEYWORD DENSITY
        # ====================================================

        "keyword_density": result.get(
            "keyword_density",
            []
        ),
    }


# ============================================================
# MAIN ENDPOINT
# ============================================================
#
# GET /audit-onpage?url=https://example.com
#
# Python analyse seulement.
# Aucun stockage.
# Aucun Redis.
# ============================================================

@router.get("")
async def audit_onpage(url: str):

    # --------------------------------------------------------
    # Validation URL
    # --------------------------------------------------------

    if not is_valid_url(url):

        return {
            "status": "failed",
            "error_message": "Invalid or restricted URL",
            "url": url,
        }

    # --------------------------------------------------------
    # Analyse BeautifulSoup
    # --------------------------------------------------------

    try:

        result = analyze(url)

    except Exception as e:

        return {
            "status": "failed",
            "error_message": str(e),
            "url": url,
        }

    # --------------------------------------------------------
    # Vérification résultat
    # --------------------------------------------------------

    if not result.get("success"):

        return {
            "status": "failed",
            "error_message": result.get(
                "error",
                "Unknown error"
            ),
            "url": url,
        }

    # --------------------------------------------------------
    # Construction payload
    # --------------------------------------------------------

    payload = build_onpage_payload(
        result
    )

    # --------------------------------------------------------
    # Retour direct à Symfony
    # --------------------------------------------------------

    return payload


# ============================================================
# DEBUG ENDPOINT
# ============================================================
#
# GET /audit-onpage/debug?url=https://example.com
#
# Retourne exactement le même payload détaillé.
# Utile pour tester le Python indépendamment de Symfony.
# ============================================================

@router.get("/debug")
async def audit_onpage_debug(url: str):

    # --------------------------------------------------------
    # Validation
    # --------------------------------------------------------

    if not is_valid_url(url):

        return {
            "status": "failed",
            "error_message": "Invalid URL",
            "url": url,
        }

    # --------------------------------------------------------
    # Analyse
    # --------------------------------------------------------

    try:

        result = analyze(url)

    except Exception as e:

        return {
            "status": "failed",
            "error_message": str(e),
            "url": url,
        }

    # --------------------------------------------------------
    # Erreur analyse
    # --------------------------------------------------------

    if not result.get("success"):

        return {
            "status": "failed",
            "error_message": result.get(
                "error",
                "Unknown error"
            ),
            "url": url,
        }

    # --------------------------------------------------------
    # Retour détaillé
    # --------------------------------------------------------

    return build_onpage_payload(
        result
    )
