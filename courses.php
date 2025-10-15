
<?php
session_start();
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    // Rediriger vers la page de connexion avec un message
    setFlashMessage('warning', 'Vous devez être connecté pour accéder aux cours.');
    header('Location: login.php');
    exit();
}

// Récupérer les paramètres de filtrage
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;
$level = isset($_GET['level']) ? sanitizeInput($_GET['level']) : null;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;

// Récupérer l'utilisateur connecté
$user = getUserById($_SESSION['user_id']);

// Récupérer les statistiques utilisateur
$enrolledCoursesCount = getEnrolledCoursesCount($user['id']);
$studyHours = getStudyHours($user['id']);
$completedResources = getCompletedResourcesCount($user['id']);
$userCertifications = getUserCertifications($user['id']);

// Récupérer les cours
$courses = getAllCourses($category, $level);

// Filtrer par recherche si nécessaire
if ($search) {
    $courses = array_filter($courses, function($course) use ($search) {
        return stripos($course['title'], $search) !== false || 
               stripos($course['description'], $search) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cours - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 overflow-x-hidden">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <div class="flex">
        <!-- Sidebar (desktop) -->
        <div class="hidden md:block w-64 bg-white shadow-md border-r border-gray-200 rounded-r-xl fixed left-0 top-16 h-[calc(100vh-4rem-1.5rem)] z-30">
            <div class="px-3 pt-3 pb-3 h-full overflow-y-auto">
            <!-- Profil utilisateur -->
            <div class="text-center mb-3">
                <div class="w-14 h-14 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-2 shadow-sm">
                    <i class="fas fa-user-graduate text-white text-lg"></i>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm tracking-tight"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                <p class="text-xs text-gray-500">Étudiant</p>
                
            </div>

            <!-- Navigation -->
            <nav class="space-y-1 mb-0">
                <a href="dashboard.php" class="group flex items-center px-3 py-2 rounded-lg text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-tachometer-alt mr-2.5 w-4 text-gray-500 group-hover:text-blue-600"></i>
                    Tableau de bord
                </a>
                <a href="courses.php" class="group flex items-center px-3 py-2 rounded-lg font-medium text-sm text-blue-600 bg-blue-50 ring-1 ring-blue-100">
                    <i class="fas fa-graduation-cap mr-2.5 w-4 text-blue-600"></i>
                    Mes cours
                </a>
                <a href="resources.php" class="group flex items-center px-3 py-2 rounded-lg text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-folder mr-2.5 w-4 text-gray-500 group-hover:text-blue-600"></i>
                    Ressources
                </a>
                <a href="bibliotheque.php" class="group flex items-center px-3 py-2 rounded-lg text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-book mr-2.5 w-4 text-gray-500 group-hover:text-blue-600"></i>
                    Bibliothèque
                </a>
                <a href="certifications.php" class="group flex items-center px-3 py-2 rounded-lg text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-certificate mr-2.5 w-4 text-gray-500 group-hover:text-blue-600"></i>
                    Certifications
                </a>
                <a href="forum.php" class="group flex items-center px-3 py-2 rounded-lg text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-comments mr-2.5 w-4 text-gray-500 group-hover:text-blue-600"></i>
                    Forum
                </a>
                <a href="profile.php" class="group flex items-center px-3 py-2 rounded-lg text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-user mr-2.5 w-4 text-gray-500 group-hover:text-blue-600"></i>
                    Mon profil
                </a>
            </nav>

            <!-- Actions rapides -->
            <div class="mt-6 space-y-2">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Actions</div>
                <a href="courses.php" class="flex items-center px-3 py-2 text-xs text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                    <i class="fas fa-search mr-2 w-3"></i>
                    Trouver un cours
                </a>
                <a href="bibliotheque.php" class="flex items-center px-3 py-2 text-xs text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                    <i class="fas fa-download mr-2 w-3"></i>
                    Explorer la bibliothèque
                </a>
            </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <main class="flex-1 ml-64 mt-16 p-4 md:p-8 pb-24">
        <div class="py-4 md:py-6">
            <div class="mb-8">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Cours Disponibles</h1>
                <p class="text-sm md:text-base text-gray-600">D&eacute;couvrez nos cours et formations multidisciplinaires (sciences, droit, informatique, gestion, langues, etc.)</p>
            </div>

            <!-- Filtres et recherche -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <form method="GET" class="space-y-4 md:space-y-0 md:flex md:items-center md:space-x-4">
                    <div class="flex-1">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Rechercher un cours...">
                    </div>
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
                        <select id="category" name="category" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Toutes les catégories</option>
                            <option value="embedded-systems" <?php echo ($category === 'embedded-systems') ? 'selected' : ''; ?>>Systèmes Embarqués</option>
                            <option value="artificial-intelligence" <?php echo ($category === 'artificial-intelligence') ? 'selected' : ''; ?>>Intelligence Artificielle</option>
                            <option value="machine-learning" <?php echo ($category === 'machine-learning') ? 'selected' : ''; ?>>Machine Learning</option>
                            <option value="deep-learning" <?php echo ($category === 'deep-learning') ? 'selected' : ''; ?>>Deep Learning</option>
                            <option value="software-engineering" <?php echo ($category === 'software-engineering') ? 'selected' : ''; ?>>G&eacute;nie Logiciel</option>
                        </select>
                    </div>
                    <div>
                        <label for="level" class="block text-sm font-medium text-gray-700 mb-2">Niveau</label>
                        <select id="level" name="level" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Tous les niveaux</option>
                            <option value="beginner" <?php echo ($level === 'beginner') ? 'selected' : ''; ?>>Débutant</option>
                            <option value="intermediate" <?php echo ($level === 'intermediate') ? 'selected' : ''; ?>>Intermédiaire</option>
                            <option value="advanced" <?php echo ($level === 'advanced') ? 'selected' : ''; ?>>Avancé</option>
                        </select>
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Filtrer
                        </button>
                        <a href="courses.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                            <i class="fas fa-times mr-2"></i>Effacer
                        </a>
                    </div>
                </form>
            </div>

            <!-- Liste des cours -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($courses)): ?>
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-graduation-cap text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun cours trouvé</h3>
                    <p class="text-gray-600">Essayez de modifier vos critères de recherche.</p>
                </div>
                <?php else: ?>
                <?php foreach ($courses as $course): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-graduation-cap text-white"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($course['instructor']); ?></p>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getLevelColor($course['level']); ?>">
                                <?php echo ucfirst($course['level']); ?>
                            </span>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                        
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                            <span><i class="fas fa-clock mr-1"></i><?php echo $course['duration']; ?></span>
                            <span><i class="fas fa-users mr-1"></i><?php echo $course['enrolled_students']; ?> étudiants</span>
                            <span><i class="fas fa-book mr-1"></i><?php echo $course['modules_count']; ?> modules</span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-blue-600"><?php echo ucfirst($course['category']); ?></span>
                            <a href="course.php?id=<?php echo $course['id']; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                Voir le cours
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        </main>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Toggle mobile menu when clicking the hamburger button
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', toggleMobileMenu);
            }
        });
    </script>
</body>
</html>
