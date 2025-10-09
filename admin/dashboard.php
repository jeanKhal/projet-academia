<?php
session_start();
require_once '../includes/functions.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!isLoggedIn()) {
    header('Location: ../login-admin.php');
    exit();
}

$user = getUserById($_SESSION['user_id']);

// Vérification stricte du rôle admin
if ($user['role'] !== 'admin') {
    // Rediriger les étudiants vers leur dashboard
    if ($user['role'] === 'student') {
        header('Location: ../dashboard.php');
        exit();
    }
    // Pour tout autre rôle, rediriger vers la page de connexion
    header('Location: ../login-admin.php');
    exit();
}

$pdo = getDB();

// Statistiques générales
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_students' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
    'total_courses' => $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
    'total_resources' => $pdo->query("SELECT COUNT(*) FROM resources")->fetchColumn(),
    'active_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
    'inactive_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 0")->fetchColumn()
];





// Activités récentes
$recent_activities = $pdo->query("
    SELECT 'user_registration' as type, u.full_name, u.created_at as date
    FROM users u
    WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'course_enrollment' as type, u.full_name, ce.enrolled_at as date
    FROM course_enrollments ce
    JOIN users u ON ce.user_id = u.id
    WHERE ce.enrolled_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY date DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrateur - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 overflow-x-hidden">
    <!-- Navigation Admin -->
    <?php include 'includes/header.php'; ?>

    <div class="flex">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Contenu principal -->
        <div class="flex-1 p-3 md:p-6 mt-16 pb-20">
            <div class="mb-6">
                <h1 class="text-xl md:text-2xl font-bold text-gray-900">Dashboard Administrateur</h1>
                <p class="text-xs md:text-sm text-gray-600">Vue d'ensemble de la plateforme Académie IA</p>
            </div>

            <!-- Statistiques principales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="p-2.5 bg-blue-100 rounded-lg">
                            <i class="fas fa-users text-blue-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-gray-600">Total Utilisateurs</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $stats['total_users']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="p-2.5 bg-green-100 rounded-lg">
                            <i class="fas fa-user-graduate text-green-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-gray-600">Étudiants</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $stats['total_students']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="p-2.5 bg-purple-100 rounded-lg">
                            <i class="fas fa-graduation-cap text-purple-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-gray-600">Cours</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $stats['total_courses']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="p-2.5 bg-yellow-100 rounded-lg">
                            <i class="fas fa-book text-yellow-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-gray-600">Ressources</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $stats['total_resources']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="p-2.5 bg-green-100 rounded-lg">
                            <i class="fas fa-user-check text-green-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-gray-600">Utilisateurs Actifs</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $stats['active_users']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="p-2.5 bg-red-100 rounded-lg">
                            <i class="fas fa-user-times text-red-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-gray-600">Utilisateurs Inactifs</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $stats['inactive_users']; ?></p>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Actions rapides -->
            <div class="mt-6 bg-white rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-900">
                        <i class="fas fa-bolt mr-2 text-orange-600"></i>
                        Actions Rapides
                    </h2>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <a href="users.php" class="flex flex-col items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <i class="fas fa-users text-blue-600 text-xl mb-1.5"></i>
                            <span class="font-medium text-gray-900 text-center text-xs">Gérer les utilisateurs</span>
                        </a>
                        <a href="courses.php" class="flex flex-col items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                            <i class="fas fa-graduation-cap text-green-600 text-xl mb-1.5"></i>
                            <span class="font-medium text-gray-900 text-center text-xs">Gérer les cours</span>
                        </a>
                        <a href="resources.php" class="flex flex-col items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                            <i class="fas fa-book text-purple-600 text-xl mb-1.5"></i>
                            <span class="font-medium text-gray-900 text-center text-xs">Gérer les ressources</span>
                        </a>
                        <a href="settings.php" class="flex flex-col items-center p-3 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                            <i class="fas fa-cog text-orange-600 text-xl mb-1.5"></i>
                            <span class="font-medium text-gray-900 text-center text-xs">Paramètres</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
