<?php
require_once __DIR__ . '/../config/database.php';

// Force UTF-8 output and internal encodings
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// Connexion à la base de données
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                DB_OPTIONS
            );
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Fonctions d'authentification
function registerUser($fullName, $email, $password, $role = 'student') {
    $pdo = getDB();
    
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
    }
    
    // Hasher le mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insérer l'utilisateur
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt->execute([$fullName, $email, $hashedPassword, $role])) {
        return ['success' => true, 'message' => 'Inscription réussie'];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de l\'inscription'];
}

function loginUser($email, $password) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Vérifier si le compte est actif
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Votre compte a été désactivé par l\'administrateur. Veuillez contacter l\'administration pour plus d\'informations.'];
        }
        
        // Démarrer la session si elle n'est pas déjà active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        return ['success' => true, 'user' => $user];
    }
    
    return ['success' => false, 'message' => 'Email ou mot de passe incorrect'];
}

function getUserById($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    // Vérifier si l'utilisateur connecté est toujours actif
    $user = getUserById($_SESSION['user_id']);
    if (!$user || !$user['is_active']) {
        // Détruire la session
        session_destroy();
        header('Location: login.php?error=account_disabled');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
        header('Location: index.php');
        exit();
    }
}

// Fonctions pour les cours
function getAllCourses($category = null, $level = null) {
    $pdo = getDB();
    
    $sql = "SELECT c.*, 
            COALESCE(ce.enrolled_count, 0) as enrolled_students,
            COALESCE(cm.modules_count, 0) as modules_count
            FROM courses c
            LEFT JOIN (
                SELECT course_id, COUNT(*) as enrolled_count 
                FROM course_enrollments 
                GROUP BY course_id
            ) ce ON c.id = ce.course_id
            LEFT JOIN (
                SELECT course_id, COUNT(*) as modules_count 
                FROM course_modules 
                GROUP BY course_id
            ) cm ON c.id = cm.course_id
            WHERE c.is_active = TRUE";
    $params = [];
    
    if ($category) {
        $sql .= " AND c.category = ?";
        $params[] = $category;
    }
    
    if ($level) {
        $sql .= " AND c.level = ?";
        $params[] = $level;
    }
    
    $sql .= " ORDER BY c.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getCourseById($courseId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    return $stmt->fetch();
}

function getRecentCourses($limit = 6) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE is_active = TRUE ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}



function getEnrolledCoursesCount($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_courses WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'];
}

