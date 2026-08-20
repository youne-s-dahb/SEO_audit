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
    has_structured_data,
    get_clean_soup
)

from analyzer.keyword_density import calculate_keyword_density

# Fallback Playwright pour les sites JS
from analyzer.js_renderer import (
    render_page_with_js,
    looks_like_empty_shell
)


# =========================================================
# CONSTANTES
# =========================================================

USER_AGENT = {
    "User-Agent": "SEO Audit Bot/1.0"
}

# Timeout connexion
REQUEST_CONNECT_TIMEOUT = 5

# Timeout lecture
REQUEST_READ_TIMEOUT = 10

# Timeout requests :
# 5 secondes pour la connexion
# 10 secondes pour recevoir les données
REQUEST_TIMEOUT = (
    REQUEST_CONNECT_TIMEOUT,
    REQUEST_READ_TIMEOUT
)

# Taille maximale : 10 MB
MAX_CONTENT_SIZE = 10 * 1024 * 1024

# Nombre max de redirections
MAX_REDIRECTS = 5

# Lock pour sécuriser le patch DNS
_dns_patch_lock = threading.Lock()


# =========================================================
# VÉRIFIER IP PRIVÉE - PROTECTION SSRF
# =========================================================

def is_private_ip(hostname: str) -> bool:
    """
    Vérifie si le hostname pointe vers une IP privée/interne.

    Protection SSRF :
    - IPv4
    - IPv6
    - localhost
    - loopback
    """

    try:

        infos = socket.getaddrinfo(
            hostname,
            None,
            socket.AF_UNSPEC,
            socket.SOCK_STREAM
        )

        for (
            family,
            socktype,
            proto,
            canonname,
            sockaddr
        ) in infos:

            ip = sockaddr[0]

            ip_obj = ipaddress.ip_address(ip)

            if (
                ip_obj.is_private
                or ip_obj.is_loopback
            ):
                return True

        return False

    except Exception:

        # Fail-safe
        return True


# =========================================================
# PROTECTION SSRF - DNS PINNING
# =========================================================

@contextmanager
def _ssrf_safe_connection():
    """
    Intercepte la résolution DNS de urllib3.

    Vérifie les IPs avant chaque connexion TCP
    et empêche les connexions vers des IPs privées.
    """

    global _dns_patch_lock

    original_create_connection = (
        urllib3_connection.create_connection
    )

    def patched_create_connection(
        address,
        *args,
        **kwargs
    ):

        host, port = address

        try:

            infos = socket.getaddrinfo(
                host,
                port,
                socket.AF_UNSPEC,
                socket.SOCK_STREAM
            )

        except socket.gaierror as exc:

            raise requests.exceptions.ConnectionError(
                f"DNS resolution failed for host: {host}"
            ) from exc

        # Vérifier toutes les IP retournées
        for (
            family,
            socktype,
            proto,
            canonname,
            sockaddr
        ) in infos:

            ip = sockaddr[0]

            try:

                ip_obj = ipaddress.ip_address(ip)

            except ValueError:

                continue

            if (
                ip_obj.is_private
                or ip_obj.is_loopback
            ):

                raise requests.exceptions.ConnectionError(
                    "Blocked connection to private/internal IP address"
                )

        # DNS pinning
        target_ip = infos[0][4][0]

        return original_create_connection(
            (target_ip, port),
            *args,
            **kwargs
        )

    with _dns_patch_lock:

        urllib3_connection.create_connection = (
            patched_create_connection
        )

        try:

            yield

        finally:

            urllib3_connection.create_connection = (
                original_create_connection
            )


# =========================================================
# DOWNLOAD PAGE HTML
# =========================================================

