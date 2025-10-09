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

// Récupérer les ressources de la certification
function getCertificationResources($certificationId, $userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT r.*, 
               CASE 
                   WHEN ur.user_id IS NOT NULL THEN 'completed'
                   WHEN urv.user_id IS NOT NULL THEN 'viewed'
                   ELSE 'not_started'
               END as status
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

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'start_certification':
                if (!$progress) {
                    $pdo = getDB();
                    $stmt = $pdo->prepare("
                        INSERT INTO user_progress (user_id, certification_path_id, progress_percentage, resources_completed)
                        VALUES (?, ?, 0, 0)
                    ");
                    $stmt->execute([$user['id'], $certificationId]);
                    $progress = getUserCertificationProgress($user['id'], $certificationId);
                }
                break;
                
            case 'complete_resource':
                $resourceId = (int)$_POST['resource_id'];
                $pdo = getDB();
                
                // Marquer la ressource comme complétée
                $stmt = $pdo->prepare("
                    INSERT INTO user_resources (user_id, resource_id, completed_at) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE completed_at = NOW()
                ");
                $stmt->execute([$user['id'], $resourceId]);
                
                // Mettre à jour la progression
                $completedResources = $pdo->prepare("
                    SELECT COUNT(*) FROM user_resources ur
                    JOIN resources r ON ur.resource_id = r.id
                    WHERE ur.user_id = ? AND r.certification_path_id = ?
                ");
                $completedResources->execute([$user['id'], $certificationId]);
                $completedCount = $completedResources->fetchColumn();
                
                $progressPercentage = count($resources) > 0 ? round(($completedCount / count($resources)) * 100) : 0;
                
                $stmt = $pdo->prepare("
                    UPDATE user_progress 
                    SET progress_percentage = ?, resources_completed = ?
                    WHERE user_id = ? AND certification_path_id = ?
                ");
                $stmt->execute([$progressPercentage, $completedCount, $user['id'], $certificationId]);
                
                $progress = getUserCertificationProgress($user['id'], $certificationId);
                $resources = getCertificationResources($certificationId, $user['id']);
                break;
        }
    }
}

// Vérifier si l'utilisateur a déjà obtenu cette certification
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
    <title><?php echo htmlspecialchars($certification['title']); ?> - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Contenu principal -->
    <main class="flex-1 mt-16 p-4 md:p-8 pb-48">
        <div class="max-w-7xl mx-auto">
        <!-- En-tête de la certification -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-8">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-4">
                            <a href="certifications.php" class="text-blue-200 hover:text-white mr-4">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($certification['title']); ?></h1>
                        </div>
                        <p class="text-blue-100 text-lg mb-4"><?php echo htmlspecialchars($certification['description']); ?></p>
                        <div class="flex items-center space-x-6">
                            <div class="flex items-center">
                                <i class="fas fa-clock mr-2"></i>
                                <span><?php echo $certification['estimated_hours']; ?>h estimées</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-book mr-2"></i>
                                <span><?php echo $certification['resources_count']; ?> ressources</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-users mr-2"></i>
                                <span><?php echo $certification['enrolled_users']; ?> certifiés</span>
                            </div>
                            <span class="px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm">
                                <?php 
                                $levelTranslations = [
                                    'beginner' => 'Débutant',
                                    'intermediate' => 'Intermédiaire', 
                                    'advanced' => 'Avancé'
                                ];
                                echo $levelTranslations[$certification['level']] ?? ucfirst($certification['level']);
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-certificate text-4xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Progression -->
            <?php if ($progress || $hasCertification): ?>
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Votre progression</h2>
                    <?php if ($progress): ?>
                    <span class="text-2xl font-bold text-blue-600"><?php echo $progress['progress_percentage']; ?>%</span>
                    <?php endif; ?>
                </div>
                <?php if ($progress): ?>
                <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full transition-all duration-500" 
                         style="width: <?php echo $progress['progress_percentage']; ?>%"></div>
                </div>
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <span><?php echo $progress['resources_completed']; ?> / <?php echo $progress['total_resources'] ?? count($resources); ?> ressources complétées</span>
                    <span>Dernière activité : <?php echo isset($progress['last_activity']) && $progress['last_activity'] ? date('d/m/Y', strtotime($progress['last_activity'])) : '-'; ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Contenu principal -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Liste des ressources -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-list mr-2"></i>
                            Ressources d'apprentissage
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($resources)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-book-open text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600">Aucune ressource disponible pour cette certification.</p>
                        </div>
                        <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($resources as $index => $resource): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-4 
                                                    <?php 
                                                    if ($resource['status'] === 'completed') echo 'bg-green-500';
                                                    elseif ($resource['status'] === 'viewed') echo 'bg-blue-500';
                                                    else echo 'bg-gray-300';
                                                    ?>">
                                            <i class="fas fa-<?php echo getResourceTypeIcon($resource['type']); ?> text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($resource['title']); ?></h3>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars(substr($resource['description'], 0, 80)) . '...'; ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <?php if ($resource['status'] === 'completed'): ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                            <i class="fas fa-check mr-1"></i>Terminé
                                        </span>
                                        <?php elseif ($resource['status'] === 'viewed'): ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                            <i class="fas fa-eye mr-1"></i>Vu
                                        </span>
                                        <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                            <i class="fas fa-clock mr-1"></i>En attente
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                                        <span><i class="fas fa-tag mr-1"></i><?php echo ucfirst($resource['type']); ?></span>
                                        <span><i class="fas fa-clock mr-1"></i><?php echo $resource['estimated_time'] ?? '5'; ?> min</span>
                                        <span><i class="fas fa-signal mr-1"></i>
                                            <?php 
                                            $levelTranslations = [
                                                'beginner' => 'Débutant',
                                                'intermediate' => 'Intermédiaire', 
                                                'advanced' => 'Avancé'
                                            ];
                                            echo $levelTranslations[$resource['level'] ?? 'intermediate'] ?? ucfirst($resource['level'] ?? 'intermediate');
                                            ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="resource.php?id=<?php echo $resource['id']; ?>" 
                                           class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-play mr-2"></i>
                                            <?php echo $resource['status'] === 'completed' ? 'Revoir' : 'Commencer'; ?>
                                        </a>
                                        <?php if ($resource['status'] !== 'completed'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="complete_resource">
                                            <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                            <button type="submit" 
                                                    class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                                <i class="fas fa-check mr-2"></i>
                                                Marquer comme terminé
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Panneau latéral -->
            <div class="space-y-6">
                <!-- Actions -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Actions</h3>
                    </div>
                    <div class="p-6">
                        <?php if ($hasCertification): ?>
                        <div class="text-center py-4">
                            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-trophy text-white text-2xl"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">Certification obtenue !</h4>
                            <p class="text-gray-600 text-sm">Félicitations ! Vous avez complété cette certification.</p>
                        </div>
                        <?php elseif (!$progress): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="start_certification">
                            <button type="submit" 
                                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                <i class="fas fa-play mr-2"></i>
                                Commencer la certification
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="space-y-3">
                            <a href="certification-exam.php?id=<?php echo $certificationId; ?>" 
                               class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition-colors font-medium text-center block">
                                <i class="fas fa-graduation-cap mr-2"></i>
                                Passer l'examen
                            </a>
                            <a href="certification-progress.php?id=<?php echo $certificationId; ?>" 
                               class="w-full bg-purple-600 text-white py-3 px-4 rounded-lg hover:bg-purple-700 transition-colors font-medium text-center block">
                                <i class="fas fa-chart-line mr-2"></i>
                                Voir le détail
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Informations -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Informations</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Niveau</span>
                                <span class="font-medium">
                                    <?php 
                                    $levelTranslations = [
                                        'beginner' => 'Débutant',
                                        'intermediate' => 'Intermédiaire', 
                                        'advanced' => 'Avancé'
                                    ];
                                    echo $levelTranslations[$certification['level']] ?? ucfirst($certification['level']);
                                    ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Catégorie</span>
                                <span class="font-medium"><?php echo ucfirst($certification['category']); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Durée estimée</span>
                                <span class="font-medium"><?php echo $certification['estimated_hours']; ?>h</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Ressources</span>
                                <span class="font-medium"><?php echo $certification['resources_count']; ?></span>
                            </div>
                            <?php if ($progress && isset($progress['started_at']) && $progress['started_at']): ?>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Démarré le</span>
                                <span class="font-medium"><?php echo date('d/m/Y', strtotime($progress['started_at'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Conseils -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">
                        <i class="fas fa-lightbulb text-blue-600 mr-2"></i>
                        Conseils
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                            <span>Suivez les ressources dans l'ordre recommandé</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                            <span>Prenez des notes pendant votre apprentissage</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                            <span>Pratiquez régulièrement pour consolider vos connaissances</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                            <span>Passez l'examen seulement quand vous vous sentez prêt</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>
