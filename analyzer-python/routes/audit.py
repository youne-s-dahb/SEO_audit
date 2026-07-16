from fastapi import APIRouter
# Importi ga3 l-functions mn l-fichie dyalk
from services.pageSpeed import get_pagespeed_data, format_simple_report
import requests

router = APIRouter()
@router.get("/audit")
async def audit_url(url: str):
    full_data = await get_pagespeed_data(url)
    
    # الـ Data اللي بغيتي تصيفط لـ Symfony
    payload = {
        "status": "completed",
        "globalScore": full_data['score'],
        "url": url
    }
    
    # صيفط لـ Symfony
    response = requests.post("http://api:8000/api/audit/callback", json=payload)
    
    return {"message": "Audit sent to Symfony", "status": response.status_code}