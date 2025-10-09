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
$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category = $_POST['category'];
    
    if (empty($title) || empty($content) || empty($category)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO forum_posts (user_id, title, content, category, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        
        if ($stmt->execute([$user['id'], $title, $content, $category])) {
            $success = 'Votre post a été créé avec succès et est en attente de modération.';
        } else {
            $error = 'Erreur lors de la création du post. Veuillez réessayer.';
        }
    }
}

// Fonction pour obtenir les labels des catégories du forum
function getForumCategoryLabel($category) {
    $labels = [
        'general' => 'Général',
        'courses' => 'Cours',
        'resources' => 'Ressources',
        'certifications' => 'Certifications',
        'technical' => 'Technique',
        'help' => 'Aide',
        'discussion' => 'Discussion'
    ];
    return $labels[$category] ?? 'Autre';
}

// Fonction pour obtenir les couleurs des catégories du forum
function getForumCategoryColor($category) {
    $colors = [
        'general' => 'bg-gray-100 text-gray-800',
        'courses' => 'bg-blue-100 text-blue-800',
        'resources' => 'bg-green-100 text-green-800',
        'certifications' => 'bg-purple-100 text-purple-800',
        'technical' => 'bg-orange-100 text-orange-800',
        'help' => 'bg-red-100 text-red-800',
        'discussion' => 'bg-indigo-100 text-indigo-800'
    ];
    return $colors[$category] ?? 'bg-gray-100 text-gray-800';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Post - Forum Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Contenu principal -->
    <main class="flex-1 mt-16 p-4 md:p-8 pb-40">
        <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-plus-circle text-green-600 mr-2"></i>
                        Nouveau Post
                    </h1>
                    <p class="text-gray-600 mt-1">Créez une nouvelle discussion sur le forum</p>
                </div>
                <a href="forum.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Retour au forum
                </a>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <!-- Formulaire de création -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-edit mr-2 text-blue-600"></i>
                    Créer un nouveau post
                </h2>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-6">
                    <!-- Titre -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Titre du post <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Entrez le titre de votre discussion"
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>

                    <!-- Catégorie -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                            Catégorie <span class="text-red-500">*</span>
                        </label>
                        <select id="category" name="category" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionnez une catégorie</option>
                            <option value="general" <?php echo (isset($_POST['category']) && $_POST['category'] === 'general') ? 'selected' : ''; ?>>Général</option>
                            <option value="courses" <?php echo (isset($_POST['category']) && $_POST['category'] === 'courses') ? 'selected' : ''; ?>>Cours</option>
                            <option value="resources" <?php echo (isset($_POST['category']) && $_POST['category'] === 'resources') ? 'selected' : ''; ?>>Ressources</option>
                            <option value="certifications" <?php echo (isset($_POST['category']) && $_POST['category'] === 'certifications') ? 'selected' : ''; ?>>Certifications</option>
                            <option value="technical" <?php echo (isset($_POST['category']) && $_POST['category'] === 'technical') ? 'selected' : ''; ?>>Technique</option>
                            <option value="help" <?php echo (isset($_POST['category']) && $_POST['category'] === 'help') ? 'selected' : ''; ?>>Aide</option>
                            <option value="discussion" <?php echo (isset($_POST['category']) && $_POST['category'] === 'discussion') ? 'selected' : ''; ?>>Discussion</option>
                        </select>
                    </div>

                    <!-- Contenu -->
                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                            Contenu <span class="text-red-500">*</span>
                        </label>
                        <textarea id="content" name="content" rows="10" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Décrivez votre question ou discussion en détail..."><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                        <p class="mt-1 text-sm text-gray-500">
                            Utilisez un langage clair et respectueux. Les posts sont modérés avant publication.
                        </p>
                    </div>

                    <!-- Conseils -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-blue-900 mb-2">
                            <i class="fas fa-lightbulb mr-2"></i>
                            Conseils pour un bon post
                        </h3>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li>• Utilisez un titre clair et descriptif</li>
                            <li>• Expliquez votre question ou sujet en détail</li>
                            <li>• Choisissez la bonne catégorie</li>
                            <li>• Soyez respectueux envers les autres membres</li>
                            <li>• Vérifiez l'orthographe et la grammaire</li>
                        </ul>
                    </div>

                    <!-- Boutons -->
                    <div class="flex justify-end space-x-4">
                        <a href="forum.php" class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 transition-colors">
                            Annuler
                        </a>
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Publier le post
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Informations sur la modération -->
        <div class="mt-6 mb-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-yellow-900 mb-2">
                <i class="fas fa-info-circle mr-2"></i>
                Modération
            </h3>
            <p class="text-sm text-yellow-800">
                Tous les nouveaux posts sont soumis à modération avant publication. 
                Cela peut prendre quelques heures. Merci de votre patience !
            </p>
        </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>
