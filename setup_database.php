<?php
// Script de configuration automatique de la base de données
echo "Configuration de la base de données Academy IA...\n";

// Connexion à MySQL sans spécifier de base de données
try {
    $pdo = new PDO(
        "mysql:host=localhost;charset=utf8mb4",
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✓ Connexion à MySQL réussie\n";
} catch (PDOException $e) {
    die("Erreur de connexion à MySQL : " . $e->getMessage() . "\n");
}

// Créer la base de données si elle n'existe pas
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS academy_ia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Base de données 'academy_ia' créée ou déjà existante\n";
} catch (PDOException $e) {
    die("Erreur lors de la création de la base de données : " . $e->getMessage() . "\n");
}

// Se connecter à la base de données academy_ia
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
    echo "✓ Connexion à la base de données 'academy_ia' réussie\n";
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage() . "\n");
}

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

echo "✓ Configuration terminée avec succès !\n";
echo "  - Tables créées : $tablesCreated\n";
echo "  - Données insérées : $dataInserted\n";
echo "\nVotre base de données est maintenant prête à être utilisée !\n";
?>
