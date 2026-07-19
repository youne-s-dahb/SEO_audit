import re
import copy
from collections import Counter
from typing import List, Dict

from bs4 import BeautifulSoup

from analyzer.html_parser import (
    get_title,
    get_meta_description,
    get_headings,
)
from analyzer.utils import remove_extra_spaces

STOP_WORDS = {
    "le", "la", "les", "un", "une", "des", "de", "du", "et", "en",
    "est", "sont", "pour", "dans", "sur", "par", "avec", "au", "aux",
    "ce", "ces", "cet", "cette", "que", "qui", "quoi", "dont", "ou",
    "il", "elle", "ils", "elles", "nous", "vous", "je", "tu", "on",
    "ne", "pas", "plus", "moins", "tres", "son", "sa", "ses",
    "leur", "leurs", "notre", "votre", "mon", "ma", "mes", "ton", "ta",
    "tes", "se", "comme", "mais", "donc", "car", "si",
    "etre", "avoir", "faire", "tout", "tous", "toute", "toutes",

    "the", "a", "an", "and", "or", "but", "if", "then", "of", "to",
    "in", "on", "at", "for", "with", "by", "from", "is", "are", "was",
    "were", "be", "been", "being", "this", "that", "these", "those",
    "it", "its", "as", "not", "we", "you", "they", "he", "she",
}

MIN_WORD_LENGTH = 3

NOISE_TAGS = [
    "script", "style", "noscript", "iframe", "form",
    "nav", "header", "footer", "aside",
]

NOISE_ROLES = {"navigation", "banner", "contentinfo", "search"}

NOISE_CLASS_KEYWORDS = [
    "mw-editsection", "navbox", "vector-toc", "vector-menu",
    "navigation", "menu", "breadcrumb", "sidebar", "cookie",
    "skip-link", "mw-jump-link", "printfooter", "catlinks",
]


def clean_soup_for_text(soup: BeautifulSoup) -> BeautifulSoup:
    try:
        if not soup:
            return soup

        cleaned = copy.deepcopy(soup)

        for tag in cleaned.find_all(NOISE_TAGS):
            tag.decompose()

        for tag in cleaned.find_all(attrs={"role": True}):
            if tag.parent is None:
                continue
            if tag.get("role") in NOISE_ROLES:
                tag.decompose()

        for tag in cleaned.find_all(True):
            if tag.parent is None:
                continue

            classes = tag.get("class") or []
            id_attr = tag.get("id") or ""
            combined = (" ".join(classes) + " " + id_attr).lower()

            if any(keyword in combined for keyword in NOISE_CLASS_KEYWORDS):
                tag.decompose()

        return cleaned

    except Exception:
        return soup


def build_seo_text(soup: BeautifulSoup) -> str:
    """
    IMPORTANT: chaque etape est protegee individuellement. Si le nettoyage
    (clean_soup_for_text) ou une extraction precise echoue sur une structure
    HTML reelle complexe, on ne perd PAS tout le texte d'un coup - on garde
    ce qui a pu etre recupere, et en dernier recours on retombe sur le texte
    brut non nettoye plutot que de renvoyer une chaine vide.
    """
    try:
        if not soup:
            return ""

        cleaned_soup = clean_soup_for_text(soup)

        parts = []

        # Chaque extraction est isolee: une erreur ici ne doit pas
        # faire perdre les autres parties deja recuperees.
        try:
            title = get_title(cleaned_soup)
            if title:
                parts.append(title)
        except Exception:
            pass

        try:
            meta = get_meta_description(cleaned_soup)
            if meta:
                parts.append(meta)
        except Exception:
            pass

        try:
            headings = get_headings(cleaned_soup)
            if headings and isinstance(headings, dict):
                for values in headings.values():
                    if isinstance(values, list):
                        parts.extend(values)
        except Exception:
            pass

        try:
            body_text = cleaned_soup.get_text(separator=" ", strip=True)
            if body_text:
                parts.append(body_text)
        except Exception:
            pass

        result = remove_extra_spaces(" ".join(parts))

        # Filet de securite: si le nettoyage a fini par tout vider
        # (bug de decompose() sur une structure complexe reelle), on
        # retombe sur le texte brut NON nettoye plutot que de perdre
        # completement le calcul de densite.
        if not result:
            try:
                fallback_text = soup.get_text(separator=" ", strip=True)
                return remove_extra_spaces(fallback_text)
            except Exception:
                return ""

        return result

    except Exception:
        return ""


def clean_text(text: str) -> str:
    try:
        if not text:
            return ""
        text = text.lower()
        text = re.sub(r"[^a-zàâäéèêëîïôöùûüÿçñ\s]", " ", text)
        return remove_extra_spaces(text)
    except Exception:
        return ""


def tokenize(text: str) -> List[str]:
    try:
        if not text:
            return []
        words = text.split()
        return [
            w for w in words
            if len(w) >= MIN_WORD_LENGTH and w not in STOP_WORDS
        ]
    except Exception:
        return []


def get_bigrams(tokens: List[str]) -> List[str]:
    try:
        if not tokens or len(tokens) < 2:
            return []
        return [
            f"{tokens[i]} {tokens[i + 1]}"
            for i in range(len(tokens) - 1)
        ]
    except Exception:
        return []


def calculate_keyword_density(soup: BeautifulSoup, top_n: int = 10) -> List[Dict]:
    try:
        if not soup:
            return []

        raw_text = build_seo_text(soup)
        cleaned = clean_text(raw_text)
        tokens = tokenize(cleaned)

        total_words = len(tokens)
        if total_words == 0:
            return []

        bigrams = get_bigrams(tokens)
        counter = Counter(tokens) + Counter(bigrams)

        results = []
        for keyword, occurrences in counter.most_common(top_n):
            safe_keyword = keyword.strip()[:255]
            density = round((occurrences / total_words) * 100, 2)

            results.append({
                "keyword": safe_keyword,
                "occurrences": occurrences,
                "density_percent": density,
            })

        return results

    except Exception:
        return []