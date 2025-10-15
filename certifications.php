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

// Récupérer la progression de l'utilisateur
function getUserCertificationProgress($userId, $certificationPathId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = ? AND certification_path_id = ?");
    $stmt->execute([$userId, $certificationPathId]);
    return $stmt->fetch();
}

$certificationPaths = getCertificationPaths(6);
$userCertifications = $isLoggedIn ? getUserCertifications($user['id']) : [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certifications - Académie IA</title>
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
                <a href="certifications.php" class="group flex items-center px-3 py-2 rounded-lg font-medium text-sm text-blue-600 bg-blue-50 ring-1 ring-blue-100">
                    <i class="fas fa-certificate mr-2.5 w-4 text-blue-600"></i>
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
        <main class="flex-1 ml-64 mt-16 p-4 md:p-8 pb-24">
            <div class="py-4 md:py-6">
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Certifications</h1>
                    <p class="text-sm md:text-base text-gray-600">Validez vos compétences dans de nombreux domaines avec nos certifications reconnues</p>
                </div>

                <!-- Mes Certifications -->
                <?php if ($isLoggedIn && !empty($userCertifications)): ?>
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Mes Certifications</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($userCertifications as $cert): ?>
                        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($cert['title']); ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($cert['category'] ?? 'Certification'); ?></p>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                    <?php
                                    $levelTranslations = [
                                        'beginner' => 'Débutant',
                                        'intermediate' => 'Intermédiaire',
                                        'advanced' => 'Avancé'
                                    ];
                                    echo $levelTranslations[$cert['level']] ?? ucfirst($cert['level']);
                                    ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 mb-4">
                                <p><?php echo htmlspecialchars(substr($cert['description'], 0, 100)) . '...'; ?></p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Obtenue le <?php echo date('d/m/Y', strtotime($cert['completed_at'])); ?></span>
                                <a href="certification.php?id=<?php echo $cert['id']; ?>" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    Voir détails
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Parcours de Certification -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Parcours de Certification</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($certificationPaths as $path): ?>
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-200 flex flex-col">
                            <div class="p-6 flex-1 flex flex-col">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($path['title']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($path['category'] ?? 'Certification'); ?></p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                        <?php
                                        $levelTranslations = [
                                            'beginner' => 'Débutant',
                                            'intermediate' => 'Intermédiaire',
                                            'advanced' => 'Avancé'
                                        ];
                                        echo $levelTranslations[$path['level']] ?? ucfirst($path['level']);
                                        ?>
                                    </span>
                                </div>
                                
                                <p class="text-sm text-gray-600 mb-4 flex-1"><?php echo htmlspecialchars(substr($path['description'], 0, 120)) . '...'; ?></p>
                                
                                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                    <span><i class="fas fa-clock mr-1"></i><?php echo $path['estimated_duration'] ?? '10'; ?> heures</span>
                                    <span><i class="fas fa-users mr-1"></i><?php echo $path['enrolled_users'] ?? '0'; ?> étudiants</span>
                                </div>
                                
                                <div class="mt-auto">
                                    <?php if ($isLoggedIn): ?>
                                        <?php 
                                        $progress = getUserCertificationProgress($user['id'], $path['id']);
                                        if ($progress): 
                                        ?>
                                            <div class="mb-3">
                                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                                    <span>Progression</span>
                                                    <span><?php echo $progress['progress_percentage']; ?>%</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $progress['progress_percentage']; ?>%"></div>
                                                </div>
                                            </div>
                                            <a href="certification.php?id=<?php echo $path['id']; ?>" class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded hover:bg-blue-700 transition-colors">
                                                Continuer
                                            </a>
                                        <?php else: ?>
                                            <a href="certification.php?id=<?php echo $path['id']; ?>" class="start-cert-btn block w-full bg-blue-600 text-white text-center py-2 px-4 rounded hover:bg-blue-700 transition-colors" data-cert-id="<?php echo $path['id']; ?>">
                                                Commencer
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="login.php" class="block w-full bg-gray-600 text-white text-center py-2 px-4 rounded hover:bg-gray-700 transition-colors">
                                            Se connecter pour commencer
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Modal: Aucune formation achevée -->
    <div id="noCompletionModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50" data-close="modal"></div>
        <div class="relative mx-auto mt-24 w-11/12 max-w-sm sm:max-w-md">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-5 sm:p-6 flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-r from-blue-600 to-purple-600 text-white flex items-center justify-center mr-3">
                        <i class="fas fa-info"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900">Action indisponible</h3>
                        <p class="mt-1 text-sm text-gray-600">Aucune formation n'a été achevée pour l'instant. Terminez un parcours pour accéder à la certification.</p>
                    </div>
                    <button type="button" class="ml-3 text-gray-400 hover:text-gray-600" aria-label="Fermer" title="Fermer" data-close="modal">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                <div class="px-5 pb-5 sm:px-6 sm:pb-6">
                    <button type="button" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg transition-colors" data-close="modal">Compris</button>
                </div>
            </div>
        </div>
    </div>

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

            // Intercepter les clics sur "Commencer" pour afficher le modal
            document.querySelectorAll('.start-cert-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openNoCompletionModal();
                });
            });
        });

        function openNoCompletionModal() {
            const modal = document.getElementById('noCompletionModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            const card = modal.querySelector('.rounded-2xl');
            if (card) {
                card.style.transform = 'translateY(8px)';
                card.style.opacity = '0';
                requestAnimationFrame(() => {
                    card.style.transition = 'transform 200ms ease, opacity 200ms ease';
                    card.style.transform = 'translateY(0)';
                    card.style.opacity = '1';
                });
            }
        }

        function closeNoCompletionModal() {
            const modal = document.getElementById('noCompletionModal');
            if (!modal) return;
            const card = modal.querySelector('.rounded-2xl');
            if (card) {
                card.style.transform = 'translateY(8px)';
                card.style.opacity = '0';
                setTimeout(() => modal.classList.add('hidden'), 180);
            } else {
                modal.classList.add('hidden');
            }
        }

        document.addEventListener('click', function(e) {
            if (e.target.closest('[data-close="modal"]')) {
                closeNoCompletionModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeNoCompletionModal();
            }
        });
    </script>
</body>
</html>
