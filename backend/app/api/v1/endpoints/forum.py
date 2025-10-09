from typing import List, Optional
from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session
from pydantic import BaseModel
from datetime import datetime

from app.core.auth import get_current_user
from app.core.database import get_db
from app.models.user import User

router = APIRouter()

# Modèles Pydantic
class PostBase(BaseModel):
    title: str
    content: str
    course_id: Optional[int] = None
    tags: List[str] = []

class PostCreate(PostBase):
    pass

class PostResponse(PostBase):
    id: int
    author_id: int
    author_name: str
    created_at: datetime
    updated_at: Optional[datetime] = None
    likes_count: int = 0
    replies_count: int = 0
    is_solved: bool = False
    
    class Config:
        from_attributes = True

class ReplyBase(BaseModel):
    content: str

class ReplyCreate(ReplyBase):
    pass

class ReplyResponse(ReplyBase):
    id: int
    post_id: int
    author_id: int
    author_name: str
    created_at: datetime
    updated_at: Optional[datetime] = None
    likes_count: int = 0
    is_accepted: bool = False
    
    class Config:
        from_attributes = True

# Modèles temporaires pour le forum (à remplacer par de vrais modèles)
class Post:
    def __init__(self, **kwargs):
        for key, value in kwargs.items():
            setattr(self, key, value)

class Reply:
    def __init__(self, **kwargs):
        for key, value in kwargs.items():
            setattr(self, key, value)

# Données fictives pour le forum
forum_posts = [
    Post(
        id=1,
        title="Problème avec Arduino et LED",
        content="Je n'arrive pas à faire clignoter ma LED avec Arduino. Quelqu'un peut m'aider ?",
        course_id=1,
        author_id=2,
        author_name="Marie Dupont",
        created_at=datetime.now(),
        likes_count=3,
        replies_count=2,
        is_solved=True,
        tags=["arduino", "led", "problème"]
    ),
    Post(
        id=2,
        title="Meilleur algorithme pour la classification",
        content="Quel est le meilleur algorithme de classification pour un dataset avec 1000 échantillons ?",
        course_id=4,
        author_id=3,
        author_name="Jean Martin",
        created_at=datetime.now(),
        likes_count=5,
        replies_count=1,
        is_solved=False,
        tags=["ml", "classification", "algorithme"]
    ),
    Post(
        id=3,
        title="Installation de TensorFlow",
        content="J'ai des problèmes avec l'installation de TensorFlow sur Windows. Des conseils ?",
        course_id=5,
        author_id=4,
        author_name="Sophie Bernard",
        created_at=datetime.now(),
        likes_count=2,
        replies_count=3,
        is_solved=False,
        tags=["tensorflow", "installation", "windows"]
    )
]

forum_replies = [
    Reply(
        id=1,
        post_id=1,
        content="Vérifiez que votre LED est bien connectée au bon pin et que vous avez mis une résistance.",
        author_id=1,
        author_name="Prof. Smith",
        created_at=datetime.now(),
        likes_count=4,
        is_accepted=True
    ),
    Reply(
        id=2,
        post_id=1,
        content="Voici un exemple de code qui fonctionne : digitalWrite(13, HIGH); delay(1000); digitalWrite(13, LOW);",
        author_id=5,
        author_name="Pierre Durand",
        created_at=datetime.now(),
        likes_count=2,
        is_accepted=False
    )
]

