<?php
session_start();
require_once 'includes/functions.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $adminCode = sanitizeInput($_POST['admin_code']);
    
    // Validation
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword) || empty($adminCode)) {
        $error = 'Veuillez remplir tous les champs';
    } elseif ($password !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide';
    } else {
        // Récupérer le code administrateur attendu depuis les paramètres (fallback: ADMIN2025)
        $expectedAdminCode = (string) trim(getSetting('admin_access_code', 'ADMIN2025'));
        $providedAdminCode = (string) trim($adminCode);

        if (!function_exists('hash_equals') || !hash_equals($expectedAdminCode, $providedAdminCode)) {
            $error = 'Code d\'accès administrateur invalide';
        }
    }

    if (empty($error)) {
        $result = registerUser($fullName, $email, $password, 'admin');
        
        if ($result['success']) {
            $success = $result['message'];
            // Rediriger vers la page de connexion après 2 secondes
            header('Refresh: 2; URL=login-admin.php');
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
    <title>Inscription Administrateur - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full space-y-6 animate-fade-in">
        <div class="text-center">
            <div class="flex justify-center mb-6">
                <div class="w-12 h-12 bg-red-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-xl">IA</span>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Académie IA</h1>
            <p class="text-gray-600">Inscription Administrateur</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form class="mt-6 space-y-4" method="POST" id="registerForm">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="space-y-4">
                    <!-- Nom complet -->
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nom complet
                        </label>
                        <input id="full_name" name="full_name" type="text" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-red-500" 
                               placeholder="Votre nom complet"
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email
                        </label>
                        <input id="email" name="email" type="email" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-red-500" 
                               placeholder="votre@email.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <!-- Code d'accès administrateur -->
                    <div>
                        <label for="admin_code" class="block text-sm font-medium text-gray-700 mb-1">
                            Code d'accès administrateur
                        </label>
                        <input id="admin_code" name="admin_code" type="password" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-red-500" 
                               placeholder="Code d'accès requis">
                    </div>

                    <!-- Mot de passe -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Mot de passe
                        </label>
                        <input id="password" name="password" type="password" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-red-500" 
                               placeholder="Minimum 6 caractères">
                    </div>

                    <!-- Confirmation du mot de passe -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                            Confirmer le mot de passe
                        </label>
                        <input id="confirm_password" name="confirm_password" type="password" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-red-500" 
                               placeholder="Confirmez votre mot de passe">
                    </div>
                </div>

                <!-- Conditions d'utilisation -->
                <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg mt-4">
                    <input id="agree-terms" name="agree-terms" type="checkbox" required
                           class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded mt-0.5">
                    <label for="agree-terms" class="text-sm text-gray-700">
                        J'accepte les <a href="#" class="text-red-600 hover:text-red-500">conditions d'utilisation</a> et la <a href="#" class="text-red-600 hover:text-red-500">politique de confidentialité</a>
                    </label>
                </div>

                <!-- Bouton d'inscription -->
                <div class="mt-4">
                    <button type="submit" id="submitBtn"
                            class="w-full bg-red-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                        <i class="fas fa-user-shield mr-2"></i>
                        Créer mon compte administrateur
                    </button>
                </div>
            </div>
        </form>
        
        <div class="text-center">
            <p class="text-gray-600 mb-4">
                Vous avez déjà un compte administrateur ?
            </p>
            <a href="login-admin.php" class="text-red-600 hover:text-red-700 font-medium">
                Se connecter
            </a>
        </div>
        
        <div class="text-center">
            <a href="login.php" class="text-gray-500 hover:text-gray-700 text-sm">
                <i class="fas fa-arrow-left mr-1"></i>
                Retour à la sélection
            </a>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const agreeTerms = document.getElementById('agree-terms').checked;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
                return false;
            }
            
            if (!agreeTerms) {
                e.preventDefault();
                alert('Vous devez accepter les conditions d\'utilisation');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Création du compte...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
