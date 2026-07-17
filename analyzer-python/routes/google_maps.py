from fastapi import APIRouter
from services.places import (
    check_google_maps_presence
)

router = APIRouter(
    prefix="/maps",
    tags=["Google Maps"]
)


@router.get("/presence")
async def presence(url: str):
    return await check_google_maps_presence(url)