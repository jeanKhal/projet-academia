<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/logger.php';

// Encodage et locale en français
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'fr_FR.UTF-8', 'fr_FR', 'fr');

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$user = null;

if ($isLoggedIn) {
    $user = getUserById($_SESSION['user_id']);
    // Logger l'accès à la médiathèque
    logUserActivity($user['id'], $user['full_name'], 'Accès à la médiathèque');
} else {
    // Créer un utilisateur par défaut pour l'accès sans connexion
    $user = ['id' => 0, 'full_name' => 'Visiteur', 'level' => 'Débutant'];
    // Logger l'accès visiteur
    logUserActivity(0, 'Visiteur', 'Accès à la médiathèque (sans connexion)');
}

// Récupérer le nom de la série depuis l'URL
$seriesName = isset($_GET['series']) ? $_GET['series'] : '';

if (empty($seriesName)) {
    header('Location: resources.php');
    exit();
}

// Récupérer les vidéos de la série
$pdo = getDB();

// Construire les épisodes selon la série demandée
$episodes = [];

// Mapping des séries vers leurs chemins de dossiers
$seriesFolderMap = [
    'Développement personnel' => ['videos/devperso/'],
    'Cybersécurité' => ['videos/cybersecu/'],
    'Design' => ['videos/Design/', 'videos/design/'],
    'Droit' => ['videos/droit/'],
    'Économie & Management' => ['videos/economie/'],
    'Santé et Médecine' => ['videos/science-medicale/'],
    'Environnement' => ['videos/environnement/'],
    'Chimie' => ['videos/chimie/'],
    'Langues' => ['videos/langues/'],
    'Droit des contrats' => ['videos/droit-contrats/'],
    'Marketing' => ['videos/marketing/'],
    'Leadership' => ['videos/leadership/'],
    'Communication' => ['videos/communication/'],
    'Informatique' => ['videos/informatique/']
];

