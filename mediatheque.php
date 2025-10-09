<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Encodage et locale en français
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'fr_FR.UTF-8', 'fr_FR', 'fr');

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$user = null;

if ($isLoggedIn) {
    $user = getUserById($_SESSION['user_id']);
} else {
    // Créer un utilisateur par défaut pour l'accès sans connexion
    $user = ['id' => 0, 'full_name' => 'Visiteur', 'level' => 'Débutant'];
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
    'Droit' => ['videos/droit/']
];

if ($seriesName === 'Python pour tous') {
    // Vidéos numérotées pour la série Python
    $stmt = $pdo->prepare("
        SELECT r.*, COUNT(rv.id) as view_count
        FROM resources r
        LEFT JOIN resource_views rv ON r.id = rv.resource_id
        WHERE r.type = 'video'
          AND r.title REGEXP '^[0-9]+\\.'
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
} elseif (isset($seriesFolderMap[$seriesName])) {
    // Séries basées sur des dossiers (devperso, cybersecu, design, droit)
    $likeClauses = [];
    $params = [];
    foreach ($seriesFolderMap[$seriesName] as $folder) {
        $likeClauses[] = "r.file_url LIKE ?";
        $params[] = '%' . $folder . '%';
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
    $index = 1;
    foreach ($videos as $video) {
        $episodes[$index++] = $video;
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

if ($currentVideoId) {
    foreach ($episodes as $episode) {
        if ($episode['id'] == $currentVideoId) {
            $currentVideo = $episode;
            break;
        }
    }
}

// Si aucune vidéo spécifique n'est demandée, prendre la première disponible
if (!$currentVideo && !empty($episodes)) {
    $currentVideo = reset($episodes);
}

// Marquer la vidéo comme vue si l'utilisateur est connecté
if ($currentVideo && $isLoggedIn) {
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
    <main class="w-full mt-16 p-4 md:p-8 pb-48">
        <!-- En-tête style LinkedIn Learning -->
        <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-play-circle text-white text-xl"></i>
                                    </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($seriesName); ?></h1>
                            <p class="text-sm text-gray-600">Cours vidéo interactif</p>
                                    </div>
                                </div>
                    
                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                        <span class="flex items-center">
                            <i class="fas fa-play-circle mr-1"></i>
                            <?php echo count($episodes); ?> vidéos
                        </span>
                        <span class="flex items-center">
                            <i class="fas fa-clock mr-1"></i>
                            ~<?php echo count($episodes) * 5; ?> minutes
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
                                    </div>
                                    </div>
                        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Lecteur vidéo -->
            <div class="lg:col-span-3">
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
                    ?>
                    <div class="relative">
                        
                        
                        <video 
                            id="mainVideo" 
                            class="w-full h-64 md:h-96 bg-black" 
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
                        
                        <!-- Contrôles personnalisés -->
                        <div class="bg-gray-100 p-4 border-t">
                            <div class="flex items-center space-x-4">
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
                                
                                <!-- Bouton plein écran -->
                                <button id="fullscreenBtn" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                                    <i class="fas fa-expand mr-1"></i>Plein écran
                                </button>
                                </div>
                        </div>

                        <!-- Section commentaires, vues et partage -->
                        <div class="bg-white border-t border-gray-200 p-6">
                            <!-- Statistiques -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-6">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-eye text-gray-500"></i>
                                        <span class="text-sm text-gray-600"><?php echo rand(150, 2500); ?> vues</span>
                            </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-thumbs-up text-gray-500"></i>
                                        <span class="text-sm text-gray-600"><?php echo rand(20, 150); ?> j'aime</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-comment text-gray-500"></i>
                                        <span class="text-sm text-gray-600"><?php echo rand(5, 50); ?> commentaires</span>
                                    </div>
                                </div>
                                
                                <!-- Boutons de partage -->
                                <div class="flex items-center space-x-2">
                                    <button class="flex items-center space-x-1 bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-share mr-1"></i>
                                        <span>Partager</span>
                                    </button>
                                    <button class="flex items-center space-x-1 bg-gray-600 text-white px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                                        <i class="fas fa-bookmark mr-1"></i>
                                        <span>Enregistrer</span>
                                    </button>
                            </div>
                        </div>

                            <!-- Zone de commentaires -->
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Commentaires</h3>
                                
                                <!-- Formulaire de commentaire -->
                                <div class="mb-6">
                                    <form id="commentForm" class="space-y-4">
                                        <div>
                                            <textarea 
                                                id="commentText" 
                                                placeholder="Ajouter un commentaire..." 
                                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                                rows="3"
                                            ></textarea>
                                    </div>
                                        <div class="flex justify-end">
                                            <button 
                                                type="submit" 
                                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                                            >
                                                <i class="fas fa-paper-plane mr-2"></i>Publier
                                            </button>
                                    </div>
                                    </form>
                        </div>

                                <!-- Liste des commentaires -->
                                <div id="commentsList" class="space-y-4">
                                    <!-- Commentaire exemple 1 -->
                                    <div class="flex space-x-3 p-4 bg-gray-50 rounded-lg">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                                M
                            </div>
                                    </div>
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-1">
                                                <span class="font-semibold text-gray-900">Marie Dubois</span>
                                                <span class="text-xs text-gray-500">il y a 2 heures</span>
                                    </div>
                                            <p class="text-gray-700">Excellent cours ! Très bien expliqué et facile à suivre. Merci pour cette formation.</p>
                                            <div class="flex items-center space-x-4 mt-2">
                                                <button class="text-gray-500 hover:text-blue-600 text-sm">
                                                    <i class="fas fa-thumbs-up mr-1"></i>5
                                                </button>
                                                <button class="text-gray-500 hover:text-blue-600 text-sm">
                                                    <i class="fas fa-reply mr-1"></i>Répondre
                                                </button>
                                </div>
                            </div>
                        </div>

                                    <!-- Commentaire exemple 2 -->
                                    <div class="flex space-x-3 p-4 bg-gray-50 rounded-lg">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                                P
                            </div>
                                    </div>
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-1">
                                                <span class="font-semibold text-gray-900">Pierre Martin</span>
                                                <span class="text-xs text-gray-500">il y a 1 jour</span>
                                    </div>
                                            <p class="text-gray-700">Parfait pour débuter en Python. Les exemples sont très clairs.</p>
                                            <div class="flex items-center space-x-4 mt-2">
                                                <button class="text-gray-500 hover:text-blue-600 text-sm">
                                                    <i class="fas fa-thumbs-up mr-1"></i>3
                                                </button>
                                                <button class="text-gray-500 hover:text-blue-600 text-sm">
                                                    <i class="fas fa-reply mr-1"></i>Répondre
                                                </button>
                                </div>
                            </div>
                    </div>
                    
                        </div>
                        
                                <!-- Bouton pour charger plus de commentaires -->
                                <div class="text-center mt-6">
                                    <button class="text-blue-600 hover:text-blue-700 font-medium">
                                        <i class="fas fa-chevron-down mr-1"></i>Charger plus de commentaires
                            </button>
                                </div>
                        </div>
                    </div>
                    
                        <!-- Overlay de progression -->
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/50 to-transparent p-4">
                            <div class="text-white">
                                <h3 class="font-semibold text-lg mb-1"><?php echo htmlspecialchars($currentVideo['title']); ?></h3>
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

            <!-- Table des matières -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6 h-full overflow-y-hidden hover:overflow-y-auto overscroll-contain sidebar-scroll" style="scrollbar-width: thin; scrollbar-color: #cbd5e0 #f7fafc;">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Table des matières</h2>
                    
                    <div class="space-y-2 pr-2">
                        <?php foreach ($episodes as $episodeNum => $episode): ?>
                        <div class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer <?php echo ($currentVideo && $currentVideo['id'] == $episode['id']) ? 'bg-blue-50 border-blue-300' : ''; ?>" 
                             onclick="loadVideo(<?php echo $episode['id']; ?>, '<?php echo htmlspecialchars($episode['title']); ?>')">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 <?php echo $userProgress[$episodeNum] ? 'bg-green-600' : 'bg-blue-600'; ?> rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas <?php echo $userProgress[$episodeNum] ? 'fa-check' : 'fa-play'; ?> text-white text-sm"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-medium text-gray-900 line-clamp-2">
                                        <?php echo htmlspecialchars($episode['title']); ?>
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo $episodeNum * 5; ?> min</p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
                // Données des épisodes pour la navigation automatique
                const episodes = <?php echo json_encode(array_values($episodes)); ?>;
                const currentVideoId = <?php echo $currentVideo ? $currentVideo['id'] : 'null'; ?>;
                
        
        function loadVideo(videoId, title) {
            // Mettre à jour l'URL sans recharger la page
            const url = new URL(window.location);
            url.searchParams.set('video_id', videoId);
            window.history.pushState({}, '', url);
            
            // Recharger la page pour charger la nouvelle vidéo
            window.location.reload();
        }
        
        function startSeries(firstVideoId, firstTitle) {
            // Commencer par la première vidéo
            loadVideo(firstVideoId, firstTitle);
        }
        
        function playNextVideo() {
            // Trouver l'index de la vidéo actuelle
            const currentIndex = episodes.findIndex(ep => ep.id == currentVideoId);
            
            if (currentIndex !== -1 && currentIndex < episodes.length - 1) {
                // Jouer la vidéo suivante
                const nextVideo = episodes[currentIndex + 1];
                loadVideo(nextVideo.id, nextVideo.title);
            } else {
                // Fin de la série
                alert('Félicitations ! Vous avez terminé la série.');
            }
        }

                // Gestion des événements vidéo
        document.addEventListener('DOMContentLoaded', function() {
                    const video = document.getElementById('mainVideo');
                    const volumeSlider = document.getElementById('volumeSlider');
                    const volumeValue = document.getElementById('volumeValue');
                    const qualitySelect = document.getElementById('qualitySelect');
                    const fullscreenBtn = document.getElementById('fullscreenBtn');
                    
                    if (video) {
                        // S'assurer que le son est activé
                        video.muted = false;
                        video.volume = 1.0;
                        
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
                        
                        // Protection contre le téléchargement
                        video.addEventListener('loadstart', function() {
                            // Désactiver le clic droit
                            video.addEventListener('contextmenu', function(e) {
                                e.preventDefault();
                                return false;
                            });
                            
                            // Désactiver les raccourcis clavier de téléchargement
        document.addEventListener('keydown', function(e) {
                                // Bloquer Ctrl+S, Ctrl+Shift+S, F12, Ctrl+U
            if ((e.ctrlKey && e.key === 's') || 
                                    (e.ctrlKey && e.shiftKey && e.key === 'S') ||
                e.key === 'F12' || 
                                    (e.ctrlKey && e.key === 'u')) {
                e.preventDefault();
                return false;
            }
                            });
        });

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
                    
                    // Vérifier s'il y a une vidéo suivante
                    const currentIndex = episodes.findIndex(ep => ep.id == currentVideoId);
                    if (currentIndex !== -1 && currentIndex < episodes.length - 1) {
                        const nextVideo = episodes[currentIndex + 1];
                        
                        // Afficher l'animation de transition
                        showTransitionAnimation(nextVideo.title, 15);
                        
                        // Passer automatiquement à la vidéo suivante après 15 secondes
                        setTimeout(() => {
                            playNextVideo();
                        }, 15000);
                } else {
                        // Fin de la série
                        showCompletionAnimation();
                    }
                });
                
            }
        });
        
        // Fonction pour afficher l'animation de transition
        function showTransitionAnimation(nextTitle, duration) {
            // Créer l'overlay d'animation
            const overlay = document.createElement('div');
            overlay.id = 'transitionOverlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                color: white;
                font-family: Arial, sans-serif;
            `;
            
            // Contenu de l'animation
            overlay.innerHTML = `
                <div style="text-align: center; animation: fadeIn 1s ease-in-out;">
                    <div style="font-size: 4rem; margin-bottom: 2rem; animation: pulse 2s infinite;">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <h2 style="font-size: 2.5rem; margin-bottom: 1rem; animation: slideInUp 1s ease-out;">
                        Vidéo terminée !
                    </h2>
                    <p style="font-size: 1.5rem; margin-bottom: 2rem; animation: slideInUp 1s ease-out 0.3s both;">
                        Prochaine vidéo : <strong>${nextTitle}</strong>
                    </p>
                    <div style="font-size: 1.2rem; margin-bottom: 2rem; animation: slideInUp 1s ease-out 0.6s both;">
                        Début automatique dans :
                    </div>
                    <div id="countdown" style="font-size: 3rem; font-weight: bold; color: #ffd700; animation: slideInUp 1s ease-out 0.9s both;">
                        ${duration}
                    </div>
                    <div style="margin-top: 2rem; animation: slideInUp 1s ease-out 1.2s both;">
                        <button onclick="skipTransition()" style="
                            background: rgba(255,255,255,0.2);
                            border: 2px solid white;
                            color: white;
                            padding: 12px 24px;
                            border-radius: 25px;
                            cursor: pointer;
                            font-size: 1rem;
                            transition: all 0.3s ease;
                        " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                            <i class="fas fa-forward mr-2"></i>Passer maintenant
                        </button>
                    </div>
                </div>
            `;
            
            // Ajouter les styles CSS pour les animations
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideInUp {
                    from { 
                        opacity: 0; 
                        transform: translateY(30px); 
                    }
                    to { 
                        opacity: 1; 
                        transform: translateY(0); 
                    }
                }
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                }
            `;
            document.head.appendChild(style);
            
            // Ajouter l'overlay au body
            document.body.appendChild(overlay);
            
            // Compte à rebours
            let countdown = duration;
            const countdownElement = document.getElementById('countdown');
            const countdownInterval = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    removeTransitionOverlay();
                }
            }, 1000);
            
            // Stocker l'intervalle pour pouvoir l'arrêter si nécessaire
            window.transitionCountdown = countdownInterval;
        }
        
        // Fonction pour afficher l'animation de fin de série
        function showCompletionAnimation() {
            const overlay = document.createElement('div');
            overlay.id = 'completionOverlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                color: white;
                font-family: Arial, sans-serif;
            `;
            
            overlay.innerHTML = `
                <div style="text-align: center; animation: fadeIn 1s ease-in-out;">
                    <div style="font-size: 5rem; margin-bottom: 2rem; animation: bounce 2s infinite;">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h2 style="font-size: 3rem; margin-bottom: 1rem; animation: slideInUp 1s ease-out;">
                        Félicitations !
                    </h2>
                    <p style="font-size: 1.5rem; margin-bottom: 2rem; animation: slideInUp 1s ease-out 0.3s both;">
                        Vous avez terminé la série complète !
                    </p>
                    <div style="margin-top: 2rem; animation: slideInUp 1s ease-out 0.6s both;">
                        <button onclick="closeCompletion()" style="
                            background: rgba(255,255,255,0.2);
                            border: 2px solid white;
                            color: white;
                            padding: 12px 24px;
                            border-radius: 25px;
                            cursor: pointer;
                            font-size: 1rem;
                            transition: all 0.3s ease;
                        " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                            <i class="fas fa-check mr-2"></i>Continuer
                        </button>
                    </div>
                </div>
            `;
            
            // Ajouter les styles CSS pour l'animation de fin
            const style = document.createElement('style');
            style.textContent += `
                @keyframes bounce {
                    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                    40% { transform: translateY(-20px); }
                    60% { transform: translateY(-10px); }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(overlay);
        }
        
        // Fonction pour passer la transition
        function skipTransition() {
            if (window.transitionCountdown) {
                clearInterval(window.transitionCountdown);
            }
            removeTransitionOverlay();
            playNextVideo();
        }
        
        // Fonction pour fermer l'animation de fin
        function closeCompletion() {
            const overlay = document.getElementById('completionOverlay');
            if (overlay) {
                overlay.remove();
            }
        }
        
        // Fonction pour supprimer l'overlay de transition
        function removeTransitionOverlay() {
            const overlay = document.getElementById('transitionOverlay');
            if (overlay) {
                overlay.remove();
            }
        }
        
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
            
            // Animation d'apparition
            commentDiv.style.opacity = '0';
            commentDiv.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                commentDiv.style.transition = 'all 0.3s ease';
                commentDiv.style.opacity = '1';
                commentDiv.style.transform = 'translateY(0)';
            }, 100);
        }
    </script>
</body>
</html>
