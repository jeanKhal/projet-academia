<?php
// Import all CSV files from the datasets directory into the resources table as type 'dataset'

session_start();

require_once __DIR__ . '/../includes/functions.php';

// Optional: simple auth check (only allow admins if session exists)
if (isset($_SESSION['user_id'])) {
    $currentUser = getUserById($_SESSION['user_id']);
    if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
        // Allow running even without strict auth if needed; uncomment to enforce
        // http_response_code(403);
        // exit('Accès refusé: Administrateur requis');
    }
}

// Config
$datasetsRoot = realpath(__DIR__ . '/../datasets');
if ($datasetsRoot === false) {
    http_response_code(500);
    exit('Le dossier datasets est introuvable.');
}

// Helpers
function formatBytes($bytes) {
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    if ($bytes <= 0) return '0 B';
    $i = (int)floor(log($bytes, 1024));
    $i = max(0, min($i, count($sizes) - 1));
    return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
}

function buildRelativePathFromRoot($absolutePath) {
    // Normalize dir separators
    $absolutePath = str_replace('\\', '/', $absolutePath);
    $root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    if (strpos($absolutePath, $root) === 0) {
        return ltrim(substr($absolutePath, strlen($root)), '/');
    }
    return basename($absolutePath);
}

function inferCategoryFromPath($path) {
    $lower = strtolower($path);
    // Map path keywords to allowed categories in schema
    if (strpos($lower, 'embedded') !== false) return 'embedded-systems';
    if (strpos($lower, 'ai') !== false || strpos($lower, 'intelligence') !== false) return 'artificial-intelligence';
    if (strpos($lower, 'deep') !== false) return 'deep-learning';
    if (strpos($lower, 'software') !== false) return 'software-engineering';
    if (strpos($lower, 'math') !== false) return 'mathematics';
    if (strpos($lower, 'program') !== false || strpos($lower, 'code') !== false) return 'programming';
    if (strpos($lower, 'ml') !== false || strpos($lower, 'machine') !== false) return 'machine-learning';
    // Default bucket
    return 'machine-learning';
}

function titleFromFilename($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = str_replace(['_', '-'], ' ', $name);
    // Capitalize words (basic)
    return ucwords(trim($name));
}

$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Collect CSV files recursively
$csvFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($datasetsRoot, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $fileInfo) {
    if ($fileInfo->isFile()) {
        $ext = strtolower($fileInfo->getExtension());
        if ($ext === 'csv') {
            $csvFiles[] = $fileInfo->getPathname();
        }
    }
}

$inserted = 0;
$skipped = 0;
$errors = 0;
$details = [];

foreach ($csvFiles as $absolutePath) {
    $relativeFromRoot = buildRelativePathFromRoot($absolutePath);

    // Avoid duplicates based on file_url for datasets
    $checkStmt = $pdo->prepare("SELECT id FROM resources WHERE type = 'dataset' AND file_url = ? LIMIT 1");
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
    $author = 'Dataset Import';
    $description = 'Fichier CSV importé: ' . $relativeFromRoot;
    $tags = json_encode([]);

    try {
        $stmt = $pdo->prepare("INSERT INTO resources (title, description, type, category, file_size, file_url, author, downloads, views, tags, is_active, created_at) VALUES (?, ?, 'dataset', ?, ?, ?, ?, 0, 0, ?, 1, NOW())");
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

// Simple HTML report
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Datasets</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style> body{ padding:20px } code{ background:#f3f4f6; padding:2px 6px; border-radius:4px } </style>
    </head>
<body class="bg-gray-50">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Import des datasets (CSV)</h1>
        <div class="bg-white shadow rounded p-4 mb-4">
            <p class="mb-1"><strong>Dossier scanné:</strong> <code><?php echo htmlspecialchars($datasetsRoot); ?></code></p>
            <p class="mb-1"><strong>Fichiers CSV trouvés:</strong> <?php echo count($csvFiles); ?></p>
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
                        <?php if (!empty($row['id'])): ?>
                            (id: <?php echo (int)$row['id']; ?>)
                        <?php endif; ?>
                        <?php if (!empty($row['message'])): ?>
                            <div class="text-red-600 mt-1"><?php echo htmlspecialchars($row['message']); ?></div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="mt-6 flex space-x-3">
            <a href="../resources.php?type=dataset" class="px-4 py-2 bg-blue-600 text-white rounded">Voir la section Datasets</a>
            <a href="../admin/resources.php?type=dataset" class="px-4 py-2 bg-gray-700 text-white rounded">Gérer côté Admin</a>
        </div>
    </div>
</body>
</html>


