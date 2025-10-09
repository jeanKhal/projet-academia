from fastapi import FastAPI, HTTPException, Depends
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from contextlib import asynccontextmanager
import uvicorn
from typing import List

from app.core.config import settings
from app.core.database import engine, Base
from app.api.v1.api import api_router
from app.core.auth import get_current_user
from app.models.user import User

# Créer les tables au démarrage
@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup
    Base.metadata.create_all(bind=engine)
    print("🚀 Plateforme Éducative IA démarrée!")
    yield
    # Shutdown
    print("👋 Arrêt de la plateforme...")

# Création de l'application FastAPI
app = FastAPI(
    title="Plateforme Éducative IA",
    description="API pour l'enseignement des systèmes embarqués et IA",
    version="1.0.0",
    lifespan=lifespan
)

# Configuration CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:3000", "http://127.0.0.1:3000"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Montage des fichiers statiques
app.mount("/static", StaticFiles(directory="static"), name="static")

# Inclusion des routes API
app.include_router(api_router, prefix="/api/v1")

# Route de santé
@app.get("/")
async def root():
    return {
        "message": "🎓 Plateforme Éducative IA",
        "version": "1.0.0",
        "status": "active",
        "courses": [
            "Systèmes Embarqués",
            "Intelligence Artificielle 1",
            "Intelligence Artificielle 2", 
            "Machine Learning",
            "Deep Learning",
            "Génie Logiciel"
        ]
    }

@app.get("/health")
async def health_check():
    return {"status": "healthy", "service": "plateforme-educative-ia"}

# Route protégée pour tester l'authentification
@app.get("/api/v1/me", response_model=dict)
async def get_current_user_info(current_user: User = Depends(get_current_user)):
    return {
        "id": current_user.id,
        "email": current_user.email,
        "role": current_user.role,
        "courses_enrolled": current_user.courses_enrolled
    }

if __name__ == "__main__":
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=8000,
        reload=True,
        log_level="info"
    )
