# 1 
# extrait depuis le HTML les informations utiles comme le title, les balises meta, les titres, les images et les liens.
# Mission
# Analyser le HTML avec BeautifulSoup.

#--------------------------------------------------------------------------

from bs4 import BeautifulSoup
from typing import Dict, List
from urllib.parse import urlparse

# Parse HTML

def parse_html(html: str) -> BeautifulSoup:
    """
    Had fonction kat7awel HTML l BeautifulSoup object.
    Ila HTML khawi, kat3ti soup khawi bach ma yti7ch programme.
    """

    if not html:
        html = ""

    return BeautifulSoup(html, "lxml")


# Title

def get_title(soup: BeautifulSoup) -> str:
    """
    Katjib Title.
    """

    try:
        if soup.title and soup.title.string:
            return soup.title.string.strip()

        return ""

    except Exception:
        return ""

# title length
def get_title_length(soup) -> int:
    """
    Kat7seb 3adad l7orof dyal Title.
    """

    try:
        title = get_title(soup)
        return len(title)

    except Exception:
        return 0

# Meta Description

def get_meta_description(soup: BeautifulSoup) -> str:
    """
    Katjib Meta Description.
    """

    try:
        meta = soup.find("meta", attrs={"name": "description"})

        if meta:
            return meta.get("content", "").strip()

        return ""

    except Exception:
        return ""

# meta length
def get_meta_length(soup) -> int:
    """
    Kat7seb 3adad l7orof dyal Meta Description.
    """

    try:
        meta = get_meta_description(soup)
        return len(meta)

    except Exception:
        return 0

# Headings

def get_headings(soup: BeautifulSoup) -> Dict[str, List[str]]:
    """
    Katjib H1 -> H6.
    """

    headings = {}

    try:

        for i in range(1, 7):

            tag = f"h{i}"

            headings[tag] = [
                h.get_text(strip=True)
                for h in soup.find_all(tag)
            ]

        return headings

    except Exception:
        return {
            "h1": [],
            "h2": [],
            "h3": [],
            "h4": [],
            "h5": [],
            "h6": [],
        }


# Images Count

def count_images(soup: BeautifulSoup) -> int:
    """
    Kat7seb ch7al mn image kayna.
    """

    try:
        return len(soup.find_all("img"))

    except Exception:
        return 0


# Images without ALT

def get_images_without_alt(soup: BeautifulSoup) -> List[str]:
    """
    Katjib images li ma3andhomch ALT.
    """

    images_without_alt = []

    try:

        images = soup.find_all("img")

        for image in images:

            alt = image.get("alt")

            if alt is None or alt.strip() == "":
                images_without_alt.append(
                    image.get("src", "")
                )

        return images_without_alt

    except Exception:
        return []


# les images avec alt
def get_images_with_alt(soup) -> list:
    """
    Katjib ghir les images li 3andhom alt.
    """

    try:
        images = []

        for image in soup.find_all("img"):
            alt = image.get("alt", "").strip()

            if alt:
                images.append({
                    "src": image.get("src", "").strip(),
                    "alt": alt
                })

        return images

    except Exception:
        return []


# Links Count

def count_links(soup: BeautifulSoup) -> int:
    """
    Kat7seb ga3 links.
    """

    try:
        return len(soup.find_all("a"))

    except Exception:
        return 0
    

# links
def get_links(soup) -> list:
    """
    Katjib ga3 les liens.
    """

    try:

        links = []

        for link in soup.find_all("a"):

            href = link.get("href", "").strip()

            if href:
                links.append(href)

        return links

    except Exception:
        return []


# Word Count

def count_words(soup: BeautifulSoup) -> int:
    """
    Kat7seb 3adad lklmat.
    """

    try:

        text = soup.get_text(separator=" ", strip=True)

        words = text.split()

        return len(words)

    except Exception:
        return 0
    

# canonical url
def get_canonical_url(soup) -> str:
    """
    Katjib Canonical URL ila kayna.
    """

    try:

        canonical = soup.find("link", rel="canonical")

        if canonical:
            return canonical.get("href", "").strip()

        return ""

    except Exception:
        return ""

# meta robots
def get_meta_robots(soup) -> str:
    """
    Katjib Meta Robots.
    """

    try:

        robots = soup.find("meta", attrs={"name": "robots"})

        if robots:
            return robots.get("content", "").strip()

        return ""

    except Exception:
        return ""
    

#lang (depuis html lang="")
def get_lang(soup) -> str:
    """
    Katjib language men <html lang="">
    """

    try:

        html = soup.find("html")

        if html:
            return html.get("lang", "").strip()

        return ""

    except Exception:
        return ""

# viewport meta
def get_viewport(soup) -> bool:
    """
    Katchecki wach viewport meta kayna.
    """

    try:

        viewport = soup.find(
            "meta",
            attrs={"name": "viewport"}
        )

        return viewport is not None

    except Exception:
        return False

# nbr des h1
def get_h1_count(soup) -> int:
    """
    Kat7seb ch7al mn H1 kayn.
    """

    try:
        return len(soup.find_all("h1"))

    except Exception:
        return 0
    
# si h1 est unique
def is_h1_unique(soup) -> bool:
    """
    Katverifi wach kayn H1 wa7ed.
    """

    try:
        return get_h1_count(soup) == 1

    except Exception:
        return False

# tjib ga3 les liens
def is_h1_unique(soup) -> bool:
    """
    Katverifi wach kayn H1 wa7ed.
    """

    try:
        return get_h1_count(soup) == 1

    except Exception:
        return False

#les liens interne (khaso base url)

def count_internal_links(links: list, base_url: str) -> int:
    """
    Kat7seb les liens internes.
    """

    try:

        base_domain = urlparse(base_url).netloc

        count = 0

        for link in links:

            parsed = urlparse(link)

            if parsed.netloc == "" or parsed.netloc == base_domain:
                count += 1

        return count

    except Exception:
        return 0


# les liens externe (khaso base url)
from urllib.parse import urlparse


def count_external_links(links: list, base_url: str) -> int:
    """
    Kat7seb les liens externes.
    """

    try:

        base_domain = urlparse(base_url).netloc

        count = 0

        for link in links:

            parsed = urlparse(link)

            if parsed.netloc and parsed.netloc != base_domain:
                count += 1

        return count

    except Exception:
        return 0

# data structurée (Structured Data ma katzidch directement le référencement (SEO), walakin kat7assen l'affichage f Google, katfham contenu mzyan, w kat9dar tzid nombre de clics.)
def has_structured_data(soup) -> bool:
    """
    Katchecki wach Structured Data kayna.
    JSON-LD ou Microdata.
    """

    try:

        json_ld = soup.find(
            "script",
            attrs={"type": "application/ld+json"}
        )

        microdata = soup.find(attrs={"itemscope": True})

        return json_ld is not None or microdata is not None

    except Exception:
        return False
