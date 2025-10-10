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
                    Découvrez notre bibliothèque complète de ressources en Intelligence Artificielle, systèmes embarqués et génie logiciel
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
                            }
                            
                            // Ajouter une série pour le dossier devperso (même logique d'affichage qu'une série)
                            $devPersoEpisodes = [];
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/devperso/') !== false || strpos($normalized, 'videos/devperso/') !== false) {
                                    $devPersoEpisodes[] = [
                                        'episode' => $idx + 1,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                    unset($nonSeries[$idx]);
                                }
                            }
                            if (!empty($devPersoEpisodes)) {
                                // Réindexer et trier par episode
                                $devPersoEpisodes = array_values($devPersoEpisodes);
                                usort($devPersoEpisodes, function($a, $b) { return $a['episode'] - $b['episode']; });
                                $videoSeries['Développement personnel'] = $devPersoEpisodes;
                            }

                            // Ajouter une série pour le dossier cybersecu
                            $cyberSecEpisodes = [];
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/cybersecu/') !== false || strpos($normalized, 'videos/cybersecu/') !== false) {
                                    $cyberSecEpisodes[] = [
                                        'episode' => $idx + 1,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                    unset($nonSeries[$idx]);
                                }
                            }
                            if (!empty($cyberSecEpisodes)) {
                                $cyberSecEpisodes = array_values($cyberSecEpisodes);
                                usort($cyberSecEpisodes, function($a, $b) { return $a['episode'] - $b['episode']; });
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

                            // Ajouter une série pour le dossier droit
                            $droitEpisodes = [];
                            foreach ($nonSeries as $idx => $video) {
                                $path = $video['file_url'] ?? '';
                                $normalized = str_replace('\\', '/', $path);
                                if (strpos($normalized, '/videos/droit/') !== false || strpos($normalized, 'videos/droit/') !== false) {
                                    $droitEpisodes[] = [
                                        'episode' => $idx + 1,
                                        'episodeTitle' => $video['title'] ?? 'Épisode',
                                        'video' => $video
                                    ];
                                    unset($nonSeries[$idx]);
                                }
                            }
                            if (!empty($droitEpisodes)) {
                                $droitEpisodes = array_values($droitEpisodes);
                                usort($droitEpisodes, function($a, $b) { return $a['episode'] - $b['episode']; });
                                $videoSeries['Droit'] = $droitEpisodes;
                            }
                            foreach ($videoSeries as $seriesName => $episodes) {
                                usort($videoSeries[$seriesName], function($a, $b) { return $a['episode'] - $b['episode']; });
                            }
                            ?>
                            <?php if (!empty($videoSeries)): ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-6">
                                <?php foreach ($videoSeries as $seriesName => $episodes): ?>
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-200 cursor-pointer" 
                                     onclick="window.location.href='mediatheque.php?series=<?php echo urlencode($seriesName); ?>&video_id=<?php echo $episodes[0]['video']['id']; ?>'">
                                    <div class="relative h-48 bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center">
                                        <div class="text-center text-white">
                                            <i class="fas fa-play-circle text-5xl mb-3"></i>
                                            <p class="text-sm font-medium"><?php echo count($episodes); ?> épisode(s)</p>
                                        </div>
                                        <div class="absolute top-3 right-3 bg-black bg-opacity-80 text-white text-xs px-2 py-1 rounded font-medium">
                                            <?php echo count($episodes) * 15; ?> min
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-semibold text-gray-900 text-lg mb-2 line-clamp-2"><?php echo htmlspecialchars($seriesName); ?></h3>
                                        <p class="text-sm text-gray-600 mb-3 line-clamp-2">Série complète avec <?php echo count($episodes); ?> épisode(s) pour apprendre progressivement.</p>
                                        <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                                            <span class="flex items-center"><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($episodes[0]['video']['author']); ?></span>
                                            <span class="flex items-center"><i class="fas fa-eye mr-1"></i>
                                                <?php $totalViews = 0; foreach ($episodes as $episode) { $totalViews += ($episode['video']['view_count'] ?? 0); } echo number_format($totalViews); ?> vues
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between mb-4">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium"><?php echo ucfirst($episodes[0]['video']['category']); ?></span>
                                            <span class="text-xs text-gray-500 font-medium">Débutant</span>
                                        </div>
                                        <div class="w-full bg-blue-600 text-white text-center py-2 px-4 rounded hover:bg-blue-700 transition-colors text-sm font-medium">
                                            <i class="fas fa-play mr-2"></i>Commencer le cours
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <?php 
                            // 2) Regrouper les autres vidéos par dossier
                            $videosByFolder = [];
                            foreach ($nonSeries as $video) {
                                $path = $video['file_url'] ?? '';
                                $folderName = 'Autres';
                                if (!empty($path)) {
                                    $normalized = str_replace('\\', '/', $path);
                                    $parts = explode('/', $normalized);
                                    $idx = array_search('videos', $parts);
                                    if ($idx !== false && isset($parts[$idx + 1])) {
                                        $folderName = $parts[$idx + 1];
                                    } else {
                                        $dirParts = explode('/', trim(dirname($normalized)));
                                        $last = end($dirParts);
                                        if (!empty($last) && $last !== '.' && $last !== '/') {
                                            $folderName = $last;
                                        }
                                    }
                                }
                                $videosByFolder[$folderName][] = $video;
                            }
                            ?>
                            <?php foreach ($videosByFolder as $folder => $videos): ?>
                                <div class="mb-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <i class="fas fa-folder-open text-gray-500 mr-2"></i><?php echo htmlspecialchars(ucfirst($folder)); ?>
                                        </h3>
                                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700 border"><?php echo count($videos); ?> vidéo(s)</span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                        <?php foreach ($videos as $resource): ?>
                                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-200">
                                            <div class="relative h-48 bg-gradient-to-br from-gray-500 to-gray-700 flex items-center justify-center">
                                                <div class="text-center text-white">
                                                    <i class="fas fa-video text-4xl mb-2"></i>
                                                    <p class="text-sm font-medium">Vidéo</p>
                                                </div>
                                            </div>
                                            <div class="p-4">
                                                <h3 class="font-semibold text-gray-900 text-lg mb-2 line-clamp-2"><?php echo htmlspecialchars($resource['title']); ?></h3>
                                                <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?php echo htmlspecialchars(substr($resource['description'], 0, 100)) . '...'; ?></p>
                                                <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                                                    <span class="flex items-center"><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($resource['author']); ?></span>
                                                    <span class="flex items-center"><i class="fas fa-eye mr-1"></i><?php echo isset($resource['view_count']) ? number_format($resource['view_count']) : '0'; ?> vues</span>
                                                </div>
                                                <a href="resource.php?id=<?php echo $resource['id']; ?>" class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded hover:bg-blue-700 transition-colors text-sm font-medium">
                                                    <i class="fas fa-play mr-2"></i>Regarder
                                                </a>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
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