if ($seriesName === 'Python pour tous') {
    // Vidéos Python uniquement du dossier spécifique
    $stmt = $pdo->prepare("
        SELECT r.*, COUNT(rv.id) as view_count
        FROM resources r
        LEFT JOIN resource_views rv ON r.id = rv.resource_id
        WHERE r.type = 'video'
          AND r.file_url LIKE '%python pour tous%'
          AND r.is_active = 1
        GROUP BY r.id
        ORDER BY r.title ASC
    ");
    $stmt->execute();
    $videos = $stmt->fetchAll();
    foreach ($videos as $video) {
        if (preg_match('/^(\d+)\.\s*(.+)/', $video['title'], $matches)) {
            $episodeNumber = intval($matches[1]);
            $episodes[$episodeNumber] = $video;
        }
    }
} elseif (in_array($seriesName, ['Programmation', 'Réseaux informatiques', 'Bases de données', 'Systèmes d\'exploitation', 'Sécurité informatique'])) {
    // Gestion des séries d'informatique séparées
    $likeClauses = [];
    $params = [];
    
    // Détecter le dossier selon le nom de la série
    $folderPath = '';
    switch ($seriesName) {
        case 'Programmation':
            $folderPath = 'videos/informatique/programmation/';
            break;
        case 'Réseaux informatiques':
            $folderPath = 'videos/informatique/reseaux/';
            break;
        case 'Bases de données':
            $folderPath = 'videos/informatique/bases-donnees/';
            break;
        case 'Systèmes d\'exploitation':
            $folderPath = 'videos/informatique/systemes/';
            break;
        case 'Sécurité informatique':
            $folderPath = 'videos/informatique/securite/';
            break;
    }
    
    if ($folderPath) {
        $likeClauses[] = "r.file_url LIKE ?";
        $params[] = '%' . str_replace('/', '\\', $folderPath) . '%';
        $likeClauses[] = "r.file_url LIKE ?";
        $params[] = '%' . $folderPath . '%';
    }
    
    if (!empty($likeClauses)) {
        $likeSql = implode(' OR ', $likeClauses);
        $sql = "
            SELECT r.*, COUNT(rv.id) as view_count
            FROM resources r
            LEFT JOIN resource_views rv ON r.id = rv.resource_id
            WHERE r.type = 'video' AND r.is_active = 1 AND ($likeSql)
            GROUP BY r.id
            ORDER BY r.title ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $videos = $stmt->fetchAll();
        
        // Trier par numérotation pour les séries d'informatique
        usort($videos, function($a, $b) {
            $titleA = $a['title'] ?? '';
            $titleB = $b['title'] ?? '';
            
            $numA = 999;
            $numB = 999;
            
            if (preg_match('/^(\d+)\./', $titleA, $matches)) {
                $numA = intval($matches[1]);
            }
            
            if (preg_match('/^(\d+)\./', $titleB, $matches)) {
                $numB = intval($matches[1]);
            }
            
            return $numA - $numB;
        });
        
        // Construire les épisodes avec index séquentiel
        $index = 1;
        foreach ($videos as $video) {
            $episodes[$index++] = $video;
        }
    } else {
        $videos = [];
    }
    
} elseif (isset($seriesFolderMap[$seriesName])) {
    // Séries basées sur des dossiers (devperso, cybersecu, design, droit)
    $likeClauses = [];
    $params = [];
    foreach ($seriesFolderMap[$seriesName] as $folder) {
        // Variante web (slashes)
        $likeClauses[] = "r.file_url LIKE ?";
        $params[] = '%' . str_replace('\\\\', '/', $folder) . '%';
        // Variante Windows (backslashes)
        $likeClauses[] = "r.file_url LIKE ?";
        $params[] = '%' . str_replace('/', '\\', $folder) . '%';
    }
    $likeSql = implode(' OR ', $likeClauses);
    $sql = "
        SELECT r.*, COUNT(rv.id) as view_count
        FROM resources r
        LEFT JOIN resource_views rv ON r.id = rv.resource_id
        WHERE r.type = 'video' AND r.is_active = 1 AND ($likeSql)
        GROUP BY r.id
        ORDER BY r.upload_date ASC, r.title ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $videos = $stmt->fetchAll();

    // Construire un index par basename de fichier pour fusionner avec le système de fichiers
    $dbByBasename = [];
    foreach ($videos as $v) {
        $p = $v['file_url'] ?? '';
        $bn = strtolower(basename(str_replace('\\', '/', (string)$p)));
        if (!empty($bn)) { $dbByBasename[$bn] = $v; }
    }

    // Pour Droit: fusion DB + fichiers présents sur disque, avec ordre dédié
    if ($seriesName === 'Droit') {
        $fsVideos = [];
        $dir = __DIR__ . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'droit';
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                if ($file === '.' || $file === '..') continue;
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($ext, ['mp4', 'webm', 'ogg'], true)) continue;
                $bn = strtolower($file);
                if (isset($dbByBasename[$bn])) {
                    $fsVideos[] = $dbByBasename[$bn];
                } else {
                    $title = pathinfo($file, PATHINFO_FILENAME);
                    $fsVideos[] = [
                        'id' => null,
                        'title' => $title,
                        'file_url' => 'videos/droit/' . $file,
                        'view_count' => 0
                    ];
                }
            }
        }
        // Si aucun fichier trouvé, fallback aux vidéos DB
        $sourceVideos = !empty($fsVideos) ? $fsVideos : $videos;

        $featured = null;
        $numericItems = [];
        $others = [];
        foreach ($sourceVideos as $video) {
            $path = $video['file_url'] ?? '';
            $normalized = str_replace('\\', '/', (string)$path);
            $basename = strtolower(basename($normalized));
            if ($basename === strtolower('Les fondements de la propriété intellectuelle et du droit numérique.mp4')) {
                $featured = $video;
                continue;
            }
            $nameNoExt = pathinfo($basename, PATHINFO_FILENAME);
            // Extraire le numéro du début du nom de fichier (ex: "1.Les fondements..." -> 1)
            if (preg_match('/^(\d+)\./', $nameNoExt, $matches)) {
                $numericItems[] = ['num' => (int)$matches[1], 'video' => $video];
            } else {
                $others[] = $video;
            }
        }
        usort($numericItems, function($a, $b) { return $a['num'] <=> $b['num']; });
        $index = 1;
        if (!empty($featured)) {
            $episodes[$index++] = $featured;
        }
        foreach ($numericItems as $item) {
            $episodes[$index++] = $item['video'];
        }
        foreach ($others as $vid) {
            $episodes[$index++] = $vid;
        }
    } else {
        // Tri par numérotation pour toutes les séries
        usort($videos, function($a, $b) use ($seriesName) {
            $titleA = $a['title'] ?? '';
            $titleB = $b['title'] ?? '';
            
            // Extraire les numéros d'épisode
            $numA = 999; // Par défaut à la fin
            $numB = 999;
            
            // Tri spécial pour Cybersécurité (CompTIA Security puis numérotées)
            if ($seriesName === 'Cybersécurité') {
                if (preg_match('/CompTIA Security\(SY0-601\)\s*(\d+)/', $titleA, $matches)) {
                    $numA = intval($matches[1]);
                } elseif (preg_match('/^(\d+)\./', $titleA, $matches)) {
                    $numA = intval($matches[1]);
                }
                
                if (preg_match('/CompTIA Security\(SY0-601\)\s*(\d+)/', $titleB, $matches)) {
                    $numB = intval($matches[1]);
                } elseif (preg_match('/^(\d+)\./', $titleB, $matches)) {
                    $numB = intval($matches[1]);
                }
            } else {
                // Pour toutes les autres séries, tri par numéro d'épisode
                if (preg_match('/^(\d+)\./', $titleA, $matches)) {
                    $numA = intval($matches[1]);
                }
                
                if (preg_match('/^(\d+)\./', $titleB, $matches)) {
                    $numB = intval($matches[1]);
                }
            }
            
            return $numA - $numB;
        });
        
        $index = 1;
        foreach ($videos as $video) {
            $episodes[$index++] = $video;
        }
    }
} else {
    // Série générique: fallback sur les vidéos actives triées par date
    $stmt = $pdo->prepare("
        SELECT r.*, COUNT(rv.id) as view_count
        FROM resources r
        LEFT JOIN resource_views rv ON r.id = rv.resource_id
        WHERE r.type = 'video' AND r.is_active = 1
        GROUP BY r.id
        ORDER BY r.upload_date ASC
    ");
    $stmt->execute();
    $videos = $stmt->fetchAll();
    $index = 1;
    foreach ($videos as $video) {
        $episodes[$index++] = $video;
    }
}

// Obtenir le statut de déverrouillage et la progression pour l'utilisateur connecté
$unlockStatus = [];
$userProgress = [];

if ($isLoggedIn) {
    // Pour les utilisateurs connectés, vérifier la progression réelle
    foreach ($episodes as $episodeNum => $episode) {
        // Vérifier si l'épisode est déverrouillé (tous déverrouillés pour l'instant)
        $unlockStatus[$episodeNum] = true;
        
        // Vérifier la progression de l'utilisateur
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as progress 
            FROM resource_views 
            WHERE user_id = ? AND resource_id = ? AND viewed_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$user['id'], $episode['id']]);
        $progress = $stmt->fetch();
        $userProgress[$episodeNum] = $progress['progress'] > 0;
    }
} else {
    // Pour les utilisateurs non connectés, tout est verrouillé sauf le premier épisode
    foreach ($episodes as $episodeNum => $episode) {
        $unlockStatus[$episodeNum] = ($episodeNum == 1);
        $userProgress[$episodeNum] = false;
    }
}

