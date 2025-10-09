<?php
// Script pour ajouter la colonne duration manquante aux certifications
echo "Ajout de la colonne duration aux certifications...\n";

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

// Ajouter la colonne duration
echo "\nAjout de la colonne duration :\n";

try {
    $pdo->exec("ALTER TABLE certification_paths ADD COLUMN duration INT DEFAULT 0");
    echo "✓ Colonne duration ajoutée avec succès\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✓ Colonne duration existe déjà\n";
    } else {
        echo "⚠️  Erreur: " . $e->getMessage() . "\n";
    }
}

// Mettre à jour les durées des certifications existantes
echo "\nMise à jour des durées des certifications :\n";

try {
    $pdo->exec("UPDATE certification_paths SET duration = 120 WHERE id = 1");
    echo "✓ Certification 1 (Machine Learning) : 120 heures\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour certification 1: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("UPDATE certification_paths SET duration = 80 WHERE id = 2");
    echo "✓ Certification 2 (Data Science) : 80 heures\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour certification 2: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("UPDATE certification_paths SET duration = 150 WHERE id = 3");
    echo "✓ Certification 3 (Systèmes Embarqués) : 150 heures\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour certification 3: " . $e->getMessage() . "\n";
}

echo "\n✅ Ajout de la colonne duration terminé !\n";
echo "  - Colonne duration ajoutée à certification_paths\n";
echo "  - Durées mises à jour pour toutes les certifications\n";
echo "\nLes certifications ont maintenant des durées définies !\n";
?>