@router.get("/posts", response_model=List[PostResponse])
def get_posts(
    course_id: Optional[int] = Query(None, description="Filtrer par cours"),
    tag: Optional[str] = Query(None, description="Filtrer par tag"),
    solved: Optional[bool] = Query(None, description="Filtrer par statut résolu"),
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Récupérer tous les posts du forum"""
    posts = forum_posts.copy()
    
    # Filtrer par cours
    if course_id:
        posts = [p for p in posts if p.course_id == course_id]
    
    # Filtrer par tag
    if tag:
        posts = [p for p in posts if tag in p.tags]
    
    # Filtrer par statut résolu
    if solved is not None:
        posts = [p for p in posts if p.is_solved == solved]
    
    return posts

@router.post("/posts", response_model=PostResponse)
def create_post(
    post_data: PostCreate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Créer un nouveau post"""
    new_post = Post(
        id=len(forum_posts) + 1,
        title=post_data.title,
        content=post_data.content,
        course_id=post_data.course_id,
        author_id=current_user.id,
        author_name=current_user.full_name,
        created_at=datetime.now(),
        likes_count=0,
        replies_count=0,
        is_solved=False,
        tags=post_data.tags
    )
    
    forum_posts.append(new_post)
    return new_post

@router.get("/posts/{post_id}", response_model=PostResponse)
def get_post(
    post_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Récupérer un post spécifique"""
    post = next((p for p in forum_posts if p.id == post_id), None)
    
    if not post:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Post non trouvé"
        )
    
    return post

@router.put("/posts/{post_id}", response_model=PostResponse)
def update_post(
    post_id: int,
    post_data: PostCreate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Mettre à jour un post"""
    post = next((p for p in forum_posts if p.id == post_id), None)
    
    if not post:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Post non trouvé"
        )
    
    # Seul l'auteur peut modifier son post
    if post.author_id != current_user.id and not current_user.is_admin:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Vous ne pouvez modifier que vos propres posts"
        )
    
    # Mettre à jour les champs
    post.title = post_data.title
    post.content = post_data.content
    post.course_id = post_data.course_id
    post.tags = post_data.tags
    post.updated_at = datetime.now()
    
    return post

@router.delete("/posts/{post_id}")
def delete_post(
    post_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Supprimer un post"""
    post = next((p for p in forum_posts if p.id == post_id), None)
    
    if not post:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Post non trouvé"
        )
    
    # Seul l'auteur ou un admin peut supprimer
    if post.author_id != current_user.id and not current_user.is_admin:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Vous ne pouvez supprimer que vos propres posts"
        )
    
    forum_posts.remove(post)
    return {"message": "Post supprimé avec succès"}

# Endpoints pour les réponses
@router.get("/posts/{post_id}/replies", response_model=List[ReplyResponse])
def get_post_replies(
    post_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Récupérer toutes les réponses d'un post"""
    # Vérifier que le post existe
    post = next((p for p in forum_posts if p.id == post_id), None)
    if not post:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Post non trouvé"
        )
    
    replies = [r for r in forum_replies if r.post_id == post_id]
    return replies

@router.post("/posts/{post_id}/replies", response_model=ReplyResponse)
def create_reply(
    post_id: int,
    reply_data: ReplyCreate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Créer une nouvelle réponse"""
    # Vérifier que le post existe
    post = next((p for p in forum_posts if p.id == post_id), None)
    if not post:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Post non trouvé"
        )
    
    new_reply = Reply(
        id=len(forum_replies) + 1,
        post_id=post_id,
        content=reply_data.content,
        author_id=current_user.id,
        author_name=current_user.full_name,
        created_at=datetime.now(),
        likes_count=0,
        is_accepted=False
    )
    
    forum_replies.append(new_reply)
    
    # Mettre à jour le nombre de réponses du post
    post.replies_count += 1
    
    return new_reply

@router.put("/replies/{reply_id}/accept")
def accept_reply(
    reply_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Accepter une réponse comme solution"""
    reply = next((r for r in forum_replies if r.id == reply_id), None)
    
    if not reply:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Réponse non trouvée"
        )
    
    # Seul l'auteur du post ou un admin peut accepter une réponse
    post = next((p for p in forum_posts if p.id == reply.post_id), None)
    if post.author_id != current_user.id and not current_user.is_admin:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Vous ne pouvez accepter que les réponses à vos propres posts"
        )
    
    # Marquer la réponse comme acceptée
    reply.is_accepted = True
    
    # Marquer le post comme résolu
    post.is_solved = True
    
    return {"message": "Réponse acceptée comme solution"}

@router.get("/tags/popular")
def get_popular_tags():
    """Récupérer les tags les plus populaires"""
    tag_counts = {}
    
    for post in forum_posts:
        for tag in post.tags:
            if tag in tag_counts:
                tag_counts[tag] += 1
            else:
                tag_counts[tag] = 1
    
    # Trier par popularité
    popular_tags = sorted(tag_counts.items(), key=lambda x: x[1], reverse=True)[:10]
    
    return {
        "tags": [{"name": tag, "count": count} for tag, count in popular_tags]
    }
