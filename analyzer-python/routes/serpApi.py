from fastapi import APIRouter
from services.serApi import get_ranking

router = APIRouter(prefix="/serp", tags=["SERP"])


@router.get("/get-ranking")
async def get_ranking_route(keyword: str, site_url: str):
    result = await get_ranking(keyword, site_url)

    # Erreur interne (Serper down, clé manquante, timeout...)
    if result.get("error"):
        return {
            "status": "error",
            "message": result["error"],
        }

    position = result.get("position")

    if position:
        return {
            "status": "success",
            "keyword": keyword,
            "site_url": site_url,
            "position": position,
            "search_page": ((position - 1) // 10) + 1,
        }

    return {
        "status": "not_found",
        "message": result.get("message", "Le site n'est pas dans les résultats."),
    }