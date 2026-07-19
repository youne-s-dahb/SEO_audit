import os
import httpx
from urllib.parse import urlparse


async def get_ranking(keyword: str, site_url: str):
    api_key = os.getenv("SERPER_API_KEY")

    if not api_key:
        return {
            "error": "SERPER_API_KEY n'est pas configurée."
        }

    headers = {
        "X-API-KEY": api_key,
        "Content-Type": "application/json"
    }

    payload = {
        "q": keyword,
        "gl": "ma",
        "hl": "fr"
    }

    target_domain = urlparse(site_url).netloc.replace("www.", "")

    async with httpx.AsyncClient() as client:
        response = await client.post(
            "https://google.serper.dev/search",
            headers=headers,
            json=payload
        )

    data = response.json()
    print(data)
    for result in data.get("organic", []):
        link = result.get("link", "")
        result_domain = urlparse(link).netloc.replace("www.", "")

        if result_domain == target_domain:
            return result.get("position")

    return None