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
}

// Statistiques utilisateur pour le sidebar (comme le dashboard)
$enrolledCoursesCount = isset($user['id']) ? getEnrolledCoursesCount($user['id']) : 0;
$studyHours = isset($user['id']) ? getStudyHours($user['id']) : 0;
$completedResources = isset($user['id']) ? getCompletedResourcesCount($user['id']) : 0;
$userCertifications = isset($user['id']) ? getUserCertifications($user['id']) : [];

// Récupérer les ressources avec filtres et statistiques (similaire à resources.php)
function getResourcesWithStats($type = null, $category = null, $search = null) {
    $pdo = getDB();
    
    $sql = "SELECT r.*, 
            COUNT(rv.id) as view_count,
            COUNT(DISTINCT rv.user_id) as unique_viewers
            FROM resources r
            LEFT JOIN resource_views rv ON r.id = rv.resource_id
            WHERE r.is_active = 1";
    
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

// Paramètres de filtrage
$type = $_GET['type'] ?? null;
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;

$resources = getResourcesWithStats($type, $category, $search);
$stats = getResourceStats();

// Compter les livres par catégorie
function getBookCategoryCounts() {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM resources WHERE type = 'book' AND is_active = 1 GROUP BY category ORDER BY count DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

$bookCategories = getBookCategoryCounts();
// Total livres
$bookTotal = 0;
foreach ($stats['by_type'] as $typeStat) {
    if ($typeStat['type'] === 'book') {
        $bookTotal = (int)$typeStat['count'];
        break;
    }
}

// Aides de données (style dashboard)
function getRecentBooks($limit = 6) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE type = 'book' AND is_active = 1 ORDER BY upload_date DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getRecentlyViewedBooks($userId, $limit = 4) {
    if (!$userId) { return []; }
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT r.*, rv.viewed_at
                           FROM resource_views rv
                           JOIN resources r ON r.id = rv.resource_id
                           WHERE rv.user_id = ? AND r.type = 'book'
                           ORDER BY rv.viewed_at DESC
                           LIMIT ?");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

function getTotalBookViews() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT COUNT(*) FROM resource_views rv JOIN resources r ON r.id = rv.resource_id WHERE r.type = 'book'");
    return (int)$stmt->fetchColumn();
}

$recentBooks = getRecentBooks(6);
$recentlyViewedBooks = getRecentlyViewedBooks($user['id'] ?? null, 4);
$totalBookViews = getTotalBookViews();

// Comptage par types académiques
function getTypeCounts(array $types) {
    $pdo = getDB();
    $in  = str_repeat('?,', count($types) - 1) . '?';
    $stmt = $pdo->prepare("SELECT type, COUNT(*) as count FROM resources WHERE is_active = 1 AND type IN ($in) GROUP BY type");
    $stmt->execute($types);
    $rows = $stmt->fetchAll();
    $result = array_fill_keys($types, 0);
    foreach ($rows as $row) {
        $result[$row['type']] = (int)$row['count'];
    }
    return $result;
}

$academicTypes = ['book', 'article', 'review', 'journal', 'thesis', 'conference', 'report', 'whitepaper', 'book-chapter'];
$typeCounts = getTypeCounts($academicTypes);
// Icônes par type académique (Font Awesome)
$typeIconMap = [
    'book' => 'book',
    'article' => 'file-alt',
    'review' => 'star-half-alt',
    'journal' => 'newspaper',
    'thesis' => 'graduation-cap',
    'conference' => 'microphone',
    'report' => 'clipboard',
    'whitepaper' => 'file',
    'book-chapter' => 'bookmark',
];

// Facultés universitaires (catégorisation scientifique)
function mapCategoryToFaculty($category) {
    $category = strtolower((string)$category);
    $informatics = ['artificial-intelligence', 'machine-learning', 'deep-learning', 'software-engineering', 'programming', 'embedded-systems', 'computer-science', 'informatics'];
    if (in_array($category, $informatics, true)) {
        return 'Informatique & Ingénierie';
    }
    $sciences = ['mathematics', 'physics', 'chemistry', 'biology', 'earth-sciences', 'geology', 'astronomy'];
    if (in_array($category, $sciences, true)) {
        return 'Sciences (Math/Physique/Chimie)';
    }
    $health = ['medicine', 'health', 'nursing', 'pharmacy', 'public-health'];
    if (in_array($category, $health, true)) {
        return 'Médecine & Santé';
    }
    $law = ['law', 'political-science', 'international-relations'];
    if (in_array($category, $law, true)) {
        return 'Droit & Sciences Politiques';
    }
    $economics = ['economics', 'management', 'finance', 'business'];
    if (in_array($category, $economics, true)) {
        return 'Économie & Gestion';
    }
    $arts = ['arts', 'literature', 'languages', 'history', 'philosophy'];
    if (in_array($category, $arts, true)) {
        return 'Arts & Lettres';
    }
    $social = ['education', 'psychology', 'sociology', 'anthropology'];
    if (in_array($category, $social, true)) {
        return 'Sciences Sociales & Éducation';
    }
    return 'Autres';
}

function getFacultyCountsBooksOnly() {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM resources WHERE is_active = 1 AND type = 'book' GROUP BY category");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $faculties = [
        'Informatique & Ingénierie' => 0,
        'Sciences (Math/Physique/Chimie)' => 0,
        'Médecine & Santé' => 0,
        'Droit & Sciences Politiques' => 0,
        'Économie & Gestion' => 0,
        'Arts & Lettres' => 0,
        'Sciences Sociales & Éducation' => 0,
        'Autres' => 0,
    ];
    foreach ($rows as $row) {
        $faculty = mapCategoryToFaculty($row['category']);
        $faculties[$faculty] = ($faculties[$faculty] ?? 0) + (int)$row['count'];
    }
    return $faculties;
}

$facultyCounts = getFacultyCountsBooksOnly();

// Catégories thématiques (bibliothèque moderne)
function getThemeCountsByMapping(array $themeToCategories)
{
    $pdo = getDB();
    // Récupérer les comptes par catégorie pour les livres actifs
    $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM resources WHERE is_active = 1 AND type = 'book' GROUP BY category");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $byCategory = [];
    foreach ($rows as $row) {
        $byCategory[strtolower((string)$row['category'])] = (int)$row['count'];
    }
    // Agréger vers thèmes
    $themeCounts = [];
    foreach ($themeToCategories as $themeKey => $categories) {
        $themeCounts[$themeKey] = 0;
        foreach ($categories as $cat) {
            $themeCounts[$themeKey] += $byCategory[strtolower($cat)] ?? 0;
        }
    }
    return $themeCounts;
}

$themeMap = [
    'vies' => ['life', 'wellbeing', 'health', 'personal-development'],
    'cuisines' => ['cooking', 'food', 'nutrition', 'gastronomy'],
    'physiques' => ['physics'],
    'religions' => ['religion', 'theology', 'spirituality'],
    'langues' => ['languages', 'linguistics'],
    'finances' => ['finance', 'economics', 'accounting', 'business'],
    'leadership' => ['leadership', 'management', 'business'],
    'developpement-personnel' => ['personal-development', 'psychology', 'coaching', 'self-help'],
    'medecine' => ['medicine', 'health', 'public-health', 'nursing', 'pharmacy'],
    'mines' => ['mining', 'geology', 'earth-sciences', 'engineering'],
    // Extensions (logique bibliothèque moderne)
    'technologie' => ['computer-science', 'informatics', 'software-engineering', 'artificial-intelligence', 'machine-learning', 'deep-learning', 'data-science'],
    'programmation' => ['programming', 'software-engineering'],
    'data-science' => ['data-science', 'statistics', 'machine-learning'],
    'environnement' => ['environment', 'sustainability', 'ecology'],
    'droit' => ['law', 'legal-studies'],
    'arts-culture' => ['arts', 'literature', 'history', 'philosophy'],
    'education' => ['education', 'pedagogy'],
    'entrepreneuriat' => ['entrepreneurship', 'startups', 'business']
];

$themeCounts = getThemeCountsByMapping($themeMap);

// Intégrer les couvertures du dossier 'livres' dans les comptages de thèmes et facultés
$coversDir = __DIR__ . DIRECTORY_SEPARATOR . 'livres';
$coverCategoryCounts = [];
if (is_dir($coversDir)) {
    // Compter les images à la racine de 'livres' (classées sous 'autres')
    $rootCount = 0;
    foreach (scandir($coversDir) as $entry) {
        if ($entry === '.' || $entry === '..') { continue; }
        $fullPath = $coversDir . DIRECTORY_SEPARATOR . $entry;
        if (is_file($fullPath)) {
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                $rootCount++;
            }
        }
    }
    if ($rootCount > 0) {
        $coverCategoryCounts['autres'] = ($coverCategoryCounts['autres'] ?? 0) + $rootCount;
    }
    // Compter les images par sous-dossier (le nom du dossier = catégorie)
    foreach (scandir($coversDir) as $dir) {
        if ($dir === '.' || $dir === '..') { continue; }
        $subPath = $coversDir . DIRECTORY_SEPARATOR . $dir;
        if (is_dir($subPath)) {
            $count = 0;
            foreach (scandir($subPath) as $file) {
                if ($file === '.' || $file === '..') { continue; }
                $full = $subPath . DIRECTORY_SEPARATOR . $file;
                if (is_file($full)) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                        $count++;
                    }
                }
            }
            if ($count > 0) {
                $categoryKey = strtolower(trim(preg_replace('/\s+/', '-', (string)$dir)));
                $coverCategoryCounts[$categoryKey] = ($coverCategoryCounts[$categoryKey] ?? 0) + $count;
            }
        }
    }
}

