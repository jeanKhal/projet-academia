from sqlalchemy import Column, Integer, String, Boolean, DateTime, Text, JSON
from sqlalchemy.sql import func
from sqlalchemy.orm import relationship
from app.core.database import Base
from datetime import datetime
from typing import List, Optional

class User(Base):
    __tablename__ = "users"
    
    id = Column(Integer, primary_key=True, index=True)
    email = Column(String(255), unique=True, index=True, nullable=False)
    username = Column(String(100), unique=True, index=True, nullable=False)
    full_name = Column(String(200), nullable=False)
    hashed_password = Column(String(255), nullable=False)
    role = Column(String(50), default="student")  # student, teacher, admin
    is_active = Column(Boolean, default=True)
    is_verified = Column(Boolean, default=False)
    
    # Informations académiques
    student_id = Column(String(50), nullable=True)
    department = Column(String(100), nullable=True)
    year_level = Column(Integer, nullable=True)
    
    # Cours inscrits (stocké en JSON pour simplifier)
    courses_enrolled = Column(JSON, default=list)
    
    # Métadonnées
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    last_login = Column(DateTime(timezone=True), nullable=True)
    
    # Relations
    enrollments = relationship("Enrollment", back_populates="user")
    submissions = relationship("Submission", back_populates="user")
    forum_posts = relationship("ForumPost", back_populates="user")
    
    def __repr__(self):
        return f"<User(id={self.id}, email='{self.email}', role='{self.role}')>"
    
    @property
    def is_student(self) -> bool:
        return self.role == "student"
    
    @property
    def is_teacher(self) -> bool:
        return self.role == "teacher"
    
    @property
    def is_admin(self) -> bool:
        return self.role == "admin"
    
    def can_access_course(self, course_id: int) -> bool:
        """Vérifie si l'utilisateur peut accéder à un cours"""
        if self.is_admin or self.is_teacher:
            return True
        return course_id in self.courses_enrolled
