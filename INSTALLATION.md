# 🚀 Guide d'Installation Rapide - Windows

## Prérequis

### 1. Installer Docker Desktop

1. **Télécharger Docker Desktop** :
   - Allez sur : https://www.docker.com/products/docker-desktop/
   - Cliquez sur "Download for Windows"
   - Choisissez la version appropriée (Windows 10/11)

2. **Installer Docker Desktop** :
   - Exécutez le fichier téléchargé
   - Suivez les instructions d'installation
   - Redémarrez votre ordinateur si demandé

3. **Vérifier l'installation** :
   - Ouvrez PowerShell
   - Tapez : `docker --version`
   - Vous devriez voir la version de Docker

### 2. Vérifier WSL2 (si nécessaire)

Si Docker Desktop demande WSL2 :
1. Ouvrez PowerShell en tant qu'administrateur
2. Exécutez : `wsl --install`
3. Redémarrez votre ordinateur

## 🎯 Lancement Rapide

### Option 1 : Script Automatique (Recommandé)

```powershell
# Dans PowerShell, naviguez vers le dossier du projet
cd "C:\Users\jeanm\Documents\DEALS\Projet academie"

# Exécutez le script de configuration
.\scripts\setup.ps1
```

### Option 2 : Commandes Manuelles

```powershell
# 1. Créer les répertoires nécessaires
New-Item -ItemType Directory -Force -Path "backend/uploads"
New-Item -ItemType Directory -Force -Path "frontend/build"

# 2. Construire les images Docker
docker-compose build

# 3. Démarrer les services
docker-compose up -d

# 4. Vérifier l'état
docker-compose ps
```

## 🌐 Accès à l'Application

Une fois lancée, accédez à :

- **Frontend** : http://localhost:3000
- **Backend API** : http://localhost:8000
- **Documentation API** : http://localhost:8000/docs
- **Jupyter Notebook** : http://localhost:8888

## 🔑 Comptes de Test

- **Étudiant** : student@academy.com / password123
- **Enseignant** : teacher@academy.com / password123
- **Admin** : admin@academy.com / password123

## 📋 Commandes Utiles

```powershell
# Voir les logs en temps réel
docker-compose logs -f

# Arrêter l'application
docker-compose down

# Redémarrer l'application
docker-compose restart

# Reconstruire après modifications
docker-compose build --no-cache
docker-compose up -d
```

## 🔧 Dépannage

### Problème : Docker ne démarre pas
- Vérifiez que Docker Desktop est en cours d'exécution
- Redémarrez Docker Desktop

### Problème : Ports déjà utilisés
- Arrêtez les services qui utilisent les ports 3000, 8000, ou 8888
- Ou modifiez les ports dans `docker-compose.yml`

### Problème : Erreur de permissions
- Exécutez PowerShell en tant qu'administrateur
- Ou vérifiez les permissions Docker

## 📞 Support

Si vous rencontrez des problèmes :
1. Vérifiez que Docker Desktop fonctionne
2. Consultez les logs : `docker-compose logs`
3. Redémarrez Docker Desktop
4. Relancez le script d'installation