// Ajouter ces comptes aux facultés
if (!empty($coverCategoryCounts)) {
    foreach ($coverCategoryCounts as $categoryKey => $count) {
        $faculty = mapCategoryToFaculty($categoryKey);
        $facultyCounts[$faculty] = ($facultyCounts[$faculty] ?? 0) + (int)$count;
    }
}

// Ajouter ces comptes aux thèmes
if (!empty($coverCategoryCounts)) {
    foreach ($coverCategoryCounts as $categoryKey => $count) {
        foreach ($themeMap as $themeKey => $categories) {
            if (in_array($categoryKey, array_map('strtolower', $categories), true)) {
                $themeCounts[$themeKey] = ($themeCounts[$themeKey] ?? 0) + (int)$count;
            }
        }
    }
}

// Type sélectionné pour la liste complète
$selectedType = isset($_GET['atype']) && in_array($_GET['atype'], $academicTypes, true)
    ? $_GET['atype']
    : 'book';
// Libellés pour affichage du type sélectionné
$typeLabels = [
    'book' => 'Livre',
    'article' => 'Article',
    'review' => 'Revue',
    'journal' => 'Journal',
    'thesis' => 'Thèse',
    'conference' => 'Conférence',
    'report' => 'Rapport',
    'whitepaper' => 'Livre blanc',
    'book-chapter' => 'Chapitre'
];
$selectedTypeLabel = $typeLabels[$selectedType] ?? ucfirst($selectedType);

