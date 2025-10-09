<?php
session_start();
require_once 'includes/functions.php';

// Si l'utilisateur vient d'une page de connexion spécifique et veut retourner à la sélection
if (isset($_GET['return_to_selection']) && $_GET['return_to_selection'] === '1') {
    // Déconnecter l'utilisateur s'il était connecté
    if (isLoggedIn()) {
        session_destroy();
    }
    // Rediriger vers la page de sélection sans paramètre
    header('Location: login.php');
    exit();
}

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Vérifier s'il y a un message d'erreur dans l'URL
if (isset($_GET['error']) && $_GET['error'] === 'account_disabled') {
    $error = 'Votre compte a été désactivé par l\'administrateur. Veuillez contacter l\'administration pour plus d\'informations.';
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            // Mettre à jour la dernière connexion
            $pdo = getDB();
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$result['user']['id']]);
            
            header('Location: index.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="flex justify-center">
                <div class="flex items-center space-x-2">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">IA</span>
                    </div>
                    <span class="text-3xl font-bold text-gray-900">Académie IA</span>
                </div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Connexion à votre compte
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Choisissez votre type de compte
            </p>
        </div>
        
        <?php 
        // Afficher les messages flash
        $flash = getFlashMessage();
        if ($flash): 
        ?>
            <div class="bg-<?php echo $flash['type'] === 'warning' ? 'yellow' : ($flash['type'] === 'success' ? 'green' : 'red'); ?>-100 border border-<?php echo $flash['type'] === 'warning' ? 'yellow' : ($flash['type'] === 'success' ? 'green' : 'red'); ?>-400 text-<?php echo $flash['type'] === 'warning' ? 'yellow' : ($flash['type'] === 'success' ? 'green' : 'red'); ?>-700 px-4 py-3 rounded">
                <i class="fas fa-<?php echo $flash['type'] === 'warning' ? 'exclamation-triangle' : ($flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'); ?> mr-2"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="<?php 
                if (strpos($error, 'désactivé') !== false) {
                    echo 'bg-orange-100 border-orange-400 text-orange-700';
                } elseif (strpos($error, 'compte') !== false && strpos($error, 'est un compte') !== false) {
                    echo 'bg-yellow-100 border-yellow-400 text-yellow-700';
                } else {
                    echo 'bg-red-100 border-red-400 text-red-700';
                }
            ?> border px-4 py-3 rounded">
                <div class="flex items-center">
                    <i class="fas <?php 
                        if (strpos($error, 'désactivé') !== false) {
                            echo 'fa-user-slash';
                        } elseif (strpos($error, 'compte') !== false && strpos($error, 'est un compte') !== false) {
                            echo 'fa-exclamation-triangle';
                        } else {
                            echo 'fa-exclamation-triangle';
                        }
                    ?> mr-2"></i>
                    <div>
                        <p class="font-medium"><?php echo htmlspecialchars($error); ?></p>
                        <?php if (strpos($error, 'désactivé') !== false): ?>
                        <p class="text-sm mt-1 opacity-75">Contactez l'administration à l'adresse : admin@academy.com</p>
                        <?php endif; ?>
                        <?php if (strpos($error, 'compte') !== false && strpos($error, 'est un compte') !== false): ?>
                        <p class="text-sm mt-1 opacity-75">Utilisez le bon lien de connexion pour votre type de compte</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Choix du type de compte -->
        <div class="grid grid-cols-2 gap-4">
            <a href="login-student.php" class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 hover:border-blue-300 hover:shadow-md transition-all text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-graduation-cap text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Étudiant</h3>
                <p class="text-sm text-gray-600">Accédez à vos cours et ressources</p>
            </a>
            
            <a href="login-admin.php" class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 hover:border-red-300 hover:shadow-md transition-all text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-shield text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Administrateur</h3>
                <p class="text-sm text-gray-600">Gérez la plateforme</p>
            </a>
        </div>
        
        <div class="text-center">
            <p class="text-gray-600 mb-4">
                Vous n'avez pas encore de compte ?
            </p>
            <div class="grid grid-cols-2 gap-4">
                <a href="register-student.php" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                    <i class="fas fa-user-plus mr-1"></i>
                    Inscription Étudiant
                </a>
                <a href="register-admin.php" class="bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors text-sm">
                    <i class="fas fa-user-plus mr-1"></i>
                    Inscription Admin
                </a>
            </div>
        </div>
        
        <div class="text-center">
            <a href="index.php" class="text-blue-600 hover:text-blue-500">
                <i class="fas fa-arrow-left mr-1"></i>
                Retour à l'accueil
            </a>
        </div>
    </div>
</body>
</html>
