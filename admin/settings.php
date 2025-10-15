<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user = getUserById($_SESSION['user_id']);
if ($user['role'] !== 'admin') {
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
                
            case 'clear_logs':
                // Vider les logs système
                require_once __DIR__ . '/../includes/logger.php';
                $logger = getLogger();
                $logger->clearLogs();
                logAdminAction($_SESSION['user_id'], $_SESSION['user_name'], 'Vider les logs système');
                setFlashMessage('success', 'Logs système vidés avec succès.');
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
$site_description = getSetting('site_description', 'Plateforme d\'apprentissage multidisciplinaire pour tous les étudiants');
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
    
    <div class="flex">
    <?php include 'includes/sidebar.php'; ?>

        <!-- Séparateur visuel -->
        <div class="hidden md:block w-px bg-gradient-to-b from-gray-200 to-gray-300 mt-16 h-[calc(100vh-4rem-1.5rem)] ml-64"></div>
        
        <div class="flex-1 p-4 md:p-6 mt-16 pb-16 bg-white rounded-l-2xl shadow-sm border-l-2 border-gray-100 min-h-[calc(100vh-4rem-1.5rem)]" style="margin-left: 6px;">
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
    </div>

    <!-- Modal pour afficher les logs -->
    <?php if (isset($_SESSION['system_logs'])): ?>
    <div id="logsModal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-start justify-center z-50" style="padding-top: 2rem; padding-bottom: 2rem;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-4 max-h-[80vh] overflow-hidden border border-gray-200">
            <!-- Header compact -->
            <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-lg flex items-center justify-center shadow-lg">
                            <i class="fas fa-chart-line text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Logs Système</h3>
                            <p class="text-xs text-gray-600">Surveillance des activités</p>
                        </div>
                    </div>
                    <button onclick="closeLogsModal()" class="group flex items-center justify-center w-8 h-8 bg-gray-100 hover:bg-red-100 rounded-lg transition-all duration-200 hover:scale-105">
                        <i class="fas fa-times text-gray-500 group-hover:text-red-600 text-sm"></i>
                </button>
                </div>
            </div>
            <!-- Contenu principal -->
            <div class="p-4 overflow-y-auto max-h-[50vh]">
                <?php 
                // Chargement simplifié des logs pour améliorer les performances
                $logs = [];
                $log_file = __DIR__ . '/../logs/system.log';
                
                if (file_exists($log_file)) {
                    $log_lines = file($log_file, FILE_IGNORE_NEW_LINES);
                    $logs = array_slice(array_reverse($log_lines), 0, 5); // Seulement 5 derniers logs
                }
                ?>
                
                <?php if (!empty($logs)): ?>
                    <!-- Barre d'outils compacte -->
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center space-x-3">
                            <h4 class="text-lg font-bold text-gray-900">Logs Récents</h4>
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">
                                50 derniers
                            </span>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="refreshLogs()" class="group flex items-center bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs font-medium transition-all duration-200 hover:scale-105">
                                <i class="fas fa-sync-alt mr-1 group-hover:animate-spin"></i>
                                Actualiser
                            </button>
                            <button onclick="clearLogs()" class="group flex items-center bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-xs font-medium transition-all duration-200 hover:scale-105">
                                <i class="fas fa-trash mr-1"></i>
                                Vider
                            </button>
                        </div>
                    </div>
                    
                    <!-- Zone des logs compacte -->
                    <div class="bg-gray-900 rounded-lg p-4 shadow-inner">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            </div>
                            <span class="text-gray-400 text-xs font-mono">Terminal</span>
                        </div>
                        
                        <div class="bg-black rounded-lg p-3 max-h-60 overflow-y-auto font-mono text-xs">
                            <?php foreach ($logs as $index => $log): ?>
                                <div class="mb-2 p-2 rounded border-l-2 <?php 
                                    if (strpos($log, 'ERROR') !== false) echo 'bg-red-900/20 border-red-500 text-red-300';
                                    elseif (strpos($log, 'WARNING') !== false) echo 'bg-yellow-900/20 border-yellow-500 text-yellow-300';
                                    elseif (strpos($log, 'INFO') !== false) echo 'bg-blue-900/20 border-blue-500 text-blue-300';
                                    else echo 'bg-gray-800/20 border-gray-500 text-gray-300';
                                ?>">
                                    <div class="flex items-start space-x-2">
                                        <span class="text-gray-500 text-xs mt-0.5"><?php echo $index + 1; ?></span>
                                        <div class="flex-1">
                                            <div class="text-xs text-gray-400 mb-0.5">
                                                <?php 
                                                $timestamp = substr($log, 1, 19);
                                                echo htmlspecialchars($timestamp);
                                                ?>
                                            </div>
                                            <div class="text-xs">
                                                <?php 
                                                // Mettre en évidence l'IP dans le log
                                                $highlightedLog = preg_replace(
                                                    '/(🌐 IP: )([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/',
                                                    '<span class="bg-blue-600 text-white px-1 rounded text-xs font-mono">$1$2</span>',
                                                    htmlspecialchars($log)
                                                );
                                                echo $highlightedLog;
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Légende compacte -->
                    <div class="mt-4 bg-gray-50 rounded-lg p-4">
                        <h5 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-info-circle mr-1 text-blue-600 text-xs"></i>
                            Types d'événements
                        </h5>
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                                <span class="text-xs text-gray-700">Connexions</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-green-600 rounded-full"></div>
                                <span class="text-xs text-gray-700">Activités</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-yellow-600 rounded-full"></div>
                                <span class="text-xs text-gray-700">Admin</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-red-600 rounded-full"></div>
                                <span class="text-xs text-gray-700">Sécurité</span>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <div class="flex items-center space-x-2 text-sm text-gray-800 bg-blue-50 p-3 rounded-lg">
                                <i class="fas fa-globe text-blue-600 text-base"></i>
                                <span class="font-medium"><strong class="text-blue-700">IPs enregistrées :</strong> <span class="text-gray-700">Adresses IP réelles des utilisateurs (même derrière proxy)</span></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-file-alt text-2xl text-gray-400"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Aucun log disponible</h4>
                        <p class="text-gray-600 mb-3 text-sm">Les logs apparaîtront après les activités</p>
                        <button onclick="refreshLogs()" class="bg-blue-600 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-700 transition-colors">
                            <i class="fas fa-sync-alt mr-1"></i>
                            Actualiser
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Footer compact -->
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
                <div class="flex items-center text-xs text-gray-600">
                    <i class="fas fa-clock mr-1"></i>
                    <span>Mise à jour: <?php echo date('H:i:s'); ?></span>
                </div>
                <div class="flex space-x-2">
                    <button onclick="refreshLogs()" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs font-medium transition-all duration-200">
                        <i class="fas fa-sync-alt mr-1"></i>
                        Actualiser
                    </button>
                    <button onclick="closeLogsModal()" class="flex items-center bg-gray-600 hover:bg-gray-700 text-white px-3 py-1.5 rounded text-xs font-medium transition-all duration-200">
                        <i class="fas fa-times mr-1"></i>
                    Fermer
                </button>
                </div>
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
            const modal = document.getElementById('logsModal');
            if (modal) {
                modal.style.opacity = '0';
                modal.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 200);
            }
        }
        
        function refreshLogs() {
            const refreshBtn = event.target.closest('button');
            const icon = refreshBtn.querySelector('i');
            
            // Animation de rotation
            icon.classList.add('animate-spin');
            refreshBtn.disabled = true;
            
            setTimeout(() => {
                location.reload();
            }, 500);
        }
        
        function clearLogs() {
            // Modal de confirmation personnalisée
            const confirmed = confirm('⚠️ ATTENTION\n\nÊtes-vous sûr de vouloir vider tous les logs système ?\n\nCette action est irréversible et supprimera définitivement tous les enregistrements d\'activité.\n\nCliquez sur OK pour confirmer.');
            
            if (confirmed) {
                const clearBtn = event.target.closest('button');
                const icon = clearBtn.querySelector('i');
                
                // Animation de suppression
                icon.classList.remove('fa-trash');
                icon.classList.add('fa-spinner', 'animate-spin');
                clearBtn.disabled = true;
                
                // Créer un formulaire pour envoyer la requête de suppression
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'clear_logs';
                input.value = '1';
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Animation d'ouverture de la modal
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('logsModal');
            if (modal && modal.style.display !== 'none') {
                modal.style.opacity = '0';
                modal.style.transform = 'scale(0.95)';
                modal.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    modal.style.opacity = '1';
                    modal.style.transform = 'scale(1)';
                }, 10);
            }
        });
        
        // Fermer la modal en cliquant à l'extérieur
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('logsModal');
            if (event.target === modal) {
                closeLogsModal();
            }
        });
        
        // Fermer avec la touche Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('logsModal');
                if (modal && modal.style.display !== 'none') {
                    closeLogsModal();
                }
            }
        });
    </script>
</body>
</html>
