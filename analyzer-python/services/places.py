import os
import json
import re
import unicodedata
import logging

from urllib.parse import urlparse

import httpx
from bs4 import BeautifulSoup


# =========================================================
# LOGGING
# =========================================================

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


# =========================================================
# NORMALIZE TEXT
# =========================================================

def normalize_text(text: str) -> str:

    if not text:
        return ""

    text = str(text).lower().strip()

    text = unicodedata.normalize(
        "NFD",
        text
    )

    text = "".join(
        char
        for char in text
        if unicodedata.category(char) != "Mn"
    )

    text = re.sub(
        r"[^\w\s]",
        " ",
        text,
        flags=re.UNICODE
    )

    text = re.sub(
        r"\s+",
        " ",
        text
    )

    return text.strip()


# =========================================================
# EXTRACT DOMAIN
# =========================================================

def extract_domain(url: str):

    try:

        hostname = urlparse(url).hostname

        if not hostname:
            return None

        return hostname.lower().replace(
            "www.",
            ""
        )

    except Exception:

        return None


# =========================================================
# DETECT COUNTRY
# =========================================================

def detect_country(domain: str):

    if not domain:
        return "us"

    domain = domain.lower()

    country_map = {

        ".ma": "ma",
        ".fr": "fr",
        ".jp": "jp",
        ".uk": "gb",
        ".de": "de",
        ".es": "es",
        ".it": "it",
        ".be": "be",
        ".ch": "ch",
        ".ca": "ca",
        ".au": "au",
        ".nl": "nl",
        ".pt": "pt",
        ".at": "at",
        ".ae": "ae",
        ".sa": "sa",

    }

    for suffix, country in country_map.items():

        if domain.endswith(suffix):

            return country

    return "us"


# =========================================================
# SEARCH JSON-LD
# =========================================================

def search_jsonld_name(data):

    if isinstance(data, dict):

        data_type = data.get(
            "@type",
            ""
        )

        if isinstance(data_type, list):

            types = [
                normalize_text(x)
                for x in data_type
            ]

        else:

            types = [
                normalize_text(data_type)
            ]


        valid_types = [

            "organization",
            "localbusiness",
            "restaurant",
            "store",
            "hotel",
            "medicalbusiness",
            "professionalservice",
            "automotivebusiness",
            "financialservice",
            "realestateagent",
            "shoppingcenter"

        ]


        if any(
            item in valid_types
            for item in types
        ):

            name = data.get("name")

            if name:

                return str(
                    name
                ).strip()


        for value in data.values():

            result = search_jsonld_name(
                value
            )

            if result:

                return result


    elif isinstance(data, list):

        for item in data:

            result = search_jsonld_name(
                item
            )

            if result:

                return result


    return None


# =========================================================
# EXTRACT BUSINESS NAME
# =========================================================

async def extract_business_name(
    url: str,
    client: httpx.AsyncClient
):

    try:

        response = await client.get(
            url,
            timeout=12,
            follow_redirects=True
        )

        response.raise_for_status()

        soup = BeautifulSoup(
            response.text,
            "html.parser"
        )


        # =================================================
        # JSON-LD
        # =================================================

        scripts = soup.find_all(
            "script",
            type="application/ld+json"
        )


        for script in scripts:

            try:

                content = script.string

                if not content:
                    continue

                data = json.loads(
                    content
                )

                name = search_jsonld_name(
                    data
                )

                if name:

                    logger.info(
                        f"JSON-LD name: {name}"
                    )

                    return name

            except Exception:

                continue


        # =================================================
        # OG SITE NAME
        # =================================================

        meta = soup.find(
            "meta",
            property="og:site_name"
        )

        if meta:

            content = meta.get(
                "content"
            )

            if content:

                return content.strip()


        # =================================================
        # APPLICATION NAME
        # =================================================

        meta = soup.find(
            "meta",
            attrs={
                "name": "application-name"
            }
        )

        if meta:

            content = meta.get(
                "content"
            )

            if content:

                return content.strip()


        # =================================================
        # TITLE
        # =================================================

        if soup.title:

            title = soup.title.get_text(
                strip=True
            )

            if title:

                separators = [

                    " | ",
                    " - ",
                    " – ",
                    " — ",
                    " :: ",
                    " : "

                ]


                parts = [title]


                for separator in separators:

                    if separator in title:

                        parts = [
                            p.strip()
                            for p in title.split(
                                separator
                            )
                            if p.strip()
                        ]

                        break


                if len(parts) > 1:

                    # Try first AND last.
                    # We will search both later.

                    return parts[0]

                return title


    except Exception as e:

        logger.warning(
            f"Business extraction failed: {e}"
        )


    return None


# =========================================================
# GET SEARCH NAME VARIATIONS
# =========================================================

def build_name_variations(
    business_name,
    domain
):

    variations = []


    if business_name:

        variations.append(
            business_name
        )


    if domain:

        domain_name = re.sub(
            r"\.(com|net|org|ma|fr|jp|co\.jp|uk)$",
            "",
            domain,
            flags=re.IGNORECASE
        )

        domain_name = domain_name.replace(
            "-",
            " "
        ).replace(
            "_",
            " "
        )


        if domain_name:

            variations.append(
                domain_name
            )


    # Remove duplicates
    result = []

    seen = set()


    for item in variations:

        normalized = normalize_text(
            item
        )

        if normalized and normalized not in seen:

            seen.add(
                normalized
            )

            result.append(
                item.strip()
            )


    return result


# =========================================================
# MATCH SCORE
# =========================================================

