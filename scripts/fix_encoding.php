<?php
// Fix encoding for book resources: normalize title, description, author to UTF-8 (fr)

require_once __DIR__ . '/../config/database.php';

function getPDO(): PDO {
    return new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
}

function looksLikeMojibake(string $s): bool {
    $patterns = [
        'Ã', 'â¬', 'â', 'â€”', 'â€“', 'â€œ', 'â€	d', 'â€™', 'â€˜', 'Â', 'Ã©', 'Ã¨', 'Ãª', 'Ã«', 'Ã ', 'Ã¢', 'Ã¤', 'Ã¹', 'Ã»', 'Ã¼', 'Ã´', 'Ã¶', 'Ã§', 'Ã9' 
    ];
    foreach ($patterns as $p) {
        if (strpos($s, $p) !== false) return true;
    }
    return false;
}

function replaceCommonMojibake(string $s): string {
    $map = [
        // Accents
        'Ã©' => 'é', 'Ã¨' => 'è', 'Ãª' => 'ê', 'Ã«' => 'ë',
        'Ã ' => 'à', 'Ã¢' => 'â', 'Ã¤' => 'ä',
        'Ã¹' => 'ù', 'Ã»' => 'û', 'Ã¼' => 'ü',
        'Ã´' => 'ô', 'Ã¶' => 'ö',
        'Ã§' => 'ç',
        'Ã‰' => 'É', 'Ãˆ' => 'È', 'ÃŠ' => 'Ê', 'Ã‹' => 'Ë',
        'Ã€' => 'À', 'Ã‚' => 'Â', 'Ã„' => 'Ä',
        'Ã™' => 'Ù', 'Ã›' => 'Û', 'Ãœ' => 'Ü',
        'Ã”' => 'Ô', 'Ã–' => 'Ö',
        'Ã‡' => 'Ç',
        // Guillemets / ponctuation
        'â€œ' => '“', 'â€' => '”', 'â€˜' => '‘', 'â€™' => '’',
        'â€“' => '–', 'â€”' => '—', 'â€¦' => '…',
        'Â«' => '«', 'Â»' => '»', 'Â·' => '·', 'Â°' => '°', 'Â©' => '©',
        // Euro & divers
        'â¬' => '€', 'Â' => '',
    ];
    return strtr($s, $map);
}

function normalizeUtf8(?string $val): ?string {
    if ($val === null || $val === '') return $val;
    $orig = $val;
    // First, ensure UTF-8
    if (!mb_check_encoding($val, 'UTF-8')) {
        $val = mb_convert_encoding($val, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
    }
    // Replace common mojibake sequences
    if (looksLikeMojibake($val)) {
        $val = replaceCommonMojibake($val);
    }
    // Trim weird leftovers
    $val = preg_replace("/\x{00A0}/u", ' ', $val); // nbsp -> space
    $val = preg_replace('/\s+/u', ' ', $val);
    $val = trim($val);
    return $val === $orig ? $orig : $val;
}

function main(): void {
    setlocale(LC_ALL, 'fr_FR.UTF-8', 'fr_FR', 'fr');
    mb_internal_encoding('UTF-8');
    header('Content-Type: text/plain; charset=utf-8');

    $pdo = getPDO();
    $stmt = $pdo->query("SELECT id, title, description, author FROM resources WHERE type = 'book'");
    $rows = $stmt->fetchAll();

    $updated = 0; $checked = 0; $skipped = 0;
    $upd = $pdo->prepare("UPDATE resources SET title = ?, description = ?, author = ? WHERE id = ?");

    foreach ($rows as $r) {
        $checked++;
        $t = normalizeUtf8($r['title']);
        $d = normalizeUtf8($r['description']);
        $a = normalizeUtf8($r['author']);
        if ($t !== $r['title'] || $d !== $r['description'] || $a !== $r['author']) {
            $upd->execute([$t, $d, $a, $r['id']]);
            $updated++;
            echo "#" . $r['id'] . " corrigé\n";
        } else {
            $skipped++;
        }
    }

    echo "\nTerminé. Vérifiés: $checked, corrigés: $updated, inchangés: $skipped\n";
}

main();
?>