def fetch_page(url: str):
    """
    Télécharge la page HTML.

    Vérifications :
    - URL valide
    - Protection SSRF
    - Timeout connexion
    - Timeout lecture
    - User-Agent
    - Taille maximale
    - Content-Type
    """

    try:

        # =================================================
        # URL VALIDATION
        # =================================================

        if not validate_url(url):

            return {
                "success": False,
                "error": "Invalid URL."
            }

        # =================================================
        # NORMALIZE URL
        # =================================================

        url = normalize_url(url)

        parsed = urlparse(url)

        # =================================================
        # SSRF CHECK
        # =================================================

        if not parsed.hostname:

            return {
                "success": False,
                "error": "Invalid hostname."
            }

        if is_private_ip(parsed.hostname):

            return {
                "success": False,
                "error": "Private IPs are not allowed."
            }

        # =================================================
        # TIMER
        # =================================================

        start = time.perf_counter()

        # =================================================
        # REQUEST
        # =================================================

        with _ssrf_safe_connection():

            session = requests.Session()

            session.max_redirects = MAX_REDIRECTS

            response = session.get(
                url,
                headers=USER_AGENT,
                timeout=REQUEST_TIMEOUT,
                allow_redirects=True,
                stream=True
            )

            # =================================================
            # CONTENT LENGTH
            # =================================================

            content_length = response.headers.get(
                "Content-Length"
            )

            if content_length:

                try:

                    if int(content_length) > MAX_CONTENT_SIZE:

                        return {
                            "success": False,
                            "error": "Page too large."
                        }

                except ValueError:

                    pass

            # =================================================
            # DOWNLOAD CHUNKS
            # =================================================

            content = bytearray()

            for chunk in response.iter_content(
                chunk_size=8192
            ):

                if not chunk:
                    continue

                content.extend(chunk)

                if len(content) > MAX_CONTENT_SIZE:

                    return {
                        "success": False,
                        "error":
                            "Page too large (Stream limit exceeded)."
                    }

        # =================================================
        # RESPONSE TIME
        # =================================================

        end = time.perf_counter()

        response_time = round(
            (end - start) * 1000,
            2
        )

        # =================================================
        # CONTENT TYPE
        # =================================================

        content_type = response.headers.get(
            "Content-Type",
            ""
        )

        if "text/html" not in content_type.lower():

            return {
                "success": False,
                "error": "URL is not an HTML page."
            }

        # =================================================
        # DECODE HTML
        # =================================================

        html_text = content.decode(
            "utf-8",
            errors="ignore"
        )

        # =================================================
        # SUCCESS
        # =================================================

        return {

            "success": True,

            "html":
                html_text,

            "status_code":
                response.status_code,

            "response_time_ms":
                response_time,

            "content_type":
                content_type,

            "final_url":
                response.url

        }

    # =====================================================
    # CONNECTION ERROR
    # =====================================================

    except requests.exceptions.ConnectionError as e:

        return {
            "success": False,
            "error": str(e)
        }

    # =====================================================
    # TIMEOUT
    # =====================================================

    except requests.exceptions.Timeout:

        return {
            "success": False,
            "error":
                "Le site web a mis trop de temps à répondre (Timeout)."
        }

    # =====================================================
    # REQUEST ERROR
    # =====================================================

    except requests.RequestException:

        return {
            "success": False,
            "error":
                "Erreur lors de la connexion au site web."
        }

    # =====================================================
    # OTHER ERROR
    # =====================================================

    except Exception:

        return {
            "success": False,
            "error":
                "Unexpected error during SEO analysis."
        }


# =========================================================
# SEO ANALYSIS
# =========================================================

