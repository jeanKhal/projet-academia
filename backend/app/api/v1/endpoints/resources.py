from fastapi import APIRouter, Depends, HTTPException, status, UploadFile, File
from sqlalchemy.orm import Session
from typing import List, Optional
from app.core.database import get_db
from app.core.auth import get_current_user, require_role
from app.models.user import User
from pydantic import BaseModel
import os
from datetime import datetime

router = APIRouter()

# Modèles Pydantic
class ResourceBase(BaseModel):
    title: str
    description: str
    type: str
    category: str
    file_size: str
    upload_date: str
    author: str
    downloads: int
    views: int
    tags: List[str]

class ResourceCreate(ResourceBase):
    pass

class ResourceResponse(ResourceBase):
    id: int
    file_url: Optional[str] = None

    class Config:
        from_attributes = True

# Données de test pour les ressources
MOCK_RESOURCES = [
    {
        "id": 1,
        "title": "Guide Complet des Systèmes Embarqués",
        "description": "Un guide détaillé couvrant tous les aspects des systèmes embarqués, de la conception à l'implémentation.",
        "type": "document",
        "category": "embedded-systems",
        "file_size": "2.5 MB",
        "upload_date": "2024-01-15T10:30:00Z",
        "author": "Dr. Marie Dubois",
        "downloads": 156,
        "views": 342,
        "tags": ["systèmes embarqués", "microcontrôleurs", "RTOS", "programmation C"]
    },
    {
        "id": 2,
        "title": "Introduction au Machine Learning - Cours Vidéo",
        "description": "Série de vidéos couvrant les fondamentaux du machine learning avec des exemples pratiques.",
        "type": "video",
        "category": "machine-learning",
        "file_size": "450 MB",
        "upload_date": "2024-01-20T14:15:00Z",
        "author": "Prof. Jean Martin",
        "downloads": 89,
        "views": 234,
        "tags": ["machine learning", "python", "scikit-learn", "algorithmes"]
    },
    {
        "id": 3,
        "title": "Code Source - Projet Deep Learning",
        "description": "Implémentation complète d'un réseau de neurones convolutif pour la classification d'images.",
        "type": "code",
        "category": "deep-learning",
        "file_size": "15 MB",
        "upload_date": "2024-01-25T09:45:00Z",
        "author": "Dr. Sophie Bernard",
        "downloads": 67,
        "views": 189,
        "tags": ["deep learning", "tensorflow", "CNN", "classification d'images"]
    },
    {
        "id": 4,
        "title": "Architecture Logicielle - Patterns et Bonnes Pratiques",
        "description": "Livre électronique sur les patterns d'architecture et les bonnes pratiques en génie logiciel.",
        "type": "book",
        "category": "software-engineering",
        "file_size": "8.2 MB",
        "upload_date": "2024-01-30T16:20:00Z",
        "author": "Dr. Anne Moreau",
        "downloads": 123,
        "views": 298,
        "tags": ["architecture", "patterns", "bonnes pratiques", "design patterns"]
    },
    {
        "id": 5,
        "title": "Présentation - Intelligence Artificielle Avancée",
        "description": "Support de cours sur les techniques avancées d'intelligence artificielle et leurs applications.",
        "type": "presentation",
        "category": "artificial-intelligence",
        "file_size": "3.1 MB",
        "upload_date": "2024-02-05T11:10:00Z",
        "author": "Prof. Pierre Durand",
        "downloads": 78,
        "views": 156,
        "tags": ["IA", "algorithmes", "logique floue", "systèmes experts"]
    },
    {
        "id": 6,
        "title": "Dataset - Données de Capteurs IoT",
        "description": "Collection de données de capteurs IoT pour l'analyse et le traitement de signaux.",
        "type": "dataset",
        "category": "embedded-systems",
        "file_size": "25 MB",
        "upload_date": "2024-02-10T13:30:00Z",
        "author": "Prof. Michel Leroy",
        "downloads": 45,
        "views": 98,
        "tags": ["IoT", "capteurs", "données", "analyse"]
    },
    {
        "id": 7,
        "title": "Tutoriel Python pour l'IA",
        "description": "Guide pratique pour utiliser Python dans les projets d'intelligence artificielle.",
        "type": "document",
        "category": "artificial-intelligence",
        "file_size": "1.8 MB",
        "upload_date": "2024-02-15T15:45:00Z",
        "author": "Dr. Sophie Bernard",
        "downloads": 234,
        "views": 567,
        "tags": ["python", "IA", "tutoriel", "programmation"]
    },
    {
        "id": 8,
        "title": "Vidéo - Programmation Temps Réel",
        "description": "Cours vidéo sur la programmation temps réel pour les systèmes embarqués critiques.",
        "type": "video",
        "category": "embedded-systems",
        "file_size": "320 MB",
        "upload_date": "2024-02-20T10:20:00Z",
        "author": "Prof. Michel Leroy",
        "downloads": 34,
        "views": 87,
        "tags": ["temps réel", "systèmes critiques", "RTOS", "programmation"]
    }
]

