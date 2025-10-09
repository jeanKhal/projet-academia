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

// Récupérer l'ID du cours
$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$courseId) {
    header('Location: courses.php');
    exit();
}

// Récupérer le cours avec détails
function getCourseWithDetails($courseId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT ce.user_id) as enrolled_students,
               AVG(ce.progress) as avg_progress
        FROM courses c
        LEFT JOIN course_enrollments ce ON c.id = ce.course_id
        WHERE c.id = ? AND c.is_active = TRUE
        GROUP BY c.id
    ");
    $stmt->execute([$courseId]);
    return $stmt->fetch();
}

// Récupérer les modules du cours
function getCourseModules($courseId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT * FROM course_modules 
        WHERE course_id = ? AND is_active = TRUE 
        ORDER BY module_order
    ");
    $stmt->execute([$courseId]);
    return $stmt->fetchAll();
}

// Récupérer les cours similaires
function getSimilarCourses($courseId, $category, $limit = 4) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(DISTINCT ce.user_id) as enrolled_students
        FROM courses c
        LEFT JOIN course_enrollments ce ON c.id = ce.course_id
        WHERE c.id != ? AND c.category = ? AND c.is_active = TRUE
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$courseId, $category, $limit]);
    return $stmt->fetchAll();
}

// Vérifier si l'utilisateur est inscrit au cours
function isUserEnrolled($userId, $courseId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM course_enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$userId, $courseId]);
    return $stmt->fetch();
}

$course = getCourseWithDetails($courseId);

if (!$course) {
    header('Location: courses.php');
    exit();
}

