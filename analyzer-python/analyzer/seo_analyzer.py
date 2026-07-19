# 3
# contient la logique principale de l’audit SEO et calcule les résultats globaux.
#----------------------------------------------------------------------------


import time
import socket
import ipaddress
import requests
import threading


from contextlib import contextmanager
from urllib.parse import urlparse
from urllib3.util import connection as urllib3_connection
from datetime import datetime, UTC


from analyzer.utils import (
    validate_url,
    normalize_url
)

from analyzer.html_parser import (
    parse_html,
    get_title,
    get_title_length,
    get_meta_description,
    get_meta_length,
    get_canonical_url,
    get_meta_robots,
    get_lang,
    get_viewport,
    get_headings,
    get_h1_count,
    is_h1_unique,
    count_words,
    get_links,
    count_internal_links,
    count_external_links,
    count_images,
    get_images_with_alt,
    get_images_without_alt,
    has_structured_data
)

from analyzer.keyword_density import calculate_keyword_density

# Constantes

# User-Agent bach ba3d sites ma y7bsouch request
USER_AGENT = {
    "User-Agent": "SEO Audit Bot/1.0"
}

# Timeout maximum
REQUEST_TIMEOUT = 10

# Taille maximale (10 MB)
MAX_CONTENT_SIZE = 10 * 1024 * 1024

# Nombre max de redirections suivies manuellement
MAX_REDIRECTS = 5

# Lock bach n-sécuriser l-patch f 7alat l-multithreading
_dns_patch_lock = threading.Lock()


# --------------------------------------------------
# Vérifier si IP privée (Protection SSRF)
# --------------------------------------------------

def is_private_ip(hostname: str) -> bool:
    """
    Had fonction kat7mi men SSRF.

    Kat7awl hostname l IP (IPv4 w IPv6)
    puis katchecki wach private.
    """
    try:
        # getaddrinfo kat-gérer l-IPv4 w l-IPv6 b-jouj mzyan
        infos = socket.getaddrinfo(hostname, None, socket.AF_UNSPEC, socket.SOCK_STREAM)
        for family, socktype, proto, canonname, sockaddr in infos:
            ip = sockaddr[0]
            ip_obj = ipaddress.ip_address(ip)
            if ip_obj.is_private or ip_obj.is_loopback:
                return True
        return False
    except Exception:
        # Ila ma9drnach njibo IP, n-blokiwh d'office (Fail-safe)
        return True


# --------------------------------------------------
# Protection SSRF réelle : pin de la résolution DNS
# --------------------------------------------------

@contextmanager
def _ssrf_safe_connection():
    """
    Kat-intercepti la résolution DNS dyal urllib3 w kat-force chaque connexion TCP 
    - y compris celles déclenchées par des redirections HTTP - tmchi mn check li IP privée.
    
    Correction: support dyal IPv4/IPv6 dual-stack w thread safety.
    """
    global _dns_patch_lock

    original_create_connection = urllib3_connection.create_connection

    def patched_create_connection(address, *args, **kwargs):
        host, port = address

        try:
            # getaddrinfo kat-rj3 ga3 les IPs (v4 w v6) li mlinked m3a l-host
            infos = socket.getaddrinfo(host, port, socket.AF_UNSPEC, socket.SOCK_STREAM)
        except socket.gaierror as exc:
            raise requests.exceptions.ConnectionError(
                f"DNS resolution failed for host: {host}"
            ) from exc

        # Checki ga3 les adresses resolution-at lihom l-domain
        for family, socktype, proto, canonname, sockaddr in infos:
            ip = sockaddr[0]
            
            try:
                ip_obj = ipaddress.ip_address(ip)
            except ValueError:
                continue
                
            if ip_obj.is_private or ip_obj.is_loopback:
                raise requests.exceptions.ConnectionError(
                    f"Blocked connection to private/internal IP address: {ip}"
                )

        # Pinning: n-stbto direct l-IP l-ola li t-validat (IPv4 wla IPv6) 
        # bach n-mna3 l-DNS Rebinding tamamen.
        target_ip = infos[0][4][0]
        return original_create_connection((target_ip, port), *args, **kwargs)

    # Nest3mlo lock bach l-patch global may-rabjch les threads khrin
    with _dns_patch_lock:
        urllib3_connection.create_connection = patched_create_connection
        try:
            yield
        finally:
            # Rej3o l-comportement normal dima mlli nsaliw l-block context
            urllib3_connection.create_connection = original_create_connection


