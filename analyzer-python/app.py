from fastapi import FastAPI
from routes import audit
from routes import serpApi
from routes import google_maps

app = FastAPI()

app.include_router(audit.router)
app.include_router(serpApi.router)
app.include_router(google_maps.router)

