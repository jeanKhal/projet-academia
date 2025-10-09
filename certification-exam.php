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

// Vérifier la progression de l'utilisateur
function getUserCertificationProgress($userId, $certificationPathId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = ? AND certification_path_id = ?");
    $stmt->execute([$userId, $certificationPathId]);
    return $stmt->fetch();
}

$progress = getUserCertificationProgress($user['id'], $certificationId);

// Vérifier si l'utilisateur peut passer l'examen (progression > 80%)
if (!$progress || $progress['progress_percentage'] < 80) {
    setFlashMessage('warning', 'Vous devez compléter au moins 80% de la certification avant de pouvoir passer l\'examen.');
    header('Location: certification.php?id=' . $certificationId);
    exit();
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

if ($hasCertification) {
    setFlashMessage('info', 'Vous avez déjà obtenu cette certification.');
    header('Location: certification.php?id=' . $certificationId);
    exit();
}

// Questions d'examen (pour l'instant, questions statiques basées sur la catégorie)
function getExamQuestions($certificationId, $category) {
    $questions = [];
    
    switch ($category) {
        case 'artificial-intelligence':
            $questions = [
                [
                    'id' => 1,
                    'question' => 'Qu\'est-ce que l\'intelligence artificielle ?',
                    'options' => [
                        'A' => 'Un type d\'ordinateur',
                        'B' => 'La simulation de l\'intelligence humaine par des machines',
                        'C' => 'Un langage de programmation',
                        'D' => 'Un algorithme de tri'
                    ],
                    'correct' => 'B'
                ],
                [
                    'id' => 2,
                    'question' => 'Quel algorithme est utilisé pour la recherche dans un arbre de décision ?',
                    'options' => [
                        'A' => 'Bubble Sort',
                        'B' => 'Depth-First Search',
                        'C' => 'Quick Sort',
                        'D' => 'Binary Search'
                    ],
                    'correct' => 'B'
                ],
                [
                    'id' => 3,
                    'question' => 'Qu\'est-ce qu\'un système expert ?',
                    'options' => [
                        'A' => 'Un ordinateur très rapide',
                        'B' => 'Un système qui reproduit l\'expertise humaine',
                        'C' => 'Un type de réseau neuronal',
                        'D' => 'Un algorithme de machine learning'
                    ],
                    'correct' => 'B'
                ],
                [
                    'id' => 4,
                    'question' => 'Quel est l\'objectif principal du machine learning ?',
                    'options' => [
                        'A' => 'Créer des jeux vidéo',
                        'B' => 'Permettre aux machines d\'apprendre sans être explicitement programmées',
                        'C' => 'Optimiser les performances des ordinateurs',
                        'D' => 'Créer des interfaces utilisateur'
                    ],
                    'correct' => 'B'
                ],
                [
                    'id' => 5,
                    'question' => 'Qu\'est-ce que la logique floue ?',
                    'options' => [
                        'A' => 'Un type de logique binaire',
                        'B' => 'Une logique qui traite des valeurs de vérité partielles',
                        'C' => 'Un algorithme de cryptage',
                        'D' => 'Un système de base de données'
                    ],
                    'correct' => 'B'
                ]
            ];
            break;
            
        case 'machine-learning':
            $questions = [
                [
                    'id' => 1,
                    'question' => 'Qu\'est-ce que la régression linéaire ?',
                    'options' => [
                        'A' => 'Un algorithme de classification',
                        'B' => 'Un modèle qui prédit une valeur continue',
                        'C' => 'Un type de clustering',
                        'D' => 'Un algorithme de deep learning'
                    ],
                    'correct' => 'B'
                ],
                [
                    'id' => 2,
                    'question' => 'Qu\'est-ce que le surapprentissage (overfitting) ?',
                    'options' => [
                        'A' => 'Quand le modèle apprend trop bien les données d\'entraînement',
                        'B' => 'Quand le modèle n\'apprend pas assez',
                        'C' => 'Quand le modèle est trop simple',
                        'D' => 'Quand le modèle utilise trop de données'
                    ],
                    'correct' => 'A'
                ],
                [
                    'id' => 3,
                    'question' => 'Qu\'est-ce que la validation croisée ?',
                    'options' => [
                        'A' => 'Une technique pour diviser les données en ensembles d\'entraînement et de test',
                        'B' => 'Un type d\'algorithme de machine learning',
                        'C' => 'Une méthode de normalisation des données',
                        'D' => 'Un type de réseau neuronal'
                    ],
                    'correct' => 'A'
                ],
                [
                    'id' => 4,
                    'question' => 'Qu\'est-ce que le clustering ?',
                    'options' => [
                        'A' => 'Un algorithme de classification supervisée',
                        'B' => 'Un algorithme de classification non supervisée',
                        'C' => 'Un type de régression',
                        'D' => 'Un algorithme de deep learning'
                    ],
                    'correct' => 'B'
                ],
                [
                    'id' => 5,
                    'question' => 'Qu\'est-ce que la normalisation des données ?',
                    'options' => [
                        'A' => 'Supprimer les données manquantes',
                        'B' => 'Mettre les données à la même échelle',
                        'C' => 'Diviser les données en ensembles',
                        'D' => 'Créer de nouvelles features'
                    ],
                    'correct' => 'B'
                ]
            ];
            break;
            
        default:
            $questions = [
                [
                    'id' => 1,
                    'question' => 'Qu\'est-ce que la programmation orientée objet ?',
                    'options' => [
                        'A' => 'Un paradigme de programmation basé sur les objets',
                        'B' => 'Un type de base de données',
                        'C' => 'Un algorithme de tri',
                        'D' => 'Un langage de programmation'
                    ],
                    'correct' => 'A'
                ],
                [
                    'id' => 2,
                    'question' => 'Qu\'est-ce qu\'une classe en POO ?',
                    'options' => [
                        'A' => 'Un objet',
                        'B' => 'Un modèle pour créer des objets',
                        'C' => 'Une fonction',
                        'D' => 'Une variable'
                    ],
                    'correct' => 'B'
                ],
                [
                    'id' => 3,
                    'question' => 'Qu\'est-ce que l\'héritage ?',
                    'options' => [
                        'A' => 'Créer un nouvel objet',
                        'B' => 'Permettre à une classe d\'hériter des propriétés d\'une autre',
                        'C' => 'Supprimer un objet',
                        'D' => 'Modifier un objet'
                    ],
                    'correct' => 'B'
                ],
                [
                    'id' => 4,
                    'question' => 'Qu\'est-ce que l\'encapsulation ?',
                    'options' => [
                        'A' => 'Cacher les détails d\'implémentation',
                        'B' => 'Créer plusieurs objets',
                        'C' => 'Supprimer des données',
                        'D' => 'Modifier des données'
                    ],
                    'correct' => 'A'
                ],
                [
                    'id' => 5,
                    'question' => 'Qu\'est-ce que le polymorphisme ?',
                    'options' => [
                        'A' => 'Avoir plusieurs formes pour une même interface',
                        'B' => 'Créer des objets',
                        'C' => 'Supprimer des objets',
                        'D' => 'Modifier des objets'
                    ],
                    'correct' => 'A'
                ]
            ];
            break;
    }
    
    return $questions;
}

$questions = getExamQuestions($certificationId, $certification['category']);

// Traitement de la soumission de l'examen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    $score = 0;
    $totalQuestions = count($questions);
    
    foreach ($questions as $question) {
        $answer = $_POST['question_' . $question['id']] ?? '';
        if ($answer === $question['correct']) {
            $score++;
        }
    }
    
    $percentage = round(($score / $totalQuestions) * 100);
    
    // Seuil de réussite : 70%
    if ($percentage >= 70) {
        // Créer la certification
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO user_certifications (user_id, certification_path_id, score, issued_at, is_active)
            VALUES (?, ?, ?, NOW(), 1)
        ");
        $stmt->execute([$user['id'], $certificationId, $percentage]);
        
        setFlashMessage('success', 'Félicitations ! Vous avez obtenu votre certification avec un score de ' . $percentage . '%.');
        header('Location: certification.php?id=' . $certificationId);
        exit();
    } else {
        setFlashMessage('error', 'Désolé, vous n\'avez pas obtenu le score minimum requis (70%). Votre score : ' . $percentage . '%. Vous pouvez réessayer.');
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examen - <?php echo htmlspecialchars($certification['title']); ?> - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Contenu principal -->
    <main class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- En-tête de l'examen -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-red-600 to-orange-600 text-white p-8">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-4">
                            <a href="certification.php?id=<?php echo $certificationId; ?>" class="text-red-200 hover:text-white mr-4">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <h1 class="text-3xl font-bold">Examen de certification</h1>
                        </div>
                        <h2 class="text-xl text-red-100 mb-4"><?php echo htmlspecialchars($certification['title']); ?></h2>
                        <div class="flex items-center space-x-6">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle mr-2"></i>
                                <span><?php echo count($questions); ?> questions</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock mr-2"></i>
                                <span>30 minutes</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-percentage mr-2"></i>
                                <span>70% minimum pour réussir</span>
                            </div>
                        </div>
                    </div>
                    <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-4xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">
                <i class="fas fa-info-circle mr-2"></i>
                Instructions importantes
            </h3>
            <ul class="space-y-2 text-blue-800">
                <li class="flex items-start">
                    <i class="fas fa-check text-blue-600 mr-2 mt-1"></i>
                    <span>Lisez attentivement chaque question avant de répondre</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-blue-600 mr-2 mt-1"></i>
                    <span>Vous devez répondre à toutes les questions</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-blue-600 mr-2 mt-1"></i>
                    <span>Une seule réponse par question</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-blue-600 mr-2 mt-1"></i>
                    <span>Vous ne pouvez pas revenir en arrière une fois l'examen soumis</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-blue-600 mr-2 mt-1"></i>
                    <span>Score minimum requis : 70%</span>
                </li>
            </ul>
        </div>

        <!-- Formulaire d'examen -->
        <form method="POST" class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-edit mr-2"></i>
                    Questions de l'examen
                </h3>
            </div>
            
            <div class="p-6">
                <?php foreach ($questions as $index => $question): ?>
                <div class="mb-8 p-6 border border-gray-200 rounded-lg">
                    <div class="flex items-start mb-4">
                        <span class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-medium mr-4 mt-1">
                            <?php echo $index + 1; ?>
                        </span>
                        <div class="flex-1">
                            <h4 class="text-lg font-medium text-gray-900 mb-4"><?php echo htmlspecialchars($question['question']); ?></h4>
                            
                            <div class="space-y-3">
                                <?php foreach ($question['options'] as $key => $option): ?>
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                    <input type="radio" 
                                           name="question_<?php echo $question['id']; ?>" 
                                           value="<?php echo $key; ?>" 
                                           class="mr-3 text-blue-600 focus:ring-blue-500" 
                                           required>
                                    <span class="font-medium mr-2"><?php echo $key; ?>.</span>
                                    <span class="text-gray-700"><?php echo htmlspecialchars($option); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Boutons d'action -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <a href="certification.php?id=<?php echo $certificationId; ?>" 
                       class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour
                    </a>
                    
                    <button type="submit" 
                            name="submit_exam"
                            class="inline-flex items-center px-8 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Soumettre l'examen
                    </button>
                </div>
            </div>
        </form>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>
