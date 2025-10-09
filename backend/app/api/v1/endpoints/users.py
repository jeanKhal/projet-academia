from typing import List, Optional
from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session
from pydantic import BaseModel

from app.core.auth import get_current_user, require_teacher_or_admin
from app.core.database import get_db
from app.models.user import User

router = APIRouter()

# Modèles Pydantic
class UserUpdate(BaseModel):
    full_name: Optional[str] = None
    department: Optional[str] = None
    year_level: Optional[int] = None
    is_active: Optional[bool] = None

class UserResponse(BaseModel):
    id: int
    email: str
    username: str
    full_name: str
    role: str
    is_active: bool
    student_id: Optional[str] = None
    department: Optional[str] = None
    year_level: Optional[int] = None
    courses_enrolled: List[int] = []
    
    class Config:
        from_attributes = True

class UserEnrollment(BaseModel):
    course_id: int

@router.get("/", response_model=List[UserResponse])
def get_users(
    role: Optional[str] = Query(None, description="Filtrer par rôle"),
    department: Optional[str] = Query(None, description="Filtrer par département"),
    current_user: User = Depends(require_teacher_or_admin()),
    db: Session = Depends(get_db)
):
    """Récupérer tous les utilisateurs (enseignants et admins seulement)"""
    query = db.query(User)
    
    if role:
        query = query.filter(User.role == role)
    
    if department:
        query = query.filter(User.department == department)
    
    users = query.all()
    return users

@router.get("/{user_id}", response_model=UserResponse)
def get_user(
    user_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Récupérer un utilisateur spécifique"""
    # Les utilisateurs peuvent voir leur propre profil
    # Les enseignants et admins peuvent voir tous les profils
    if current_user.id != user_id and not (current_user.is_teacher or current_user.is_admin):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Accès non autorisé"
        )
    
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Utilisateur non trouvé"
        )
    
    return user

@router.put("/{user_id}", response_model=UserResponse)
def update_user(
    user_id: int,
    user_data: UserUpdate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Mettre à jour un utilisateur"""
    # Les utilisateurs peuvent modifier leur propre profil
    # Les enseignants et admins peuvent modifier tous les profils
    if current_user.id != user_id and not (current_user.is_teacher or current_user.is_admin):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Accès non autorisé"
        )
    
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Utilisateur non trouvé"
        )
    
    # Mettre à jour les champs fournis
    for field, value in user_data.dict(exclude_unset=True).items():
        setattr(user, field, value)
    
    db.commit()
    db.refresh(user)
    
    return user

@router.post("/{user_id}/enroll", response_model=UserResponse)
def enroll_user_in_course(
    user_id: int,
    enrollment: UserEnrollment,
    current_user: User = Depends(require_teacher_or_admin()),
    db: Session = Depends(get_db)
):
    """Inscrire un utilisateur à un cours"""
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Utilisateur non trouvé"
        )
    
    # Vérifier que le cours existe
    from app.models.course import Course
    course = db.query(Course).filter(Course.id == enrollment.course_id).first()
    if not course:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Cours non trouvé"
        )
    
    # Ajouter le cours à la liste des cours inscrits
    if enrollment.course_id not in user.courses_enrolled:
        user.courses_enrolled.append(enrollment.course_id)
        db.commit()
        db.refresh(user)
    
    return user

@router.delete("/{user_id}/enroll/{course_id}", response_model=UserResponse)
def unenroll_user_from_course(
    user_id: int,
    course_id: int,
    current_user: User = Depends(require_teacher_or_admin()),
    db: Session = Depends(get_db)
):
    """Désinscrire un utilisateur d'un cours"""
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Utilisateur non trouvé"
        )
    
    # Retirer le cours de la liste des cours inscrits
    if course_id in user.courses_enrolled:
        user.courses_enrolled.remove(course_id)
        db.commit()
        db.refresh(user)
    
    return user

@router.get("/me/courses", response_model=List[dict])
def get_my_courses(
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Récupérer les cours de l'utilisateur connecté"""
    from app.models.course import Course
    
    if not current_user.courses_enrolled:
        return []
    
    courses = db.query(Course).filter(
        Course.id.in_(current_user.courses_enrolled),
        Course.is_active == True
    ).all()
    
    return [
        {
            "id": course.id,
            "code": course.code,
            "title": course.title,
            "description": course.description,
            "category": course.category,
            "credits": course.credits,
            "difficulty_level": course.difficulty_level
        }
        for course in courses
    ]

@router.get("/stats/overview")
def get_user_stats(
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Récupérer les statistiques de l'utilisateur"""
    from app.models.course import Course
    
    total_courses = len(current_user.courses_enrolled)
    
    # Calculer les cours par catégorie
    courses_by_category = {}
    if current_user.courses_enrolled:
        courses = db.query(Course).filter(
            Course.id.in_(current_user.courses_enrolled)
        ).all()
        
        for course in courses:
            category = course.category
            if category not in courses_by_category:
                courses_by_category[category] = 0
            courses_by_category[category] += 1
    
    return {
        "user_id": current_user.id,
        "total_courses": total_courses,
        "courses_by_category": courses_by_category,
        "role": current_user.role,
        "department": current_user.department,
        "year_level": current_user.year_level
    }
