import os
import json
import httpx
from bs4 import BeautifulSoup


async def extract_business_name(url):
    async with httpx.AsyncClient() as client:
        response = await client.get(
            url,
            follow_redirects=True,
            timeout=15
        )

    soup = BeautifulSoup(response.text, "html.parser")

    scripts = soup.find_all(
        "script",
        type="application/ld+json"
    )

    for script in scripts:
        try:
            data = json.loads(script.string)

            if isinstance(data, dict):
                if data.get("@type") in [
                    "Organization",
                    "LocalBusiness",
                    "Store"
                ]:
                    return data.get("name")
        except:
            pass

    meta = soup.find(
        "meta",
        property="og:site_name"
    )

    if meta:
        return meta.get("content")

    if soup.title:
        title = soup.title.text.strip()

        separators = ["|", "-", "–", "—"]

        for sep in separators:
            if sep in title:
                title = title.split(sep)[0].strip()
                break

        return title

    return None


async def check_google_maps_presence(url):
    business_name = await extract_business_name(url)

    if not business_name:
        return {
            "is_present": False
        }

    headers = {
        "X-API-KEY": os.getenv("SERPER_API_KEY"),
        "Content-Type": "application/json"
    }

    payload = {
        "q": business_name
    }

    async with httpx.AsyncClient() as client:
        response = await client.post(
            "https://google.serper.dev/maps",
            headers=headers,
            json=payload
        )

    data = response.json()

    places = data.get("places", [])

    if not places:
        return {
            "is_present": False,
            "business_name": business_name
        }

    place = places[0]

    place_title = place.get(
        "title",
        ""
    ).lower()

    if business_name.lower() not in place_title:
        return {
            "is_present": False,
            "business_name": business_name
        }

    return {
        "is_present": True,
        "business_name": business_name,
        "title": place.get("title"),
        "address": place.get("address"),
        "rating": place.get("rating"),
        "reviews_count": place.get("ratingCount"),
        "place_id": place.get("placeId")
    }