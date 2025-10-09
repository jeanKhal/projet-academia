<?php
session_start();
require_once 'includes/functions.php';

// Rediriger si déjà connecté en tant qu'admin
if (isLoggedIn() && $_SESSION['user_role'] === 'admin') {
    header('Location: index.php');
    exit();
}

// Si connecté avec un autre rôle, déconnecter et rediriger
if (isLoggedIn() && $_SESSION['user_role'] !== 'admin') {
    session_destroy();
    header('Location: login.php');
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
            // Vérifier que l'utilisateur est bien un administrateur
            if ($result['user']['role'] !== 'admin') {
                $roleLabel = '';
                switch ($result['user']['role']) {
                    case 'student':
                        $roleLabel = 'étudiant';
                        break;
                    case 'teacher':
                        $roleLabel = 'enseignant';
                        break;
                    default:
                        $roleLabel = $result['user']['role'];
                }
                $error = 'Ce compte est un compte ' . $roleLabel . '. Veuillez vous connecter en tant que ' . $roleLabel . ' sur la page appropriée.';
            } else {
                // Mettre à jour la dernière connexion
                $pdo = getDB();
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$result['user']['id']]);
                
                header('Location: index.php');
                exit();
            }
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
    <title>Connexion Administrateur - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="flex justify-center">
                <div class="flex items-center space-x-2">
                    <div class="w-12 h-12 bg-red-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">IA</span>
                    </div>
                    <span class="text-3xl font-bold text-gray-900">Académie IA</span>
                </div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Connexion Administrateur
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Gérez la plateforme
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
        
        <form class="mt-8 space-y-6" method="POST">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email" class="sr-only">Adresse email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input id="email" name="email" type="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm" 
                               placeholder="Adresse email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                <div>
                    <label for="password" class="sr-only">Mot de passe</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="password" name="password" type="password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm" 
                               placeholder="Mot de passe">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" name="remember-me" type="checkbox" 
                           class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                        Se souvenir de moi
                    </label>
                </div>

                <div class="text-sm">
                    <a href="forgot-password.php" class="font-medium text-red-600 hover:text-red-500">
                        Mot de passe oublié ?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-sign-in-alt text-red-500 group-hover:text-red-400"></i>
                    </span>
                    Se connecter
                </button>
            </div>
        </form>
        
        <div class="text-center">
            <p class="text-gray-600 mb-4">
                Vous n'avez pas encore de compte administrateur ?
            </p>
            <a href="register-admin.php" class="bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors text-sm">
                <i class="fas fa-user-plus mr-1"></i>
                Inscription Administrateur
            </a>
        </div>
        
        <div class="text-center">
            <a href="login.php?return_to_selection=1" class="text-red-600 hover:text-red-500">
                <i class="fas fa-arrow-left mr-1"></i>
                Retour à la sélection
            </a>
        </div>
    </div>
</body>
</html>
