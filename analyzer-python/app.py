#lance l’API Python et reçoit les demandes d’analyse envoyées par le backend.

from fastapi import FastAPI

app = FastAPI()

@app.get("/")
def root():
    return {"message": "Analyzer API is running"}