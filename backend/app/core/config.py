from pydantic_settings import BaseSettings
from typing import Optional
import os

class Settings(BaseSettings):
    # Configuration de base
    app_name: str = "Plateforme Éducative IA"
    version: str = "1.0.0"
    debug: bool = True
    
    # Base de données
    database_url: str = "postgresql://user:password@localhost/plateforme_educative"
    database_url_async: str = "postgresql+asyncpg://user:password@localhost/plateforme_educative"
    
    # Sécurité
    secret_key: str = "your-secret-key-change-in-production"
    algorithm: str = "HS256"
    access_token_expire_minutes: int = 30
    
    # CORS
    allowed_origins: list = ["http://localhost:3000", "http://127.0.0.1:3000"]
    
    # Jupyter
    jupyter_token: str = "your-jupyter-token"
    jupyter_url: str = "http://localhost:8888"
    
    # Upload
    upload_dir: str = "uploads"
    max_file_size: int = 10 * 1024 * 1024  # 10MB
    
    # Email (optionnel)
    smtp_server: Optional[str] = None
    smtp_port: int = 587
    smtp_username: Optional[str] = None
    smtp_password: Optional[str] = None
    
    class Config:
        env_file = ".env"
        case_sensitive = True

# Instance globale des paramètres
settings = Settings()

# Créer le dossier d'upload s'il n'existe pas
os.makedirs(settings.upload_dir, exist_ok=True)
