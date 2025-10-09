<?php
// Script pour ajouter les tables manquantes pour le système de cours
echo "Réparation des tables du système de cours...\n";

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

// Créer les tables manquantes
$createTables = [
    "CREATE TABLE IF NOT EXISTS course_enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        progress DECIMAL(5,2) DEFAULT 0.00,
        completed_at TIMESTAMP NULL,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        UNIQUE KEY unique_enrollment (user_id, course_id)
    )",
    
    "CREATE TABLE IF NOT EXISTS course_modules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        module_order INT DEFAULT 0,
        duration INT DEFAULT 0,
        content_type ENUM('video', 'text', 'quiz', 'assignment') DEFAULT 'video',
        content_url VARCHAR(500),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS course_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        module_id INT NOT NULL,
        progress DECIMAL(5,2) DEFAULT 0.00,
        completed BOOLEAN DEFAULT FALSE,
        completed_at TIMESTAMP NULL,
        last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE CASCADE,
        UNIQUE KEY unique_progress (user_id, module_id)
    )",
    
    "CREATE TABLE IF NOT EXISTS course_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review_text TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        UNIQUE KEY unique_review (user_id, course_id)
    )"
];

echo "\nCréation des tables manquantes :\n";
foreach ($createTables as $query) {
    try {
        $pdo->exec($query);
        echo "✓ Table créée avec succès\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "✓ Table existe déjà\n";
        } else {
            echo "⚠️  Erreur: " . $e->getMessage() . "\n";
        }
    }
}

// Insérer des données de test pour les cours
echo "\nInsertion de données de test pour les cours :\n";

try {
    // Modules de test pour le cours 1
    $pdo->exec("INSERT INTO course_modules (course_id, title, description, module_order, duration, content_type) VALUES
    (1, 'Introduction à l''IA', 'Découverte des concepts fondamentaux de l''intelligence artificielle', 1, 45, 'video'),
    (1, 'Machine Learning de base', 'Apprentissage des algorithmes de machine learning', 2, 60, 'video'),
    (1, 'Projet pratique', 'Mise en pratique des concepts appris', 3, 90, 'assignment'),
    (2, 'Fondamentaux Python', 'Bases du langage Python pour l''IA', 1, 30, 'video'),
    (2, 'Bibliothèques IA', 'Utilisation de TensorFlow et PyTorch', 2, 75, 'video'),
    (2, 'Projet final', 'Création d''un modèle d''IA complet', 3, 120, 'assignment')");
    echo "✓ Modules de test créés\n";
} catch (PDOException $e) {
    echo "⚠️  Modules déjà existants ou erreur: " . $e->getMessage() . "\n";
}

try {
    // Inscriptions de test
    $pdo->exec("INSERT INTO course_enrollments (user_id, course_id, progress) VALUES
    (1, 1, 75.50),
    (1, 2, 45.25),
    (2, 1, 90.00),
    (2, 3, 30.75)");
    echo "✓ Inscriptions de test créées\n";
} catch (PDOException $e) {
    echo "⚠️  Inscriptions déjà existantes ou erreur: " . $e->getMessage() . "\n";
}

try {
    // Progression de test
    $pdo->exec("INSERT INTO course_progress (user_id, module_id, progress, completed) VALUES
    (1, 1, 100.00, TRUE),
    (1, 2, 75.50, FALSE),
    (1, 3, 0.00, FALSE),
    (2, 1, 100.00, TRUE),
    (2, 2, 100.00, TRUE),
    (2, 3, 90.00, FALSE)");
    echo "✓ Progression de test créée\n";
} catch (PDOException $e) {
    echo "⚠️  Progression déjà existante ou erreur: " . $e->getMessage() . "\n";
}

try {
    // Avis de test
    $pdo->exec("INSERT INTO course_reviews (user_id, course_id, rating, review_text) VALUES
    (1, 1, 5, 'Excellent cours ! Le contenu est très bien structuré et les explications sont claires.'),
    (2, 1, 4, 'Très bon cours pour débuter. Les exercices pratiques sont bien pensés.'),
    (1, 2, 4, 'Cours complet et bien organisé. Parfait pour apprendre Python.'),
    (2, 3, 5, 'Cours avancé mais accessible. Les projets sont très intéressants.')");
    echo "✓ Avis de test créés\n";
} catch (PDOException $e) {
    echo "⚠️  Avis déjà existants ou erreur: " . $e->getMessage() . "\n";
}

echo "\n✅ Réparation des tables du système de cours terminée !\n";
echo "  - Tables course_enrollments, course_modules, course_progress, course_reviews créées\n";
echo "  - Données de test insérées\n";
echo "\nLe système de cours est maintenant prêt à être utilisé !\n";
?>
