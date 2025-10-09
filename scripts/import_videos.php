<?php
// Import all MP4 files from the videos directory into the resources table as type 'video'

session_start();

require_once __DIR__ . '/../includes/functions.php';

// Optional: limit to admins if session available
if (isset($_SESSION['user_id'])) {
    $currentUser = getUserById($_SESSION['user_id']);
    if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
        // Uncomment to restrict
        // http_response_code(403);
        // exit('Accès refusé: Administrateur requis');
    }
}

$videosRoot = realpath(__DIR__ . '/../videos');
if ($videosRoot === false) {
    http_response_code(500);
    exit('Le dossier videos est introuvable.');
}

function formatBytes($bytes) {
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    if ($bytes <= 0) return '0 B';
    $i = (int)floor(log($bytes, 1024));
    $i = max(0, min($i, count($sizes) - 1));
    return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
}

function buildRelativePathFromRoot($absolutePath) {
    $absolutePath = str_replace('\\', '/', $absolutePath);
    $root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    if (strpos($absolutePath, $root) === 0) {
        return ltrim(substr($absolutePath, strlen($root)), '/');
    }
    return basename($absolutePath);
}

function inferCategoryFromPath($path) {
    $lower = strtolower($path);
    if (strpos($lower, 'embedded') !== false) return 'embedded-systems';
    if (strpos($lower, 'ai') !== false || strpos($lower, 'intelligence') !== false) return 'artificial-intelligence';
    if (strpos($lower, 'deep') !== false) return 'deep-learning';
    if (strpos($lower, 'software') !== false) return 'software-engineering';
    if (strpos($lower, 'math') !== false) return 'mathematics';
    if (strpos($lower, 'program') !== false || strpos($lower, 'code') !== false) return 'programming';
    if (strpos($lower, 'ml') !== false || strpos($lower, 'machine') !== false) return 'machine-learning';
    return 'programming';
}

function titleFromFilename($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = str_replace(['_', '-'], ' ', $name);
    return ucwords(trim($name));
}

$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Collect MP4 files recursively
$videoFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($videosRoot, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $fileInfo) {
    if ($fileInfo->isFile()) {
        $ext = strtolower($fileInfo->getExtension());
        if (in_array($ext, ['mp4', 'webm', 'mkv', 'mov'])) {
            $videoFiles[] = $fileInfo->getPathname();
        }
    }
}

$inserted = 0;
$skipped = 0;
$errors = 0;
$details = [];

foreach ($videoFiles as $absolutePath) {
    $relativeFromRoot = buildRelativePathFromRoot($absolutePath);

    // Avoid duplicates based on file_url
    $checkStmt = $pdo->prepare("SELECT id FROM resources WHERE type = 'video' AND file_url = ? LIMIT 1");
    $checkStmt->execute([$relativeFromRoot]);
    $existingId = $checkStmt->fetchColumn();
    if ($existingId) {
        $skipped++;
        $details[] = ['file' => $relativeFromRoot, 'status' => 'déjà présent', 'id' => $existingId];
        continue;
    }

    $title = titleFromFilename($absolutePath);
    $category = inferCategoryFromPath($relativeFromRoot);
    $fileSize = @filesize($absolutePath);
    $sizeLabel = $fileSize !== false ? formatBytes($fileSize) : null;
    $author = 'Video Import';
    $description = 'Vidéo importée: ' . $relativeFromRoot;
    $tags = json_encode([]);

    try {
        $stmt = $pdo->prepare("INSERT INTO resources (title, description, type, category, file_size, file_url, author, downloads, views, tags, is_active, created_at) VALUES (?, ?, 'video', ?, ?, ?, ?, 0, 0, ?, 1, NOW())");
        $stmt->execute([
            $title,
            $description,
            $category,
            $sizeLabel,
            $relativeFromRoot,
            $author,
            $tags
        ]);
        $insertedId = $pdo->lastInsertId();
        $inserted++;
        $details[] = ['file' => $relativeFromRoot, 'status' => 'importé', 'id' => $insertedId];
    } catch (Throwable $e) {
        $errors++;
        $details[] = ['file' => $relativeFromRoot, 'status' => 'erreur', 'message' => $e->getMessage()];
    }
}

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Videos</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Import des vidéos</h1>
        <div class="bg-white shadow rounded p-4 mb-4">
            <p class="mb-1"><strong>Dossier scanné:</strong> <code><?php echo htmlspecialchars($videosRoot); ?></code></p>
            <p class="mb-1"><strong>Fichiers vidéo trouvés:</strong> <?php echo count($videoFiles); ?></p>
            <p class="mb-1 text-green-700"><strong>Importés:</strong> <?php echo $inserted; ?></p>
            <p class="mb-1 text-yellow-700"><strong>Ignorés (déjà présents):</strong> <?php echo $skipped; ?></p>
            <p class="mb-1 text-red-700"><strong>Erreurs:</strong> <?php echo $errors; ?></p>
        </div>
        <div class="bg-white shadow rounded p-4">
            <h2 class="text-xl font-semibold mb-3">Détails</h2>
            <ul class="space-y-2">
                <?php foreach ($details as $row): ?>
                    <li class="text-sm">
                        <span class="font-mono"><?php echo htmlspecialchars($row['file']); ?></span>
                        — <span class="font-medium"><?php echo htmlspecialchars($row['status']); ?></span>
                        <?php if (!empty($row['id'])): ?>(id: <?php echo (int)$row['id']; ?>)<?php endif; ?>
                        <?php if (!empty($row['message'])): ?>
                            <div class="text-red-600 mt-1"><?php echo htmlspecialchars($row['message']); ?></div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="mt-6 flex space-x-3">
            <a href="../resources.php?type=video" class="px-4 py-2 bg-blue-600 text-white rounded">Voir les vidéos</a>
            <a href="../admin/resources.php?type=video" class="px-4 py-2 bg-gray-700 text-white rounded">Gérer côté Admin</a>
        </div>
    </div>
</body>
</html>


