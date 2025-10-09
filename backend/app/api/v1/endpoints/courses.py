from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List, Optional
from app.core.database import get_db
from app.core.auth import get_current_user, require_role
from app.models.user import User
from app.models.course import Course, Module, Lesson
from pydantic import BaseModel

router = APIRouter()

# Modèles Pydantic pour les requêtes et réponses
class CourseBase(BaseModel):
    title: str
    description: str
    instructor: str
    duration: str
    level: str
    category: str
    enrolled_students: int
    modules_count: int

class CourseCreate(CourseBase):
    pass

class CourseResponse(CourseBase):
    id: int

    class Config:
        from_attributes = True

# Données de test pour les cours
MOCK_COURSES = [
    {
        "id": 1,
        "title": "Introduction aux Systèmes Embarqués",
        "description": "Découvrez les fondamentaux des systèmes embarqués, de l'architecture matérielle aux logiciels temps réel.",
        "instructor": "Dr. Marie Dubois",
        "duration": "12 semaines",
        "level": "beginner",
        "category": "embedded-systems",
        "enrolled_students": 45,
        "modules_count": 8
    },
    {
        "id": 2,
        "title": "Intelligence Artificielle Fondamentale",
        "description": "Maîtrisez les concepts de base de l'IA : algorithmes de recherche, logique floue et systèmes experts.",
        "instructor": "Prof. Jean Martin",
        "duration": "16 semaines",
        "level": "intermediate",
        "category": "artificial-intelligence",
        "enrolled_students": 78,
        "modules_count": 12
    },
    {
        "id": 3,
        "title": "Machine Learning Avancé",
        "description": "Apprenez les techniques avancées de machine learning : réseaux de neurones, SVM, et ensemble methods.",
        "instructor": "Dr. Sophie Bernard",
        "duration": "14 semaines",
        "level": "advanced",
        "category": "machine-learning",
        "enrolled_students": 32,
        "modules_count": 10
    },
    {
        "id": 4,
        "title": "Deep Learning avec TensorFlow",
        "description": "Plongez dans le deep learning avec TensorFlow : CNNs, RNNs, et architectures modernes.",
        "instructor": "Prof. Pierre Durand",
        "duration": "18 semaines",
        "level": "advanced",
        "category": "deep-learning",
        "enrolled_students": 28,
        "modules_count": 15
    },
    {
        "id": 5,
        "title": "Génie Logiciel et Architecture",
        "description": "Développez des applications robustes avec les meilleures pratiques du génie logiciel.",
        "instructor": "Dr. Anne Moreau",
        "duration": "10 semaines",
        "level": "intermediate",
        "category": "software-engineering",
        "enrolled_students": 56,
        "modules_count": 9
    },
    {
        "id": 6,
        "title": "Programmation Temps Réel",
        "description": "Maîtrisez la programmation temps réel pour les systèmes embarqués critiques.",
        "instructor": "Prof. Michel Leroy",
        "duration": "8 semaines",
        "level": "advanced",
        "category": "embedded-systems",
        "enrolled_students": 23,
        "modules_count": 6
    }
]

@router.get("/", response_model=List[CourseResponse])
async def get_courses(
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Récupérer la liste des cours disponibles"""
    # Pour l'instant, retourner les données de test
    # Plus tard, cela viendra de la base de données
    return MOCK_COURSES

@router.get("/{course_id}", response_model=CourseResponse)
async def get_course(
    course_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Récupérer un cours spécifique"""
    # Chercher dans les données de test
    course = next((c for c in MOCK_COURSES if c["id"] == course_id), None)
    if not course:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Cours non trouvé"
        )
    return course

@router.post("/", response_model=CourseResponse)
async def create_course(
    course: CourseCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(require_role(["teacher", "admin"]))
):
    """Créer un nouveau cours (enseignants et admins uniquement)"""
    # Simulation de création
    new_course = {
        "id": len(MOCK_COURSES) + 1,
        **course.dict()
    }
    MOCK_COURSES.append(new_course)
    return new_course

@router.get("/{course_id}/modules")
async def get_course_modules(
    course_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Récupérer les modules d'un cours"""
    # Vérifier que le cours existe
    course = next((c for c in MOCK_COURSES if c["id"] == course_id), None)
    if not course:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Cours non trouvé"
        )
    
    # Données de test pour les modules
    mock_modules = [
        {
            "id": 1,
            "title": "Introduction et Concepts de Base",
            "description": "Vue d'ensemble du cours et des concepts fondamentaux",
            "order": 1,
            "lessons_count": 3
        },
        {
            "id": 2,
            "title": "Architecture et Composants",
            "description": "Étude de l'architecture des systèmes embarqués",
            "order": 2,
            "lessons_count": 4
        }
    ]
    
    return mock_modules

@router.get("/{course_id}/modules/{module_id}/lessons")
async def get_module_lessons(
    course_id: int,
    module_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Récupérer les leçons d'un module"""
    # Vérifier que le cours existe
    course = next((c for c in MOCK_COURSES if c["id"] == course_id), None)
    if not course:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Cours non trouvé"
        )
    
    # Données de test pour les leçons
    mock_lessons = [
        {
            "id": 1,
            "title": "Qu'est-ce qu'un système embarqué ?",
            "description": "Définition et caractéristiques des systèmes embarqués",
            "content": "Contenu de la leçon...",
            "order": 1,
            "duration": "45 minutes"
        },
        {
            "id": 2,
            "title": "Applications et Domaines d'Usage",
            "description": "Exemples concrets d'applications des systèmes embarqués",
            "content": "Contenu de la leçon...",
            "order": 2,
            "duration": "60 minutes"
        }
    ]
    
    return mock_lessons
