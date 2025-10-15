<?php
// Système de logging avancé pour l'Académie IA

class SystemLogger {
    private $logFile;
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->logFile = __DIR__ . '/../logs/system.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    private function getRealIP() {
        // Vérifier les headers de proxy en ordre de priorité
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // IP directe
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Si plusieurs IPs (séparées par virgule), prendre la première
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Valider que c'est une IP valide
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback vers REMOTE_ADDR
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $this->getRealIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $userId = $_SESSION['user_id'] ?? 'guest';
        $userName = $_SESSION['user_name'] ?? 'Guest';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'message' => $message,
            'user_id' => $userId,
            'user_name' => $userName,
            'ip' => $ip,
            'user_agent' => substr($userAgent, 0, 100),
            'context' => $context
        ];
        
        $logLine = sprintf(
            "[%s] %s: %s | 👤 User: %s (%s) | 🌐 IP: %s | %s\n",
            $logEntry['timestamp'],
            $logEntry['level'],
            $logEntry['message'],
            $logEntry['user_name'],
            $logEntry['user_id'],
            $logEntry['ip'],
            !empty($context) ? json_encode($context) : ''
        );
        
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('warning', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }
    
    public function debug($message, $context = []) {
        $this->log('debug', $message, $context);
    }
    
    // Méthodes spécialisées pour les activités utilisateur
    public function logUserLogin($userId, $userName, $success = true) {
        $message = $success ? "Connexion réussie" : "Tentative de connexion échouée";
        $this->info($message, [
            'action' => 'user_login',
            'user_id' => $userId,
            'user_name' => $userName,
            'success' => $success
        ]);
    }
    
    public function logUserLogout($userId, $userName) {
        $this->info("Déconnexion utilisateur", [
            'action' => 'user_logout',
            'user_id' => $userId,
            'user_name' => $userName
        ]);
    }
    
    public function logUserActivity($userId, $userName, $activity, $details = []) {
        $this->info("Activité utilisateur: $activity", [
            'action' => 'user_activity',
            'user_id' => $userId,
            'user_name' => $userName,
            'activity' => $activity,
            'details' => $details
        ]);
    }
    
    public function logAdminAction($adminId, $adminName, $action, $target = null) {
        $this->info("Action admin: $action", [
            'action' => 'admin_action',
            'admin_id' => $adminId,
            'admin_name' => $adminName,
            'admin_action' => $action,
            'target' => $target
        ]);
    }
    
    public function logSecurityEvent($event, $details = []) {
        $this->warning("Événement de sécurité: $event", [
            'action' => 'security_event',
            'event' => $event,
            'details' => $details
        ]);
    }
    
    public function logSystemEvent($event, $details = []) {
        $this->info("Événement système: $event", [
            'action' => 'system_event',
            'event' => $event,
            'details' => $details
        ]);
    }
    
    public function getRecentLogs($limit = 100) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $logs = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_reverse(array_slice($logs, -$limit));
    }
    
    public function clearLogs() {
        if (file_exists($this->logFile)) {
            file_put_contents($this->logFile, '');
        }
    }
}

// Fonctions globales pour faciliter l'utilisation
function getLogger() {
    return SystemLogger::getInstance();
}

function logInfo($message, $context = []) {
    getLogger()->info($message, $context);
}

function logWarning($message, $context = []) {
    getLogger()->warning($message, $context);
}

function logError($message, $context = []) {
    getLogger()->error($message, $context);
}

function logUserActivity($userId, $userName, $activity, $details = []) {
    getLogger()->logUserActivity($userId, $userName, $activity, $details);
}

function logUserLogin($userId, $userName, $success = true) {
    getLogger()->logUserLogin($userId, $userName, $success);
}

function logUserLogout($userId, $userName) {
    getLogger()->logUserLogout($userId, $userName);
}

function logAdminAction($adminId, $adminName, $action, $target = null) {
    getLogger()->logAdminAction($adminId, $adminName, $action, $target);
}

function logSecurityEvent($event, $details = []) {
    getLogger()->logSecurityEvent($event, $details);
}

function logSystemEvent($event, $details = []) {
    getLogger()->logSystemEvent($event, $details);
}
?>
