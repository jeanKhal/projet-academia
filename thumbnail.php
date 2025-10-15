<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Only allow logged-in users (consistent with video_stream)
if (!isLoggedIn()) {
    http_response_code(401);
    exit('Unauthorized');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('Missing id');
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT file_url, type FROM resources WHERE id = ? AND type = 'video' AND is_active = 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        exit('Not found');
    }

    $videoPath = $row['file_url'];
    if (!file_exists($videoPath)) {
        http_response_code(404);
        exit('File missing');
    }

    // Cache path
    $thumbDir = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'thumbnails';
    if (!is_dir($thumbDir)) {
        @mkdir($thumbDir, 0775, true);
    }
    $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . 'resource_' . $id . '.jpg';

    // Generate if missing
    if (!file_exists($thumbPath)) {
        // Try using ffmpeg if available
        $ffmpegOk = false;
        // Windows path spaces handling
        $ffmpegCmd = 'ffmpeg -y -ss 00:00:01 -i ' . escapeshellarg($videoPath) . ' -frames:v 1 -q:v 3 ' . escapeshellarg($thumbPath) . ' 2>&1';
        $version = @shell_exec('ffmpeg -version');
        if ($version) {
            @shell_exec($ffmpegCmd);
            if (file_exists($thumbPath) && filesize($thumbPath) > 0) {
                $ffmpegOk = true;
            }
        }

        if (!$ffmpegOk) {
            // Could not generate – return 404 so frontend uses gradient or JS fallback
            http_response_code(404);
            exit('Thumbnail unavailable');
        }
    }

    // Serve image
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=86400');
    readfile($thumbPath);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    exit('Server error');
}
?>


