<?php
// Script pour ajouter les colonnes manquantes à la table courses
echo "Réparation de la table courses...\n";

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

// Ajouter les colonnes manquantes à la table courses
$alterQueries = [
    "ALTER TABLE courses ADD COLUMN subtitle VARCHAR(500) DEFAULT ''",
    "ALTER TABLE courses ADD COLUMN learning_objectives JSON",
    "ALTER TABLE courses ADD COLUMN prerequisites JSON",
    "ALTER TABLE courses ADD COLUMN instructor VARCHAR(255) DEFAULT 'Académie IA'",
    "ALTER TABLE courses ADD COLUMN level ENUM('débutant', 'intermédiaire', 'avancé') DEFAULT 'débutant'",
    "ALTER TABLE courses ADD COLUMN duration INT DEFAULT 0",
    "ALTER TABLE courses ADD COLUMN is_active BOOLEAN DEFAULT TRUE",
    "ALTER TABLE courses ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
];

echo "\nAjout des colonnes manquantes :\n";
foreach ($alterQueries as $query) {
    try {
        $pdo->exec($query);
        echo "✓ Colonne ajoutée avec succès\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✓ Colonne existe déjà\n";
        } else {
            echo "⚠️  Erreur: " . $e->getMessage() . "\n";
        }
    }
}

// Mettre à jour les cours existants avec des données
echo "\nMise à jour des cours existants :\n";

try {
    $pdo->exec("UPDATE courses SET 
        subtitle = 'Maîtrisez les concepts fondamentaux de l''intelligence artificielle',
        learning_objectives = '[\"Comprendre les bases de l''IA\", \"Implémenter des algorithmes de ML\", \"Créer des projets pratiques\"]',
        prerequisites = '[\"Bases en mathématiques\", \"Connaissances en programmation\"]',
        instructor = 'Dr. Marie Dupont',
        level = 'débutant',
        duration = 12,
        is_active = TRUE
        WHERE id = 1");
    echo "✓ Cours 1 mis à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour cours 1: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("UPDATE courses SET 
        subtitle = 'Apprenez Python pour l''intelligence artificielle',
        learning_objectives = '[\"Maîtriser Python\", \"Utiliser les bibliothèques IA\", \"Développer des modèles\"]',
        prerequisites = '[\"Aucun prérequis\"]',
        instructor = 'Prof. Jean Martin',
        level = 'débutant',
        duration = 8,
        is_active = TRUE
        WHERE id = 2");
    echo "✓ Cours 2 mis à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour cours 2: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("UPDATE courses SET 
        subtitle = 'Développez des systèmes embarqués intelligents',
        learning_objectives = '[\"Concevoir des systèmes embarqués\", \"Intégrer l''IA\", \"Optimiser les performances\"]',
        prerequisites = '[\"Bases en électronique\", \"Connaissances en C/C++\"]',
        instructor = 'Ing. Pierre Dubois',
        level = 'avancé',
        duration = 15,
        is_active = TRUE
        WHERE id = 3");
    echo "✓ Cours 3 mis à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour cours 3: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("UPDATE courses SET 
        subtitle = 'Créez des applications IA avec TensorFlow',
        learning_objectives = '[\"Maîtriser TensorFlow\", \"Créer des réseaux de neurones\", \"Déployer des modèles\"]',
        prerequisites = '[\"Python intermédiaire\", \"Bases en ML\"]',
        instructor = 'Dr. Sophie Bernard',
        level = 'intermédiaire',
        duration = 10,
        is_active = TRUE
        WHERE id = 4");
    echo "✓ Cours 4 mis à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour cours 4: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("UPDATE courses SET 
        subtitle = 'Analysez et visualisez des données avec Python',
        learning_objectives = '[\"Analyser des données\", \"Créer des visualisations\", \"Extraire des insights\"]',
        prerequisites = '[\"Python de base\", \"Mathématiques de base\"]',
        instructor = 'Prof. Claire Moreau',
        level = 'intermédiaire',
        duration = 6,
        is_active = TRUE
        WHERE id = 5");
    echo "✓ Cours 5 mis à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour cours 5: " . $e->getMessage() . "\n";
}

echo "\n✅ Réparation de la table courses terminée !\n";
echo "  - Colonnes manquantes ajoutées\n";
echo "  - Données mises à jour\n";
echo "\nLa table courses est maintenant complète !\n";
?>
