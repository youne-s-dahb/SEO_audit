import os
import httpx


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

    async with httpx.AsyncClient() as client:
        response = await client.post(
            "https://google.serper.dev/search",
            headers=headers,
            json=payload
        )

        data = response.json()

    for result in data.get("organic", []):
        link = result.get("link", "")
        position = result.get("position")

        if site_url in link:
            return position

    return None