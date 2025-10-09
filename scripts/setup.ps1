# Script de configuration et lancement pour Windows PowerShell
Write-Host "🚀 Configuration et lancement de la plateforme académique..." -ForegroundColor Green

# Vérifier si Docker est installé
try {
    $dockerVersion = docker --version
    Write-Host "✅ Docker détecté: $dockerVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker n'est pas installé!" -ForegroundColor Red
    Write-Host "📥 Veuillez installer Docker Desktop depuis: https://www.docker.com/products/docker-desktop/" -ForegroundColor Yellow
    Write-Host "🔧 Après l'installation, redémarrez PowerShell et relancez ce script." -ForegroundColor Yellow
    exit 1
}

# Vérifier si Docker Compose est disponible
try {
    $composeVersion = docker-compose --version
    Write-Host "✅ Docker Compose détecté: $composeVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker Compose n'est pas disponible!" -ForegroundColor Red
    exit 1
}

# Créer les répertoires nécessaires
Write-Host "📁 Création des répertoires..." -ForegroundColor Blue
New-Item -ItemType Directory -Force -Path "backend/uploads" | Out-Null
New-Item -ItemType Directory -Force -Path "frontend/build" | Out-Null

# Créer le fichier .env pour le backend
Write-Host "Configuration du backend..." -ForegroundColor Blue
$backendEnv = @"
# Database Configuration
DATABASE_URL=postgresql://postgres:password@postgres:5432/academy_db
ASYNC_DATABASE_URL=postgresql+asyncpg://postgres:password@postgres:5432/academy_db

# Security
SECRET_KEY=your-super-secret-key-change-this-in-production
ALGORITHM=HS256
ACCESS_TOKEN_EXPIRE_MINUTES=30

# CORS
CORS_ORIGINS=["http://localhost:3000", "http://127.0.0.1:3000"]

# Jupyter Configuration
JUPYTER_TOKEN=your-jupyter-token
JUPYTER_URL=http://localhost:8888

# Upload Configuration
UPLOAD_DIR=./uploads
MAX_FILE_SIZE=10485760
"@
$backendEnv | Out-File -FilePath "backend\.env" -Encoding UTF8

# Créer le fichier .env pour le frontend
Write-Host "Configuration du frontend..." -ForegroundColor Blue
$frontendEnv = @"
REACT_APP_API_URL=http://localhost:8000
REACT_APP_JUPYTER_URL=http://localhost:8888
"@
$frontendEnv | Out-File -FilePath "frontend\.env" -Encoding UTF8

# Construire et démarrer les services
Write-Host "🔨 Construction des images Docker..." -ForegroundColor Blue
docker-compose build

Write-Host "🚀 Démarrage des services..." -ForegroundColor Blue
docker-compose up -d

# Attendre que les services démarrent
Write-Host "⏳ Attente du démarrage des services..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Vérifier l'état des services
Write-Host "🔍 Vérification de l'état des services..." -ForegroundColor Blue
docker-compose ps

Write-Host ""
Write-Host "🎉 Plateforme lancée avec succès!" -ForegroundColor Green
Write-Host ""
Write-Host "📱 Accès aux services:" -ForegroundColor Cyan
Write-Host "   • Frontend (React): http://localhost:3000" -ForegroundColor White
Write-Host "   • Backend (FastAPI): http://localhost:8000" -ForegroundColor White
Write-Host "   • API Documentation: http://localhost:8000/docs" -ForegroundColor White
Write-Host "   • Jupyter Notebook: http://localhost:8888" -ForegroundColor White
Write-Host ""
Write-Host "🔑 Comptes de test:" -ForegroundColor Cyan
Write-Host "   • Étudiant: student@academy.com / password123" -ForegroundColor White
Write-Host "   • Enseignant: teacher@academy.com / password123" -ForegroundColor White
Write-Host "   • Admin: admin@academy.com / password123" -ForegroundColor White
Write-Host ""
Write-Host "📋 Commandes utiles:" -ForegroundColor Cyan
Write-Host "   • Voir les logs: docker-compose logs -f" -ForegroundColor White
Write-Host "   • Arrêter: docker-compose down" -ForegroundColor White
Write-Host "   • Redémarrer: docker-compose restart" -ForegroundColor White
Write-Host ""
Write-Host "🌐 Ouvrez http://localhost:3000 dans votre navigateur pour commencer!" -ForegroundColor Green
