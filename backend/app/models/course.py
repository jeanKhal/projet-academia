from sqlalchemy import Column, Integer, String, Boolean, DateTime, Text, JSON, Float, ForeignKey
from sqlalchemy.sql import func
from sqlalchemy.orm import relationship
from app.core.database import Base
from datetime import datetime
from typing import List, Optional

class Course(Base):
    __tablename__ = "courses"
    
    id = Column(Integer, primary_key=True, index=True)
    code = Column(String(20), unique=True, index=True, nullable=False)
    title = Column(String(200), nullable=False)
    description = Column(Text, nullable=True)
    category = Column(String(100), nullable=False)  # embarqué, ia, ml, deep_learning, genie_logiciel
    
    # Informations du cours
    credits = Column(Integer, default=3)
    duration_weeks = Column(Integer, default=12)
    difficulty_level = Column(String(20), default="intermediate")  # beginner, intermediate, advanced
    
    # Contenu et ressources
    syllabus = Column(JSON, default=dict)  # Structure du cours
    prerequisites = Column(JSON, default=list)  # Cours prérequis
    learning_objectives = Column(JSON, default=list)
    
    # Métadonnées
    is_active = Column(Boolean, default=True)
    is_public = Column(Boolean, default=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    # Relations
    modules = relationship("Module", back_populates="course", cascade="all, delete-orphan")
    enrollments = relationship("Enrollment", back_populates="course")
    resources = relationship("Resource", back_populates="course")
    
    def __repr__(self):
        return f"<Course(id={self.id}, code='{self.code}', title='{self.title}')>"
    
    @property
    def total_modules(self) -> int:
        return len(self.modules)
    
    @property
    def enrolled_students_count(self) -> int:
        return len([e for e in self.enrollments if e.user.role == "student"])

class Module(Base):
    __tablename__ = "modules"
    
    id = Column(Integer, primary_key=True, index=True)
    course_id = Column(Integer, ForeignKey("courses.id"), nullable=False)
    title = Column(String(200), nullable=False)
    description = Column(Text, nullable=True)
    order = Column(Integer, nullable=False)
    
    # Contenu du module
    content = Column(Text, nullable=True)  # Contenu markdown
    video_url = Column(String(500), nullable=True)
    duration_minutes = Column(Integer, default=60)
    
    # Évaluation
    has_quiz = Column(Boolean, default=False)
    has_assignment = Column(Boolean, default=False)
    
    # Métadonnées
    is_active = Column(Boolean, default=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    # Relations
    course = relationship("Course", back_populates="modules")
    lessons = relationship("Lesson", back_populates="module", cascade="all, delete-orphan")
    quizzes = relationship("Quiz", back_populates="module")
    
    def __repr__(self):
        return f"<Module(id={self.id}, title='{self.title}', course_id={self.course_id})>"

class Lesson(Base):
    __tablename__ = "lessons"
    
    id = Column(Integer, primary_key=True, index=True)
    module_id = Column(Integer, ForeignKey("modules.id"), nullable=False)
    title = Column(String(200), nullable=False)
    content = Column(Text, nullable=False)
    order = Column(Integer, nullable=False)
    
    # Type de contenu
    content_type = Column(String(50), default="text")  # text, video, interactive, notebook
    
    # Métadonnées
    duration_minutes = Column(Integer, default=30)
    is_active = Column(Boolean, default=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    # Relations
    module = relationship("Module", back_populates="lessons")
    
    def __repr__(self):
        return f"<Lesson(id={self.id}, title='{self.title}', module_id={self.module_id})>"
