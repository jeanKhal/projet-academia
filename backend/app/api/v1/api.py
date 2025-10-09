from fastapi import APIRouter
from app.api.v1.endpoints import auth, courses, users, resources, forum

# Création du routeur principal
api_router = APIRouter()

# Inclusion des routes des différents modules
api_router.include_router(auth.router, prefix="/auth", tags=["authentication"])
api_router.include_router(users.router, prefix="/users", tags=["users"])
api_router.include_router(courses.router, prefix="/courses", tags=["courses"])
api_router.include_router(resources.router, prefix="/resources", tags=["resources"])
api_router.include_router(forum.router, prefix="/forum", tags=["forum"])
