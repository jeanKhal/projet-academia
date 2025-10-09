<?php
// Import ZIP files from codesource into resources as type 'code'

session_start();
require_once __DIR__ . '/../includes/functions.php';

$root = realpath(__DIR__ . '/..');
$codesourceRoot = realpath(__DIR__ . '/../codesource');
if ($codesourceRoot === false) {
    http_response_code(500);
    exit('Le dossier codesource est introuvable.');
}

function formatBytes($bytes) {
    $sizes = ['B', 'KB', 'MB', 'GB'];
    if ($bytes <= 0) return '0 B';
    $i = (int)floor(log($bytes, 1024));
    $i = max(0, min($i, count($sizes) - 1));
    return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
}

function relPath($absolutePath) {
    $absolutePath = str_replace('\\', '/', $absolutePath);
    $root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    if (strpos($absolutePath, $root) === 0) {
        return ltrim(substr($absolutePath, strlen($root)), '/');
    }
    return basename($absolutePath);
}

function titleFromFilename($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = str_replace(['_', '-'], ' ', $name);
    return ucwords(trim($name));
}

$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// collect ZIPs (and optionally tar.gz later)
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($codesourceRoot, FilesystemIterator::SKIP_DOTS));
foreach ($it as $fi) {
    if ($fi->isFile()) {
        $ext = strtolower($fi->getExtension());
        if (in_array($ext, ['zip'])) {
            $files[] = $fi->getPathname();
        }
    }
}

$inserted = 0; $skipped = 0; $errors = 0; $details = [];
foreach ($files as $file) {
    $rel = relPath($file);
    // check duplicate by file_url
    $check = $pdo->prepare("SELECT id FROM resources WHERE type='code' AND file_url = ? LIMIT 1");
    $check->execute([$rel]);
    $id = $check->fetchColumn();
    if ($id) { $skipped++; $details[] = ['file'=>$rel,'status'=>'déjà présent','id'=>$id]; continue; }

    $title = titleFromFilename($file);
    $desc = 'Archive de code: ' . $rel;
    $author = 'Code Import';
    $size = @filesize($file); $sizeLabel = $size !== false ? formatBytes($size) : null;
    // cat par défaut 'software-engineering'
    $category = 'software-engineering';
    $tags = json_encode(['zip','code']);

    try {
        $stmt = $pdo->prepare("INSERT INTO resources (title, description, type, category, file_size, file_url, author, downloads, views, tags, is_active, created_at) VALUES (?, ?, 'code', ?, ?, ?, ?, 0, 0, ?, 1, NOW())");
        $stmt->execute([$title, $desc, $category, $sizeLabel, $rel, $author, $tags]);
        $inserted++; $details[] = ['file'=>$rel,'status'=>'importé','id'=>$pdo->lastInsertId()];
    } catch (Throwable $e) {
        $errors++; $details[] = ['file'=>$rel,'status'=>'erreur','message'=>$e->getMessage()];
    }
}

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Code Source</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Import des archives de code</h1>
        <div class="bg-white rounded shadow p-4 mb-4">
            <p><strong>Dossier scanné:</strong> <code><?php echo htmlspecialchars($codesourceRoot); ?></code></p>
            <p><strong>Trouvés:</strong> <?php echo count($files); ?> • <span class="text-green-700">Importés:</span> <?php echo $inserted; ?> • <span class="text-yellow-700">Ignorés:</span> <?php echo $skipped; ?> • <span class="text-red-700">Erreurs:</span> <?php echo $errors; ?></p>
        </div>
        <div class="bg-white rounded shadow p-4">
            <h2 class="text-lg font-semibold mb-3">Détails</h2>
            <ul class="space-y-2">
                <?php foreach ($details as $row): ?>
                <li class="text-sm">
                    <span class="font-mono"><?php echo htmlspecialchars($row['file']); ?></span> — <span class="font-medium"><?php echo htmlspecialchars($row['status']); ?></span>
                    <?php if (!empty($row['id'])): ?>(id: <?php echo (int)$row['id']; ?>)<?php endif; ?>
                    <?php if (!empty($row['message'])): ?><div class="text-red-600"><?php echo htmlspecialchars($row['message']); ?></div><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="mt-6 flex space-x-3">
            <a href="../resources.php?type=code" class="px-4 py-2 bg-blue-600 text-white rounded">Voir Code Source</a>
            <a href="../admin/resources.php?type=code" class="px-4 py-2 bg-gray-700 text-white rounded">Gérer côté Admin</a>
        </div>
    </div>
</body>
</html>


