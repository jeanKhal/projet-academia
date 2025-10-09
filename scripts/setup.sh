#!/bin/bash

# Script de configuration et démarrage de la Plateforme Éducative IA
echo "🚀 Configuration de la Plateforme Éducative IA"

# Vérifier si Docker est installé
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé. Veuillez installer Docker d'abord."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose n'est pas installé. Veuillez installer Docker Compose d'abord."
    exit 1
fi

# Créer les dossiers nécessaires
echo "📁 Création des dossiers..."
mkdir -p uploads
mkdir -p notebooks
mkdir -p logs

# Créer le fichier .env pour le backend
echo "⚙️ Configuration des variables d'environnement..."
cat > backend/.env << EOF
DATABASE_URL=postgresql://user:password@localhost:5432/plateforme_educative
DATABASE_URL_ASYNC=postgresql+asyncpg://user:password@localhost:5432/plateforme_educative
SECRET_KEY=your-secret-key-change-in-production
DEBUG=true
EOF

# Créer le fichier .env pour le frontend
cat > frontend/.env << EOF
REACT_APP_API_URL=http://localhost:8000
EOF

echo "✅ Configuration terminée !"

# Démarrer les services
echo "🐳 Démarrage des services Docker..."
docker-compose up -d

echo "⏳ Attente du démarrage des services..."
sleep 10

# Vérifier que les services sont démarrés
echo "🔍 Vérification des services..."

if curl -f http://localhost:8000/health > /dev/null 2>&1; then
    echo "✅ Backend démarré avec succès (http://localhost:8000)"
else
    echo "❌ Erreur lors du démarrage du backend"
fi

if curl -f http://localhost:3000 > /dev/null 2>&1; then
    echo "✅ Frontend démarré avec succès (http://localhost:3000)"
else
    echo "❌ Erreur lors du démarrage du frontend"
fi

echo ""
echo "🎉 Plateforme Éducative IA prête !"
echo ""
echo "📱 Accès aux services :"
echo "   - Frontend: http://localhost:3000"
echo "   - Backend API: http://localhost:8000"
echo "   - Documentation API: http://localhost:8000/docs"
echo "   - Jupyter (optionnel): http://localhost:8888"
echo ""
echo "📚 Prochaines étapes :"
echo "   1. Ouvrez http://localhost:3000 dans votre navigateur"
echo "   2. Créez un compte ou connectez-vous"
echo "   3. Commencez à explorer les cours !"
echo ""
echo "🛠️ Commandes utiles :"
echo "   - Arrêter: docker-compose down"
echo "   - Logs: docker-compose logs -f"
echo "   - Redémarrer: docker-compose restart"
