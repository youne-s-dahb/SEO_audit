import os
import httpx
from urllib.parse import urlparse


async def get_ranking(keyword: str, site_url: str):
    api_key = os.getenv("SERPER_API_KEY")

    if not api_key:
        return {
            "error": "SERPER_API_KEY n'est pas configurée.",
            "position": None
        }

    keyword = keyword.strip()
    site_url = site_url.strip()

    if not keyword:
        return {
            "error": "Le mot-clé est obligatoire.",
            "position": None
        }

    if not site_url:
        return {
            "error": "L'URL du site est obligatoire.",
            "position": None
        }

    try:
        parsed_url = urlparse(site_url)

        target_domain = parsed_url.netloc.lower().replace(
            "www.",
            ""
        )

        if not target_domain:
            return {
                "error": "URL du site invalide.",
                "position": None
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

        async with httpx.AsyncClient(timeout=30.0) as client:

            response = await client.post(
                "https://google.serper.dev/search",
                headers=headers,
                json=payload
            )

        print("SERPER STATUS:", response.status_code)
        print("SERPER RESPONSE:", response.text)

        # Serper error
        if response.status_code != 200:
            return {
                "error": (
                    f"Serper API error "
                    f"(HTTP {response.status_code})"
                ),
                "serper_status": response.status_code,
                "position": None
            }

        # JSON parsing
        try:
            data = response.json()
        except Exception as e:
            print("SERPER JSON ERROR:", str(e))

            return {
                "error": "Réponse invalide de Serper API.",
                "position": None
            }

        print("SERPER DATA:", data)

        # API error returned inside JSON
        if "error" in data:
            return {
                "error": str(data["error"]),
                "position": None
            }

        # Search results
        for result in data.get("organic", []):

            link = result.get("link", "")

            if not link:
                continue

            result_domain = (
                urlparse(link)
                .netloc
                .lower()
                .replace("www.", "")
            )

            print(
                "CHECK DOMAIN:",
                result_domain,
                "VS",
                target_domain
            )

            if result_domain == target_domain:

                position = result.get("position")

                return {
                    "position": position,
                    "domain": target_domain,
                    "link": link
                }

        # Site not found in first results
        return {
            "position": None,
            "domain": target_domain,
            "message": "Site non trouvé dans les résultats."
        }

    except httpx.TimeoutException:
        print("SERPER TIMEOUT")

        return {
            "error": "Timeout lors de la connexion à Serper API.",
            "position": None
        }

    except httpx.RequestError as e:
        print("SERPER REQUEST ERROR:", str(e))

        return {
            "error": f"Erreur de connexion à Serper API: {str(e)}",
            "position": None
        }

    except Exception as e:
        print("RANKING ERROR:", repr(e))

        return {
            "error": f"Erreur interne du ranking: {str(e)}",
            "position": None
        }