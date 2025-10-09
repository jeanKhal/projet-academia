# Script simplifié pour lancer la plateforme académique
Write-Host "Lancement de la plateforme academique..." -ForegroundColor Green

# Vérifier Docker
try {
    docker --version | Out-Null
    Write-Host "Docker OK" -ForegroundColor Green
} catch {
    Write-Host "Docker non installe! Installez Docker Desktop d'abord." -ForegroundColor Red
    exit 1
}

# Créer les dossiers
Write-Host "Creation des dossiers..." -ForegroundColor Blue
if (!(Test-Path "backend/uploads")) { New-Item -ItemType Directory -Force -Path "backend/uploads" }
if (!(Test-Path "frontend/build")) { New-Item -ItemType Directory -Force -Path "frontend/build" }

# Créer les fichiers .env
Write-Host "Configuration des fichiers..." -ForegroundColor Blue

# Backend .env
$backendContent = @"
DATABASE_URL=postgresql://postgres:password@postgres:5432/academy_db
ASYNC_DATABASE_URL=postgresql+asyncpg://postgres:password@postgres:5432/academy_db
SECRET_KEY=your-super-secret-key-change-this-in-production
ALGORITHM=HS256
ACCESS_TOKEN_EXPIRE_MINUTES=30
CORS_ORIGINS=["http://localhost:3000", "http://127.0.0.1:3000"]
JUPYTER_TOKEN=your-jupyter-token
JUPYTER_URL=http://localhost:8888
UPLOAD_DIR=./uploads
MAX_FILE_SIZE=10485760
"@
$backendContent | Out-File -FilePath "backend\.env" -Encoding UTF8

# Frontend .env
$frontendContent = @"
REACT_APP_API_URL=http://localhost:8000
REACT_APP_JUPYTER_URL=http://localhost:8888
"@
$frontendContent | Out-File -FilePath "frontend\.env" -Encoding UTF8

# Lancer les services
Write-Host "Construction des images..." -ForegroundColor Blue
docker-compose build

Write-Host "Demarrage des services..." -ForegroundColor Blue
docker-compose up -d

Write-Host "Attente du demarrage..." -ForegroundColor Yellow
Start-Sleep -Seconds 15

Write-Host "Verification des services..." -ForegroundColor Blue
docker-compose ps

Write-Host ""
Write-Host "Plateforme lancee!" -ForegroundColor Green
Write-Host "Frontend: http://localhost:3000" -ForegroundColor Cyan
Write-Host "Backend: http://localhost:8000" -ForegroundColor Cyan
Write-Host "API Docs: http://localhost:8000/docs" -ForegroundColor Cyan
Write-Host ""
Write-Host "Comptes de test:" -ForegroundColor Yellow
Write-Host "Etudiant: student@academy.com / password123" -ForegroundColor White
Write-Host "Enseignant: teacher@academy.com / password123" -ForegroundColor White
Write-Host "Admin: admin@academy.com / password123" -ForegroundColor White
