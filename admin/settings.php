<?php
session_start();
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$pdo = getDB();

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'general_settings':
                // Mise à jour des paramètres généraux
                $site_name = $_POST['site_name'] ?? '';
                $site_description = $_POST['site_description'] ?? '';
                $contact_email = $_POST['contact_email'] ?? '';
                
                updateSetting('site_name', $site_name);
                updateSetting('site_description', $site_description);
                updateSetting('contact_email', $contact_email);
                
                setFlashMessage('success', 'Paramètres généraux mis à jour avec succès.');
                break;
                
            case 'email_settings':
                // Mise à jour des paramètres email
                $smtp_host = $_POST['smtp_host'] ?? '';
                $smtp_port = $_POST['smtp_port'] ?? '';
                $smtp_username = $_POST['smtp_username'] ?? '';
                $smtp_password = $_POST['smtp_password'] ?? '';
                
                updateSetting('smtp_host', $smtp_host);
                updateSetting('smtp_port', $smtp_port);
                updateSetting('smtp_username', $smtp_username);
                if (!empty($smtp_password)) {
                    updateSetting('smtp_password', $smtp_password);
                }
                
                setFlashMessage('success', 'Paramètres email mis à jour avec succès.');
                break;
                
            case 'security_settings':
                // Mise à jour des paramètres de sécurité
                $max_login_attempts = $_POST['max_login_attempts'] ?? 5;
                $session_timeout = $_POST['session_timeout'] ?? 3600;
                $password_min_length = $_POST['password_min_length'] ?? 8;
                
                updateSetting('max_login_attempts', $max_login_attempts);
                updateSetting('session_timeout', $session_timeout);
                updateSetting('password_min_length', $password_min_length);
                
                setFlashMessage('success', 'Paramètres de sécurité mis à jour avec succès.');
                break;
                
            case 'clear_cache':
                // Simulation de nettoyage du cache
                setFlashMessage('success', 'Cache nettoyé avec succès.');
                break;
                
            case 'view_logs':
                // Afficher les logs système
                $logFile = __DIR__ . '/../logs/system.log';
                $logs = [];
                
                if (file_exists($logFile)) {
                    $logs = array_reverse(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                    $logs = array_slice($logs, 0, 100); // Limiter à 100 dernières lignes
                }
                
                // Stocker les logs en session pour l'affichage
                $_SESSION['system_logs'] = $logs;
                break;
        }
    }
}

// Récupérer les paramètres actuels
$site_name = getSetting('site_name', 'Académie IA');
$site_description = getSetting('site_description', 'Plateforme d\'apprentissage en intelligence artificielle');
$contact_email = getSetting('contact_email', 'contact@academy.com');

$smtp_host = getSetting('smtp_host', '');
$smtp_port = getSetting('smtp_port', '587');
$smtp_username = getSetting('smtp_username', '');

$max_login_attempts = getSetting('max_login_attempts', 5);
$session_timeout = getSetting('session_timeout', 3600);
$password_min_length = getSetting('password_min_length', 8);

$flash_message = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 overflow-x-hidden">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 p-4 md:p-8 mt-16 pb-16">
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Paramètres</h1>
                    <p class="text-gray-600">Configurez les paramètres de la plateforme</p>
                </div>
            </div>
        </div>

        <?php if ($flash_message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $flash_message['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars($flash_message['message']); ?>
        </div>
        <?php endif; ?>

        <!-- Paramètres généraux -->
        <div class="bg-white rounded-lg shadow-sm mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-cog mr-2 text-blue-600"></i>
                    Paramètres Généraux
                </h2>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="general_settings">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nom du site
                            </label>
                            <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email de contact
                            </label>
                            <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($contact_email); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label for="site_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description du site
                        </label>
                        <textarea id="site_description" name="site_description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($site_description); ?></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Paramètres email -->
        <div class="bg-white rounded-lg shadow-sm mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-envelope mr-2 text-green-600"></i>
                    Configuration Email
                </h2>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="email_settings">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="smtp_host" class="block text-sm font-medium text-gray-700 mb-2">
                                Serveur SMTP
                            </label>
                            <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="smtp_port" class="block text-sm font-medium text-gray-700 mb-2">
                                Port SMTP
                            </label>
                            <input type="number" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($smtp_port); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="smtp_username" class="block text-sm font-medium text-gray-700 mb-2">
                                Nom d'utilisateur SMTP
                            </label>
                            <input type="text" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($smtp_username); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="smtp_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Mot de passe SMTP
                            </label>
                            <input type="password" id="smtp_password" name="smtp_password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Laissez vide pour ne pas changer">
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Paramètres de sécurité -->
        <div class="bg-white rounded-lg shadow-sm mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-shield-alt mr-2 text-red-600"></i>
                    Paramètres de Sécurité
                </h2>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="security_settings">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="max_login_attempts" class="block text-sm font-medium text-gray-700 mb-2">
                                Tentatives de connexion max
                            </label>
                            <input type="number" id="max_login_attempts" name="max_login_attempts" value="<?php echo htmlspecialchars($max_login_attempts); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="session_timeout" class="block text-sm font-medium text-gray-700 mb-2">
                                Timeout de session (secondes)
                            </label>
                            <input type="number" id="session_timeout" name="session_timeout" value="<?php echo htmlspecialchars($session_timeout); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="password_min_length" class="block text-sm font-medium text-gray-700 mb-2">
                                Longueur min. mot de passe
                            </label>
                            <input type="number" id="password_min_length" name="password_min_length" value="<?php echo htmlspecialchars($password_min_length); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Outils système -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-tools mr-2 text-purple-600"></i>
                    Outils Système
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-2">Cache</h3>
                        <p class="text-sm text-gray-600 mb-3">Vider le cache de la plateforme</p>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="clear_cache">
                            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors text-sm">
                                <i class="fas fa-broom mr-2"></i>
                                Vider le cache
                            </button>
                        </form>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-2">Logs</h3>
                        <p class="text-sm text-gray-600 mb-3">Consulter les logs système</p>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="view_logs">
                            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors text-sm">
                                <i class="fas fa-file-alt mr-2"></i>
                                Voir les logs
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour afficher les logs -->
    <?php if (isset($_SESSION['system_logs'])): ?>
    <div id="logsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[80vh] overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-file-alt mr-2 text-purple-600"></i>
                    Logs Système
                </h3>
                <button onclick="closeLogsModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[60vh]">
                <?php if (!empty($_SESSION['system_logs'])): ?>
                    <div class="bg-gray-900 text-green-400 font-mono text-sm rounded-lg p-4">
                        <?php foreach ($_SESSION['system_logs'] as $log): ?>
                            <div class="mb-1"><?php echo htmlspecialchars($log); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-file-alt text-4xl mb-4"></i>
                        <p>Aucun log système disponible</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                <button onclick="closeLogsModal()" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                    Fermer
                </button>
            </div>
        </div>
    </div>
    <?php 
        // Nettoyer les logs de la session après affichage
        unset($_SESSION['system_logs']);
    endif; 
    ?>

    <script>
        function closeLogsModal() {
            document.getElementById('logsModal').style.display = 'none';
        }
        
        // Fermer la modal en cliquant à l'extérieur
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('logsModal');
            if (event.target === modal) {
                closeLogsModal();
            }
        });
    </script>
</body>
</html>