# --------------------------------------------------
# Télécharger page HTML
# --------------------------------------------------

def fetch_page(url: str):
    """
    Kattelechargi page HTML.

    Katdir plusieurs vérifications :
    ✔ URL valide
    ✔ Protection SSRF (check bkri + pin dyal DNS 7ta l connexion, m3a chaque redirection)
    ✔ Timeout w User-Agent
    ✔ Taille maximale (Streamed dghya 7ta m3a chunked encoding)
    ✔ Content-Type
    """
    try:
        # -----------------------------
        # Vérifier URL
        # -----------------------------
        if not validate_url(url):
            return {
                "success": False,
                "error": "Invalid URL."
            }

        # -----------------------------
        # Normaliser URL
        # -----------------------------
        url = normalize_url(url)
        parsed = urlparse(url)

        # -----------------------------
        # Protection SSRF (check rapide, fail-fast)
        # -----------------------------
        if is_private_ip(parsed.hostname):
            return {
                "success": False,
                "error": "Private IPs are not allowed."
            }

        # -----------------------------
        # Début chronomètre
        # -----------------------------
        start = time.perf_counter()

        with _ssrf_safe_connection():
            session = requests.Session()
            session.max_redirects = MAX_REDIRECTS

            # Stream=True m7taja bach n-sécurisow d-download dyal les chunked encoding
            response = session.get(
                url,
                headers=USER_AGENT,
                timeout=REQUEST_TIMEOUT,
                allow_redirects=True,
                stream=True
            )

            # 1. Checki l-header Content-Length l-awwal (ila 3ndna)
            content_length = response.headers.get("Content-Length")
            if content_length and int(content_length) > MAX_CONTENT_SIZE:
                return {
                    "success": False,
                    "error": "Page too large."
                }

            # 2. Telechargi l-content b chunks (haka n-mna3o DoS m3a chunked transfer)
            content = bytearray()
            for chunk in response.iter_content(chunk_size=8192):
                content.extend(chunk)
                if len(content) > MAX_CONTENT_SIZE:
                    return {
                        "success": False,
                        "error": "Page too large (Stream limit exceeded)."
                    }

        end = time.perf_counter()
        response_time = round((end - start) * 1000, 2)

        # -----------------------------
        # Vérifier Content-Type
        # -----------------------------
        content_type = response.headers.get("Content-Type", "")
        if "text/html" not in content_type:
            return {
                "success": False,
                "error": "URL is not an HTML page."
            }

        # Convertir l-code nqi safe
        html_text = content.decode('utf-8', errors='ignore')

        # -----------------------------
        # Tout est OK
        # -----------------------------
        return {
            "success": True,
            "html": html_text,
            "status_code": response.status_code,
            "response_time_ms": response_time,
            "content_type": content_type,
            "final_url": response.url
        }

    # -----------------------------
    # Blocage SSRF déclenché pendant la connexion
    # -----------------------------
    except requests.exceptions.ConnectionError as e:
        return {
            "success": False,
            "error": str(e)
        }

    # -----------------------------
    # Timeout
    # -----------------------------
    except requests.Timeout:
        return {
            "success": False,
            "error": "Request timeout."
        }

    # -----------------------------
    # Erreurs HTTP
    # -----------------------------
    except requests.RequestException as e:
        return {
            "success": False,
            "error": "Le site web a mis trop de temps à répondre (Timeout). Veuillez réessayer plus tard."
        }

    # -----------------------------
    # Autres erreurs
    # -----------------------------
    except Exception as e:
        return {
            "success": False,
            "error": "Unexpected error during SEO analysis."
        }

# --------------------------------------------------
# Analyse SEO complète
# --------------------------------------------------