// Fonctions pour les ressources
function getAllResources($type = null, $category = null) {
    $pdo = getDB();
    
    $sql = "SELECT * FROM resources WHERE 1=1";
    $params = [];
    
    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY upload_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getResourceById($resourceId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = ?");
    $stmt->execute([$resourceId]);
    return $stmt->fetch();
}

function getResourcesCount() {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM resources");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['count'];
}

// Fonctions pour les statistiques des ressources
function getResourceStats() {
    $pdo = getDB();
    $stats = [];
    
    // Total des ressources
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM resources WHERE is_active = TRUE");
    $stats['total'] = $stmt->fetch()['total'];
    
    // Par type
    $stmt = $pdo->query("SELECT type, COUNT(*) as count FROM resources WHERE is_active = TRUE GROUP BY type");
    $stats['by_type'] = $stmt->fetchAll();
    
    // Par catégorie
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM resources WHERE is_active = TRUE GROUP BY category");
    $stats['by_category'] = $stmt->fetchAll();
    
    return $stats;
}

// Marquer une ressource comme vue
function markResourceAsViewed($userId, $resourceId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO resource_views (user_id, resource_id, viewed_at) VALUES (?, ?, NOW())");
    return $stmt->execute([$userId, $resourceId]);
}

// Fonctions utilitaires pour les ressources
function getResourceTypeIcon($type) {
    $icons = [
        'document' => 'file-alt',
        'video' => 'play-circle',
        'code' => 'code',
        'book' => 'book-open',
        'presentation' => 'presentation',
        'tutorial' => 'graduation-cap',
        'article' => 'newspaper',
        'dataset' => 'database'
    ];
    return $icons[$type] ?? 'file';
}

function getResourceTypeBackground($type) {
    $backgrounds = [
        'document' => 'bg-gradient-to-br from-blue-500 to-blue-600',
        'video' => 'bg-gradient-to-br from-red-500 to-red-600',
        'code' => 'bg-gradient-to-br from-green-500 to-green-600',
        'book' => 'bg-gradient-to-br from-purple-500 to-purple-600',
        'presentation' => 'bg-gradient-to-br from-orange-500 to-orange-600',
        'tutorial' => 'bg-gradient-to-br from-indigo-500 to-indigo-600',
        'article' => 'bg-gradient-to-br from-teal-500 to-teal-600',
        'dataset' => 'bg-gradient-to-br from-pink-500 to-pink-600'
    ];
    return $backgrounds[$type] ?? 'bg-gradient-to-br from-gray-500 to-gray-600';
}

function getResourceTypeBadge($type) {
    $badges = [
        'document' => 'bg-blue-100 text-blue-800',
        'video' => 'bg-red-100 text-red-800',
        'code' => 'bg-green-100 text-green-800',
        'book' => 'bg-purple-100 text-purple-800',
        'presentation' => 'bg-orange-100 text-orange-800',
        'tutorial' => 'bg-indigo-100 text-indigo-800',
        'article' => 'bg-teal-100 text-teal-800',
        'dataset' => 'bg-pink-100 text-pink-800'
    ];
    return $badges[$type] ?? 'bg-gray-100 text-gray-800';
}

function getResourceTypeLabel($type) {
    $labels = [
        'document' => 'Document',
        'video' => 'Vidéo',
        'code' => 'Code',
        'book' => 'Livre',
        'presentation' => 'Présentation',
        'tutorial' => 'Tutoriel',
        'article' => 'Article',
        'dataset' => 'Dataset'
    ];
    return $labels[$type] ?? 'Fichier';
}

function getCategoryColor($category) {
    $colors = [
        'artificial-intelligence' => 'bg-purple-100 text-purple-800',
        'machine-learning' => 'bg-blue-100 text-blue-800',
        'deep-learning' => 'bg-indigo-100 text-indigo-800',
        'embedded-systems' => 'bg-green-100 text-green-800',
        'software-engineering' => 'bg-yellow-100 text-yellow-800',
        'programming' => 'bg-red-100 text-red-800'
    ];
    return $colors[$category] ?? 'bg-gray-100 text-gray-800';
}

// Fonctions utilitaires
function getStudyHours($userId) {
    $pdo = getDB();
    
    // Calculer les heures d'étude basées sur l'activité réelle
    // 1. Temps passé sur les ressources (estimation basée sur les vues)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) * 0.5 as estimated_hours 
        FROM resource_views rv 
        JOIN resources r ON rv.resource_id = r.id 
        WHERE rv.user_id = ? AND rv.viewed_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$userId]);
    $resourceHours = $stmt->fetchColumn() ?: 0;
    
    // 2. Temps passé sur les cours (estimation basée sur la progression)
    $stmt = $pdo->prepare("
        SELECT SUM(progress_percentage) * 0.1 as course_hours 
        FROM user_courses 
        WHERE user_id = ? AND enrolled_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$userId]);
    $courseHours = $stmt->fetchColumn() ?: 0;
    
    // 3. Temps passé sur les certifications
    $stmt = $pdo->prepare("
        SELECT SUM(progress_percentage) * 0.2 as cert_hours 
        FROM user_progress 
        WHERE user_id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$userId]);
    $certHours = $stmt->fetchColumn() ?: 0;
    
    $totalHours = $resourceHours + $courseHours + $certHours;
    
    // Arrondir à l'entier le plus proche
    return round($totalHours);
}

function getActiveStudentsCount() {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['count'] ?: rand(50, 200);
}

function getCategoryIcon($category) {
    $icons = [
        'embedded-systems' => '🔧',
        'artificial-intelligence' => '🤖',
        'machine-learning' => '🧠',
        'deep-learning' => '⚡',
        'software-engineering' => '💻',
        'mathematics' => '📐',
        'programming' => '⌨️'
    ];
    return $icons[$category] ?? '📚';
}

