from fastapi import FastAPI
from routes import audit
from routes import serpApi
from routes import google_maps
from routes import audit_onpage

app = FastAPI()

app.include_router(audit.router)
app.include_router(serpApi.router)
app.include_router(google_maps.router)
app.include_router(audit_onpage.router)

@app.get("/")
async def root():
    return {"message": "SEO Audit API is running"}

@app.get("/health")
async def health():
    return {"status": "healthy"}