<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    die('Accès non autorisé');
}

// Récupérer l'ID de la ressource
$resourceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$resourceId) {
    http_response_code(400);
    die('ID de ressource manquant');
}

try {
    $pdo = getDB();
    
    // Récupérer les informations de la ressource
    $stmt = $pdo->prepare("
        SELECT file_url, title, type 
        FROM resources 
        WHERE id = ? AND type = 'video' AND is_active = 1
    ");
    $stmt->execute([$resourceId]);
    $resource = $stmt->fetch();
    
    if (!$resource) {
        http_response_code(404);
        die('Ressource non trouvée');
    }
    
    $filePath = $resource['file_url'];
    
    // Vérifier que le fichier existe
    if (!file_exists($filePath)) {
        http_response_code(404);
        die('Fichier vidéo non trouvé');
    }
    
    // Enregistrer la vue
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO resource_views (resource_id, user_id, viewed_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$resourceId, $userId]);
    
    // Définir les headers pour le streaming
    $fileSize = filesize($filePath);
    $fileName = basename($filePath);
    
    // Headers pour empêcher la mise en cache et le téléchargement
    header('Content-Type: video/mp4');
    header('Accept-Ranges: bytes');
    header('Cache-Control: no-cache, no-store, must-revalidate, private');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'');
    
    // Support pour le streaming par chunks (Range requests)
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        $range = str_replace('bytes=', '', $range);
        $range = explode('-', $range);
        
        $start = intval($range[0]);
        $end = isset($range[1]) && $range[1] !== '' ? intval($range[1]) : $fileSize - 1;
        
        if ($start > $end || $start > $fileSize - 1 || $end >= $fileSize) {
            http_response_code(416);
            header("Content-Range: bytes */$fileSize");
            exit;
        }
        
        $length = $end - $start + 1;
        
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$fileSize");
        header("Content-Length: $length");
        
        $file = fopen($filePath, 'rb');
        fseek($file, $start);
        
        $buffer = 1024 * 8; // 8KB buffer
        while (!feof($file) && ($pos = ftell($file)) <= $end) {
            if ($pos + $buffer > $end) {
                $buffer = $end - $pos + 1;
            }
            echo fread($file, $buffer);
            flush();
        }
        fclose($file);
    } else {
        // Lecture normale du fichier
        header("Content-Length: $fileSize");
        
        $file = fopen($filePath, 'rb');
        while (!feof($file)) {
            echo fread($file, 1024 * 8);
            flush();
        }
        fclose($file);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Erreur video_stream.php: " . $e->getMessage());
    die('Erreur interne du serveur');
}
?>
