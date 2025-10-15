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

// Récupérer les ressources avec filtres et statistiques (excluant les livres)
function getResourcesWithStats($type = null, $category = null, $search = null) {
    $pdo = getDB();
    
    $sql = "SELECT r.*, 
            COUNT(rv.id) as view_count,
            COUNT(DISTINCT rv.user_id) as unique_viewers
            FROM resources r
            LEFT JOIN resource_views rv ON r.id = rv.resource_id
            WHERE r.is_active = TRUE AND r.type != 'book'";
    
    $params = [];
    
    if ($type) {
        $sql .= " AND r.type = ?";
        $params[] = $type;
    }
    
    if ($category) {
        $sql .= " AND r.category = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.author LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql .= " GROUP BY r.id ORDER BY r.upload_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Récupérer les paramètres de filtrage
$type = $_GET['type'] ?? null;
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;

$resources = getResourcesWithStats($type, $category, $search);
$stats = getResourceStats();

// Fonction pour récupérer les ressources groupées par type (excluant les livres)
function getResourcesByType() {
    $pdo = getDB();
    $stmt = $pdo->query("
        SELECT r.*, 
               COUNT(rv.id) as view_count,
               COUNT(DISTINCT rv.user_id) as unique_viewers
        FROM resources r
        LEFT JOIN resource_views rv ON r.id = rv.resource_id
        WHERE r.is_active = TRUE AND r.type != 'book'
        GROUP BY r.id
        ORDER BY r.type, r.title ASC
    ");
    $allResources = $stmt->fetchAll();
    
    // Grouper par type
    $groupedResources = [];
    foreach ($allResources as $resource) {
        $groupedResources[$resource['type']][] = $resource;
    }
    
    return $groupedResources;
}

// Récupérer les ressources groupées par type
$resourcesByType = getResourcesByType();

// Labels des types
$typeLabels = [
    'video' => 'Vidéos',
    'document' => 'Documents',
    'code' => 'Codes Sources',
    'dataset' => 'Datasets',
    'presentation' => 'Présentations'
];

// Icônes des types
$typeIcons = [
    'video' => 'play-circle',
    'document' => 'file-alt',
    'code' => 'code',
    'dataset' => 'database',
    'presentation' => 'file-powerpoint'
];

// Couleurs des types
$typeColors = [
    'video' => 'from-red-500 to-pink-600',
    'document' => 'from-blue-500 to-indigo-600',
    'code' => 'from-green-500 to-emerald-600',
    'dataset' => 'from-purple-500 to-violet-600',
    'presentation' => 'from-teal-500 to-cyan-600'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centre de Ressources - Académie IA</title>
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
        
            </div>
        </div>

        <!-- Contenu principal -->
        
    <div class="flex">
        <!-- Sidebar (desktop) -->
        <div class="hidden md:block w-64 bg-white shadow-md border-r border-gray-200 rounded-r-xl fixed left-0 top-16 h-[calc(100vh-4rem-1.5rem)] z-30" style="scrollbar-width: thin; scrollbar-color: #cbd5e0 #f7fafc;">
            <div class="px-3 pt-3 pb-3 h-full overflow-y-hidden hover:overflow-y-auto overscroll-contain pr-2 sidebar-scroll">
            <!-- Profil utilisateur -->
            <div class="text-center mb-3">
                <div class="w-14 h-14 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-2 shadow-sm">
                    <i class="fas fa-user-graduate text-white text-lg"></i>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm tracking-tight"><?php echo ($isLoggedIn && !empty($user['full_name'])) ? htmlspecialchars($user['full_name']) : 'Invité'; ?></h3>
                <p class="text-xs text-gray-500">Étudiant</p>
                <div class="mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-100 text-blue-800">
                        <i class="fas fa-star mr-1"></i>
                        Niveau <?php echo $user['level'] ?? 'Débutant'; ?>
                    </span>
                </div>
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
                <a href="resources.php" class="group flex items-center px-3 py-2 rounded-lg font-medium text-sm text-blue-600 bg-blue-50 ring-1 ring-blue-100">
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

        <!-- Contenu principal --><main class="flex-1 ml-64 mt-16 p-4 md:p-8 pb-24">
        <div class="py-3 sm:py-4">
            <!-- En-tête -->
            <div class="mb-4 sm:mb-6">
                <h1 class="text-xl sm:text-lg font-bold text-gray-900 mb-1">
                    Centre de Ressources
                </h1>
                <p class="text-xs sm:text-sm text-gray-600">
                    Découvrez notre bibliothèque complète de ressources académiques (sciences, droit, informatique, langues, management, etc.)
                </p>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-book text-lg text-blue-600"></i>
                            </div>
                            <div class="ml-4 w-0 flex-1">
                                <dl>
                                    <dt class="text-xs font-medium text-gray-500 truncate">Total Ressources</dt>
                                    <dd class="text-base font-medium text-gray-900"><?php echo $stats['total']; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-video text-lg text-green-600"></i>
                            </div>
                            <div class="ml-4 w-0 flex-1">
                                <dl>
                                    <dt class="text-xs font-medium text-gray-500 truncate">Vidéos</dt>
                                    <dd class="text-base font-medium text-gray-900">
                                        <?php 
                                        $videoCount = 0;
                                        foreach ($stats['by_type'] as $typeStat) {
                                            if ($typeStat['type'] === 'video') {
                                                $videoCount = $typeStat['count'];
                                                break;
                                            }
                                        }
                                        echo $videoCount;
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-file-alt text-lg text-purple-600"></i>
                            </div>
                            <div class="ml-4 w-0 flex-1">
                                <dl>
                                    <dt class="text-xs font-medium text-gray-500 truncate">Documents</dt>
                                    <dd class="text-base font-medium text-gray-900">
                                        <?php 
                                        $docCount = 0;
                                        foreach ($stats['by_type'] as $typeStat) {
                                            if ($typeStat['type'] === 'document') {
                                                $docCount = $typeStat['count'];
                                                break;
                                            }
                                        }
                                        echo $docCount;
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-code text-lg text-yellow-600"></i>
                            </div>
                            <div class="ml-4 w-0 flex-1">
                                <dl>
                                    <dt class="text-xs font-medium text-gray-500 truncate">Code Source</dt>
                                    <dd class="text-base font-medium text-gray-900">
                                        <?php 
                                        $codeCount = 0;
                                        foreach ($stats['by_type'] as $typeStat) {
                                            if ($typeStat['type'] === 'code') {
                                                $codeCount = $typeStat['count'];
                                                break;
                                            }
                                        }
                                        echo $codeCount;
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="bg-white shadow rounded-lg mb-4 sm:mb-6">
                <div class="p-3 sm:p-4">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                                <select name="type" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Tous les types</option>
                                <option value="document" <?php echo $type === 'document' ? 'selected' : ''; ?>>Document</option>
                                <option value="video" <?php echo $type === 'video' ? 'selected' : ''; ?>>Vidéo</option>
                                <option value="code" <?php echo $type === 'code' ? 'selected' : ''; ?>>Code Source</option>
                                <option value="presentation" <?php echo $type === 'presentation' ? 'selected' : ''; ?>>Présentation</option>
                                <option value="dataset" <?php echo $type === 'dataset' ? 'selected' : ''; ?>>Dataset</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Catégorie</label>
                                <select name="category" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Toutes les catégories</option>
                                <option value="artificial-intelligence" <?php echo $category === 'artificial-intelligence' ? 'selected' : ''; ?>>Intelligence Artificielle</option>
                                <option value="machine-learning" <?php echo $category === 'machine-learning' ? 'selected' : ''; ?>>Machine Learning</option>
                                <option value="deep-learning" <?php echo $category === 'deep-learning' ? 'selected' : ''; ?>>Deep Learning</option>
                                <option value="embedded-systems" <?php echo $category === 'embedded-systems' ? 'selected' : ''; ?>>Systèmes Embarqués</option>
                                <option value="software-engineering" <?php echo $category === 'software-engineering' ? 'selected' : ''; ?>>Génie Logiciel</option>
                                <option value="programming" <?php echo $category === 'programming' ? 'selected' : ''; ?>>Programmation</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Recherche</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                                   placeholder="Rechercher..." 
                                   class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-blue-600 text-white px-3 py-1.5 rounded-md hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-search mr-1"></i>Filtrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ressources groupées par type -->
            <?php if (empty($resourcesByType)): ?>
                <div class="bg-white shadow rounded-lg">
                    <div class="text-center py-8 sm:py-12">
                        <i class="fas fa-search text-3xl sm:text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune ressource trouvée</h3>
                        <p class="text-gray-600">Essayez de modifier vos critères de recherche</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($resourcesByType as $typeKey => $typeResources): ?>
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100 cursor-pointer hover:from-gray-100 hover:to-gray-200 transition-all duration-200" 
                         onclick="toggleCategory('<?php echo $typeKey; ?>')">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gradient-to-r <?php echo $typeColors[$typeKey] ?? 'from-gray-500 to-gray-600'; ?> rounded-lg flex items-center justify-center mr-4 shadow-sm">
                                    <i class="fas fa-<?php echo $typeIcons[$typeKey] ?? 'file'; ?> text-white text-xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">
                                        <?php echo $typeLabels[$typeKey] ?? ucfirst($typeKey); ?>
                                    </h2>
                                    <p class="text-sm text-gray-600"><?php echo count($typeResources); ?> ressource(s) disponible(s)</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="px-4 py-2 text-sm font-medium rounded-full bg-white border border-gray-200 text-gray-700 shadow-sm">
                                    <?php echo count($typeResources); ?> items
                                </span>
                                <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm border border-gray-200">
                                    <i id="icon-<?php echo $typeKey; ?>" class="fas fa-chevron-down text-gray-600 transition-transform duration-200"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="content-<?php echo $typeKey; ?>" class="p-4 sm:p-6 transition-all duration-300 ease-in-out">
                        <?php if ($typeKey === 'video'): ?>
                            <?php 
                            // 1) Logique initiale: regrouper les vidéos numérotées en série (ex: "1. Titre")
                            $videoSeries = [];
                            $nonSeries = [];
                            foreach ($typeResources as $video) {
                                if (preg_match('/^(\d+)\.\s*(.+)/', $video['title'] ?? '', $matches)) {
                                    $episodeNumber = intval($matches[1]);
                                    $episodeTitle = trim($matches[2]);
                                    
                                    // Filtrer uniquement les vidéos du dossier "python pour tous"
                                    $fileUrl = $video['file_url'] ?? '';
                                    if (strpos($fileUrl, 'python pour tous') !== false) {
                                        $seriesName = 'Python pour tous';
                                        if (!isset($videoSeries[$seriesName])) {
                                            $videoSeries[$seriesName] = [];
                                        }
                                        $videoSeries[$seriesName][] = [
                                            'episode' => $episodeNumber,
                                            'episodeTitle' => $episodeTitle,
                                            'video' => $video
                                        ];
                                    } else {
                                        $nonSeries[] = $video;
                                    }
                                } else {
                                    $nonSeries[] = $video;
                                }
                            }
                            
                            // Ajouter une série pour le dossier devperso (même logique d'affichage qu'une série)
                            $devPersoEpisodes = [];
                            foreach ($typeResources as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/devperso/') !== false || strpos($normalized, 'videos/devperso/') !== false) {
                                    $episodeNumber = 1; // Par défaut
                                    if (preg_match('/^(\d+)\./', $video['title'] ?? '', $matches)) {
                                        $episodeNumber = intval($matches[1]);
                                    } else {
                                        $episodeNumber = $idx + 1;
                                    }
                                    $devPersoEpisodes[] = [
                                        'episode' => $episodeNumber,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                }
                            }
                            if (!empty($devPersoEpisodes)) {
                                // Réindexer et trier par episode
                                $devPersoEpisodes = array_values($devPersoEpisodes);
                                usort($devPersoEpisodes, function($a, $b) { return $a['episode'] - $b['episode']; });
                                $videoSeries['Développement personnel'] = $devPersoEpisodes;
                            }

                            // Ajouter une série pour le dossier chimie
                            $chimieEpisodes = [];
                            foreach ($typeResources as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/chimie/') !== false || strpos($normalized, 'videos/chimie/') !== false ||
                                    strpos($normalized, '/videos/chemistry/') !== false || strpos($normalized, 'videos/chemistry/') !== false) {
                                    $title = $video['title'] ?? '';
                                    $episodeNumber = 1; // Par défaut
                                    
                                    // Détecter le numéro d'épisode
                                    if (preg_match('/^(\d+)\./', $title, $matches)) {
                                        $episodeNumber = intval($matches[1]);
                                    } else {
                                        $episodeNumber = $idx + 1;
                                    }
                                    
                                    $chimieEpisodes[] = [
                                        'episode' => $episodeNumber,
                                        'episodeTitle' => $title,
                                        'video' => $video
                                    ];
                                }
                            }
                            if (!empty($chimieEpisodes)) {
                                // Trier par numéro d'épisode
                                usort($chimieEpisodes, function($a, $b) { 
                                    return $a['episode'] - $b['episode']; 
                                });
                                $videoSeries['Chimie'] = $chimieEpisodes;
                            }

                            // Ajouter une série pour le dossier langues
                            $languesEpisodes = [];
                            foreach ($typeResources as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/langues/') !== false || strpos($normalized, 'videos/langues/') !== false ||
                                    strpos($normalized, '/videos/languages/') !== false || strpos($normalized, 'videos/languages/') !== false) {
                                    $title = $video['title'] ?? '';
                                    $episodeNumber = 1; // Par défaut
                                    
                                    // Détecter le numéro d'épisode
                                    if (preg_match('/^(\d+)\./', $title, $matches)) {
                                        $episodeNumber = intval($matches[1]);
                                    } else {
                                        $episodeNumber = $idx + 1;
                                    }
                                    
                                    $languesEpisodes[] = [
                                        'episode' => $episodeNumber,
                                        'episodeTitle' => $title,
                                        'video' => $video
                                    ];
                                }
                            }
                            if (!empty($languesEpisodes)) {
                                // Trier par numéro d'épisode
                                usort($languesEpisodes, function($a, $b) { 
                                    return $a['episode'] - $b['episode']; 
                                });
                                $videoSeries['Langues'] = $languesEpisodes;
                            }

                            // Ajouter une série pour le dossier droit-contrats
                            $droitContratsEpisodes = [];
                            foreach ($typeResources as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/droit-contrats/') !== false || strpos($normalized, 'videos/droit-contrats/') !== false ||
                                    strpos($normalized, '/videos/droit_contrats/') !== false || strpos($normalized, 'videos/droit_contrats/') !== false) {
                                    $title = $video['title'] ?? '';
                                    $episodeNumber = 1; // Par défaut
                                    
                                    // Détecter le numéro d'épisode
                                    if (preg_match('/^(\d+)\./', $title, $matches)) {
                                        $episodeNumber = intval($matches[1]);
                                    } else {
                                        $episodeNumber = $idx + 1;
                                    }
                                    
                                    $droitContratsEpisodes[] = [
                                        'episode' => $episodeNumber,
                                        'episodeTitle' => $title,
                                        'video' => $video
                                    ];
                                }
                            }
                            if (!empty($droitContratsEpisodes)) {
                                // Trier par numéro d'épisode
                                usort($droitContratsEpisodes, function($a, $b) { 
                                    return $a['episode'] - $b['episode']; 
                                });
                                $videoSeries['Droit des contrats'] = $droitContratsEpisodes;
                            }

                            // Ajouter une série pour le dossier marketing
                            $marketingEpisodes = [];
                            foreach ($typeResources as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/marketing/') !== false || strpos($normalized, 'videos/marketing/') !== false) {
                                    $title = $video['title'] ?? '';
                                    $episodeNumber = 1; // Par défaut
                                    
                                    // Détecter le numéro d'épisode
                                    if (preg_match('/^(\d+)\./', $title, $matches)) {
                                        $episodeNumber = intval($matches[1]);
                                    } else {
                                        $episodeNumber = $idx + 1;
                                    }
                                    
                                    $marketingEpisodes[] = [
                                        'episode' => $episodeNumber,
                                        'episodeTitle' => $title,
                                        'video' => $video
                                    ];
                                }
                            }
                            if (!empty($marketingEpisodes)) {
                                // Trier par numéro d'épisode
                                usort($marketingEpisodes, function($a, $b) { 
                                    return $a['episode'] - $b['episode']; 
                                });
                                $videoSeries['Marketing'] = $marketingEpisodes;
                            }

                            // Ajouter une série pour le dossier leadership
                            $leadershipEpisodes = [];
                            foreach ($typeResources as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/leadership/') !== false || strpos($normalized, 'videos/leadership/') !== false) {
                                    $title = $video['title'] ?? '';
                                    $episodeNumber = 1; // Par défaut
                                    
                                    // Détecter le numéro d'épisode
                                    if (preg_match('/^(\d+)\./', $title, $matches)) {
                                        $episodeNumber = intval($matches[1]);
                                    } else {
                                        $episodeNumber = $idx + 1;
                                    }
                                    
                                    $leadershipEpisodes[] = [
                                        'episode' => $episodeNumber,
                                        'episodeTitle' => $title,
                                        'video' => $video
                                    ];
                                }
                            }
                            if (!empty($leadershipEpisodes)) {
                                // Trier par numéro d'épisode
                                usort($leadershipEpisodes, function($a, $b) { 
                                    return $a['episode'] - $b['episode']; 
                                });
                                $videoSeries['Leadership'] = $leadershipEpisodes;
                            }

                            // Ajouter une série pour le dossier communication
                            $communicationEpisodes = [];
                            foreach ($typeResources as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/communication/') !== false || strpos($normalized, 'videos/communication/') !== false) {
                                    $title = $video['title'] ?? '';
                                    $episodeNumber = 1; // Par défaut
                                    
                                    // Détecter le numéro d'épisode
                                    if (preg_match('/^(\d+)\./', $title, $matches)) {
                                        $episodeNumber = intval($matches[1]);
                                    } else {
                                        $episodeNumber = $idx + 1;
                                    }
                                    
                                    $communicationEpisodes[] = [
                                        'episode' => $episodeNumber,
                                        'episodeTitle' => $title,
                                        'video' => $video
                                    ];
                                }
                            }
                            if (!empty($communicationEpisodes)) {
                                // Trier par numéro d'épisode
                                usort($communicationEpisodes, function($a, $b) { 
                                    return $a['episode'] - $b['episode']; 
                                });
                                $videoSeries['Communication'] = $communicationEpisodes;
                            }

                            // Créer des séries complètes séparées pour l'informatique
                            $informatiqueSeries = [];
                            foreach ($typeResources as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/informatique/') !== false || strpos($normalized, 'videos/informatique/') !== false ||
                                    strpos($normalized, '/videos/info/') !== false || strpos($normalized, 'videos/info/') !== false) {
                                    
                                    $title = $video['title'] ?? '';
                                    $seriesName = null; // Pas de série par défaut
                                    
                                    // Détecter la série selon le chemin
                                    if (strpos($normalized, '/programmation/') !== false || strpos($normalized, '/programming/') !== false) {
                                        $seriesName = null; // Exclure la série Programmation
                                    } elseif (strpos($normalized, '/reseaux/') !== false || strpos($normalized, '/networks/') !== false) {
                                        $seriesName = 'Réseaux informatiques';
                                    } elseif (strpos($normalized, '/bases-donnees/') !== false || strpos($normalized, '/database/') !== false) {
                                        $seriesName = 'Bases de données';
                                    } elseif (strpos($normalized, '/systemes/') !== false || strpos($normalized, '/systems/') !== false) {
                                        $seriesName = 'Systèmes d\'exploitation';
                                    } elseif (strpos($normalized, '/securite/') !== false || strpos($normalized, '/security/') !== false) {
                                        $seriesName = null; // Exclure la série Sécurité informatique
                                    }
                                    
                                    $episodeNumber = 1; // Par défaut
                                    if (preg_match('/^(\d+)\./', $title, $matches)) {
                                        $episodeNumber = intval($matches[1]);
                                    } else {
                                        $episodeNumber = $idx + 1;
                                    }
                                    
                                    // Ne pas ajouter à une série si $seriesName est null
                                    if ($seriesName !== null) {
                                        if (!isset($informatiqueSeries[$seriesName])) {
                                            $informatiqueSeries[$seriesName] = [];
                                        }
                                        
                                        $informatiqueSeries[$seriesName][] = [
                                            'episode' => $episodeNumber,
                                            'episodeTitle' => $title,
                                            'video' => $video
                                        ];
                                    }
                                }
                            }
                            
                            // Trier chaque série et les ajouter comme séries séparées
                            if (!empty($informatiqueSeries)) {
                                foreach ($informatiqueSeries as $seriesName => $episodes) {
                                    usort($informatiqueSeries[$seriesName], function($a, $b) { 
                                        return $a['episode'] - $b['episode']; 
                                    });
                                    $videoSeries[$seriesName] = $informatiqueSeries[$seriesName];
                                }
                            }

                            // Ajouter une série pour le dossier cybersecu avec ordre séquentiel
                            $cyberSecEpisodes = [];
                            foreach ($typeResources as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/cybersecu/') !== false || strpos($normalized, 'videos/cybersecu/') !== false) {
                                    $title = $video['title'] ?? '';
                                    $episodeNumber = 1; // Par défaut
                                    
                                    // Détecter le numéro d'épisode selon l'ordre séquentiel
                                    if (preg_match('/CompTIA Security\(SY0-601\)\s*(\d+)/', $title, $matches)) {
                                        // Vidéos CompTIA Security(SY0-601) 1, 2, 3, 4, 5
                                        $episodeNumber = intval($matches[1]);
                                    } elseif (preg_match('/^(\d+)\./', $title, $matches)) {
                                        // Vidéos numérotées directement 6, 7, 8, etc.
                                        $episodeNumber = intval($matches[1]);
                                    } else {
                                        // Pour les autres vidéos, utiliser l'index + 1
                                        $episodeNumber = $idx + 1;
                                    }
                                    
                                    $cyberSecEpisodes[] = [
                                        'episode' => $episodeNumber,
                                        'episodeTitle' => $title,
                                        'video' => $video
                                    ];
                                }
                            }
                            if (!empty($cyberSecEpisodes)) {
                                // Trier par numéro d'épisode pour respecter l'ordre séquentiel
                                usort($cyberSecEpisodes, function($a, $b) { 
                                    return $a['episode'] - $b['episode']; 
                                });
                                $videoSeries['Cybersécurité'] = $cyberSecEpisodes;
                            }

                            // Ajouter une série pour le dossier Design
                            $designEpisodes = [];
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/Design/') !== false || strpos($normalized, 'videos/Design/') !== false || strpos(strtolower($normalized), '/videos/design/') !== false) {
                                    $designEpisodes[] = [
                                        'episode' => $idx + 1,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                    unset($nonSeries[$idx]);
                                }
                            }
                            if (!empty($designEpisodes)) {
                                $designEpisodes = array_values($designEpisodes);
                                usort($designEpisodes, function($a, $b) { return $a['episode'] - $b['episode']; });
                                $videoSeries['Design'] = $designEpisodes;
                            }

                            // Ajouter une série pour le dossier économie
                            $economieEpisodes = [];
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/economie/') !== false || strpos($normalized, 'videos/economie/') !== false) {
                                    $economieEpisodes[] = [
                                        'episode' => $idx + 1,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                    unset($nonSeries[$idx]);
                                }
                            }
                            if (!empty($economieEpisodes)) {
                                $economieEpisodes = array_values($economieEpisodes);
                                usort($economieEpisodes, function($a, $b) { return $a['episode'] - $b['episode']; });
                                $videoSeries['Économie & Management'] = $economieEpisodes;
                            }

                            // Ajouter une série pour le dossier science-medicale
                            $scienceMedicalEpisodes = [];
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/science-medicale/') !== false || strpos($normalized, 'videos/science-medicale/') !== false) {
                                    $scienceMedicalEpisodes[] = [
                                        'episode' => $idx + 1,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                    unset($nonSeries[$idx]);
                                }
                            }
                            if (!empty($scienceMedicalEpisodes)) {
                                $scienceMedicalEpisodes = array_values($scienceMedicalEpisodes);
                                usort($scienceMedicalEpisodes, function($a, $b) { return $a['episode'] - $b['episode']; });
                                $videoSeries['Santé et Médecine'] = $scienceMedicalEpisodes;
                            }

                            // Ajouter une série pour le dossier environnement
                            $environnementEpisodes = [];
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/environnement/') !== false || strpos($normalized, 'videos/environnement/') !== false) {
                                    $environnementEpisodes[] = [
                                        'episode' => $idx + 1,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                    unset($nonSeries[$idx]);
                                }
                            }
                            if (!empty($environnementEpisodes)) {
                                $environnementEpisodes = array_values($environnementEpisodes);
                                usort($environnementEpisodes, function($a, $b) { return $a['episode'] - $b['episode']; });
                                $videoSeries['Environnement'] = $environnementEpisodes;
                            }

                            // Ajouter une série pour le dossier intelligence-artificielle
                            $aiEpisodes = [];
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/ia/') !== false || strpos($normalized, 'videos/ia/') !== false || 
                                    strpos($normalized, '/videos/ai/') !== false || strpos($normalized, 'videos/ai/') !== false ||
                                    strpos($normalized, '/videos/intelligence-artificielle/') !== false || strpos($normalized, 'videos/intelligence-artificielle/') !== false) {
                                    $aiEpisodes[] = [
                                        'episode' => $idx + 1,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                    unset($nonSeries[$idx]);
                                }
                            }
                            if (!empty($aiEpisodes)) {
                                $aiEpisodes = array_values($aiEpisodes);
                                usort($aiEpisodes, function($a, $b) { return $a['episode'] - $b['episode']; });
                                $videoSeries['Intelligence Artificielle'] = $aiEpisodes;
                            }

                            // Ajouter une série pour le dossier machine-learning
                            $mlEpisodes = [];
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/ml/') !== false || strpos($normalized, 'videos/ml/') !== false ||
                                    strpos($normalized, '/videos/machine-learning/') !== false || strpos($normalized, 'videos/machine-learning/') !== false) {
                                    $mlEpisodes[] = [
                                        'episode' => $idx + 1,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                    unset($nonSeries[$idx]);
                                }
                            }
                            if (!empty($mlEpisodes)) {
                                $mlEpisodes = array_values($mlEpisodes);
                                usort($mlEpisodes, function($a, $b) { return $a['episode'] - $b['episode']; });
                                $videoSeries['Machine Learning'] = $mlEpisodes;
                            }

                            // Ajouter une série pour le dossier programmation
                            $programmingEpisodes = [];
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/programmation/') !== false || strpos($normalized, 'videos/programmation/') !== false ||
                                    strpos($normalized, '/videos/programming/') !== false || strpos($normalized, 'videos/programming/') !== false ||
                                    strpos($normalized, '/videos/code/') !== false || strpos($normalized, 'videos/code/') !== false) {
                                    $programmingEpisodes[] = [
                                        'episode' => $idx + 1,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                    unset($nonSeries[$idx]);
                                }
                            }
                            // Série Programmation exclue - ne pas l'ajouter

                            // Ajouter une série pour le dossier data-science
                            $dataScienceEpisodes = [];
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/data-science/') !== false || strpos($normalized, 'videos/data-science/') !== false ||
                                    strpos($normalized, '/videos/datascience/') !== false || strpos($normalized, 'videos/datascience/') !== false) {
                                    $dataScienceEpisodes[] = [
                                        'episode' => $idx + 1,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                    unset($nonSeries[$idx]);
                                }
                            }
                            if (!empty($dataScienceEpisodes)) {
                                $dataScienceEpisodes = array_values($dataScienceEpisodes);
                                usort($dataScienceEpisodes, function($a, $b) { return $a['episode'] - $b['episode']; });
                                $videoSeries['Data Science'] = $dataScienceEpisodes;
                            }

                            // Ajouter une série pour le dossier web-development
                            $webDevEpisodes = [];
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/web/') !== false || strpos($normalized, 'videos/web/') !== false ||
                                    strpos($normalized, '/videos/web-development/') !== false || strpos($normalized, 'videos/web-development/') !== false ||
                                    strpos($normalized, '/videos/frontend/') !== false || strpos($normalized, 'videos/frontend/') !== false ||
                                    strpos($normalized, '/videos/backend/') !== false || strpos($normalized, 'videos/backend/') !== false) {
                                    $webDevEpisodes[] = [
                                        'episode' => $idx + 1,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                    unset($nonSeries[$idx]);
                                }
                            }
                            if (!empty($webDevEpisodes)) {
                                $webDevEpisodes = array_values($webDevEpisodes);
                                usort($webDevEpisodes, function($a, $b) { return $a['episode'] - $b['episode']; });
                                $videoSeries['Développement Web'] = $webDevEpisodes;
                            }

                            // Ajouter une série pour le dossier chimie
                            $chimieEpisodes = [];
                            foreach ($typeResources as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/chimie/') !== false || strpos($normalized, 'videos/chimie/') !== false ||
                                    strpos($normalized, '/videos/chemistry/') !== false || strpos($normalized, 'videos/chemistry/') !== false) {
                                    $title = $video['title'] ?? '';
                                    $episodeNumber = 1; // Par défaut
                                    
                                    // Détecter le numéro d'épisode
                                    if (preg_match('/^(\d+)\./', $title, $matches)) {
                                        $episodeNumber = intval($matches[1]);
                                    } else {
                                        $episodeNumber = $idx + 1;
                                    }
                                    
                                    $chimieEpisodes[] = [
                                        'episode' => $episodeNumber,
                                        'episodeTitle' => $title,
                                        'video' => $video
                                    ];
                                }
                            }
                            if (!empty($chimieEpisodes)) {
                                // Trier par numéro d'épisode
                                usort($chimieEpisodes, function($a, $b) { 
                                    return $a['episode'] - $b['episode']; 
                                });
                                $videoSeries['Chimie'] = $chimieEpisodes;
                            }

                            // Ajouter une série pour le dossier droit avec ordre personnalisé
                            $droitItems = [];
                            $featured = null; // La vidéo avec le titre spécifique en premier
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/droit/') !== false || strpos($normalized, 'videos/droit/') !== false) {
                                    $basename = strtolower(basename($normalized));
                                    // Détecter la vidéo "Les fondements ... droit numérique.mp4" comme première
                                    if ($basename === strtolower('Les fondements de la propriété intellectuelle et du droit numérique.mp4')) {
                                        $featured = [
                                            'episodeTitle' => $video['title'] ?? 'Épisode',
                                            'video' => $video
                                        ];
                                    } else {
                                        // Essayer de lire un index numérique depuis le nom de fichier (ex: 0.mp4, 1.mp4, 2.mp4)
                                        $nameNoExt = pathinfo($basename, PATHINFO_FILENAME);
                                        $num = is_numeric($nameNoExt) ? (int)$nameNoExt : PHP_INT_MAX; // non numéros à la fin
                                        $droitItems[] = [
                                            'num' => $num,
                                            'episodeTitle' => $video['title'] ?? 'Épisode',
                                            'video' => $video
                                        ];
                                    }
                                    unset($nonSeries[$idx]);
                                }
                            }
                            if (!empty($featured) || !empty($droitItems)) {
                                // Trier les éléments numériques croissants
                                usort($droitItems, function($a, $b) { return $a['num'] <=> $b['num']; });
                                $episodeCounter = 1;
                                $droitEpisodesOrdered = [];
                                if (!empty($featured)) {
                                    $droitEpisodesOrdered[] = [
                                        'episode' => $episodeCounter++,
                                        'episodeTitle' => $featured['episodeTitle'],
                                        'video' => $featured['video']
                                    ];
                                }
                                foreach ($droitItems as $item) {
                                    $droitEpisodesOrdered[] = [
                                        'episode' => $episodeCounter++,
                                        'episodeTitle' => $item['episodeTitle'],
                                        'video' => $item['video']
                                    ];
                                }
                                $videoSeries['Droit'] = $droitEpisodesOrdered;
                            }
                            foreach ($videoSeries as $seriesName => $episodes) {
                                // Trier toutes les séries de la même manière
                                if (is_array($episodes) && isset($episodes[0]) && is_array($episodes[0]) && isset($episodes[0]['episode'])) {
                                    usort($videoSeries[$seriesName], function($a, $b) { 
                                        return $a['episode'] - $b['episode']; 
                                    });
                                }
                            }
                            
                            // Trier les séries par ordre alphabétique
                            ksort($videoSeries);

                            // Calculer le nombre réel d'épisodes par série (DB + fichiers du dossier)
                            $seriesEpisodeCounts = [];
                            $seriesFolderMap = [
                                'Droit' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'droit',
                                'Développement personnel' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'devperso',
                                'Cybersécurité' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'cybersecu',
                                'Design' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'Design',
                                'Économie & Management' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'economie',
                                'Santé et Médecine' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'science-medicale',
                                'Environnement' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'environnement',
                                'Intelligence Artificielle' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'ia',
                                'Machine Learning' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'ml',
                                'Programmation' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'programmation',
                                'Data Science' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'data-science',
                                'Développement Web' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'web',
                                'Chimie' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'chimie',
                                'Langues' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'langues',
                                'Droit des contrats' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'droit-contrats',
                                'Marketing' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'marketing',
                                'Leadership' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'leadership',
                                'Communication' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'communication',
                                'Informatique' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'informatique',
                                'Informatique générale' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'informatique',
                                'Programmation' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'informatique' . DIRECTORY_SEPARATOR . 'programmation',
                                'Réseaux informatiques' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'informatique' . DIRECTORY_SEPARATOR . 'reseaux',
                                'Bases de données' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'informatique' . DIRECTORY_SEPARATOR . 'bases-donnees',
                                'Systèmes d\'exploitation' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'informatique' . DIRECTORY_SEPARATOR . 'systemes',
                                'Sécurité informatique' => __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'informatique' . DIRECTORY_SEPARATOR . 'securite',
                            ];
                            $videoExtensions = ['mp4','mkv','webm','mov','avi'];
                            foreach ($videoSeries as $seriesName => $episodes) {
                                // Pour toutes les séries, compter normalement
                                $count = count($episodes);
                                if (isset($seriesFolderMap[$seriesName]) && is_dir($seriesFolderMap[$seriesName])) {
                                    $fsCount = 0;
                                    foreach (scandir($seriesFolderMap[$seriesName]) as $file) {
                                        if ($file === '.' || $file === '..') { continue; }
                                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                        if (in_array($ext, $videoExtensions, true)) {
                                            $fsCount++;
                                        }
                                    }
                                    if ($fsCount > $count) {
                                        $count = $fsCount;
                                    }
                                }
                                $seriesEpisodeCounts[$seriesName] = $count;
                            }
                            ?>
                            <?php if (!empty($videoSeries)): ?>
                            <?php
                                // Cache pour vérification de présence de vidéos réelles dans un dossier
                                $dirHasVideoCache = [];
                                $hasRealVideos = function($dir) use (&$dirHasVideoCache) {
                                    if (!is_string($dir) || $dir === '' || !is_dir($dir)) { return false; }
                                    if (isset($dirHasVideoCache[$dir])) { return $dirHasVideoCache[$dir]; }
                                    $videoExtensions = ['mp4','webm','ogg','mov','mkv'];
                                    $found = false;
                                    foreach (scandir($dir) as $file) {
                                        if ($file === '.' || $file === '..') { continue; }
                                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                        if (in_array($ext, $videoExtensions, true)) { $found = true; break; }
                                    }
                                    $dirHasVideoCache[$dir] = $found;
                                    return $found;
                                };
                            ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-6">
                                <?php foreach ($videoSeries as $seriesName => $episodes): ?>
                                    <?php
                                    // Détecter si c'est une série fictive (pas de fichiers réels)
                                    $isFictional = false;
                                if (isset($seriesFolderMap[$seriesName])) {
                                    $dir = $seriesFolderMap[$seriesName];
                                    $hasRealFiles = $hasRealVideos($dir);
                                    $isFictional = !$hasRealFiles && count($episodes) > 0;
                                }
                                    ?>
                                    
                                    <!-- Série complète -->
                                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-200 cursor-pointer relative" 
                                         onclick="window.location.href='mediatheque.php?series=<?php echo urlencode($seriesName); ?>';">
                                        
                                        
                                        <?php if ($isFictional): ?>
                                        <!-- Design simplifié pour les séries fictives -->
                                        <div class="relative h-48 bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center opacity-30">
                                            <div class="text-center text-white">
                                                <?php if ($seriesName === 'Chimie' || $seriesName === 'Langues'): ?>
                                                <div class="animate-pulse mb-3">
                                                    <i class="fas fa-clock text-5xl text-yellow-400"></i>
                                                </div>
                                                <p class="text-sm font-medium">Pending</p>
                                                <?php else: ?>
                                                <div class="animate-spin mb-3">
                                                    <i class="fas fa-tools text-5xl"></i>
                                                </div>
                                                <p class="text-sm font-medium">Ajout des formations en cours</p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="absolute top-3 right-3 bg-black bg-opacity-80 text-white text-xs px-2 py-1 rounded font-medium">
                                                <?php 
                                                $totalDuration = 0;
                                                foreach ($episodes as $episode) {
                                                    $duration = $episode['video']['duration'] ?? null;
                                                    if ($duration) {
                                                        $totalDuration += intval($duration);
                                                    }
                                                }
                                                echo $totalDuration > 0 ? $totalDuration . ' min' : (($seriesEpisodeCounts[$seriesName] ?? count($episodes)) * 15) . ' min';
                                                ?>
                                            </div>
                                        </div>
                                        <div class="p-4">
                                            <h3 class="font-semibold text-gray-900 text-lg mb-2 line-clamp-2"><?php echo htmlspecialchars($seriesName); ?></h3>
                                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                                <!-- description removed per request -->
                                            </p>
                                            <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                                                <span class="flex items-center"><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($episodes[0]['video']['author']); ?></span>
                                                <span class="flex items-center"><i class="fas fa-eye mr-1"></i>
                                                    <?php $totalViews = 0; foreach ($episodes as $episode) { $totalViews += ($episode['video']['view_count'] ?? 0); } echo number_format($totalViews); ?> vues
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between mb-4">
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium"><?php echo ucfirst($episodes[0]['video']['category']); ?></span>
                                            </div>
                                            <div class="w-full bg-blue-600 text-white text-center py-2 px-4 rounded hover:bg-blue-700 transition-colors text-sm font-medium">
                                                <i class="fas fa-eye mr-2"></i>
                                                Voir plus
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <!-- Design complet pour les séries réelles -->
                                        <div class="relative h-48 bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center overflow-hidden">
                                            <?php 
                                            // Vignette extraite du premier épisode s'il existe
                                            $first = $episodes[0]['video'] ?? null;
                                            $firstId = $first['id'] ?? null;
                                            $thumbId = 'thumb_' . md5($seriesName);
                                            // Slug pour fallback d'image statique par série
                                            $seriesSlug = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $seriesName));
                                            $seriesSlug = preg_replace('/[^a-z0-9]+/', '-', $seriesSlug);
                                            $seriesSlug = trim($seriesSlug, '-');
                                            ?>
                                            <img id="<?php echo $thumbId; ?>" data-resource-id="<?php echo (int)$firstId; ?>" alt="Aperçu" class="absolute inset-0 w-full h-full object-cover js-thumb" style="opacity:0; transition: opacity 200ms;" loading="lazy"
                                                 src="<?php echo $firstId ? ('thumbnail.php?id=' . (int)$firstId) : '' ; ?>"
                                                 data-fallback1="<?php echo 'images/series/' . $seriesSlug . '.jpg'; ?>"
                                                 data-fallback2="images/series/default.jpg"
                                            />
                                            <div class="absolute top-3 right-3 bg-black bg-opacity-80 text-white text-xs px-2 py-1 rounded font-medium">
                                                <?php 
                                                $totalDuration = 0;
                                                foreach ($episodes as $episode) {
                                                    $duration = $episode['video']['duration'] ?? null;
                                                    if ($duration) {
                                                        $totalDuration += intval($duration);
                                                    }
                                                }
                                                echo $totalDuration > 0 ? $totalDuration . ' min' : (($seriesEpisodeCounts[$seriesName] ?? count($episodes)) * 15) . ' min';
                                                ?>
                                            </div>
                                        </div>
                                        <div class="p-4">
                                            <h3 class="font-semibold text-gray-900 text-lg mb-2 line-clamp-2"><?php echo htmlspecialchars($seriesName); ?></h3>
                                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                                <!-- description removed per request -->
                                            </p>
                                            <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                                                <span class="flex items-center"><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($episodes[0]['video']['author']); ?></span>
                                                <span class="flex items-center"><i class="fas fa-eye mr-1"></i>
                                                    <?php $totalViews = 0; foreach ($episodes as $episode) { $totalViews += ($episode['video']['view_count'] ?? 0); } echo number_format($totalViews); ?> vues
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between mb-4">
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium"><?php echo ucfirst($episodes[0]['video']['category']); ?></span>
                                            </div>
                                            <div class="w-full bg-blue-600 text-white text-center py-2 px-4 rounded hover:bg-blue-700 transition-colors text-sm font-medium">
                                                <i class="fas fa-eye mr-2"></i>
                                                Voir plus
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <?php 
                            // 2) Regrouper les autres vidéos par dossier
                            ?>
                            
                            <!-- 4 cartes "Incoming" -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6 mb-6">
                                <!-- Carte 1: Formation Avancée -->
                                <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-all duration-200">
                                    <div class="animate-pulse mb-4">
                                        <i class="fas fa-graduation-cap text-5xl text-blue-400"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Formation Avancée</h3>
                                    <p class="text-sm text-gray-500 mb-4">Contenu en préparation</p>
                                    <div class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-xs font-medium inline-block">
                                        <i class="fas fa-clock mr-1"></i>Incoming
                                    </div>
                                </div>

                                <!-- Carte 2: Certification -->
                                <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-green-400 transition-all duration-200">
                                    <div class="animate-pulse mb-4">
                                        <i class="fas fa-certificate text-5xl text-green-400"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Certification</h3>
                                    <p class="text-sm text-gray-500 mb-4">Programme en développement</p>
                                    <div class="bg-green-50 text-green-700 px-3 py-1 rounded-full text-xs font-medium inline-block">
                                        <i class="fas fa-clock mr-1"></i>Incoming
                                    </div>
                                </div>

                                <!-- Carte 3: Projet Pratique -->
                                <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-purple-400 transition-all duration-200">
                                    <div class="animate-pulse mb-4">
                                        <i class="fas fa-project-diagram text-5xl text-purple-400"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Projet Pratique</h3>
                                    <p class="text-sm text-gray-500 mb-4">Exercices en cours</p>
                                    <div class="bg-purple-50 text-purple-700 px-3 py-1 rounded-full text-xs font-medium inline-block">
                                        <i class="fas fa-clock mr-1"></i>Incoming
                                    </div>
                                </div>

                                <!-- Carte 4: Workshop -->
                                <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-orange-400 transition-all duration-200">
                                    <div class="animate-pulse mb-4">
                                        <i class="fas fa-users text-5xl text-orange-400"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Workshop</h3>
                                    <p class="text-sm text-gray-500 mb-4">Session interactive</p>
                                    <div class="bg-orange-50 text-orange-700 px-3 py-1 rounded-full text-xs font-medium inline-block">
                                        <i class="fas fa-clock mr-1"></i>Incoming
                                    </div>
                                </div>
                            </div>
                            
                        <?php else: ?>
                            <!-- Affichage normal pour les autres types -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
                                <?php foreach ($typeResources as $resource): ?>
                                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-all duration-200 hover:border-gray-300">
                                    <div class="p-5">
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="w-12 h-12 bg-gradient-to-r <?php echo $typeColors[$typeKey] ?? 'from-gray-500 to-gray-600'; ?> rounded-lg flex items-center justify-center shadow-sm">
                                                <i class="fas fa-<?php echo getResourceTypeIcon($resource['type']); ?> text-white text-lg"></i>
                                            </div>
                                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700 border">
                                                <?php echo ucfirst($resource['category']); ?>
                                            </span>
                                        </div>
                                        
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                                            <?php echo htmlspecialchars($resource['title']); ?>
                                        </h3>
                                        
                                        <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                                            <?php echo htmlspecialchars(substr($resource['description'], 0, 120)) . '...'; ?>
                                        </p>
                                        
                                        <div class="space-y-2 mb-4">
                                            <div class="flex items-center justify-between text-xs text-gray-500">
                                                <span><i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($resource['author']); ?></span>
                                                <span><i class="fas fa-eye mr-2"></i><?php echo $resource['view_count']; ?> vues</span>
                                            </div>
                                            <div class="flex items-center justify-between text-xs text-gray-500">
                                                <span><i class="fas fa-calendar mr-2"></i><?php echo date('d/m/Y', strtotime($resource['upload_date'])); ?></span>
                                                <span><i class="fas fa-weight-hanging mr-2"></i><?php echo $resource['file_size']; ?></span>
                                            </div>
                                        </div>
                                        
                                        <a href="resource.php?id=<?php echo $resource['id']; ?>" 
                                           class="block w-full bg-gradient-to-r <?php echo $typeColors[$typeKey] ?? 'from-gray-500 to-gray-600'; ?> text-white text-center py-2.5 px-4 rounded-lg hover:shadow-md transition-all duration-200 text-sm font-medium">
                                            <i class="fas fa-<?php echo $resource['type'] === 'video' ? 'play' : ($resource['type'] === 'code' ? 'code' : 'download'); ?> mr-2"></i>
                                            <?php 
                                            switch($resource['type']) {
                                                case 'video': echo 'Regarder'; break;
                                                case 'code': echo 'Voir le code'; break;
                                                case 'dataset': echo 'Télécharger'; break;
                                                default: echo 'Consulter'; break;
                                            }
                                            ?>
                                        </a>
                                        <?php if ($resource['type'] === 'code' && !empty($resource['file_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($resource['file_url']); ?>" target="_blank" download 
                                           class="mt-2 block w-full bg-white text-gray-800 text-center py-2.5 px-4 rounded-lg border border-gray-200 hover:bg-gray-50 transition-all duration-200 text-sm font-medium">
                                            <i class="fas fa-download mr-2"></i>Télécharger
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                            <script>
                            (function(){
                                // Lazy init vignettes via IntersectionObserver
                                const initThumb = function(img){
                                    const setFallback = function(){
                                        const fb1 = img.getAttribute('data-fallback1');
                                        const fb2 = img.getAttribute('data-fallback2');
                                        const test = new Image();
                                        test.onload = function(){ img.src = fb1; img.style.opacity = 1; img.dataset.ready = '1'; };
                                        test.onerror = function(){ if (fb2) { img.src = fb2; img.style.opacity = 1; img.dataset.ready = '1'; } else { img.style.display = 'none'; } };
                                        test.src = fb1;
                                    };
                                    img.onerror = function(){ setFallback(); };
                                    img.onload = function(){ if (!img.dataset.ready) { img.style.opacity = 1; img.dataset.ready = '1'; } };
                                    const id = img.getAttribute('data-resource-id');
                                    if (!id) return;
                                    const video = document.createElement('video');
                                    video.muted = true;
                                    video.preload = 'metadata';
                                    video.src = 'video_stream.php?id=' + encodeURIComponent(id);
                                    const capture = function(){
                                        try {
                                            const canvas = document.createElement('canvas');
                                            const w = video.videoWidth || 640;
                                            const h = video.videoHeight || 360;
                                            canvas.width = w;
                                            canvas.height = h;
                                            const ctx = canvas.getContext('2d');
                                            ctx.drawImage(video, 0, 0, w, h);
                                            const url = canvas.toDataURL('image/jpeg', 0.7);
                                            if (url && url.length > 32 && (!img.complete || img.naturalWidth === 0)) {
                                                img.src = url;
                                                img.style.opacity = 1;
                                                img.dataset.ready = '1';
                                            }
                                        } catch(e) {
                                            // fallback: laisser le gradient
                                        }
                                    };
                                    // Fallback de sécurité: si rien après 2s, masquer l'image pour laisser le gradient
                                    const timeoutId = setTimeout(function(){
                                        if (!img.dataset.ready) {
                                            img.style.display = 'none';
                                        }
                                    }, 2000);
                                    video.addEventListener('error', function(){
                                        img.style.display = 'none';
                                        clearTimeout(timeoutId);
                                    }, { once: true });
                                    video.addEventListener('loadedmetadata', function(){
                                        try { video.currentTime = 0.1; } catch(e) { /* ignore */ }
                                    }, { once: true });
                                    video.addEventListener('seeked', function(){ capture(); clearTimeout(timeoutId); }, { once: true });
                                };
                                const thumbs = document.querySelectorAll('.js-thumb');
                                if ('IntersectionObserver' in window) {
                                    const observer = new IntersectionObserver((entries)=>{
                                        entries.forEach(e=>{
                                            if (e.isIntersecting) {
                                                observer.unobserve(e.target);
                                                initThumb(e.target);
                                            }
                                        });
                                    }, { rootMargin: '200px' });
                                    thumbs.forEach(img=>observer.observe(img));
                                } else {
                                    thumbs.forEach(img=>initThumb(img));
                                }
                            })();
                            </script>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    </div>

        </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Fonction pour basculer l'affichage d'une catégorie
        function toggleCategory(typeKey) {
            const content = document.getElementById('content-' + typeKey);
            const icon = document.getElementById('icon-' + typeKey);
            
            if (content.style.display === 'none' || content.style.display === '') {
                // Afficher le contenu
                content.style.display = 'block';
                content.style.maxHeight = content.scrollHeight + 'px';
                icon.style.transform = 'rotate(0deg)';
                icon.className = 'fas fa-chevron-down text-gray-600 transition-transform duration-200';
            } else {
                // Masquer le contenu
                content.style.maxHeight = '0px';
                setTimeout(() => {
                    content.style.display = 'none';
                }, 300);
                icon.style.transform = 'rotate(-90deg)';
                icon.className = 'fas fa-chevron-right text-gray-600 transition-transform duration-200';
            }
        }

        // Initialiser l'état des catégories (toutes ouvertes par défaut)
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser toutes les catégories comme ouvertes
            const categories = document.querySelectorAll('[id^="content-"]');
            categories.forEach(category => {
                category.style.display = 'block';
                category.style.maxHeight = 'none';
            });
        });
    </script>
</body>
</html>

