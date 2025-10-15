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

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $fullName = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $bio = trim($_POST['bio']);
                
                // Validation
                if (empty($fullName) || empty($email)) {
                    setFlashMessage('error', 'Le nom complet et l\'email sont obligatoires');
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    setFlashMessage('error', 'Format d\'email invalide');
                } else {
                    $pdo = getDB();
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, bio = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$fullName, $email, $bio, $user['id']]);
                    
                    // Mettre à jour les données de session
                    $_SESSION['user_email'] = $email;
                    $user = getUserById($_SESSION['user_id']);
                    
                    setFlashMessage('success', 'Profil mis à jour avec succès');
                }
                break;
                
            case 'change_password':
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                $confirmPassword = $_POST['confirm_password'];
                
                // Validation
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    setFlashMessage('error', 'Tous les champs sont obligatoires');
                } elseif (!password_verify($currentPassword, $user['password'])) {
                    setFlashMessage('error', 'Mot de passe actuel incorrect');
                } elseif ($newPassword !== $confirmPassword) {
                    setFlashMessage('error', 'Les nouveaux mots de passe ne correspondent pas');
                } elseif (strlen($newPassword) < 6) {
                    setFlashMessage('error', 'Le nouveau mot de passe doit contenir au moins 6 caractères');
                } else {
                    $pdo = getDB();
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hashedPassword, $user['id']]);
                    
                    setFlashMessage('success', 'Mot de passe modifié avec succès');
                }
                break;
        }
        
        header('Location: profile.php');
        exit();
    }
}

// Récupérer les statistiques de l'utilisateur
$enrolledCoursesCount = getEnrolledCoursesCount($user['id']);
$studyHours = getStudyHours($user['id']);
$completedResourcesCount = getCompletedResourcesCount($user['id']);
$userCertifications = getUserCertifications($user['id']);

// Récupérer les cours récents
$recentCourses = getEnrolledCourses($user['id'], 5);

// Récupérer les ressources récemment consultées
$recentResources = getRecentlyViewedResources($user['id'], 5);

// Récupérer les certifications obtenues
$obtainedCertifications = [];
foreach ($userCertifications as $cert) {
    $certificationPath = getCertificationPathById($cert['certification_path_id']);
    if ($certificationPath) {
        $obtainedCertifications[] = [
            'id' => $cert['id'],
            'title' => $certificationPath['title'],
            'score' => $cert['score'],
            'issued_at' => $cert['issued_at'],
            'category' => $certificationPath['category'],
            'level' => $certificationPath['level']
        ];
    }
}

// Fonction pour récupérer les détails d'une certification
function getCertificationPathById($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM certification_paths WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo htmlspecialchars($user['full_name']); ?> - Académie IA</title>
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
                <a href="resources.php" class="group flex items-center px-3 py-2 rounded-lg text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
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
                <a href="profile.php" class="group flex items-center px-3 py-2 rounded-lg font-medium text-sm text-blue-600 bg-blue-50 ring-1 ring-blue-100">
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
        <main class="flex-1 ml-64 mt-16 p-4 md:p-8 pb-40">
            <div class="py-4 md:py-6">
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Mon Profil</h1>
                    <p class="text-sm md:text-base text-gray-600">Gérez vos informations personnelles et vos préférences</p>
                </div>

                <!-- Messages flash -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['flash_type'] === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                    <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                </div>
                <?php 
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
                endif; 
                ?>

                <!-- Statistiques utilisateur -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-graduation-cap text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Cours inscrits</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $enrolledCoursesCount; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clock text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Heures d'étude</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $studyHours; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-certificate text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Certifications</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo count($obtainedCertifications); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Informations personnelles -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">Informations personnelles</h2>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="update_profile">
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Nom complet</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Biographie</label>
                                <textarea id="bio" name="bio" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Parlez-nous de vous..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Mettre à jour le profil
                            </button>
                        </form>
                    </div>

                    <!-- Changer le mot de passe -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">Changer le mot de passe</h2>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="change_password">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Mot de passe actuel</label>
                                <input type="password" id="current_password" name="current_password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
                                <input type="password" id="new_password" name="new_password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition-colors">
                                <i class="fas fa-key mr-2"></i>Changer le mot de passe
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Cours récents -->
                <?php if (!empty($recentCourses)): ?>
                <div class="mt-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Mes cours récents</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($recentCourses as $course): ?>
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Progression: <?php echo $course['progress_percentage']; ?>%</span>
                                <a href="course.php?id=<?php echo $course['id']; ?>" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    Continuer
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Certifications obtenues -->
                <?php if (!empty($obtainedCertifications)): ?>
                <div class="mt-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Mes certifications</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($obtainedCertifications as $cert): ?>
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($cert['title']); ?></h3>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                    Score: <?php echo $cert['score']; ?>%
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-4"><?php echo ucfirst($cert['category']); ?> - <?php echo ucfirst($cert['level']); ?></p>
                            <div class="text-sm text-gray-500">
                                Obtenue le <?php echo date('d/m/Y', strtotime($cert['issued_at'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Toggle mobile menu when clicking the hamburger button
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', toggleMobileMenu);
            }
        });
    </script>
</body>
</html>
