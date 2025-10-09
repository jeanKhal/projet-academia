<?php
// Script pour ajouter les colonnes manquantes aux tables de certification
echo "Réparation des tables de certification...\n";

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

// Ajouter les colonnes manquantes aux tables de certification
$alterQueries = [
    "ALTER TABLE certification_paths ADD COLUMN skills_covered JSON",
    "ALTER TABLE certification_paths ADD COLUMN prerequisites JSON",
    "ALTER TABLE certification_paths ADD COLUMN difficulty INT DEFAULT 3",
    "ALTER TABLE certification_paths ADD COLUMN is_active BOOLEAN DEFAULT TRUE",
    "ALTER TABLE certification_paths ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    
    "ALTER TABLE path_resources ADD COLUMN resource_order INT DEFAULT 0",
    "ALTER TABLE path_resources ADD COLUMN is_required BOOLEAN DEFAULT TRUE",
    
    "ALTER TABLE certification_quizzes ADD COLUMN quiz_order INT DEFAULT 0",
    "ALTER TABLE certification_quizzes ADD COLUMN time_limit INT DEFAULT 30",
    "ALTER TABLE certification_quizzes ADD COLUMN passing_score INT DEFAULT 70",
    "ALTER TABLE certification_quizzes ADD COLUMN is_active BOOLEAN DEFAULT TRUE",
    "ALTER TABLE certification_quizzes ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    
    "ALTER TABLE user_certifications ADD COLUMN is_active BOOLEAN DEFAULT TRUE",
    "ALTER TABLE user_certifications ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
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

// Mettre à jour les certifications existantes avec des données
echo "\nMise à jour des certifications existantes :\n";

try {
    $pdo->exec("UPDATE certification_paths SET 
        skills_covered = '[\"Machine Learning\", \"Deep Learning\", \"Python\", \"TensorFlow\", \"Projets pratiques\"]',
        prerequisites = '[\"Bases en mathématiques\", \"Connaissances en programmation\", \"Python de base\"]',
        difficulty = 4,
        is_active = TRUE
        WHERE id = 1");
    echo "✓ Certification 1 mise à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour certification 1: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("UPDATE certification_paths SET 
        skills_covered = '[\"Python avancé\", \"Data Science\", \"Pandas\", \"NumPy\", \"Matplotlib\"]',
        prerequisites = '[\"Python intermédiaire\", \"Bases en statistiques\"]',
        difficulty = 3,
        is_active = TRUE
        WHERE id = 2");
    echo "✓ Certification 2 mise à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour certification 2: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("UPDATE certification_paths SET 
        skills_covered = '[\"Systèmes embarqués\", \"IoT\", \"Arduino\", \"Raspberry Pi\", \"IA embarquée\"]',
        prerequisites = '[\"Électronique de base\", \"Programmation C/C++\", \"Concepts d''IA\"]',
        difficulty = 5,
        is_active = TRUE
        WHERE id = 3");
    echo "✓ Certification 3 mise à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour certification 3: " . $e->getMessage() . "\n";
}

// Mettre à jour les ressources de certification
echo "\nMise à jour des ressources de certification :\n";

try {
    $pdo->exec("UPDATE path_resources SET 
        resource_order = 1, is_required = TRUE
        WHERE certification_path_id = 1 AND resource_id = 1");
    echo "✓ Ressource 1-1 mise à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour ressource 1-1: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("UPDATE path_resources SET 
        resource_order = 2, is_required = TRUE
        WHERE certification_path_id = 1 AND resource_id = 2");
    echo "✓ Ressource 1-2 mise à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour ressource 1-2: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("UPDATE path_resources SET 
        resource_order = 1, is_required = TRUE
        WHERE certification_path_id = 2 AND resource_id = 3");
    echo "✓ Ressource 2-1 mise à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour ressource 2-1: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("UPDATE path_resources SET 
        resource_order = 2, is_required = FALSE
        WHERE certification_path_id = 2 AND resource_id = 4");
    echo "✓ Ressource 2-2 mise à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur mise à jour ressource 2-2: " . $e->getMessage() . "\n";
}

// Créer des quiz de test
echo "\nCréation de quiz de test :\n";

try {
    $pdo->exec("INSERT INTO certification_quizzes (certification_path_id, title, description, quiz_order, time_limit, passing_score) VALUES
    (1, 'Quiz Machine Learning', 'Évaluez vos connaissances en machine learning', 1, 30, 70),
    (1, 'Quiz Deep Learning', 'Testez votre compréhension du deep learning', 2, 45, 75),
    (2, 'Quiz Python Data Science', 'Vérifiez vos compétences en Python pour la data science', 1, 25, 65),
    (3, 'Quiz Systèmes Embarqués', 'Évaluez vos connaissances en systèmes embarqués', 1, 40, 80)");
    echo "✓ Quiz de test créés\n";
} catch (PDOException $e) {
    echo "⚠️  Quiz déjà existants ou erreur: " . $e->getMessage() . "\n";
}

// Créer des questions de test
echo "\nCréation de questions de test :\n";

try {
    $pdo->exec("INSERT INTO certification_quiz_questions (quiz_id, question_text, question_type, options, correct_answer) VALUES
    (1, 'Qu''est-ce que le machine learning ?', 'multiple_choice', '[\"Un type de programmation\", \"Un sous-ensemble de l''IA\", \"Un langage de programmation\", \"Un framework\"]', 'Un sous-ensemble de l''IA'),
    (1, 'Quel algorithme est utilisé pour la classification ?', 'multiple_choice', '[\"K-means\", \"Linear Regression\", \"Random Forest\", \"Tous les précédents\"]', 'Random Forest'),
    (2, 'Qu''est-ce qu''un réseau de neurones ?', 'multiple_choice', '[\"Un algorithme simple\", \"Un modèle inspiré du cerveau\", \"Un type de base de données\", \"Un langage de programmation\"]', 'Un modèle inspiré du cerveau'),
    (3, 'Quelle bibliothèque Python est utilisée pour la data science ?', 'multiple_choice', '[\"Django\", \"Pandas\", \"Flask\", \"PyTorch\"]', 'Pandas'),
    (4, 'Qu''est-ce qu''Arduino ?', 'multiple_choice', '[\"Un ordinateur\", \"Une plateforme de développement\", \"Un langage de programmation\", \"Un système d''exploitation\"]', 'Une plateforme de développement')");
    echo "✓ Questions de test créées\n";
} catch (PDOException $e) {
    echo "⚠️  Questions déjà existantes ou erreur: " . $e->getMessage() . "\n";
}

// Créer des certifications utilisateur de test
echo "\nCréation de certifications utilisateur de test :\n";

try {
    $pdo->exec("INSERT INTO user_certifications (user_id, certification_path_id, obtained_at, score) VALUES
    (1, 1, NOW(), 85.5),
    (2, 1, NOW(), 92.0),
    (1, 2, NOW(), 78.5)");
    echo "✓ Certifications utilisateur de test créées\n";
} catch (PDOException $e) {
    echo "⚠️  Certifications utilisateur déjà existantes ou erreur: " . $e->getMessage() . "\n";
}

echo "\n✅ Réparation des tables de certification terminée !\n";
echo "  - Colonnes manquantes ajoutées\n";
echo "  - Données mises à jour\n";
echo "  - Quiz et questions créés\n";
echo "\nLe système de certification est maintenant complet !\n";
?>
