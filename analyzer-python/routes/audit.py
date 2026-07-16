from fastapi import APIRouter
import redis
import json
from services.pageSpeed import get_pagespeed_data

router = APIRouter()
r = redis.Redis(host='redis', port=6379, db=0)

@router.get("/audit")
async def audit_url(url: str):
    # 1. الدار ديال الـ Analyse
    full_data = await get_pagespeed_data(url)
    
    # 2. نوجدو الـ Payload
    payload = {
        "status": "completed",
        "globalScore": full_data.get('performance_score', 0), # استعملنا get باش ما يوقعش خطأ
        "url": url
    }
    
    # 3. نحطو النتيجة في Redis عوض ما نعيطو لـ Symfony مباشرة
    # هاد الشي كيخلي بايثون ديما "حر" ومايتسناش السيرفر الآخر
    key = f"audit_result:{url}"
    r.set(key, json.dumps(payload))
    r.expire(key, 3600)  # النتيجة كتبقى في Redis ساعة واحدة
    
    return {"message": "Audit completed and saved in Redis", "status": "success"}