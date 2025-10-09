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

// Récupérer l'ID de la certification depuis l'URL
$certificationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$certificationId) {
    header('Location: certifications.php');
    exit();
}

// Récupérer les détails de la certification
function getCertificationPathById($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT cp.*, 
               (SELECT COUNT(*) FROM resources WHERE certification_path_id = cp.id) as resources_count,
               (SELECT COUNT(*) FROM user_certifications WHERE certification_path_id = cp.id) as enrolled_users
        FROM certification_paths cp 
        WHERE cp.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

$certification = getCertificationPathById($certificationId);

if (!$certification) {
    header('Location: certifications.php');
    exit();
}

// Récupérer la progression de l'utilisateur
function getUserCertificationProgress($userId, $certificationPathId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = ? AND certification_path_id = ?");
    $stmt->execute([$userId, $certificationPathId]);
    return $stmt->fetch();
}

$progress = getUserCertificationProgress($user['id'], $certificationId);

// Récupérer les ressources de la certification avec le statut de l'utilisateur
function getCertificationResources($certificationId, $userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT r.*, 
               CASE 
                   WHEN ur.user_id IS NOT NULL THEN 'completed'
                   WHEN urv.user_id IS NOT NULL THEN 'viewed'
                   ELSE 'not_started'
               END as status,
               ur.completed_at,
               urv.viewed_at,
               urv.time_spent_minutes
        FROM resources r
        LEFT JOIN user_resources ur ON r.id = ur.resource_id AND ur.user_id = ?
        LEFT JOIN resource_views urv ON r.id = urv.resource_id AND urv.user_id = ?
        WHERE r.certification_path_id = ?
        ORDER BY r.order_index, r.id
    ");
    $stmt->execute([$userId, $userId, $certificationId]);
    return $stmt->fetchAll();
}

$resources = getCertificationResources($certificationId, $user['id']);

// Récupérer l'historique des activités
function getUserActivityHistory($userId, $certificationId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        (SELECT 'resource_completed' as type, ur.completed_at as date, r.title as description, r.type as resource_type
         FROM user_resources ur
         JOIN resources r ON ur.resource_id = r.id
         WHERE ur.user_id = ? AND r.certification_path_id = ?)
        UNION ALL
        (SELECT 'resource_viewed' as type, urv.viewed_at as date, r.title as description, r.type as resource_type
         FROM resource_views urv
         JOIN resources r ON urv.resource_id = r.id
         WHERE urv.user_id = ? AND r.certification_path_id = ? AND urv.completed = 1)
        ORDER BY date DESC
        LIMIT 20
    ");
    $stmt->execute([$userId, $certificationId, $userId, $certificationId]);
    return $stmt->fetchAll();
}

$activityHistory = getUserActivityHistory($user['id'], $certificationId);

// Calculer les statistiques
$totalResources = count($resources);
$completedResources = 0;
$viewedResources = 0;
$totalTimeSpent = 0;

foreach ($resources as $resource) {
    if ($resource['status'] === 'completed') {
        $completedResources++;
    } elseif ($resource['status'] === 'viewed') {
        $viewedResources++;
    }
    $totalTimeSpent += $resource['time_spent_minutes'] ?? 0;
}

$completionRate = $totalResources > 0 ? round(($completedResources / $totalResources) * 100) : 0;
$engagementRate = $totalResources > 0 ? round((($completedResources + $viewedResources) / $totalResources) * 100) : 0;

// Vérifier si l'utilisateur a obtenu cette certification
$hasCertification = false;
$userCertifications = getUserCertifications($user['id']);
foreach ($userCertifications as $cert) {
    if ($cert['certification_path_id'] == $certificationId) {
        $hasCertification = true;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progression - <?php echo htmlspecialchars($certification['title']); ?> - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Contenu principal -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- En-tête de la progression -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-green-600 to-teal-600 text-white p-8">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-4">
                            <a href="certification.php?id=<?php echo $certificationId; ?>" class="text-green-200 hover:text-white mr-4">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <h1 class="text-3xl font-bold">Progression détaillée</h1>
                        </div>
                        <h2 class="text-xl text-green-100 mb-4"><?php echo htmlspecialchars($certification['title']); ?></h2>
                        <div class="flex items-center space-x-6">
                            <div class="flex items-center">
                                <i class="fas fa-chart-line mr-2"></i>
                                <span><?php echo $completionRate; ?>% complété</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock mr-2"></i>
                                <span><?php echo $totalTimeSpent; ?> min passées</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-book mr-2"></i>
                                <span><?php echo $completedResources; ?>/<?php echo $totalResources; ?> ressources</span>
                            </div>
                        </div>
                    </div>
                    <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-chart-bar text-4xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Taux de complétion -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-percentage text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-900"><?php echo $completionRate; ?>%</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Taux de complétion</h3>
                <p class="text-gray-600 text-sm">Ressources terminées</p>
            </div>

            <!-- Taux d'engagement -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-eye text-green-600 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-900"><?php echo $engagementRate; ?>%</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Taux d'engagement</h3>
                <p class="text-gray-600 text-sm">Ressources consultées</p>
            </div>

            <!-- Temps passé -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-900"><?php echo $totalTimeSpent; ?>m</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Temps passé</h3>
                <p class="text-gray-600 text-sm">Minutes d'apprentissage</p>
            </div>

            <!-- Statut -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 <?php echo $hasCertification ? 'bg-yellow-100' : 'bg-gray-100'; ?> rounded-lg flex items-center justify-center">
                        <i class="fas fa-trophy <?php echo $hasCertification ? 'text-yellow-600' : 'text-gray-600'; ?> text-xl"></i>
                    </div>
                    <span class="text-lg font-bold <?php echo $hasCertification ? 'text-yellow-600' : 'text-gray-600'; ?>">
                        <?php echo $hasCertification ? 'Certifié' : 'En cours'; ?>
                    </span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Statut</h3>
                <p class="text-gray-600 text-sm">
                    <?php echo $hasCertification ? 'Certification obtenue' : 'Certification en cours'; ?>
                </p>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Graphique de progression -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-chart-line mr-2"></i>
                            Évolution de la progression
                        </h3>
                    </div>
                    <div class="p-6">
                        <canvas id="progressChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Détails des ressources -->
            <div class="space-y-6">
                <!-- Résumé des ressources -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-list mr-2"></i>
                            Résumé des ressources
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Terminées</span>
                                <span class="font-semibold text-green-600"><?php echo $completedResources; ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Consultées</span>
                                <span class="font-semibold text-blue-600"><?php echo $viewedResources; ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">En attente</span>
                                <span class="font-semibold text-gray-600"><?php echo $totalResources - $completedResources - $viewedResources; ?></span>
                            </div>
                            <div class="flex items-center justify-between border-t pt-4">
                                <span class="text-gray-900 font-semibold">Total</span>
                                <span class="font-semibold text-gray-900"><?php echo $totalResources; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-bolt mr-2"></i>
                            Actions rapides
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <a href="certification.php?id=<?php echo $certificationId; ?>" 
                               class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium text-center block">
                                <i class="fas fa-play mr-2"></i>
                                Continuer l'apprentissage
                            </a>
                            
                            <?php if ($progress && $progress['progress_percentage'] >= 80 && !$hasCertification): ?>
                            <a href="certification-exam.php?id=<?php echo $certificationId; ?>" 
                               class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition-colors font-medium text-center block">
                                <i class="fas fa-graduation-cap mr-2"></i>
                                Passer l'examen
                            </a>
                            <?php endif; ?>
                            
                            <a href="certifications.php" 
                               class="w-full bg-gray-600 text-white py-3 px-4 rounded-lg hover:bg-gray-700 transition-colors font-medium text-center block">
                                <i class="fas fa-list mr-2"></i>
                                Voir toutes les certifications
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique des activités -->
        <div class="mt-8">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">
                        <i class="fas fa-history mr-2"></i>
                        Historique des activités
                    </h3>
                </div>
                <div class="p-6">
                    <?php if (empty($activityHistory)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-clock text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">Aucune activité enregistrée pour le moment.</p>
                        <p class="text-gray-500 text-sm mt-2">Commencez à explorer les ressources pour voir votre historique.</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($activityHistory as $activity): ?>
                        <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center mr-4
                                        <?php echo $activity['type'] === 'resource_completed' ? 'bg-green-100' : 'bg-blue-100'; ?>">
                                <i class="fas fa-<?php echo $activity['type'] === 'resource_completed' ? 'check' : 'eye'; ?> 
                                             <?php echo $activity['type'] === 'resource_completed' ? 'text-green-600' : 'text-blue-600'; ?>"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($activity['description']); ?></h4>
                                <p class="text-sm text-gray-600">
                                    <?php echo $activity['type'] === 'resource_completed' ? 'Ressource terminée' : 'Ressource consultée'; ?>
                                    • <?php echo date('d/m/Y à H:i', strtotime($activity['date'])); ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                           <?php echo $activity['resource_type'] === 'video' ? 'bg-red-100 text-red-800' : 
                                                   ($activity['resource_type'] === 'document' ? 'bg-blue-100 text-blue-800' : 
                                                   'bg-gray-100 text-gray-800'); ?>">
                                    <?php echo ucfirst($activity['resource_type']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Graphique de progression
        const ctx = document.getElementById('progressChart').getContext('2d');
        
        // Données simulées pour le graphique (dans un vrai projet, ces données viendraient de la base de données)
        const progressData = {
            labels: ['Semaine 1', 'Semaine 2', 'Semaine 3', 'Semaine 4'],
            datasets: [{
                label: 'Progression (%)',
                data: [0, 25, 60, <?php echo $completionRate; ?>],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };

        new Chart(ctx, {
            type: 'line',
            data: progressData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>
