<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Force UTF-8 output and internal encodings for this page
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// Si l'utilisateur est connecté, le rediriger vers son dashboard approprié
if (isLoggedIn()) {
    $user = getUserById($_SESSION['user_id']);
    
    if ($user['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit();
    } else {
        header('Location: dashboard.php');
        exit();
    }
}

// Récupérer les cours populaires pour la page d'accueil
$popularCourses = getPopularCourses(6);
$recentResources = getRecentResources(6);
$certificationPaths = getCertificationPaths(3);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Académie IA - Plateforme d'apprentissage pour la communauté étudiante</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation simplifiée pour la page d'accueil -->
    <nav class="bg-white shadow-lg fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-24">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="index.php" class="flex items-center">
                            <img src="images/EDUTECH.png" alt="Académie IA" class="h-20 w-auto object-contain">
                        </a>
                    </div>
                </div>
                
                <!-- Boutons de connexion et inscription uniquement -->
                <div class="flex items-center space-x-2">
                    <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-sign-in-alt mr-1"></i>Connexion
                    </a>
                    <a href="register.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-user-plus mr-1"></i>Inscription
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-purple-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    Académie IA
                </h1>
                <p class="text-xl md:text-2xl mb-8 text-blue-100">
                    Accédez à des ressources multidisciplinaires adaptées à tous les étudiants
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="register.php" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                        <i class="fas fa-user-plus mr-2"></i>Commencer gratuitement
                    </a>
                    <a href="login.php" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors">
                        <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistiques -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-3xl font-bold text-blue-600 mb-2">500+</div>
                    <div class="text-gray-600">Étudiants actifs</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-green-600 mb-2">50+</div>
                    <div class="text-gray-600">Cours disponibles</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-purple-600 mb-2">200+</div>
                    <div class="text-gray-600">Ressources</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-yellow-600 mb-2">15+</div>
                    <div class="text-gray-600">Certifications</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cours populaires -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Cours Populaires</h2>
                <p class="text-gray-600">Découvrez nos cours les plus appréciés par la communauté</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($popularCourses as $course): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="h-48 bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-white text-4xl"></i>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            <?php echo htmlspecialchars($course['title']); ?>
                        </h3>
                        <p class="text-gray-600 mb-4">
                            <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?>
                        </p>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                                <?php echo ucfirst($course['level']); ?>
                            </span>
                            <span class="text-gray-500 text-sm">
                                <?php echo $course['duration']; ?>
                            </span>
                        </div>
                        <button class="block w-full bg-gray-400 text-white text-center py-2 rounded-lg cursor-not-allowed opacity-60" disabled>
                            Connexion requise
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-8">
                <button class="inline-flex items-center px-6 py-3 border border-gray-400 text-gray-400 rounded-lg cursor-not-allowed opacity-60" disabled>
                    <i class="fas fa-graduation-cap mr-2"></i>
                    Connexion requise
                </button>
            </div>
        </div>
    </section>

    <!-- Certifications -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Certifications & Parcours</h2>
                <p class="text-gray-600">Obtenez des certifications reconnues dans plusieurs domaines académiques</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($certificationPaths as $cert): ?>
                <div class="bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg p-8 text-center">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-certificate text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">
                        <?php echo htmlspecialchars($cert['title']); ?>
                    </h3>
                    <p class="text-purple-100 mb-4">
                        <?php echo htmlspecialchars(substr($cert['description'], 0, 80)) . '...'; ?>
                    </p>
                    <div class="flex items-center justify-center space-x-4 mb-4">
                        <span class="px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm">
                            <?php echo ucfirst($cert['level']); ?>
                        </span>
                        <span class="text-sm">
                            <?php echo isset($cert['duration']) ? htmlspecialchars($cert['duration']) : '—'; ?>
                        </span>
                    </div>
                    <button class="inline-block bg-gray-300 text-gray-500 px-6 py-2 rounded-lg font-semibold cursor-not-allowed opacity-60" disabled>
                        Connexion requise
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Ressources -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Centre de Ressources</h2>
                <p class="text-gray-600">Accédez à une bibliothèque complète de ressources d'apprentissage</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($recentResources as $resource): ?>
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-blue-600 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-<?php echo getResourceTypeIcon($resource['type']); ?> text-white"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">
                                <?php echo htmlspecialchars($resource['title']); ?>
                            </h3>
                            <p class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($resource['author']); ?>
                            </p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">
                        <?php echo htmlspecialchars(substr($resource['description'], 0, 80)) . '...'; ?>
                    </p>
                    <div class="flex items-center justify-between">
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">
                            <?php echo ucfirst($resource['type']); ?>
                        </span>
                        <span class="text-gray-400 text-sm font-medium cursor-not-allowed">
                            Connexion requise →
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-8">
                <button class="inline-flex items-center px-6 py-3 border border-gray-400 text-gray-400 rounded-lg cursor-not-allowed opacity-60" disabled>
                    <i class="fas fa-book mr-2"></i>
                    Connexion requise
                </button>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-indigo-600 to-purple-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold mb-4">Prêt à commencer votre parcours d’apprentissage ?</h2>
            <p class="text-xl mb-8 text-indigo-100">
                Rejoignez des milliers d'étudiants qui apprennent chaque jour
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="register.php" class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-user-plus mr-2"></i>Créer un compte gratuit
                </a>
                <a href="login.php" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-indigo-600 transition-colors">
                    <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>