$modules = getCourseModules($courseId);
$similarCourses = getSimilarCourses($courseId, $course['category']);
$isEnrolled = $isLoggedIn ? isUserEnrolled($user['id'], $courseId) : false;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Contenu principal -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
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
                            <a href="courses.php" class="text-sm font-medium text-gray-700 hover:text-blue-600">
                                Cours
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-sm font-medium text-gray-500"><?php echo htmlspecialchars($course['title']); ?></span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- En-tête du cours -->
            <div class="bg-white shadow rounded-lg overflow-hidden mb-8">
                <div class="relative h-64 bg-gradient-to-r from-blue-600 to-purple-700">
                    <div class="absolute inset-0 bg-black bg-opacity-40"></div>
                    <div class="relative h-full flex items-center justify-center">
                        <div class="text-center text-white">
                            <h1 class="text-4xl font-bold mb-4"><?php echo htmlspecialchars($course['title']); ?></h1>
                            <p class="text-xl opacity-90"><?php echo htmlspecialchars($course['subtitle']); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-4 mb-4">
                                <span class="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                                    <?php echo ucfirst($course['category']); ?>
                                </span>
                                <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800">
                                    <?php echo $course['level']; ?>
                                </span>
                                <span class="px-3 py-1 text-sm font-medium rounded-full bg-purple-100 text-purple-800">
                                    <?php echo $course['duration']; ?> heures
                                </span>
                            </div>
                            
                            <div class="flex items-center space-x-6 text-sm text-gray-600">
                                <span class="flex items-center">
                                    <i class="fas fa-user mr-2"></i>
                                    <?php echo htmlspecialchars($course['instructor']); ?>
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-users mr-2"></i>
                                    <?php echo $course['enrolled_students']; ?> étudiants inscrits
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-star mr-2 text-yellow-400"></i>
                                    4.8 (<?php echo rand(50, 200); ?> avis)
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-6 lg:mt-0 lg:ml-8">
                            <?php if ($isLoggedIn): ?>
                                <?php if ($isEnrolled): ?>
                                    <button class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition-colors font-medium">
                                        <i class="fas fa-play mr-2"></i>Continuer le cours
                                    </button>
                                <?php else: ?>
                                    <button class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                        <i class="fas fa-plus mr-2"></i>S'inscrire au cours
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium inline-block">
                                    <i class="fas fa-sign-in-alt mr-2"></i>Se connecter pour s'inscrire
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Contenu principal -->
                <div class="lg:col-span-2">
                    <!-- Description -->
                    <div class="bg-white shadow rounded-lg p-8 mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Description du cours</h2>
                        <div class="prose max-w-none">
                            <p class="text-gray-700 leading-relaxed mb-6">
                                <?php echo nl2br(htmlspecialchars($course['description'])); ?>
                            </p>
                            
                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Ce que vous apprendrez</h3>
                            <ul class="space-y-3">
                                <?php 
                                $learningObjectives = json_decode($course['learning_objectives'], true);
                                if ($learningObjectives):
                                    foreach ($learningObjectives as $objective): 
                                ?>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                    <span class="text-gray-700"><?php echo htmlspecialchars($objective); ?></span>
                                </li>
                                <?php 
                                    endforeach;
                                endif;
                                ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Programme du cours -->
                    <?php if (!empty($modules)): ?>
                    <div class="bg-white shadow rounded-lg p-8 mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Programme du cours</h2>
                        <div class="space-y-4">
                            <?php foreach ($modules as $index => $module): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-medium mr-4">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($module['title']); ?></h3>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($module['description']); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm text-gray-500"><?php echo $module['duration']; ?> min</span>
                                        <div class="flex items-center mt-1">
                                            <i class="fas fa-play-circle text-blue-600"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Avis et commentaires -->
                    <div class="bg-white shadow rounded-lg p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Avis des étudiants</h2>
                        <div class="space-y-6">
                            <!-- Avis de test -->
                            <div class="border-b border-gray-200 pb-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 bg-gray-300 rounded-full mr-4"></div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Marie Dupont</h4>
                                        <div class="flex items-center">
                                            <div class="flex text-yellow-400 mr-2">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <span class="text-sm text-gray-600">Il y a 2 semaines</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-gray-700">
                                    Excellent cours ! Le contenu est très bien structuré et les explications sont claires. 
                                    J'ai beaucoup appris et je recommande vivement.
                                </p>
                            </div>
                            
                            <div class="border-b border-gray-200 pb-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 bg-gray-300 rounded-full mr-4"></div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Jean Martin</h4>
                                        <div class="flex items-center">
                                            <div class="flex text-yellow-400 mr-2">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="far fa-star"></i>
                                            </div>
                                            <span class="text-sm text-gray-600">Il y a 1 mois</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-gray-700">
                                    Très bon cours pour débuter. Les exercices pratiques sont bien pensés 
                                    et permettent de bien assimiler les concepts.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Informations du cours -->
                    <div class="bg-white shadow rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du cours</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Niveau</span>
                                <span class="font-medium"><?php echo ucfirst($course['level']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Durée</span>
                                <span class="font-medium"><?php echo $course['duration']; ?> heures</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Modules</span>
                                <span class="font-medium"><?php echo count($modules); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Étudiants inscrits</span>
                                <span class="font-medium"><?php echo $course['enrolled_students']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Progression moyenne</span>
                                <span class="font-medium"><?php echo round($course['avg_progress'] ?? 0, 1); ?>%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Prérequis -->
                    <?php if ($course['prerequisites']): ?>
                    <div class="bg-white shadow rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Prérequis</h3>
                        <div class="space-y-2">
                            <?php 
                            $prerequisites = json_decode($course['prerequisites'], true);
                            if ($prerequisites):
                                foreach ($prerequisites as $prerequisite): 
                            ?>
                            <div class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span class="text-gray-700"><?php echo htmlspecialchars($prerequisite); ?></span>
                            </div>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Statistiques -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistiques</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Satisfaction</span>
                                    <span class="text-gray-900">96%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: 96%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Taux de réussite</span>
                                    <span class="text-gray-900">89%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: 89%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Temps moyen</span>
                                    <span class="text-gray-900"><?php echo $course['duration']; ?>h</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-purple-600 h-2 rounded-full" style="width: 85%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cours similaires -->
            <?php if (!empty($similarCourses)): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Cours similaires</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($similarCourses as $similar): ?>
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-200">
                        <div class="h-48 bg-gradient-to-r from-blue-500 to-purple-600"></div>
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                    <?php echo ucfirst($similar['category']); ?>
                                </span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                    <?php echo $similar['level']; ?>
                                </span>
                            </div>
                            
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <a href="course.php?id=<?php echo $similar['id']; ?>" class="hover:text-blue-600">
                                    <?php echo htmlspecialchars($similar['title']); ?>
                                </a>
                            </h3>
                            
                            <p class="text-gray-600 text-sm mb-4">
                                <?php echo htmlspecialchars(substr($similar['subtitle'], 0, 80)) . '...'; ?>
                            </p>
                            
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <span><i class="fas fa-users mr-1"></i><?php echo $similar['enrolled_students']; ?></span>
                                <span><i class="fas fa-clock mr-1"></i><?php echo $similar['duration']; ?>h</span>
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
</body>
</html>