// Recherche mots-clés (titre, description, auteur)
$keyword = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

// Pages dédiées par type
$typePageMap = [
    'book' => 'bibliotheque-livres.php',
    'article' => 'bibliotheque-articles.php',
    'review' => 'bibliotheque-revues.php',
    'journal' => 'bibliotheque-journaux.php',
    'thesis' => 'bibliotheque-theses.php',
    'conference' => 'bibliotheque-conferences.php',
    'report' => 'bibliotheque-rapports.php',
    'whitepaper' => 'bibliotheque-livres-blancs.php',
    'book-chapter' => 'bibliotheque-chapitres.php',
];

// Tous les livres du type sélectionné (liste complète ou limitée)
function getAllBooksByType($type, $limit = 24, $keyword = '') {
    $pdo = getDB();
    $sql = "SELECT r.*,
                   (SELECT COUNT(*) FROM resource_views v WHERE v.resource_id = r.id) as view_count
            FROM resources r
            WHERE r.type = ? AND r.is_active = 1";
    $params = [$type];
    if ($keyword !== '') {
        $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.author LIKE ?)";
        $kw = "%" . $keyword . "%";
        array_push($params, $kw, $kw, $kw);
    }
    $sql .= " ORDER BY r.upload_date DESC LIMIT ?";
    $params[] = $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$allBooks = getAllBooksByType($selectedType, 24, $keyword);

