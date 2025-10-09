<?php
// Script pour ajouter les colonnes manquantes aux tables du forum
echo "Réparation des tables du forum...\n";

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

// Ajouter les colonnes manquantes à forum_posts
$alterQueries = [
    "ALTER TABLE forum_posts ADD COLUMN is_active BOOLEAN DEFAULT TRUE",
    "ALTER TABLE forum_posts ADD COLUMN is_pinned BOOLEAN DEFAULT FALSE",
    "ALTER TABLE forum_replies ADD COLUMN is_active BOOLEAN DEFAULT TRUE"
];

echo "\nAjout des colonnes manquantes :\n";
foreach ($alterQueries as $query) {
    try {
        $pdo->exec($query);
        echo "✓ Requête exécutée avec succès\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✓ Colonne existe déjà\n";
        } else {
            echo "⚠️  Erreur: " . $e->getMessage() . "\n";
        }
    }
}

// Insérer des données de test pour le forum
echo "\nInsertion de données de test pour le forum :\n";

try {
    // Posts de test
    $pdo->exec("INSERT INTO forum_posts (user_id, title, content, category, views, likes, is_active, is_pinned) VALUES
    (1, 'Bienvenue sur le forum Academy IA !', 'Bienvenue à tous sur le forum communautaire d''Academy IA ! Ici vous pouvez échanger sur l''intelligence artificielle, les systèmes embarqués et bien plus encore.', 'general', 15, 8, TRUE, TRUE),
    (2, 'Questions sur les réseaux de neurones', 'J''ai des questions sur l''implémentation des réseaux de neurones en Python. Quelqu''un peut-il m''aider ?', 'ai-discussion', 23, 5, TRUE, FALSE),
    (1, 'Projet système embarqué avec Arduino', 'Je travaille sur un projet de système embarqué avec Arduino. Voici mon code et mes questions...', 'embedded-systems', 12, 3, TRUE, FALSE),
    (2, 'Meilleures pratiques en programmation Python', 'Partageons nos meilleures pratiques pour la programmation Python, particulièrement pour l''IA.', 'programming', 18, 7, TRUE, FALSE),
    (1, 'Aide avec TensorFlow', 'J''ai des difficultés avec TensorFlow. Quelqu''un peut-il m''expliquer les concepts de base ?', 'help', 31, 12, TRUE, FALSE),
    (2, 'Projet de reconnaissance d''images', 'Je développe un projet de reconnaissance d''images avec OpenCV. Voici mon approche...', 'projects', 27, 9, TRUE, FALSE)");
    echo "✓ Posts de test créés\n";
} catch (PDOException $e) {
    echo "⚠️  Posts déjà existants ou erreur: " . $e->getMessage() . "\n";
}

try {
    // Réponses de test
    $pdo->exec("INSERT INTO forum_replies (post_id, user_id, content, is_solution, likes, is_active) VALUES
    (2, 2, 'Pour les réseaux de neurones en Python, je recommande d''utiliser TensorFlow ou PyTorch. Voici un exemple simple...', TRUE, 6, TRUE),
    (2, 1, 'Merci pour la réponse ! J''ai une question supplémentaire sur l''optimisation...', FALSE, 2, TRUE),
    (3, 2, 'Excellent projet ! Pour Arduino, je suggère d''utiliser des bibliothèques comme...', FALSE, 4, TRUE),
    (4, 1, 'Voici mes meilleures pratiques pour Python : 1. Utilisez des virtual environments...', TRUE, 8, TRUE),
    (5, 2, 'Pour TensorFlow, commencez par les tutoriels officiels. Voici les étapes...', TRUE, 10, TRUE),
    (6, 1, 'Très intéressant ! Pour OpenCV, voici quelques optimisations que vous pourriez essayer...', FALSE, 5, TRUE)");
    echo "✓ Réponses de test créées\n";
} catch (PDOException $e) {
    echo "⚠️  Réponses déjà existantes ou erreur: " . $e->getMessage() . "\n";
}

echo "\n✅ Réparation des tables du forum terminée !\n";
echo "  - Colonnes is_active et is_pinned ajoutées\n";
echo "  - Données de test insérées\n";
echo "\nLe forum est maintenant prêt à être utilisé !\n";
?>
