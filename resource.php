<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$user = null;

if ($isLoggedIn) {
    $user = getUserById($_SESSION['user_id']);
}

// Récupérer l'ID de la ressource
$resourceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$resourceId) {
    header('Location: resources.php');
    exit();
}

// Enregistrer une vue si l'utilisateur est connecté
function recordResourceView($resourceId, $userId) {
    if (!$userId) return;
    
    $pdo = getDB();
    
    // Vérifier si l'utilisateur a déjà vu cette ressource aujourd'hui
    $stmt = $pdo->prepare("
        SELECT id FROM resource_views 
        WHERE resource_id = ? AND user_id = ? AND DATE(viewed_at) = CURDATE()
    ");
    $stmt->execute([$resourceId, $userId]);
    
    if (!$stmt->fetch()) {
        // Enregistrer la nouvelle vue
        $stmt = $pdo->prepare("
            INSERT INTO resource_views (resource_id, user_id, viewed_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$resourceId, $userId]);
    }
}

// Enregistrer la vue si l'utilisateur est connecté
if ($isLoggedIn) {
    recordResourceView($resourceId, $user['id']);
}

// Récupérer la ressource avec détails
function getResourceWithDetails($resourceId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT r.*, 
               COUNT(rv.id) as view_count,
               COUNT(DISTINCT rv.user_id) as unique_viewers
        FROM resources r
        LEFT JOIN resource_views rv ON r.id = rv.resource_id
        WHERE r.id = ? AND r.is_active = TRUE
        GROUP BY r.id
    ");
    $stmt->execute([$resourceId]);
    return $stmt->fetch();
}

// Récupérer les ressources similaires
function getSimilarResources($resourceId, $category, $limit = 4) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT r.*, COUNT(rv.id) as view_count
        FROM resources r
        LEFT JOIN resource_views rv ON r.id = rv.resource_id
        WHERE r.id != ? AND r.category = ? AND r.is_active = TRUE
        GROUP BY r.id
        ORDER BY r.upload_date DESC
        LIMIT ?
    ");
    $stmt->execute([$resourceId, $category, $limit]);
    return $stmt->fetchAll();
}

$resource = getResourceWithDetails($resourceId);

if (!$resource) {
    header('Location: resources.php');
    exit();
}

// Marquer comme vue si l'utilisateur est connecté
if ($isLoggedIn) {
    markResourceAsViewed($user['id'], $resourceId);
}

$similarResources = getSimilarResources($resourceId, $resource['category']);

