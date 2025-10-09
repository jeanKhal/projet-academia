# 🎓 Académie IA - Plateforme Éducative PHP

Une plateforme éducative moderne développée en PHP et MySQL pour l'apprentissage de l'Intelligence Artificielle, des Systèmes Embarqués et du Génie Logiciel.

## 🚀 Fonctionnalités

### 👥 Gestion des Utilisateurs
- **Système d'authentification** complet avec rôles (Étudiant, Enseignant, Admin)
- **Inscription et connexion** sécurisées
- **Profils utilisateurs** personnalisables
- **Gestion des sessions** sécurisée

### 📚 Gestion des Cours
- **Catalogue de cours** avec filtres par catégorie et niveau
- **Système d'inscription** aux cours
- **Suivi de progression** des étudiants
- **Modules et leçons** organisés

### 📖 Centre de Ressources
- **Bibliothèque de ressources** (documents, vidéos, code, livres)
- **Système de tags** pour la recherche
- **Statistiques de téléchargement** et de vues
- **Upload de fichiers** pour les enseignants

### 💬 Forum de Discussion
- **Posts et réponses** pour l'entraide
- **Système de tags** pour organiser les discussions
- **Marquage des solutions** acceptées
- **Recherche dans les discussions**

### 🎯 Quiz et Évaluations
- **Création de quiz** par les enseignants
- **Questions à choix multiples** et vrai/faux
- **Suivi des résultats** des étudiants
- **Système de notation** automatique

## 🛠️ Technologies Utilisées

- **Backend** : PHP 8.0+
- **Base de données** : MySQL 8.0+
- **Frontend** : HTML5, CSS3, JavaScript
- **Framework CSS** : Tailwind CSS
- **Icônes** : Font Awesome
- **Sécurité** : Sessions PHP, hachage des mots de passe

## 📋 Prérequis

- **PHP** 8.0 ou supérieur
- **MySQL** 8.0 ou supérieur
- **Serveur web** (Apache/Nginx)
- **Extension PHP** : PDO, JSON, Session

## 🚀 Installation

### 1. Cloner le projet
```bash
git clone [url-du-repo]
cd academie-ia
```

### 2. Configurer la base de données
```bash
# Créer la base de données
mysql -u root -p < database/schema.sql
```

### 3. Configurer la connexion
Éditez le fichier `config/database.php` :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'academy_ia');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```

### 4. Configurer le serveur web
Placez le projet dans le répertoire web de votre serveur (ex: `htdocs/` pour XAMPP).

### 5. Accéder à l'application
Ouvrez votre navigateur et allez sur : `http://localhost/academie-ia`

## 👤 Comptes de Test

### Étudiant
- **Email** : `student@academy.com`
- **Mot de passe** : `password123`

### Enseignant
- **Email** : `teacher@academy.com`
- **Mot de passe** : `password123`

### Administrateur
- **Email** : `admin@academy.com`
- **Mot de passe** : `password123`

## 📁 Structure du Projet

```
academie-ia/
├── config/
│   └── database.php          # Configuration de la base de données
├── includes/
│   ├── functions.php         # Fonctions utilitaires
│   ├── header.php           # En-tête réutilisable
│   └── footer.php           # Pied de page réutilisable
├── database/
│   └── schema.sql           # Schéma de la base de données
├── assets/
│   ├── css/
│   ├── js/
│   └── uploads/             # Fichiers uploadés
├── index.php               # Page d'accueil
├── login.php              # Page de connexion
├── register.php           # Page d'inscription
├── courses.php            # Liste des cours
├── resources.php          # Centre de ressources
├── forum.php              # Forum de discussion
├── profile.php            # Profil utilisateur
├── admin.php              # Panel d'administration
└── README.md              # Ce fichier
```

## 🔧 Configuration

### Variables d'environnement
Créez un fichier `.env` à la racine du projet :
```env
DB_HOST=localhost
DB_NAME=academy_ia
DB_USER=root
DB_PASS=
UPLOAD_MAX_SIZE=10485760
ALLOWED_FILE_TYPES=pdf,doc,docx,mp4,avi,jpg,png,zip
```

### Permissions des dossiers
```bash
chmod 755 assets/uploads/
chmod 644 config/database.php
```

## 🎨 Personnalisation

### Thème et couleurs
Modifiez les classes Tailwind CSS dans les fichiers PHP pour changer l'apparence :
- Couleurs principales : `blue-600`, `purple-600`
- Couleurs secondaires : `gray-600`, `gray-800`

### Ajout de nouvelles catégories
1. Modifiez l'enum dans `database/schema.sql`
2. Ajoutez l'icône dans `includes/functions.php`
3. Mettez à jour les formulaires de filtrage

## 🔒 Sécurité

### Mesures implémentées
- **Hachage des mots de passe** avec `password_hash()`
- **Protection contre les injections SQL** avec PDO
- **Échappement des données** avec `htmlspecialchars()`
- **Validation des entrées** utilisateur
- **Gestion sécurisée des sessions**

### Recommandations
- Utilisez HTTPS en production
- Changez les mots de passe par défaut
- Configurez un pare-feu
- Faites des sauvegardes régulières

## 📊 Base de Données

### Tables principales
- `users` : Utilisateurs et authentification
- `courses` : Catalogue des cours
- `modules` : Modules de cours
- `lessons` : Leçons individuelles
- `resources` : Ressources éducatives
- `user_courses` : Inscriptions aux cours
- `forum_posts` : Posts du forum
- `forum_replies` : Réponses du forum
- `quizzes` : Quiz et évaluations

## 🚀 Déploiement

### Serveur de production
1. **Serveur web** : Apache ou Nginx
2. **PHP** : Version 8.0+ avec extensions requises
3. **MySQL** : Version 8.0+
4. **SSL** : Certificat HTTPS

### Optimisations
- **Cache** : Configurez OPcache pour PHP
- **Base de données** : Optimisez les requêtes et index
- **Images** : Compressez les images uploadées
- **CDN** : Utilisez un CDN pour les assets statiques

## 🤝 Contribution

1. Fork le projet
2. Créez une branche pour votre fonctionnalité
3. Committez vos changements
4. Poussez vers la branche
5. Ouvrez une Pull Request

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 📞 Support

Pour toute question ou problème :
- Ouvrez une issue sur GitHub
- Contactez l'équipe de développement
- Consultez la documentation technique

## 🔄 Mises à jour

### Version 1.0.0
- ✅ Système d'authentification
- ✅ Gestion des cours
- ✅ Centre de ressources
- ✅ Forum de discussion
- ✅ Interface responsive

### Prochaines fonctionnalités
- 🔄 Système de notifications
- 🔄 API REST
- 🔄 Intégration vidéo
- 🔄 Système de badges
- 🔄 Export des données

---

**Développé avec ❤️ pour l'éducation en Intelligence Artificielle**
