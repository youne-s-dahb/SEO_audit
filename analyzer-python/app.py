from fastapi import FastAPI
from routes import audit

app = FastAPI()

app.include_router(audit.router)

@app.get("/")
def read_root():
    return {"status": "Analyzer is UP"}