from fastapi import APIRouter
# Importi ga3 l-functions mn l-fichie dyalk
from services.pageSpeed import get_pagespeed_data, format_simple_report

router = APIRouter()

@router.get("/audit")
async def audit_url(url: str):
    # 1. Jib l-data l-kamla
    full_data = await get_pagespeed_data(url)
    
    # Check ila kayna error f l-request
    if "error" in full_data:
        return full_data
        
    # 2. Formattiha l-rapport nqi l-user
    return format_simple_report(full_data)