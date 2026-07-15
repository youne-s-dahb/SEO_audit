# 2 
# regroupe les fonctions utilitaires partagées par les autres fichiers.

# ----------------------------------------------------------------------------

"""
utils.py

Had fichier kayjma3 les fonctions utilitaires
li ymkn ysta3mlhom html_parser.py
ou seo_analyzer.py.
"""

import re
from urllib.parse import urlparse

# ----------------------------------------------------
# Supprimer les espaces inutiles
# ----------------------------------------------------

def remove_extra_spaces(text: str) -> str:
    """
    Kat7yed ga3 les espaces zaydin.
    """

    try:

        if not text:
            return ""

        return re.sub(r"\s+", " ", text).strip()

    except Exception:
        return ""


# ----------------------------------------------------
# Vérifier URL
# ----------------------------------------------------

def validate_url(url: str) -> bool:
    """
    Katverifi wach URL valide.

    Kat9bel ghir HTTP ou HTTPS.
    """

    try:

        parsed = urlparse(url)

        if parsed.scheme not in ("http", "https"):
            return False

        if not parsed.netloc:
            return False

        return True

    except Exception:
        return False


# ----------------------------------------------------
# Normaliser URL
# ----------------------------------------------------

def normalize_url(url: str) -> str:
    """
    Kat7yed '/' lakher men URL
    bach n9arno URLs b s7i7.
    """

    try:

        if not url:
            return ""

        return url.rstrip("/")

    except Exception:
        return ""


# ----------------------------------------------------
# Conversion sécurisée vers int
# ----------------------------------------------------

def safe_int(value, default: int = 0) -> int:
    """
    Kat7awel ay valeur l int
    bla ma yti7 programme.
    """

    try:
        return int(value)

    except (TypeError, ValueError):
        return default


# ----------------------------------------------------
# Conversion sécurisée vers float
# ----------------------------------------------------

def safe_float(value, default: float = 0.0) -> float:
    """
    Kat7awel ay valeur l float
    bla ma yti7 programme.
    """

    try:
        return float(value)

    except (TypeError, ValueError):
        return default