def calculate_match_score(
    business_name,
    place_title,
    domain
):

    business = normalize_text(
        business_name
    )

    place = normalize_text(
        place_title
    )

    domain_name = normalize_text(
        domain or ""
    )


    if not place:

        return 0


    # =====================================================
    # EXACT
    # =====================================================

    if business and business == place:

        return 100


    # =====================================================
    # CONTAINS
    # =====================================================

    if business and business in place:

        return 95


    if business and place in business:

        return 90


    score = 0


    # =====================================================
    # BUSINESS WORDS
    # =====================================================

    business_words = [

        word

        for word in business.split()

        if len(word) >= 3

    ]


    if business_words:

        matches = sum(

            1

            for word in business_words

            if word in place

        )


        ratio = (
            matches /
            len(business_words)
        )


        score = max(
            score,
            int(ratio * 85)
        )


    # =====================================================
    # DOMAIN WORDS
    # =====================================================

    domain_words = re.split(
        r"[.\-_]",
        domain_name
    )


    domain_words = [

        word

        for word in domain_words

        if len(word) >= 3

    ]


    if domain_words:

        domain_matches = sum(

            1

            for word in domain_words

            if word in place

        )


        if domain_matches:

            score += min(
                domain_matches * 8,
                15
            )


    return min(
        score,
        100
    )


# =========================================================
# GOOGLE MAPS PRESENCE
# =========================================================

async def check_google_maps_presence(
    url: str,
    country_code: str = None
):

    # =====================================================
    # API KEY
    # =====================================================

    api_key = os.getenv(
        "SERPER_API_KEY"
    )


    if not api_key:

        return {

            "is_present": False,

            "business_name": None,

            "status": "not_analyzed",

            "error":
                "SERPER_API_KEY is not configured"

        }


    # =====================================================
    # DOMAIN
    # =====================================================

    domain = extract_domain(
        url
    )


    if not domain:

        return {

            "is_present": False,

            "business_name": None,

            "status": "not_found",

            "error":
                "Invalid URL"

        }


    # =====================================================
    # COUNTRY
    # =====================================================

    country = (
        country_code
        or detect_country(domain)
    )


    logger.info(
        f"Domain: {domain}"
    )

    logger.info(
        f"Country: {country}"
    )


    # =====================================================
    # HTTP CLIENT
    # =====================================================

    async with httpx.AsyncClient(
        follow_redirects=True,
        timeout=20
    ) as client:


        # =================================================
        # BUSINESS NAME
        # =================================================

        business_name = await extract_business_name(
            url,
            client
        )


        if not business_name:

            business_name = domain


        logger.info(
            f"Business name: {business_name}"
        )


        # =================================================
        # SEARCH VARIATIONS
        # =================================================

        names = build_name_variations(
            business_name,
            domain
        )


        queries = []


        for name in names:

            queries.append(
                f'"{name}"'
            )

            queries.append(
                f'{name} {domain}'
            )

            queries.append(
                name
            )


        # Domain search
        queries.append(
            domain
        )


        # Remove duplicates
        unique_queries = []

        seen_queries = set()


        for query in queries:

            key = normalize_text(
                query
            )

            if key not in seen_queries:

                seen_queries.add(
                    key
                )

                unique_queries.append(
                    query
                )


        # =================================================
        # SERPER
        # =================================================

        headers = {

            "X-API-KEY":
                api_key,

            "Content-Type":
                "application/json"

        }


        all_places = []


        for query in unique_queries:

            try:

                logger.info(
                    f"Maps query: {query}"
                )


                payload = {

                    "q":
                        query,

                    "hl":
                        "fr",

                    "gl":
                        country

                }


                response = await client.post(

                    "https://google.serper.dev/maps",

                    headers=headers,

                    json=payload,

                    timeout=20

                )


                response.raise_for_status()


                data = response.json()


                places = data.get(
                    "places",
                    []
                )


                if places:

                    all_places.extend(
                        places
                    )


                # Enough results
                if len(
                    all_places
                ) >= 15:

                    break


            except Exception as e:

                logger.warning(
                    f"Serper error for '{query}': {e}"
                )

                continue


        # =================================================
        # NO RESULTS
        # =================================================

        if not all_places:

            return {

                "is_present": False,

                "business_name":
                    business_name,

                "status":
                    "not_found"

            }


        # =================================================
        # UNIQUE PLACES
        # =================================================

        unique_places = {}


        for place in all_places:

            place_id = place.get(
                "placeId"
            )

            title = place.get(
                "title",
                ""
            )


            key = (

                place_id

                or normalize_text(
                    title
                )

            )


            if key:

                unique_places[key] = place


        places = list(
            unique_places.values()
        )


        # =================================================
        # FIND BEST RESULT
        # =================================================

        best_place = None
        best_score = 0


        for place in places:

            title = place.get(
                "title",
                ""
            )


            # Test against every possible name
            place_score = 0


            for name in names:

                score = calculate_match_score(

                    name,

                    title,

                    domain

                )


                place_score = max(
                    place_score,
                    score
                )


            logger.info(
                f"Result: {title} | score={place_score}"
            )


            if place_score > best_score:

                best_score = place_score

                best_place = place


        # =================================================
        # MATCH NOT GOOD ENOUGH
        # =================================================

        if not best_place or best_score < 45:

            return {

                "is_present": False,

                "business_name":
                    business_name,

                "status":
                    "not_found"

            }


        # =================================================
        # FOUND
        # =================================================

        return {

            "is_present":
                True,

            "business_name":
                business_name,

            "title":
                best_place.get(
                    "title"
                ),

            "address":
                best_place.get(
                    "address"
                ),

            "rating":
                best_place.get(
                    "rating"
                ),

            "reviews_count":
                best_place.get(
                    "ratingCount"
                ),

            "place_id":
                best_place.get(
                    "placeId"
                ),

            "status":
                "present",

            "match_score":
                best_score

        }