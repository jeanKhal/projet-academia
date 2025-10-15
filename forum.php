<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$user = null;

if ($isLoggedIn) {
    $user = getUserById($_SESSION['user_id']);
    
    // Récupérer les statistiques utilisateur
    $enrolledCoursesCount = getEnrolledCoursesCount($user['id']);
    $studyHours = getStudyHours($user['id']);
    $completedResources = getCompletedResourcesCount($user['id']);
    $userCertifications = getUserCertifications($user['id']);
} else {
    // Valeurs par défaut pour les utilisateurs non connectés
    $enrolledCoursesCount = 0;
    $studyHours = 0;
    $completedResources = 0;
    $userCertifications = [];
}

// Récupérer les posts du forum avec filtres
function getForumPosts($category = null, $search = null, $limit = 20) {
    $pdo = getDB();
    
    $sql = "SELECT fp.*, u.full_name as author_name, u.role as author_role,
            COUNT(fr.id) as replies_count,
            MAX(fr.created_at) as last_reply_date
            FROM forum_posts fp
            LEFT JOIN users u ON fp.user_id = u.id
            LEFT JOIN forum_replies fr ON fp.id = fr.post_id
            WHERE fp.is_active = 1";
    
    $params = [];
    
    if ($category) {
        $sql .= " AND fp.category = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $sql .= " AND (fp.title LIKE ? OR fp.content LIKE ? OR u.full_name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql .= " GROUP BY fp.id ORDER BY fp.is_pinned DESC, fp.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Récupérer les statistiques du forum
function getForumStats() {
    $pdo = getDB();
    $stats = [];
    
    // Total des posts
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM forum_posts WHERE is_active = TRUE");
    $stats['total_posts'] = $stmt->fetch()['total'];
    
    // Total des réponses
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM forum_replies WHERE is_active = TRUE");
    $stats['total_replies'] = $stmt->fetch()['total'];
    
    // Total des utilisateurs
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as total FROM forum_posts WHERE is_active = TRUE");
    $stats['total_users'] = $stmt->fetch()['total'];
    
    // Posts par catégorie
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM forum_posts WHERE is_active = TRUE GROUP BY category");
    $stats['by_category'] = $stmt->fetchAll();
    
    return $stats;
}

// Récupérer les paramètres de filtrage
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;

$posts = getForumPosts($category, $search);
$stats = getForumStats();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Communautaire - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Barre de défilement personnalisée pour WebKit */
        .sidebar-scroll::-webkit-scrollbar {
            width: 8px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
    </style>
</head>
<body class="bg-gray-50 overflow-x-hidden">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <div class="flex">
        <!-- Sidebar (desktop) -->
        <div class="hidden md:block w-64 bg-white shadow-md border-r border-gray-200 rounded-r-xl fixed left-0 top-16 h-[calc(100vh-4rem-1.5rem)] z-30" style="scrollbar-width: thin; scrollbar-color: #cbd5e0 #f7fafc;">
            <div class="px-3 pt-3 pb-3 h-full overflow-y-hidden hover:overflow-y-auto overscroll-contain pr-2 sidebar-scroll">
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
                <a href="courses.php" class="group flex items-center px-3 py-2 rounded-lg text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-graduation-cap mr-2.5 w-4 text-gray-500 group-hover:text-blue-600"></i>
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
                <a href="forum.php" class="group flex items-center px-3 py-2 rounded-lg font-medium text-sm text-blue-600 bg-blue-50 ring-1 ring-blue-100">
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
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Forum Communautaire</h1>
                    <p class="text-sm md:text-base text-gray-600">Partagez vos connaissances et posez vos questions à la communauté</p>
                </div>

                <!-- Statistiques du forum -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-comments text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total des discussions</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_posts']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-reply text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total des réponses</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_replies']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-users text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Membres actifs</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_users']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres et recherche -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <form method="GET" class="space-y-4 md:space-y-0 md:flex md:items-center md:space-x-4">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Rechercher dans le forum...">
                        </div>
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
                            <select id="category" name="category" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Toutes les catégories</option>
                                <option value="general" <?php echo ($category === 'general') ? 'selected' : ''; ?>>Général</option>
                                <option value="courses" <?php echo ($category === 'courses') ? 'selected' : ''; ?>>Cours</option>
                                <option value="projects" <?php echo ($category === 'projects') ? 'selected' : ''; ?>>Projets</option>
                                <option value="help" <?php echo ($category === 'help') ? 'selected' : ''; ?>>Aide</option>
                                <option value="announcements" <?php echo ($category === 'announcements') ? 'selected' : ''; ?>>Annonces</option>
                            </select>
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                            <a href="forum.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                                <i class="fas fa-times mr-2"></i>Effacer
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Actions rapides -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Discussions récentes</h2>
                    <?php if ($isLoggedIn): ?>
                    <a href="new-post.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Nouvelle discussion
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Liste des posts -->
                <div class="space-y-4">
                    <?php if (empty($posts)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-comments text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune discussion trouvée</h3>
                        <p class="text-gray-600">Essayez de modifier vos critères de recherche.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <?php if ($post['is_pinned']): ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-thumbtack mr-1"></i>Épinglé
                                        </span>
                                        <?php endif; ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                            <?php echo ucfirst($post['category']); ?>
                                        </span>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                        <a href="post.php?id=<?php echo $post['id']; ?>" class="hover:text-blue-600 transition-colors">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h3>
                                    <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars(substr($post['content'], 0, 150)) . '...'; ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <div class="flex items-center space-x-4">
                                    <span class="flex items-center">
                                        <i class="fas fa-user mr-1"></i>
                                        <?php echo htmlspecialchars($post['author_name']); ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-reply mr-1"></i>
                                        <?php echo $post['replies_count']; ?> réponses
                                    </span>
                                </div>
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="text-blue-600 hover:text-blue-700 font-medium">
                                    Lire la suite
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