def analyze(url: str) -> dict:
    """
    Had fonction hiya li ghadi y3ayet liha FastAPI.

    URL
        ↓
    fetch_page()
        ↓
    BeautifulSoup
        ↓
    HTML Parser
        ↓
    JSON
    """

    try:
        # --------------------------------------
        # Télécharger la page
        # --------------------------------------
        page = fetch_page(url)

        # Ila kayn chi erreur f t-telechargement, n7bes hna direct
        if not page["success"]:
            return page

        # --------------------------------------
        # Parser HTML
        # --------------------------------------
        soup = parse_html(page["html"])
        if not soup:
            return {
                "success": False,
                "error": "Failed to parse HTML document."
            }

        # --------------------------------------
        # Récupération sécurisée des éléments SEO de base
        # (Bach d-mna3 AttributeError f l-global dict)
        # --------------------------------------
        try:
            title = get_title(soup) or ""
            title_length = len(title) if title else 0
        except Exception:
            title, title_length = "", 0

        try:
            meta_desc = get_meta_description(soup) or ""
            meta_length = len(meta_desc) if meta_desc else 0
        except Exception:
            meta_desc, meta_length = "", 0

        # --------------------------------------
        # Links Parsing
        # --------------------------------------
        try:
            links = get_links(soup) or []
        except Exception:
            links = []

        # Final URL m-sécurisya l l-comparaison
        final_url = page.get("final_url") or url

        # --------------------------------------
        # Headings (FIX: 3ayetna l get_headings() ghi mrra wa7da,
        # m7mya b _safe_call, bach ma-ntkarrarouch call w ma-tsqtch
        # analyze() kaml ila rmat exception)
        # --------------------------------------
        headings = _safe_call(get_headings, soup, default={})

        # --------------------------------------
        # Construire résultat
        # --------------------------------------
        result = {
            # -----------------------------
            # Informations générales
            # -----------------------------
            "success": True,
            "url": final_url,
            "status_code": page.get("status_code"),
            "response_time_ms": page.get("response_time_ms"),
            "content_type": page.get("content_type"),

            # -----------------------------
            # SEO (Optimisé: parsing unique d'éléments)
            # -----------------------------
            "title": title,
            "title_length": title_length,
            "meta_description": meta_desc,
            "meta_length": meta_length,
            
            "canonical_url": _safe_call(get_canonical_url, soup, default=""),
            "meta_robots": _safe_call(get_meta_robots, soup, default=""),
            "language": _safe_call(get_lang, soup, default=""),
            "viewport": _safe_call(get_viewport, soup, default=""),

            # -----------------------------
            # Headings
            # -----------------------------
            "headings": headings,
            "headings_count": sum(len(v) for v in headings.values()),
            "h1_count": _safe_call(get_h1_count, soup, default=0),
            "h1_is_unique": _safe_call(is_h1_unique, soup, default=False),

            # -----------------------------
            # Contenu
            # -----------------------------
            "word_count": _safe_call(count_words, soup, default=0),

            # -----------------------------
            # Liens
            # -----------------------------
            "links": links,
            "links_count": len(links),
            "internal_links": _safe_call(count_internal_links, links, final_url, default=0),
            "external_links": _safe_call(count_external_links, links, final_url, default=0),

            # -----------------------------
            # Images
            # -----------------------------
            "images_count": _safe_call(count_images, soup, default=0),
            "images_with_alt": _safe_call(get_images_with_alt, soup, default=[]),
            "images_without_alt": _safe_call(get_images_without_alt, soup, default=[]),

            # -----------------------------
            # Structured Data
            # -----------------------------
            "structured_data": _safe_call(has_structured_data, soup, default=False),

            #---------------
            #date
            #---------------
            "analysis_date": datetime.now(UTC).isoformat(),

            # -----------------------------
            # Keyword Density
            # -----------------------------
            "keyword_density": _safe_call(calculate_keyword_density, soup, default=[])
        }

        return result

    except Exception as e:
        return {
            "success": False,
            "error": f"Analysis failed: {str(e)}"
        }


def _safe_call(func, *args, default=None):
    """
    Helper function sghira bach ila ay helper function rmat exception
    may-ti7ch l-audit kamel, n-rj3o gha default value (b7al empty list/string).
    """
    try:
        return func(*args)
    except Exception:
        return default