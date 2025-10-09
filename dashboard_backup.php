<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getUserById($_SESSION['user_id']);

// Sécurité: si l'utilisateur n'existe plus en base, réinitialiser la session
if (!$user) {
    session_destroy();
    header('Location: login.php?error=session_invalid');
    exit();
}

// Rediriger les admins vers leur dashboard
if ($user['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

// Récupérer les statistiques de l'utilisateur
$enrolledCoursesCount = getEnrolledCoursesCount($user['id']);
$studyHours = getStudyHours($user['id']);
$completedResources = getCompletedResourcesCount($user['id']);

// Récupérer les cours récents
$recentCourses = getRecentCourses(6);

// Récupérer les ressources récentes
$recentResources = getAllResources(null, null);
$recentResources = array_slice($recentResources, 0, 6);

$userCertifications = getUserCertifications($user['id']);

// Récupérer la progression des certifications
function getUserCertificationProgressList($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT up.*, cp.title, cp.category, cp.level
        FROM user_progress up
        JOIN certification_paths cp ON up.certification_path_id = cp.id
        WHERE up.user_id = ?
        ORDER BY up.last_activity DESC
        LIMIT 3
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

$certificationProgress = getUserCertificationProgressList($user['id']);

// Récupérer les cours où l'étudiant est inscrit
$enrolledCourses = getEnrolledCourses($user['id'], 4);

// Récupérer les ressources consultées récemment
$recentlyViewedResources = getRecentlyViewedResources($user['id'], 4);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Tableau de Bord - Académie IA</title>
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
                <p class="text-xs text-gray-500">Étudiant IA</p>
                <div class="mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-100 text-blue-800">
                        <i class="fas fa-star mr-1"></i>
                        Niveau <?php echo $user['level'] ?? 'Débutant'; ?>
                    </span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="space-y-1 mb-0">
                <a href="dashboard.php" class="group flex items-center px-3 py-2 rounded-lg font-medium text-sm text-blue-600 bg-blue-50 ring-1 ring-blue-100">
                    <i class="fas fa-tachometer-alt mr-2.5 w-4 text-blue-600"></i>
                    Tableau de bord
                </a>
                <a href="courses.php" class="group flex items-center px-3 py-2 rounded-lg text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-graduation-cap mr-2.5 w-4 text-gray-500 group-hover:text-blue-600"></i>
                    Mes cours
                </a>
                <a href="bibliotheque.php" class="group flex items-center px-3 py-2 rounded-lg text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-book mr-2.5 w-4 text-gray-500 group-hover:text-blue-600"></i>
                    Bibliothèque
                </a>
                <a href="resources.php" class="group flex items-center px-3 py-2 rounded-lg text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-folder mr-2.5 w-4 text-gray-500 group-hover:text-blue-600"></i>
                    Ressources
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

            

            <!-- Statistiques rapides -->
            <div class="mt-3 pt-3 border-t border-gray-200">
                <h4 class="text-xs font-medium text-gray-900 mb-3">Mes Statistiques</h4>
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Cours suivis</span>
                        <span class="text-xs font-medium text-gray-900"><?php echo $enrolledCoursesCount; ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Heures d'étude</span>
                        <span class="text-xs font-medium text-gray-900"><?php echo $studyHours; ?>h</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Ressources vues</span>
                        <span class="text-xs font-medium text-gray-900"><?php echo $completedResources; ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Certifications</span>
                        <span class="text-xs font-medium text-gray-900"><?php echo count($userCertifications); ?></span>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="mt-2 pt-3 border-t border-gray-200 mb-0 pb-3">
                <h4 class="text-xs font-medium text-gray-900 mb-3">Actions rapides</h4>
                <div class="space-y-1.5">
                    <a href="new-post.php" class="flex items-center px-3 py-2 text-xs text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                        <i class="fas fa-plus mr-2 w-3"></i>
                        Nouveau post
                    </a>
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
            <!-- En-tête avec bienvenue et actions rapides -->
            <div class="mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-6 lg:mb-0">
                    <h1 class="text-2xl md:text-4xl font-bold text-gray-900 mb-2">
                        Bonjour, <?php echo htmlspecialchars($user['full_name']); ?> ! 👋
                    </h1>
                    <p class="text-base md:text-lg text-gray-600">
                        Continuez votre apprentissage en Intelligence Artificielle
                    </p>
                    <div class="mt-4 flex items-center space-x-4">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>
                            <span class="text-sm text-gray-600"><?php echo date('l, j F Y'); ?></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-clock text-green-600 mr-2"></i>
                            <span class="text-sm text-gray-600"><?php echo $studyHours; ?>h d'étude cette semaine</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="courses.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Nouveau cours
                    </a>
                    <a href="resources.php" class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-book mr-2"></i>
                        Explorer
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistiques principales -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-10">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Cours Inscrits</p>
                        <p class="text-3xl font-bold"><?php echo $enrolledCoursesCount; ?></p>
                        <p class="text-blue-200 text-sm mt-1">En cours d'apprentissage</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Heures d'Étude</p>
                        <p class="text-3xl font-bold"><?php echo $studyHours; ?>h</p>
                        <p class="text-green-200 text-sm mt-1">Cette semaine</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Ressources Consultées</p>
                        <p class="text-3xl font-bold"><?php echo $completedResources; ?></p>
                        <p class="text-purple-200 text-sm mt-1">Matériel étudié</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-book text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm font-medium">Certifications</p>
                        <p class="text-3xl font-bold"><?php echo count($userCertifications); ?></p>
                        <p class="text-yellow-200 text-sm mt-1">Obtenues</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-certificate text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="space-y-8">
                <!-- Mes cours inscrits -->
                <?php if (!empty($enrolledCourses)): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">
                                <i class="fas fa-graduation-cap text-blue-600 mr-3"></i>
                                Mes Cours en Cours
                            </h2>
                            <a href="courses.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Voir tous →
                            </a>
                        </div>
                    </div>
                    <div class="p-4 md:p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
                            <?php foreach ($enrolledCourses as $course): ?>
                            <div class="bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-lg p-5 hover:shadow-md transition-all duration-300">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-gray-900 text-lg mb-2"><?php echo htmlspecialchars($course['title']); ?></h3>
                                        <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars(substr($course['description'], 0, 80)) . '...'; ?></p>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 ml-3">
                                        <?php echo ucfirst($course['level']); ?>
                                    </span>
                                </div>
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                                        <span>Progression</span>
                                        <span class="font-medium"><?php echo $course['progress_percentage'] ?? 0; ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300" 
                                             style="width: <?php echo $course['progress_percentage'] ?? 0; ?>%"></div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center text-sm text-gray-500">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <span>Inscrit le <?php echo date('d/m/Y', strtotime($course['enrolled_at'])); ?></span>
                                    </div>
                                    <a href="course.php?id=<?php echo $course['id']; ?>" 
                                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-play mr-2"></i>
                                        Continuer
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Progression des certifications -->
                <?php if (!empty($certificationProgress)): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">
                                <i class="fas fa-road text-green-600 mr-3"></i>
                                Certifications en Cours
                            </h2>
                            <a href="certifications.php" class="text-green-600 hover:text-green-800 text-sm font-medium">
                                Voir toutes →
                            </a>
                        </div>
                    </div>
                    <div class="p-4 md:p-6">
                        <div class="space-y-4 md:space-y-6">
                            <?php foreach ($certificationProgress as $progress): ?>
                            <div class="bg-gradient-to-br from-green-50 to-white border border-green-200 rounded-lg p-5">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-gray-900 text-lg mb-2"><?php echo htmlspecialchars($progress['title']); ?></h3>
                                        <p class="text-gray-600 text-sm"><?php echo ucfirst($progress['category']); ?></p>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo getLevelColor($progress['level']); ?> ml-3">
                                        <?php echo ucfirst($progress['level']); ?>
                                    </span>
                                </div>
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                                        <span>Progression globale</span>
                                        <span class="font-medium"><?php echo $progress['progress_percentage']; ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-300" 
                                             style="width: <?php echo $progress['progress_percentage']; ?>%"></div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-500">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        <?php echo $progress['resources_completed']; ?> / <?php echo $progress['total_resources']; ?> ressources complétées
                                    </div>
                                    <a href="certifications.php" class="text-green-600 hover:text-green-800 text-sm font-medium">
                                        Continuer →
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <!-- Certifications obtenues -->
                <?php if (!empty($userCertifications)): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-yellow-50 to-amber-50 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">
                            <i class="fas fa-trophy text-yellow-600 mr-3"></i>
                            Certifications Obtenues
                        </h2>
                    </div>
                    <div class="p-4 md:p-6">
                        <div class="space-y-4">
                            <?php foreach ($userCertifications as $cert): ?>
                            <div class="bg-gradient-to-br from-yellow-50 to-white rounded-lg p-4 border border-yellow-200">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-full flex items-center justify-center">
                                        <i class="fas fa-certificate text-white"></i>
                                    </div>
                                    <span class="text-sm text-yellow-700 font-medium bg-yellow-100 px-2 py-1 rounded-full">
                                        Score: <?php echo $cert['score']; ?>%
                                    </span>
                                </div>
                                <h3 class="font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($cert['title']); ?></h3>
                                <p class="text-sm text-gray-600 mb-2"><?php echo ucfirst($cert['level']); ?></p>
                                <p class="text-xs text-gray-500">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Obtenue le <?php echo date('d/m/Y', strtotime($cert['issued_at'])); ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Ressources récemment consultées -->
                <?php if (!empty($recentlyViewedResources)): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">
                            <i class="fas fa-history text-purple-600 mr-3"></i>
                            Ressources Récentes
                        </h2>
                    </div>
                    <div class="p-4 md:p-6">
                        <div class="space-y-4">
                            <?php foreach ($recentlyViewedResources as $resource): ?>
                            <div class="flex items-center space-x-4 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-<?php echo getResourceTypeIcon($resource['type']); ?> text-white"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 text-sm truncate"><?php echo htmlspecialchars($resource['title']); ?></h3>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo date('d/m/Y', strtotime($resource['viewed_at'])); ?>
                                    </p>
                                </div>
                                <a href="resource.php?id=<?php echo $resource['id']; ?>" class="text-purple-600 hover:text-purple-800 text-sm font-medium flex-shrink-0">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions rapides -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-orange-50 to-red-50 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">
                            <i class="fas fa-bolt text-orange-600 mr-3"></i>
                            Actions Rapides
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-4">
                            <a href="courses.php" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors group">
                                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-4 group-hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-graduation-cap text-white"></i>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900">Explorer les cours</span>
                                    <p class="text-sm text-gray-600">Découvrir de nouveaux contenus</p>
                                </div>
                            </a>
                            <a href="resources.php" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors group">
                                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-4 group-hover:bg-green-700 transition-colors">
                                    <i class="fas fa-book text-white"></i>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900">Centre de ressources</span>
                                    <p class="text-sm text-gray-600">Accéder aux matériaux</p>
                                </div>
                            </a>
                            <a href="certifications.php" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors group">
                                <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center mr-4 group-hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-certificate text-white"></i>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900">Mes certifications</span>
                                    <p class="text-sm text-gray-600">Suivre mes progrès</p>
                                </div>
                            </a>
                            <a href="forum.php" class="flex items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors group">
                                <div class="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center mr-4 group-hover:bg-orange-700 transition-colors">
                                    <i class="fas fa-comments text-white"></i>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900">Forum communautaire</span>
                                    <p class="text-sm text-gray-600">Échanger avec les autres</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>