@router.get("/", response_model=List[ResourceResponse])
async def get_resources(
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Récupérer la liste des ressources disponibles"""
    # Pour l'instant, retourner les données de test
    # Plus tard, cela viendra de la base de données
    return MOCK_RESOURCES

@router.get("/{resource_id}", response_model=ResourceResponse)
async def get_resource(
    resource_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Récupérer une ressource spécifique"""
    # Chercher dans les données de test
    resource = next((r for r in MOCK_RESOURCES if r["id"] == resource_id), None)
    if not resource:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ressource non trouvée"
        )
    
    # Simuler une augmentation des vues
    resource["views"] += 1
    
    return resource

@router.post("/upload")
async def upload_resource(
    title: str,
    description: str,
    category: str,
    tags: str,
    file: UploadFile = File(...),
    db: Session = Depends(get_db),
    current_user: User = Depends(require_role(["teacher", "admin"]))
):
    """Télécharger une nouvelle ressource (enseignants et admins uniquement)"""
    
    # Validation du type de fichier
    allowed_types = {
        'application/pdf': 'document',
        'video/mp4': 'video',
        'video/avi': 'video',
        'text/plain': 'code',
        'application/zip': 'code',
        'application/epub+zip': 'book',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'presentation',
        'text/csv': 'dataset'
    }
    
    file_type = allowed_types.get(file.content_type, 'document')
    
    # Simulation de l'upload
    new_resource = {
        "id": len(MOCK_RESOURCES) + 1,
        "title": title,
        "description": description,
        "type": file_type,
        "category": category,
        "file_size": f"{len(file.file.read()) / (1024*1024):.1f} MB",
        "upload_date": datetime.utcnow().isoformat() + "Z",
        "author": current_user.full_name,
        "downloads": 0,
        "views": 0,
        "tags": [tag.strip() for tag in tags.split(',') if tag.strip()]
    }
    
    MOCK_RESOURCES.append(new_resource)
    
    return {
        "message": "Ressource téléchargée avec succès",
        "resource": new_resource
    }

@router.delete("/{resource_id}")
async def delete_resource(
    resource_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(require_role(["teacher", "admin"]))
):
    """Supprimer une ressource (enseignants et admins uniquement)"""
    # Chercher dans les données de test
    resource_index = next((i for i, r in enumerate(MOCK_RESOURCES) if r["id"] == resource_id), None)
    if resource_index is None:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ressource non trouvée"
        )
    
    # Supprimer la ressource
    deleted_resource = MOCK_RESOURCES.pop(resource_index)
    
    return {
        "message": "Ressource supprimée avec succès",
        "resource": deleted_resource
    }

@router.get("/types/list")
async def get_resource_types():
    """Récupérer la liste des types de ressources disponibles"""
    return [
        {"value": "document", "label": "Document", "icon": "📄"},
        {"value": "video", "label": "Vidéo", "icon": "🎥"},
        {"value": "code", "label": "Code", "icon": "💻"},
        {"value": "book", "label": "Livre", "icon": "📖"},
        {"value": "presentation", "label": "Présentation", "icon": "📊"},
        {"value": "dataset", "label": "Dataset", "icon": "📊"}
    ]

@router.post("/{resource_id}/download")
async def download_resource(
    resource_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Simuler le téléchargement d'une ressource"""
    # Chercher dans les données de test
    resource = next((r for r in MOCK_RESOURCES if r["id"] == resource_id), None)
    if not resource:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ressource non trouvée"
        )
    
    # Simuler une augmentation des téléchargements
    resource["downloads"] += 1
    
    return {
        "message": "Téléchargement simulé avec succès",
        "download_url": f"/api/v1/resources/{resource_id}/file"
    }