// Récupérer les vidéos de la série pour la table des matières
function getSeriesEpisodes($resourceId) {
    $pdo = getDB();
    
    // Récupérer la série de la vidéo actuelle
    $stmt = $pdo->prepare("
        SELECT file_url FROM resources WHERE id = ?
    ");
    $stmt->execute([$resourceId]);
    $currentResource = $stmt->fetch();
    
    if (!$currentResource) return [];
    
    $fileUrl = $currentResource['file_url'];
    
    // Déterminer la série selon le chemin
    if (strpos($fileUrl, 'python pour tous') !== false) {
        // Récupérer toutes les vidéos Python pour tous
        $stmt = $pdo->prepare("
            SELECT id, title, file_url, duration
            FROM resources 
            WHERE file_url LIKE '%python pour tous%' 
            AND type = 'video' 
            AND is_active = 1
            ORDER BY title ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    return [];
}

$seriesEpisodes = getSeriesEpisodes($resourceId);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($resource['title']); ?> - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html { scroll-behavior: smooth; }
        .anchor-offset { scroll-margin-top: 5rem; }
        .sidebar-scroll::-webkit-scrollbar { width: 8px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: #f7fafc; border-radius: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: #a0aec0; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <div class="flex">
        <!-- Sidebar (desktop) -->
        <div class="hidden md:block w-64 bg-white shadow-md border-r border-gray-200 rounded-r-xl absolute left-0 top-16 h-[calc(100vh-4rem)] z-30">
            <div class="px-3 pt-3 pb-3 h-full overflow-y-hidden hover:overflow-y-auto overscroll-contain pr-2 sidebar-scroll" style="scrollbar-width: thin; scrollbar-color: #cbd5e0 #f7fafc;">
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

            <!-- Statistiques rapides -->
            <div class="mt-3 pt-3 border-t border-gray-200">
                <h4 class="text-xs font-medium text-gray-900 mb-3">Mes Statistiques</h4>
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Cours suivis</span>
                        <span class="text-xs font-medium text-gray-900"><?php echo getEnrolledCoursesCount($user['id']); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Heures d'étude</span>
                        <span class="text-xs font-medium text-gray-900"><?php echo getStudyHours($user['id']); ?>h</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Ressources vues</span>
                        <span class="text-xs font-medium text-gray-900"><?php echo getCompletedResourcesCount($user['id']); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Certifications</span>
                        <span class="text-xs font-medium text-gray-900"><?php echo count(getUserCertifications($user['id'])); ?></span>
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
        </div>

        <!-- Contenu principal -->
        <main class="flex-1 ml-64 mt-16 p-4 md:p-8 pb-24">
        <div class="px-4 py-6 sm:px-0">
            <!-- Fil d'Ariane -->
            <nav class="flex mb-8" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="index.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                            <i class="fas fa-home mr-2"></i>
                            Accueil
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <a href="resources.php" class="text-sm font-medium text-gray-700 hover:text-blue-600">
                                Ressources
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-sm font-medium text-gray-500"><?php echo htmlspecialchars($resource['title']); ?></span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Détails de la ressource -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <!-- En-tête de la ressource -->
                <div class="p-8 border-b border-gray-200">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-<?php echo getResourceTypeIcon($resource['type']); ?> text-white text-2xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                                    <?php echo htmlspecialchars($resource['title']); ?>
                                </h1>
                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <span class="flex items-center">
                                        <i class="fas fa-user mr-1"></i>
                                        <?php echo htmlspecialchars($resource['author']); ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?php echo date('d/m/Y', strtotime($resource['upload_date'])); ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-eye mr-1"></i>
                                        <?php echo $resource['view_count']; ?> vues
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col items-end space-y-2">
                            <span class="px-3 py-1 text-sm font-medium rounded-full <?php echo getCategoryColor($resource['category']); ?>">
                                <?php echo ucfirst($resource['type']); ?>
                            </span>
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-gray-100 text-gray-800">
                                <?php echo ucfirst($resource['category']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Contenu de la ressource -->
                <div class="p-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Colonne principale -->
                        <div class="lg:col-span-2">
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold text-gray-900 mb-4">Description</h2>
                                <p class="text-gray-700 leading-relaxed">
                                    <?php echo nl2br(htmlspecialchars($resource['description'])); ?>
                                </p>
                            </div>

                            <!-- Lecteur vidéo ou aperçu de la ressource -->
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                                    <?php echo $resource['type'] === 'video' ? 'Lecteur vidéo' : 'Aperçu'; ?>
                                </h2>
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <?php if ($resource['type'] === 'video'): ?>
                                        <!-- Lecteur vidéo sécurisé -->
                                        <div class="relative bg-black rounded-lg overflow-hidden shadow-lg">
                                            <video 
                                                id="videoPlayer" 
                                                class="w-full h-auto max-h-96" 
                                                controls 
                                                preload="metadata"
                                                poster=""
                                                data-resource-id="<?php echo $resource['id']; ?>"
                                                oncontextmenu="return false;"
                                                onselectstart="return false;"
                                                ondragstart="return false;"
                                            >
                                                <source src="video_stream.php?id=<?php echo $resource['id']; ?>" type="video/mp4">
                                                <p>Votre navigateur ne supporte pas la lecture vidéo HTML5.</p>
                                            </video>
                                            
                                            <!-- Overlay de protection -->
                                            <div id="videoOverlay" class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center pointer-events-none">
                                                <div class="text-white text-center">
                                                    <i class="fas fa-play-circle text-6xl mb-2 opacity-80"></i>
                                                    <p class="text-sm opacity-90">Cliquez pour lire</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Contrôles personnalisés -->
                                        <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
                                            <div class="flex items-center space-x-4">
                                                <span><i class="fas fa-eye mr-1"></i> <?php echo $resource['view_count']; ?> vues</span>
                                                <span><i class="fas fa-clock mr-1"></i> <span id="videoDuration">--:--</span></span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <button id="fullscreenBtn" class="p-2 hover:bg-gray-200 rounded-lg transition-colors" title="Plein écran">
                                                    <i class="fas fa-expand"></i>
                                                </button>
                                                <button id="speedBtn" class="p-2 hover:bg-gray-200 rounded-lg transition-colors" title="Vitesse de lecture">
                                                    <i class="fas fa-tachometer-alt"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Menu de vitesse -->
                                        <div id="speedMenu" class="hidden absolute bg-white border border-gray-200 rounded-lg shadow-lg p-2 mt-1 z-10">
                                            <button class="speed-option block w-full text-left px-3 py-2 hover:bg-gray-100 rounded text-sm" data-speed="0.5">0.5x</button>
                                            <button class="speed-option block w-full text-left px-3 py-2 hover:bg-gray-100 rounded text-sm" data-speed="0.75">0.75x</button>
                                            <button class="speed-option block w-full text-left px-3 py-2 hover:bg-gray-100 rounded text-sm font-semibold" data-speed="1">1x (Normal)</button>
                                            <button class="speed-option block w-full text-left px-3 py-2 hover:bg-gray-100 rounded text-sm" data-speed="1.25">1.25x</button>
                                            <button class="speed-option block w-full text-left px-3 py-2 hover:bg-gray-100 rounded text-sm" data-speed="1.5">1.5x</button>
                                            <button class="speed-option block w-full text-left px-3 py-2 hover:bg-gray-100 rounded text-sm" data-speed="2">2x</button>
                                        </div>
                                        
                                    <?php elseif ($resource['type'] === 'code'): ?>
                                        <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm">
                                            <pre><code>// Exemple de code
function example() {
    console.log("Hello World!");
}</code></pre>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center justify-center h-32 bg-gray-200 rounded-lg">
                                            <i class="fas fa-file-alt text-4xl text-gray-400"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                                <?php if ($resource['file_url'] && $resource['type'] !== 'video'): ?>
                                <a href="<?php echo htmlspecialchars($resource['file_url']); ?>" 
                                   class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors text-center font-medium">
                                    <i class="fas fa-download mr-2"></i>Télécharger
                                </a>
                                <?php endif; ?>
                                
                                <button class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors font-medium">
                                    <i class="fas fa-share mr-2"></i>Partager
                                </button>
                                
                                <?php if ($isLoggedIn): ?>
                                <button class="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors font-medium">
                                    <i class="fas fa-bookmark mr-2"></i>Ajouter aux favoris
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="lg:col-span-1">
                            <!-- Informations techniques -->
                            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Type</span>
                                        <span class="font-medium"><?php echo ucfirst($resource['type']); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Catégorie</span>
                                        <span class="font-medium"><?php echo ucfirst($resource['category']); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Taille</span>
                                        <span class="font-medium"><?php echo $resource['file_size']; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Téléchargements</span>
                                        <span class="font-medium"><?php echo $resource['downloads']; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Vues uniques</span>
                                        <span class="font-medium"><?php echo $resource['unique_viewers']; ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tags -->
                            <?php if ($resource['tags']): ?>
                            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Tags</h3>
                                <div class="flex flex-wrap gap-2">
                                    <?php 
                                    $tags = json_decode($resource['tags'], true);
                                    if ($tags):
                                        foreach ($tags as $tag): 
                                    ?>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                                        <?php echo htmlspecialchars($tag); ?>
                                    </span>
                                    <?php 
                                        endforeach;
                                    endif;
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Statistiques -->
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistiques</h3>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">Popularité</span>
                                        <div class="flex items-center">
                                            <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo min(($resource['view_count'] / 100) * 100, 100); ?>%"></div>
                                            </div>
                                            <span class="text-sm text-gray-600"><?php echo $resource['view_count']; ?></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">Note</span>
                                        <div class="flex items-center">
                                            <div class="flex text-yellow-400">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="far fa-star"></i>
                                            </div>
                                            <span class="ml-2 text-sm text-gray-600">4.0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table des matières -->
            <?php if (!empty($seriesEpisodes)): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Table des matières</h2>
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="space-y-3">
                            <?php foreach ($seriesEpisodes as $index => $episode): ?>
                            <div class="flex items-center space-x-4 p-3 rounded-lg <?php echo $episode['id'] == $resourceId ? 'bg-blue-50 border border-blue-200' : 'hover:bg-gray-50'; ?>">
                                <div class="w-8 h-8 <?php echo $episode['id'] == $resourceId ? 'bg-blue-600' : 'bg-gray-400'; ?> text-white rounded-full flex items-center justify-center text-sm font-semibold">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-medium text-gray-900 <?php echo $episode['id'] == $resourceId ? 'text-blue-700' : ''; ?>">
                                        <?php echo htmlspecialchars($episode['title']); ?>
                                    </h3>
                                    <?php if (!empty($episode['duration'])): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo $episode['duration']; ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($episode['id'] == $resourceId): ?>
                                <div class="flex items-center text-blue-600">
                                    <i class="fas fa-play-circle mr-1"></i>
                                    <span class="text-sm font-medium">En cours</span>
                                </div>
                                <?php else: ?>
                                <a href="resource.php?id=<?php echo $episode['id']; ?>" 
                                   class="text-gray-400 hover:text-blue-600 transition-colors">
                                    <i class="fas fa-play"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ressources similaires -->
            <?php if (!empty($similarResources)): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Ressources similaires</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($similarResources as $similar): ?>
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-200">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-<?php echo getResourceTypeIcon($similar['type']); ?> text-white"></i>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getCategoryColor($similar['category']); ?>">
                                    <?php echo ucfirst($similar['type']); ?>
                                </span>
                            </div>
                            
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <a href="resource.php?id=<?php echo $similar['id']; ?>" class="hover:text-blue-600">
                                    <?php echo htmlspecialchars($similar['title']); ?>
                                </a>
                            </h3>
                            
                            <p class="text-gray-600 text-sm mb-4">
                                <?php echo htmlspecialchars(substr($similar['description'], 0, 80)) . '...'; ?>
                            </p>
                            
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <span><i class="fas fa-eye mr-1"></i><?php echo $similar['view_count']; ?></span>
                                <span><i class="fas fa-calendar mr-1"></i><?php echo date('d/m/Y', strtotime($similar['upload_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript pour le lecteur vidéo -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const video = document.getElementById('videoPlayer');
        const overlay = document.getElementById('videoOverlay');
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        const speedBtn = document.getElementById('speedBtn');
        const speedMenu = document.getElementById('speedMenu');
        const durationSpan = document.getElementById('videoDuration');
        
        if (video) {
            // Masquer l'overlay au premier clic
            video.addEventListener('click', function() {
                overlay.style.display = 'none';
            });
            
            // Afficher la durée de la vidéo
            video.addEventListener('loadedmetadata', function() {
                const duration = video.duration;
                const minutes = Math.floor(duration / 60);
                const seconds = Math.floor(duration % 60);
                durationSpan.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            });
            
            // Contrôle plein écran
            fullscreenBtn.addEventListener('click', function() {
                if (video.requestFullscreen) {
                    video.requestFullscreen();
                } else if (video.webkitRequestFullscreen) {
                    video.webkitRequestFullscreen();
                } else if (video.msRequestFullscreen) {
                    video.msRequestFullscreen();
                }
            });
            
            // Contrôle de vitesse
            speedBtn.addEventListener('click', function() {
                speedMenu.classList.toggle('hidden');
            });
            
            // Fermer le menu de vitesse en cliquant ailleurs
            document.addEventListener('click', function(e) {
                if (!speedBtn.contains(e.target) && !speedMenu.contains(e.target)) {
                    speedMenu.classList.add('hidden');
                }
            });
            
            // Changer la vitesse de lecture
            document.querySelectorAll('.speed-option').forEach(option => {
                option.addEventListener('click', function() {
                    const speed = parseFloat(this.dataset.speed);
                    video.playbackRate = speed;
                    
                    // Mettre à jour l'affichage
                    document.querySelectorAll('.speed-option').forEach(opt => {
                        opt.classList.remove('font-semibold');
                    });
                    this.classList.add('font-semibold');
                    
                    speedMenu.classList.add('hidden');
                });
            });
            
            // Protection contre le clic droit et la sélection
            video.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
            
            // Désactiver les raccourcis clavier pour télécharger
            video.addEventListener('keydown', function(e) {
                // Bloquer Ctrl+S, Ctrl+Shift+S, F12, etc.
                if ((e.ctrlKey && e.key === 's') || 
                    (e.ctrlKey && e.shiftKey && e.key === 'S') ||
                    e.key === 'F12' ||
                    (e.ctrlKey && e.shiftKey && e.key === 'I')) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Désactiver le glisser-déposer
            video.addEventListener('dragstart', function(e) {
                e.preventDefault();
                return false;
            });
            
            // Empêcher l'ouverture du menu contextuel sur l'overlay
            overlay.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
        }
    });
    </script>
    </div>
</body>
</html>
