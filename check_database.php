<?php
// Script de vérification et réparation de la base de données
echo "Vérification de la base de données Academy IA...\n";

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=academy_ia;charset=utf8mb4",
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✓ Connexion à la base de données réussie\n";
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage() . "\n");
}

// Vérifier les tables existantes
$tables = ['users', 'courses', 'modules', 'lessons', 'resources', 'user_courses', 'forum_posts', 'forum_replies', 'quizzes', 'quiz_questions', 'quiz_results', 'notifications'];

echo "\nVérification des tables :\n";
$missingTables = [];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' existe\n";
        } else {
            echo "✗ Table '$table' manquante\n";
            $missingTables[] = $table;
        }
    } catch (PDOException $e) {
        echo "✗ Erreur lors de la vérification de '$table': " . $e->getMessage() . "\n";
        $missingTables[] = $table;
    }
}

if (empty($missingTables)) {
    echo "\n✅ Toutes les tables existent !\n";
} else {
    echo "\n⚠️  Tables manquantes détectées. Recréation en cours...\n";
    
    // Lire et exécuter le fichier schema.sql
    $schemaFile = 'database/schema.sql';
    if (!file_exists($schemaFile)) {
        die("Erreur : Le fichier schema.sql n'existe pas dans le dossier database/\n");
    }

    $sql = file_get_contents($schemaFile);
    
    // Diviser le SQL en requêtes individuelles
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $tablesCreated = 0;
    $dataInserted = 0;
    
    foreach ($queries as $query) {
        if (empty($query) || strpos($query, '--') === 0) {
            continue; // Ignorer les commentaires et lignes vides
        }
        
        try {
            $pdo->exec($query);
            
            if (stripos($query, 'CREATE TABLE') !== false) {
                $tablesCreated++;
            } elseif (stripos($query, 'INSERT INTO') !== false) {
                $dataInserted++;
            }
        } catch (PDOException $e) {
            // Ignorer les erreurs de tables déjà existantes
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "Attention lors de l'exécution : " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "✓ Réparation terminée !\n";
    echo "  - Tables créées : $tablesCreated\n";
    echo "  - Données insérées : $dataInserted\n";
}

// Vérification finale
echo "\nVérification finale des tables :\n";
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' OK\n";
        } else {
            echo "❌ Table '$table' toujours manquante\n";
        }
    } catch (PDOException $e) {
        echo "❌ Erreur avec '$table': " . $e->getMessage() . "\n";
    }
}

echo "\nVérification terminée !\n";
?>