// Images depuis le dossier 'livres'
$bookImageDir = __DIR__ . DIRECTORY_SEPARATOR . 'livres';
$bookImageWebBase = 'livres';
$bookImages = [];
if (is_dir($bookImageDir)) {
    foreach (scandir($bookImageDir) as $file) {
        if ($file === '.' || $file === '..') { continue; }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $bookImages[] = $file;
        }
    }
    sort($bookImages);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html { scroll-behavior: smooth; }
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
                <a href="resources.php" class="group flex items-center px-3 py-2 rounded-lg text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <i class="fas fa-folder mr-2.5 w-4 text-gray-500 group-hover:text-blue-600"></i>
                    Ressources
                </a>
                <a href="bibliotheque.php" class="group flex items-center px-3 py-2 rounded-lg font-medium text-sm text-blue-600 bg-blue-50 ring-1 ring-blue-100">
                    <i class="fas fa-book mr-2.5 w-4 text-blue-600"></i>
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

    <div class="flex">
        
            </div>
        </div>

        <!-- Contenu principal -->
        <main class="flex-1 ml-64 mt-16 p-4 md:p-8 pb-24">
        <div>
            <!-- En-tête (style dashboard) -->
            <div class="mb-6 sm:mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">Bibliothèque</h1>
                        <div class="flex flex-col sm:flex-row gap-2">
                        <form method="GET" class="flex items-center gap-2">
                            <input type="hidden" name="atype" value="<?php echo htmlspecialchars($selectedType); ?>">
                            <input
                                type="text"
                                name="q"
                                value="<?php echo htmlspecialchars($keyword); ?>"
                                placeholder="Rechercher par titre, auteur, mots-clés..."
                                class="w-64 max-w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition-colors text-sm">
                                <i class="fas fa-search mr-2"></i> Rechercher
                            </button>
                        </form>
                        <a href="#themes" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 text-sm font-medium shadow-sm hover:shadow-md">
                            <i class="fas fa-th-large mr-2 text-sm"></i> Catégories
                        </a>
                        <a href="#all-books" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 text-sm font-medium shadow-sm hover:shadow-md">
                            <i class="fas fa-book-open mr-2 text-sm"></i> Tous les livres
                        </a>
                        </div>
                        <div class="mt-3 flex items-center space-x-4">
                            <div class="flex items-center text-xs sm:text-sm text-gray-600">
                                <i class="fas fa-book text-blue-600 mr-2"></i>
                                <span><?php echo $bookTotal; ?> livres disponibles</span>
                            </div>
                            <div class="flex items-center text-xs sm:text-sm text-gray-600">
                                <i class="fas fa-eye text-green-600 mr-2"></i>
                                <span><?php echo $totalBookViews; ?> vues cumulées</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques principales (style dashboard) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-xs font-medium">Livres</p>
                            <p class="text-2xl font-bold"><?php echo $bookTotal; ?></p>
                            <p class="text-blue-200 text-xs mt-1">Disponibles</p>
                        </div>
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-book text-lg"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl shadow-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-xs font-medium">Vues</p>
                            <p class="text-2xl font-bold"><?php echo $totalBookViews; ?></p>
                            <p class="text-green-200 text-xs mt-1">Cumulées</p>
                        </div>
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-eye text-lg"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-xs font-medium">Catégories</p>
                            <p class="text-2xl font-bold"><?php echo count($bookCategories); ?></p>
                            <p class="text-purple-200 text-xs mt-1">Disponibles</p>
                        </div>
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-th-large text-lg"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-100 text-xs font-medium">Mes lectures</p>
                            <p class="text-2xl font-bold"><?php echo count($recentlyViewedBooks); ?></p>
                            <p class="text-yellow-200 text-xs mt-1">Récentes</p>
                        </div>
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-history text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Types académiques (boîtes carrées) -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-university text-blue-600 mr-2"></i>
                        Types Académiques
                    </h2>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                        <?php 
                        foreach ($academicTypes as $t): 
                            $icon = $typeIconMap[$t] ?? 'file';
                            $count = $typeCounts[$t] ?? 0;
                            $label = $typeLabels[$t] ?? ucfirst($t);
                        ?>
                        <a href="<?php echo isset($typePageMap[$t]) ? $typePageMap[$t] : ('bibliotheque.php?atype=' . urlencode($t)); ?>" class="group block" title="Voir: <?php echo htmlspecialchars($label); ?>">
                            <div class="relative w-full aspect-square rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 transition-colors flex items-center justify-center">
                                <div class="text-center px-2">
                                    <i class="fas fa-<?php echo $icon; ?> text-xl sm:text-2xl text-blue-600 mb-2 block"></i>
                                    <div class="text-[11px] sm:text-xs font-medium text-gray-900"><?php echo htmlspecialchars($label); ?></div>
                                    <div class="text-[10px] sm:text-xs text-gray-500"><?php echo (int)$count; ?> items</div>
                                </div>
                                <span class="absolute inset-0 rounded-lg ring-1 ring-transparent group-hover:ring-blue-300"></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Facultés universitaires (boîtes carrées) -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-layer-group text-emerald-600 mr-2"></i>
                        Facultés Universitaires
                    </h2>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                        <?php foreach ($facultyCounts as $faculty => $count): ?>
                        <div class="group block">
                            <div class="relative w-full aspect-square rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 transition-colors flex items-center justify-center">
                                <div class="text-center px-2">
                                    <div class="text-[11px] sm:text-xs font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($faculty); ?></div>
                                    <div class="text-[10px] sm:text-xs text-gray-500"><?php echo (int)$count; ?> livres</div>
                                </div>
                                <span class="absolute inset-0 rounded-lg ring-1 ring-transparent group-hover:ring-emerald-300"></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Catégories thématiques (bibliothèque moderne) -->
            <div id="themes" class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-shapes text-amber-600 mr-2"></i>
                        Catégories Thématiques
                    </h2>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                        <?php 
                        $themeLabels = [
                            'vies' => 'Vies',
                            'cuisines' => 'Cuisines',
                            'physiques' => 'Physiques',
                            'religions' => 'Religions',
                            'langues' => 'Langues',
                            'finances' => 'Finances',
                            'leadership' => 'Leadership',
                            'developpement-personnel' => 'Développement Personnel',
                            'medecine' => 'Médecine',
                            'mines' => 'Mines',
                            'technologie' => 'Technologie',
                            'programmation' => 'Programmation',
                            'data-science' => 'Data Science',
                            'environnement' => 'Environnement',
                            'droit' => 'Droit',
                            'arts-culture' => 'Arts & Culture',
                            'education' => 'Éducation',
                            'entrepreneuriat' => 'Entrepreneuriat'
                        ];
                        foreach ($themeCounts as $key => $count):
                            $label = $themeLabels[$key] ?? ucfirst($key);
                        ?>
                        <div class="group block">
                            <div class="relative w-full aspect-square rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 transition-colors flex items-center justify-center">
                                <div class="text-center px-2">
                                    <div class="text-[11px] sm:text-xs font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($label); ?></div>
                                    <div class="text-[10px] sm:text-xs text-gray-500"><?php echo (int)$count; ?> livres</div>
                                </div>
                                <span class="absolute inset-0 rounded-lg ring-1 ring-transparent group-hover:ring-orange-300"></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Tous les livres -->
            <div id="all-books" class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-slate-50 to-gray-50 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-list mr-2 text-gray-700"></i>
                            Tous les <?php echo htmlspecialchars($selectedTypeLabel); ?>s
                        </h2>
                        <span class="text-xs text-gray-500">(<?php echo count($bookImages); ?> éléments)</span>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <?php if (!empty($bookImages)): ?>
                        <div class="mb-6">
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                <?php foreach ($bookImages as $img): ?>
                                    <div class="border border-gray-200 rounded-lg overflow-hidden bg-white hover:shadow-md transition-shadow">
                                        <img src="<?php echo htmlspecialchars($bookImageWebBase . '/' . $img); ?>" alt="Couverture" loading="lazy" class="w-full h-auto object-contain">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-600">Aucune couverture trouvée dans le dossier « livres ».</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
    </div>

    <!-- Menu mobile -->
    <div class="md:hidden fixed inset-0 z-50 hidden" id="mobile-menu">
        <div class="fixed inset-0 bg-black bg-opacity-25" onclick="toggleMobileMenu()"></div>
        <div class="fixed top-0 left-0 h-full w-64 bg-white shadow-lg">
            <div class="p-4">
                
                    <h3 class="font-semibold text-gray-900 text-sm"><?php echo ($isLoggedIn && !empty($user['full_name'])) ? htmlspecialchars($user['full_name']) : 'Invité'; ?></h3>
                    <p class="text-xs text-gray-600">Étudiant IA</p>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-star mr-1"></i>
                            Niveau <?php echo $user['level'] ?? 'Débutant'; ?>
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>

        </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        function toggleMobileMenu() {
            const menu = document.querySelector('.mobile-menu');
            menu.classList.toggle('hidden');
        }

        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.classList.toggle('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', toggleMobileMenu);
            }

            // Fermer le menu utilisateur en cliquant ailleurs
            document.addEventListener('click', function(event) {
                const userMenu = document.getElementById('userMenu');
                const userButton = event.target.closest('[onclick="toggleUserMenu()"]');
                
                if (userMenu && !userButton && !userMenu.contains(event.target)) {
                    userMenu.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>