function getLevelColor($level) {
    $colors = [
        'beginner' => 'bg-green-100 text-green-800',
        'intermediate' => 'bg-yellow-100 text-yellow-800',
        'advanced' => 'bg-red-100 text-red-800',
        'expert' => 'bg-purple-100 text-purple-800'
    ];
    return $colors[$level] ?? 'bg-gray-100 text-gray-800';
}

function getLevelLabel($level) {
    $labels = [
        'beginner' => 'Débutant',
        'intermediate' => 'Intermédiaire',
        'advanced' => 'Avancé',
        'expert' => 'Expert'
    ];
    return $labels[$level] ?? 'Inconnu';
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// Fonctions pour les messages flash
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Fonctions pour la pagination
function paginate($query, $params = [], $page = 1, $perPage = 10) {
    $pdo = getDB();
    
    // Compter le total
    $countSql = "SELECT COUNT(*) as total FROM ($query) as subquery";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Calculer les limites
    $offset = ($page - 1) * $perPage;
    $totalPages = ceil($total / $perPage);
    
    // Récupérer les données
    $dataSql = $query . " LIMIT $perPage OFFSET $offset";
    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute($params);
    $data = $dataStmt->fetchAll();
    
    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => $totalPages,
        'hasNext' => $page < $totalPages,
        'hasPrev' => $page > 1
    ];
}

// Fonction pour compter les ressources consultées par un utilisateur
function getCompletedResourcesCount($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_resources WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

// Fonction pour récupérer les certifications obtenues par un utilisateur
function getUserCertifications($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT uc.*, cp.title, cp.category, cp.level, cp.badge_image
        FROM user_certifications uc
        JOIN certification_paths cp ON uc.certification_path_id = cp.id
        WHERE uc.user_id = ? AND uc.passed = TRUE
        ORDER BY uc.issued_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Fonction pour récupérer les cours populaires
function getPopularCourses($limit = 6) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COALESCE(ce.enrolled_count, 0) as enrolled_students
        FROM courses c
        LEFT JOIN (
            SELECT course_id, COUNT(*) as enrolled_count 
            FROM course_enrollments 
            GROUP BY course_id
        ) ce ON c.id = ce.course_id
        WHERE c.is_active = TRUE
        ORDER BY ce.enrolled_count DESC, c.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Fonction pour récupérer les ressources récentes
function getRecentResources($limit = 6) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT * FROM resources 
        WHERE is_active = TRUE 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Fonction pour récupérer les cours auxquels un utilisateur est inscrit
function getEnrolledCourses($userId, $limit = 5) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT c.*, uc.progress_percentage, uc.enrolled_at
        FROM courses c
        JOIN user_courses uc ON c.id = uc.course_id
        WHERE uc.user_id = ? AND c.is_active = TRUE
        ORDER BY uc.enrolled_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// Fonction pour récupérer les ressources récemment consultées par un utilisateur
function getRecentlyViewedResources($userId, $limit = 5) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT r.*, rv.viewed_at, rv.time_spent_minutes
        FROM resources r
        JOIN resource_views rv ON r.id = rv.resource_id
        WHERE rv.user_id = ? AND r.is_active = TRUE
        ORDER BY rv.viewed_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// Fonction pour récupérer les parcours de certification
function getCertificationPaths($limit = 6) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT cp.*, 
               COALESCE(pr.resources_count, 0) as resources_count,
               COALESCE(uc.enrolled_users, 0) as enrolled_users
        FROM certification_paths cp
        LEFT JOIN (
            SELECT certification_path_id, COUNT(*) as resources_count 
            FROM path_resources 
            GROUP BY certification_path_id
        ) pr ON cp.id = pr.certification_path_id
        LEFT JOIN (
            SELECT certification_path_id, COUNT(*) as enrolled_users 
            FROM user_certifications 
            WHERE passed = 1
            GROUP BY certification_path_id
        ) uc ON cp.id = uc.certification_path_id
        WHERE cp.is_active = 1 
        ORDER BY cp.created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Fonctions pour la gestion des paramètres
function getSetting($key, $default = '') {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : $default;
}

function updateSetting($key, $value) {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

?>