def analyze(url: str) -> dict:
    """
    Fonction principale appelée par FastAPI.

    URL
        ↓
    fetch_page()
        ↓
    BeautifulSoup
        ↓
    Détection JS
        ↓
    Playwright si nécessaire
        ↓
    HTML Parser
        ↓
    JSON
    """

    try:

        # =================================================
        # DOWNLOAD PAGE
        # =================================================

        page = fetch_page(url)

        if not page["success"]:

            return page

        # =================================================
        # PARSE HTML
        # =================================================

        soup = parse_html(
            page["html"]
        )

        if not soup:

            return {
                "success": False,
                "error":
                    "Failed to parse HTML document."
            }

        clean_soup = get_clean_soup(
            soup
        )

        # =================================================
        # QUICK JS SHELL DETECTION
        # =================================================

        quick_headings = _safe_call(
            get_headings,
            soup,
            default={}
        )

        quick_links = _safe_call(
            get_links,
            soup,
            default=[]
        )

        quick_check = {

            "word_count":
                _safe_call(
                    count_words,
                    clean_soup,
                    default=0
                ),

            "headings_count":
                sum(
                    len(v)
                    for v in quick_headings.values()
                ),

            "images_count":
                _safe_call(
                    count_images,
                    soup,
                    default=0
                ),

            "links_count":
                len(quick_links)

        }

        # =================================================
        # PLAYWRIGHT FALLBACK
        # =================================================

        if looks_like_empty_shell(
            quick_check
        ):

            js_page = render_page_with_js(
                url
            )

            if js_page.get("success"):

                js_soup = parse_html(
                    js_page["html"]
                )

                if js_soup:

                    js_word_count = _safe_call(
                        count_words,
                        js_soup,
                        default=0
                    )

                    # Utiliser JS uniquement
                    # s'il apporte plus de contenu
                    if (
                        js_word_count
                        > quick_check["word_count"]
                    ):

                        page = js_page

                        soup = js_soup

                        clean_soup = get_clean_soup(
                            soup
                        )

        # =================================================
        # TITLE
        # =================================================

        try:

            title = get_title(
                soup
            ) or ""

            title_length = len(
                title
            ) if title else 0

        except Exception:

            title = ""

            title_length = 0

        # =================================================
        # META DESCRIPTION
        # =================================================

        try:

            meta_desc = get_meta_description(
                soup
            ) or ""

            meta_length = len(
                meta_desc
            ) if meta_desc else 0

        except Exception:

            meta_desc = ""

            meta_length = 0

        # =================================================
        # LINKS
        # =================================================

        links = _safe_call(
            get_links,
            soup,
            default=[]
        )

        # =================================================
        # FINAL URL
        # =================================================

        final_url = (
            page.get("final_url")
            or url
        )

        # =================================================
        # HEADINGS
        # =================================================

        headings = _safe_call(
            get_headings,
            soup,
            default={}
        )

        # =================================================
        # RESULT
        # =================================================

        result = {

            # =================================================
            # GENERAL
            # =================================================

            "success":
                True,

            "url":
                final_url,

            "status_code":
                page.get(
                    "status_code"
                ),

            "response_time_ms":
                page.get(
                    "response_time_ms"
                ),

            "content_type":
                page.get(
                    "content_type"
                ),

            "rendered_with_js":
                page.get(
                    "rendered_with_js",
                    False
                ),

            # =================================================
            # TITLE
            # =================================================

            "title":
                title,

            "title_length":
                title_length,

            # =================================================
            # META
            # =================================================

            "meta_description":
                meta_desc,

            "meta_length":
                meta_length,

            # =================================================
            # TECHNICAL SEO
            # =================================================

            "canonical_url":
                _safe_call(
                    get_canonical_url,
                    soup,
                    default=""
                ),

            "meta_robots":
                _safe_call(
                    get_meta_robots,
                    soup,
                    default=""
                ),

            "language":
                _safe_call(
                    get_lang,
                    soup,
                    default=""
                ),

            "viewport":
                _safe_call(
                    get_viewport,
                    soup,
                    default=""
                ),

            # =================================================
            # HEADINGS
            # =================================================

            "headings":
                headings,

            "headings_count":
                sum(
                    len(v)
                    for v in headings.values()
                ),

            "h1_count":
                _safe_call(
                    get_h1_count,
                    soup,
                    default=0
                ),

            "h1_is_unique":
                _safe_call(
                    is_h1_unique,
                    soup,
                    default=False
                ),

            # =================================================
            # CONTENT
            # =================================================

            "word_count":
                _safe_call(
                    count_words,
                    soup,
                    default=0
                ),

            # =================================================
            # LINKS
            # =================================================

            "links":
                links,

            "links_count":
                len(links),

            "internal_links":
                _safe_call(
                    count_internal_links,
                    links,
                    final_url,
                    default=0
                ),

            "external_links":
                _safe_call(
                    count_external_links,
                    links,
                    final_url,
                    default=0
                ),

            # =================================================
            # IMAGES
            # =================================================

            "images_count":
                _safe_call(
                    count_images,
                    soup,
                    default=0
                ),

            "images_with_alt":
                _safe_call(
                    get_images_with_alt,
                    soup,
                    default=[]
                ),

            "images_without_alt":
                _safe_call(
                    get_images_without_alt,
                    soup,
                    default=[]
                ),

            # =================================================
            # STRUCTURED DATA
            # =================================================

            "structured_data":
                _safe_call(
                    has_structured_data,
                    soup,
                    default=False
                ),

            # =================================================
            # DATE
            # =================================================

            "analysis_date":
                datetime.now(
                    UTC
                ).isoformat(),

            # =================================================
            # KEYWORD DENSITY
            # =================================================

            "keyword_density":
                _safe_call(
                    calculate_keyword_density,
                    clean_soup,
                    default=[]
                )

        }

        return result

    # =====================================================
    # GLOBAL ERROR
    # =====================================================

    except Exception as e:

        return {
            "success": False,
            "error":
                f"Analysis failed: {str(e)}"
        }


# =========================================================
# SAFE CALL
# =========================================================

def _safe_call(
    func,
    *args,
    default=None
):
    """
    Helper function bach ila chi helper function
    rmat exception, l-audit kamel ma yti7ch.
    """

    try:

        return func(
            *args
        )

    except Exception:

        return default