// Récupérer la vidéo actuelle
$currentVideo = null;
$currentVideoId = isset($_GET['video_id']) ? intval($_GET['video_id']) : null;
$currentVideoFile = isset($_GET['file']) ? $_GET['file'] : null;

if ($currentVideoId) {
    foreach ($episodes as $episode) {
        if ($episode['id'] == $currentVideoId) {
            $currentVideo = $episode;
            break;
        }
    }
}
// Fallback: si un file= est passé (élément sans id depuis le disque)
if (!$currentVideo && $currentVideoFile) {
    foreach ($episodes as $episode) {
        if (!empty($episode['file_url']) && $episode['file_url'] === $currentVideoFile) {
            $currentVideo = $episode;
            break;
        }
    }
}

// Si aucune vidéo spécifique n'est demandée, prendre la première disponible
if (!$currentVideo && !empty($episodes)) {
    $currentVideo = reset($episodes);
}

// Marquer la vidéo comme vue si l'utilisateur est connecté et que la ressource a un ID valide
if ($currentVideo && $isLoggedIn && !empty($currentVideo['id'])) {
    $stmt = $pdo->prepare("
        INSERT INTO resource_views (user_id, resource_id, viewed_at) 
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE viewed_at = NOW()
    ");
    $stmt->execute([$user['id'], $currentVideo['id']]);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($seriesName); ?> - Médiathèque - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        html { scroll-behavior: smooth; }
        /* Responsive 16:9 wrapper for the main video */
        .video-wrapper {
            aspect-ratio: 16 / 9;
            background: #000;
        }
        .video-wrapper video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        /* Masquer complètement les contrôles de téléchargement */
        video::-webkit-media-controls-download-button {
            display: none !important;
        }
        
        video::-webkit-media-controls-overlay-play-button {
            display: none !important;
        }
        
        video::-webkit-media-controls-fullscreen-button {
            display: none !important;
        }
        
        /* Masquer les contrôles de vitesse */
        video::-webkit-media-controls-playback-rate-button {
            display: none !important;
        }
    </style>
</head>
<body class="bg-gray-50 overflow-x-hidden">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- CSS pour la protection des vidéos -->
    <style>
        /* Désactiver la sélection de texte sur la vidéo */
        #mainVideo {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Masquer les contrôles de téléchargement */
        video::-webkit-media-controls-download-button {
            display: none !important;
        }
        
        video::-webkit-media-controls-fullscreen-button {
            display: none !important;
        }
        
        /* Désactiver le clic droit sur la vidéo */
        #mainVideo {
            pointer-events: auto;
        }
        
        /* Scrollbar personnalisée pour la table des matières */
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 3px;
        }
        
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
    </style>

    <!-- Contenu principal -->
    <main class="w-full mt-16 p-3 sm:p-4 md:p-8 pb-32 md:pb-48">
        <!-- En-tête style LinkedIn Learning -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-4 sm:mb-6 bg-white border border-gray-200 rounded-lg p-4 sm:p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-3 sm:mb-2">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-play-circle text-white text-xl"></i>
                                    </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($seriesName); ?></h1>
                            <p class="text-xs sm:text-sm text-gray-600">Cours vidéo interactif</p>
                                    </div>
                                </div>
                    
                    <div class="flex flex-wrap items-center gap-3 sm:space-x-4 text-xs sm:text-sm text-gray-600">
                        <span class="flex items-center">
                            <i class="fas fa-play-circle mr-1"></i>
                            <?php echo count($episodes); ?> vidéos
                        </span>
                        <span class="flex items-center">
                            <i class="fas fa-clock mr-1"></i>
                            ~<?php 
                            $totalDuration = 0;
                            foreach ($episodes as $episode) {
                                $duration = $episode['duration'] ?? null;
                                if ($duration) {
                                    $totalDuration += intval($duration);
                                }
                            }
                            echo $totalDuration > 0 ? $totalDuration . ' minutes' : (count($episodes) * 5) . ' minutes';
                            ?>
                        </span>
                        <span class="flex items-center">
                            <i class="fas fa-users mr-1"></i>
                            <?php echo count($episodes) * 150; ?> étudiants
                        </span>
                                    </div>
                        </div>

                <div class="flex items-center space-x-2">
                    <?php 
                    // Trouver la première vidéo de la série
                    $firstVideo = null;
                    if (!empty($episodes)) {
                        $firstEpisodeNum = min(array_keys($episodes));
                        $firstVideo = $episodes[$firstEpisodeNum];
                    }
                    ?>
                    <?php if ($firstVideo): ?>
                    <button onclick="startSeries(<?php echo $firstVideo['id']; ?>, '<?php echo htmlspecialchars($firstVideo['title']); ?>')" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-play mr-2"></i>Commencer la série
                    </button>
                            <?php else: ?>
                    <button disabled class="bg-gray-400 text-white px-4 py-2 rounded-lg cursor-not-allowed">
                        <i class="fas fa-play mr-2"></i>Commencer la série
                    </button>
                            <?php endif; ?>
                    <button class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-bookmark mr-2"></i>Enregistrer
                    </button>
                    <!-- Mobile TOC toggle -->
                    <button class="md:hidden bg-white border border-gray-200 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors" id="toggleMobileToc">
                        <i class="fas fa-list mr-2"></i>Vidéos
                    </button>
                                    </div>
                                    </div>
                                    </div>
                        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-8">
            <!-- Lecteur vidéo -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <?php if ($currentVideo): ?>
                            <?php 
                    // Utiliser file_url si disponible, sinon fallback à l'ancien mapping Python
                    $videoFiles = [
                        '1. Bienvenue dans Python' => '1.Bienvenue dans Python.mp4',
                        '2. Fichiers exercices' => '2.Fichiers exercices.mp4',
                        '3. Présentations Python' => '3.Presentations Python.mp4',
                        '4. Utilisation de l\'environnement Jupyter' => '4.Utilisation de l\'environnement Jupyter & NoteBook.mp4'
                    ];
                    $videoPath = !empty($currentVideo['file_url']) ? $currentVideo['file_url'] : ('videos/' . ($videoFiles[$currentVideo['title']] ?? $currentVideo['title']));
                    // Si la vidéo n'a pas d'id (issue du disque), s'assurer que le chemin est correct
                    if (empty($currentVideo['id']) && isset($currentVideo['file_url'])) {
                        $videoPath = $currentVideo['file_url'];
                    }
                    ?>
                    <div class="md:grid md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-4">
                        <div class="md:col-span-3 lg:col-span-4 xl:col-span-5">
                    <div class="relative">
                        
                        
                        <div class="video-wrapper">
                        <video 
                            id="mainVideo" 
                                class="w-full h-full" 
                            controls 
                            preload="metadata"
                            poster=""
                            muted="false"
                            volume="1.0"
                            autoplay="false"
                            allowfullscreen
                            controlsList="nodownload nofullscreen noremoteplayback"
                            disablePictureInPicture
                            oncontextmenu="return false;"
                        >
                            <source src="<?php echo htmlspecialchars($videoPath); ?>" type="video/mp4">
                            Votre navigateur ne supporte pas la lecture vidéo.
                        </video>
                        </div>
                        <!-- Titre de la vidéo dans la zone de lecture -->
                        <div class="px-3 sm:px-4 py-2 bg-white border-t">
                            <h3 class="text-sm sm:text-base md:text-lg font-semibold text-gray-900 line-clamp-2"><?php echo htmlspecialchars($currentVideo['title']); ?></h3>
                        </div>
                        
                        <!-- Contrôles personnalisés -->
                        <div class="bg-gray-100 p-3 sm:p-4 border-t">
                            <div class="flex items-center flex-wrap gap-2 sm:gap-3">
                                <!-- Contrôle du volume -->
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm font-medium text-gray-700">Volume:</label>
                                    <input type="range" id="volumeSlider" min="0" max="1" step="0.1" value="1" 
                                           class="w-20 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                                    <span id="volumeValue" class="text-sm text-gray-600">100%</span>
                                    </div>
                                
                                <!-- Contrôle de la qualité -->
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm font-medium text-gray-700">Qualité:</label>
                                    <select id="qualitySelect" class="text-sm border border-gray-300 rounded px-2 py-1">
                                        <option value="auto">Auto</option>
                                        <option value="1080p">1080p</option>
                                        <option value="720p">720p</option>
                                        <option value="480p">480p</option>
                                        <option value="360p">360p</option>
                                    </select>
                                    </div>
                                
                                <!-- Précédent / Suivant -->
                                <div class="flex items-center gap-2 sm:gap-3">
                                    <button id="prevBtn" title="Vidéo précédente (←)" aria-label="Vidéo précédente" class="flex items-center gap-2 bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-full text-xs sm:text-sm shadow-sm hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                        <i class="fas fa-arrow-left"></i>
                                        <span class="hidden xs:inline">Précédent</span>
                                    </button>
                                    <button id="nextBtn" title="Vidéo suivante (→)" aria-label="Vidéo suivante" class="flex items-center gap-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-2 rounded-full text-xs sm:text-sm shadow hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                        <span class="hidden xs:inline">Suivant</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                                <!-- Bouton plein écran -->
                                <button id="fullscreenBtn" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">
                                    <i class="fas fa-expand mr-1"></i>Plein écran
                                </button>
                                </div>
                        </div>
                            </div>
                        <aside class="mt-4 md:mt-0 md:col-span-1 lg:col-span-1 xl:col-span-1"></aside>
                                </div>
                                
                        
                        </div>

                        
                            </div>
                    <?php else: ?>
                    <div class="h-64 md:h-96 bg-gray-900 flex items-center justify-center">
                        <div class="text-center text-white">
                            <i class="fas fa-video text-4xl mb-4"></i>
                            <p class="text-lg">Aucune vidéo disponible</p>
                                    </div>
                                    </div>
                    <?php endif; ?>
                                </div>
                            </div>
                    </div>
                    
            <!-- Table des matières (desktop), Mobile TOC below -->
            <div class="md:col-span-1">
                <div class="sticky top-20 bg-white rounded-lg shadow-md p-4 max-h:[calc(100vh-7rem)] max-h-[calc(100vh-7rem)] overflow-y-auto overscroll-contain sidebar-scroll" style="scrollbar-width: thin; scrollbar-color: #cbd5e0 #f7fafc;">
                    <h2 class="text-lg font-semibold text-gray-900 mb-3">Table des matières</h2>
                    <div class="mb-3">
                        <input id="episodeSearch" type="text" placeholder="Rechercher un épisode..." class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    <div class="space-y-2 pr-1">
                        <!-- Affichage normal des épisodes -->
                        <?php foreach ($episodes as $episodeNum => $episode): ?>
                        <div class="episode-item group p-3 border rounded-lg transition-colors cursor-pointer <?php echo ($currentVideo && ($episode['id'] ?? null) == ($currentVideo['id'] ?? null)) ? 'bg-blue-50 border-blue-300' : 'border-gray-200 hover:bg-gray-50'; ?>"
                             data-title="<?php echo htmlspecialchars(strtolower($episode['title'])); ?>"
                             onclick="<?php echo isset($episode['id']) && $episode['id'] ? 'loadVideo(' . $episode['id'] . ', \' ' . htmlspecialchars($episode['title']) . ' \')' : 'loadVideoByPath(\'' . htmlspecialchars($episode['file_url']) . '\')'; ?>">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 <?php echo !empty($userProgress[$episodeNum]) ? 'bg-green-600' : 'bg-blue-600'; ?> rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-white text-xs font-semibold"><?php echo (int)$episodeNum; ?></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-medium text-gray-900 line-clamp-2 group-hover:text-blue-700"><?php echo htmlspecialchars($episode['title']); ?></h3>
                        </div>
                    </div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Mobile Table des matières (collapsible) -->
        <div id="mobileToc" class="md:hidden mt-4 hidden">
            <div class="bg-white rounded-lg shadow-md p-3">
                <h2 class="text-base font-semibold text-gray-900 mb-2">Vidéos de la série</h2>
                <div class="space-y-2">
                    <!-- Affichage mobile normal des épisodes -->
                    <?php foreach ($episodes as $episodeNum => $episode): ?>
                    <div class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                         onclick="<?php echo isset($episode['id']) && $episode['id'] ? 'loadVideo(' . $episode['id'] . ', \' ' . htmlspecialchars($episode['title']) . ' \')' : 'loadVideoByPath(\'' . htmlspecialchars($episode['file_url']) . '\')'; ?>">
                            <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-semibold"><?php echo (int)$episodeNum; ?></div>
                                <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($episode['title']); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        </main>
    
    

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
                // Données des épisodes pour la navigation automatique
                const episodes = <?php echo json_encode(array_values($episodes)); ?>;
                const currentVideoId = <?php echo $currentVideo && isset($currentVideo['id']) ? ($currentVideo['id'] ?: 'null') : 'null'; ?>;
                const currentVideoFile = <?php echo $currentVideo && !empty($currentVideo['file_url']) ? json_encode($currentVideo['file_url']) : 'null'; ?>;
                
        
        function loadVideo(videoId, title) {
            // Mettre à jour l'URL sans recharger la page
            const url = new URL(window.location);
            url.searchParams.set('video_id', videoId);
            window.history.pushState({}, '', url);
            
            // Recharger la page pour charger la nouvelle vidéo
            window.location.reload();
        }
        function loadVideoByPath(fileUrl) {
            // Pour les éléments provenant du disque sans id, on met à jour un param path
            const url = new URL(window.location);
            url.searchParams.delete('video_id');
            url.searchParams.set('file', fileUrl);
            window.history.pushState({}, '', url);
            window.location.reload();
        }
        
        function startSeries(firstVideoId, firstTitle) {
            // Commencer par la première vidéo
            loadVideo(firstVideoId, firstTitle);
        }
        
        function playNextVideo() {
            // Trouver l'index de la vidéo actuelle (par id sinon par chemin)
            let currentIndex = episodes.findIndex(ep => ep.id && currentVideoId && ep.id == currentVideoId);
            if (currentIndex === -1 && currentVideoFile) {
                currentIndex = episodes.findIndex(ep => ep.file_url && ep.file_url === currentVideoFile);
            }
            if (currentIndex !== -1 && currentIndex < episodes.length - 1) {
                const nextVideo = episodes[currentIndex + 1];
                
                // Afficher l'animation de transition
                showTransitionAnimation();
                
                // Délai pour l'animation puis chargement de la vidéo suivante
                setTimeout(() => {
                    if (nextVideo.id) {
                        loadVideo(nextVideo.id, nextVideo.title);
                    } else if (nextVideo.file_url) {
                        loadVideoByPath(nextVideo.file_url);
                    }
                }, 2000);
            } else {
                // Animation de fin de série
                showCompletionAnimation();
            }
        }

        function playPrevVideo() {
            let currentIndex = episodes.findIndex(ep => ep.id && currentVideoId && ep.id == currentVideoId);
            if (currentIndex === -1 && currentVideoFile) {
                currentIndex = episodes.findIndex(ep => ep.file_url && ep.file_url === currentVideoFile);
            }
            if (currentIndex > 0) {
                const prevVideo = episodes[currentIndex - 1];
                if (prevVideo.id) {
                    loadVideo(prevVideo.id, prevVideo.title);
                } else if (prevVideo.file_url) {
                    loadVideoByPath(prevVideo.file_url);
                }
            }
        }

                        // Animation de transition entre vidéos
        function showTransitionAnimation() {
            // Créer l'overlay de transition
            const overlay = document.createElement('div');
            overlay.id = 'transition-overlay';
            overlay.className = 'fixed inset-0 z-50 bg-black bg-opacity-80 flex items-center justify-center';
            overlay.innerHTML = `
                <div class="text-center text-white">
                    <div class="animate-spin mb-4">
                        <i class="fas fa-play-circle text-6xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Chargement de la vidéo suivante...</h3>
                    <div class="w-64 bg-gray-700 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        function removeTransitionOverlay() {
            const overlay = document.getElementById('transition-overlay');
            if (overlay) {
                overlay.remove();
            }
        }

        function showCompletionAnimation() {
            // Créer l'overlay de fin de série
            const overlay = document.createElement('div');
            overlay.id = 'completion-overlay';
            overlay.className = 'fixed inset-0 z-50 bg-black bg-opacity-90 flex items-center justify-center';
            overlay.innerHTML = `
                <div class="text-center text-white">
                    <div class="animate-bounce mb-4">
                        <i class="fas fa-trophy text-6xl text-yellow-400"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-2">Félicitations !</h3>
                    <p class="text-lg mb-4">Vous avez terminé la série</p>
                    <div class="flex space-x-4 justify-center">
                        <button onclick="closeCompletion()" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded-lg transition-colors">
                            Fermer
                        </button>
                        <button onclick="location.reload()" class="bg-green-600 hover:bg-green-700 px-6 py-2 rounded-lg transition-colors">
                            Recommencer
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        function closeCompletion() {
            const overlay = document.getElementById('completion-overlay');
            if (overlay) {
                overlay.remove();
            }
        }

        // Gestion des événements vidéo
        document.addEventListener('DOMContentLoaded', function() {
                    const video = document.getElementById('mainVideo');
                    const volumeSlider = document.getElementById('volumeSlider');
                    const volumeValue = document.getElementById('volumeValue');
                    const qualitySelect = document.getElementById('qualitySelect');
                    const fullscreenBtn = document.getElementById('fullscreenBtn');
                    const episodeSearch = document.getElementById('episodeSearch');
                    const toggleMobileToc = document.getElementById('toggleMobileToc');
                    const mobileToc = document.getElementById('mobileToc');
                    
                    if (video) {
                        // S'assurer que le son est activé
                        video.muted = false;
                        video.volume = 1.0;
                        
                        // Événement de fin de vidéo - transition automatique vers la suivante
                        video.addEventListener('ended', function() {
                            console.log('Vidéo terminée - transition vers la suivante');
                            showTransitionAnimation();
                            
                            // Délai pour l'animation puis passage à la vidéo suivante
                            setTimeout(() => {
                                playNextVideo();
                            }, 2000);
                        });
                        
                        // Contrôle du volume
                        if (volumeSlider && volumeValue) {
                            volumeSlider.addEventListener('input', function() {
                                video.volume = this.value;
                                volumeValue.textContent = Math.round(this.value * 100) + '%';
                            });
                            
                            // Synchroniser le slider avec le volume de la vidéo
                            video.addEventListener('volumechange', function() {
                                volumeSlider.value = video.volume;
                                volumeValue.textContent = Math.round(video.volume * 100) + '%';
                            });
                        }
                        
                        // Contrôle de la qualité (simulation)
                        if (qualitySelect) {
                            qualitySelect.addEventListener('change', function() {
                                const quality = this.value;
                                console.log('Qualité sélectionnée:', quality);
                                // Ici vous pourriez implémenter la logique de changement de qualité
                                // Pour l'instant, c'est juste informatif
                                alert('Qualité ' + quality + ' sélectionnée (fonctionnalité à implémenter)');
                            });
                        }
                        
                        // Bouton plein écran
                        if (fullscreenBtn) {
                            fullscreenBtn.addEventListener('click', function() {
                                if (video.requestFullscreen) {
                                    video.requestFullscreen();
                                } else if (video.webkitRequestFullscreen) {
                                    video.webkitRequestFullscreen();
                                } else if (video.msRequestFullscreen) {
                                    video.msRequestFullscreen();
                                }
                            });
                        }

                        // Précédent / Suivant
                        const prevBtn = document.getElementById('prevBtn');
                        const nextBtn = document.getElementById('nextBtn');
                        if (prevBtn) {
                            prevBtn.addEventListener('click', function() {
                                playPrevVideo();
                            });
                        }
                        if (nextBtn) {
                            nextBtn.addEventListener('click', function() {
                                playNextVideo();
                            });
                        }
                        
                        // Protection contre le téléchargement
                        video.addEventListener('loadstart', function() {
                            // Désactiver le clic droit
                            video.addEventListener('contextmenu', function(e) {
                                e.preventDefault();
                                return false;
                            });
                            
                            // Désactiver les raccourcis clavier de téléchargement
        document.addEventListener('keydown', function(e) {
                                // Bloquer Ctrl+S, Ctrl+Shift+S, F12, Ctrl+U; Ajout raccourcis ←/→
            if ((e.ctrlKey && e.key === 's') || 
                                    (e.ctrlKey && e.shiftKey && e.key === 'S') ||
                e.key === 'F12' || 
                                    (e.ctrlKey && e.key === 'u')) {
                e.preventDefault();
                return false;
            }
                                if (e.key === 'ArrowRight') {
                                    e.preventDefault();
                                    playNextVideo();
                                } else if (e.key === 'ArrowLeft') {
                                    e.preventDefault();
                                    playPrevVideo();
            }
                            });
        });

        // Filtrage épisodes
        (function setupEpisodeFilter(){
            const input = document.getElementById('episodeSearch');
            if (!input) return;
            input.addEventListener('input', function(){
                const q = this.value.trim().toLowerCase();
                document.querySelectorAll('.episode-item').forEach(el => {
                    const title = el.getAttribute('data-title') || '';
                    el.style.display = title.includes(q) ? '' : 'none';
                });
            });
        })();

        // Mobile TOC toggle
        (function setupMobileToc(){
            if (!toggleMobileToc || !mobileToc) return;
            toggleMobileToc.addEventListener('click', function(){
                mobileToc.classList.toggle('hidden');
            });
        })();

        // Aside comments submit duplicates into main comments list as well
        (function hookAsideComments(){
            const submitAside = document.getElementById('submitAside');
            const inputAside = document.getElementById('commentTextAside');
            const listAside = document.getElementById('commentsListAside');
            if (!submitAside || !inputAside || !listAside) return;
            submitAside.addEventListener('click', function(){
                const text = (inputAside.value || '').trim();
                if (!text) return;
                addComment(text);
                // Simple clone render also in aside
                const div = document.createElement('div');
                div.className = 'p-3 bg-gray-50 rounded border border-gray-200 text-sm';
                div.textContent = text;
                listAside.prepend(div);
                inputAside.value = '';
            });
        })();

                        // Désactiver la sélection de texte sur la vidéo
                        video.addEventListener('selectstart', function(e) {
            e.preventDefault();
            return false;
        });

        // Désactiver le glisser-déposer
                        video.addEventListener('dragstart', function(e) {
            e.preventDefault();
            return false;
        });

                        // Gestion des commentaires
                        const commentForm = document.getElementById('commentForm');
                        if (commentForm) {
                            commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
                                const commentText = document.getElementById('commentText').value.trim();
                                
                                if (commentText) {
                                    addComment(commentText);
                                    document.getElementById('commentText').value = '';
                                }
                            });
                        }
                        
                        // Gestion des erreurs audio
            video.addEventListener('loadstart', function() {
                            console.log('Chargement de la vidéo commencé');
            });

            video.addEventListener('canplay', function() {
                            console.log('Vidéo prête à être lue');
                            // S'assurer que le son est activé quand la vidéo est prête
                            video.muted = false;
                            video.volume = 1.0;
                            
                        });
                        
                        video.addEventListener('error', function(e) {
                            console.error('Erreur vidéo:', e);
                            alert('Erreur lors du chargement de la vidéo. Vérifiez le chemin du fichier.');
                        });
                video.addEventListener('ended', function() {
                    // Marquer la vidéo comme terminée
                    console.log('Vidéo terminée');
                });
                
            }
        });
        
        
        
        
        // Fonction pour ajouter un commentaire
        function addComment(text) {
            const commentsList = document.getElementById('commentsList');
            if (!commentsList) return;
            
            // Créer un nouveau commentaire
            const commentDiv = document.createElement('div');
            commentDiv.className = 'flex space-x-3 p-4 bg-gray-50 rounded-lg';
            
            // Générer une initiale aléatoire
            const initials = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
            const randomInitial = initials[Math.floor(Math.random() * initials.length)];
            const colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-red-500', 'bg-yellow-500'];
            const randomColor = colors[Math.floor(Math.random() * colors.length)];
            
            commentDiv.innerHTML = `
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 ${randomColor} rounded-full flex items-center justify-center text-white text-sm font-semibold">
                        ${randomInitial}
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-1">
                        <span class="font-semibold text-gray-900">Utilisateur</span>
                        <span class="text-xs text-gray-500">à l'instant</span>
                    </div>
                    <p class="text-gray-700">${text}</p>
                    <div class="flex items-center space-x-4 mt-2">
                        <button class="text-gray-500 hover:text-blue-600 text-sm">
                            <i class="fas fa-thumbs-up mr-1"></i>0
                        </button>
                        <button class="text-gray-500 hover:text-blue-600 text-sm">
                            <i class="fas fa-reply mr-1"></i>Répondre
                        </button>
                    </div>
                </div>
            `;
            
            // Ajouter le commentaire en haut de la liste
            commentsList.insertBefore(commentDiv, commentsList.firstChild);
        }
    </script>
</body>
</html>
