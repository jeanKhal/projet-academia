<?php
// Script pour créer le système de certifications basé sur les ressources
echo "Création du système de certifications Academy IA...\n";

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

// Nouvelles tables pour le système de certifications
$newTables = [
    'certification_paths' => "CREATE TABLE certification_paths (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        category ENUM('embedded-systems', 'artificial-intelligence', 'machine-learning', 'deep-learning', 'software-engineering', 'mathematics', 'programming') NOT NULL,
        level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
        estimated_hours INT DEFAULT 20,
        badge_image VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    'path_resources' => "CREATE TABLE path_resources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        certification_path_id INT NOT NULL,
        resource_id INT NOT NULL,
        order_index INT DEFAULT 0,
        is_required BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (certification_path_id) REFERENCES certification_paths(id) ON DELETE CASCADE,
        FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
        UNIQUE KEY unique_path_resource (certification_path_id, resource_id)
    )",
    
    'certification_quizzes' => "CREATE TABLE certification_quizzes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        certification_path_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        time_limit_minutes INT DEFAULT 45,
        passing_score DECIMAL(5,2) DEFAULT 75.00,
        questions_count INT DEFAULT 20,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (certification_path_id) REFERENCES certification_paths(id) ON DELETE CASCADE
    )",
    
    'user_certifications' => "CREATE TABLE user_certifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        certification_path_id INT NOT NULL,
        quiz_id INT NOT NULL,
        score DECIMAL(5,2),
        passed BOOLEAN DEFAULT FALSE,
        certificate_url VARCHAR(255),
        issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (certification_path_id) REFERENCES certification_paths(id) ON DELETE CASCADE,
        FOREIGN KEY (quiz_id) REFERENCES certification_quizzes(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_certification (user_id, certification_path_id)
    )",
    
    'user_progress' => "CREATE TABLE user_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        certification_path_id INT NOT NULL,
        resources_completed INT DEFAULT 0,
        total_resources INT DEFAULT 0,
        progress_percentage DECIMAL(5,2) DEFAULT 0.00,
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (certification_path_id) REFERENCES certification_paths(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_progress (user_id, certification_path_id)
    )",
    
    'resource_views' => "CREATE TABLE resource_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        resource_id INT NOT NULL,
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        time_spent_minutes INT DEFAULT 0,
        completed BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
    )"
];

echo "\nCréation des nouvelles tables :\n";
$tablesCreated = 0;

foreach ($newTables as $tableName => $createSQL) {
    try {
        $pdo->exec($createSQL);
        echo "✓ Table '$tableName' créée\n";
        $tablesCreated++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "✓ Table '$tableName' existe déjà\n";
        } else {
            echo "✗ Erreur lors de la création de '$tableName': " . $e->getMessage() . "\n";
        }
    }
}

// Insérer des parcours de certification de test
echo "\nInsertion des parcours de certification :\n";

try {
    $pdo->exec("INSERT INTO certification_paths (title, description, category, level, estimated_hours, badge_image) VALUES
    ('Certification IA Fondamentale', 'Maîtrisez les concepts de base de l\'intelligence artificielle et des algorithmes de machine learning.', 'artificial-intelligence', 'beginner', 25, 'badges/ai-fundamental.png'),
    ('Certification Deep Learning', 'Plongez dans les réseaux de neurones profonds et les architectures modernes.', 'deep-learning', 'advanced', 40, 'badges/deep-learning.png'),
    ('Certification Systèmes Embarqués', 'Développez des systèmes embarqués robustes et temps réel.', 'embedded-systems', 'intermediate', 30, 'badges/embedded-systems.png'),
    ('Certification Machine Learning Avancé', 'Maîtrisez les techniques avancées de ML et d\'optimisation.', 'machine-learning', 'advanced', 35, 'badges/ml-advanced.png'),
    ('Certification Génie Logiciel', 'Développez des applications robustes avec les meilleures pratiques.', 'software-engineering', 'intermediate', 28, 'badges/software-engineering.png'),
    ('Certification Python pour l\'IA', 'Apprenez Python et ses bibliothèques pour l\'intelligence artificielle.', 'programming', 'beginner', 20, 'badges/python-ai.png')");
    echo "✓ Parcours de certification créés\n";
} catch (PDOException $e) {
    echo "⚠️  Parcours déjà existants ou erreur: " . $e->getMessage() . "\n";
}

// Créer des quiz de certification
try {
    $pdo->exec("INSERT INTO certification_quizzes (certification_path_id, title, description, time_limit_minutes, passing_score, questions_count) VALUES
    (1, 'Quiz IA Fondamentale', 'Testez vos connaissances en intelligence artificielle de base', 45, 75.00, 25),
    (2, 'Quiz Deep Learning', 'Évaluez votre maîtrise des réseaux de neurones profonds', 60, 80.00, 30),
    (3, 'Quiz Systèmes Embarqués', 'Validez vos compétences en programmation embarquée', 50, 75.00, 28),
    (4, 'Quiz Machine Learning Avancé', 'Testez vos connaissances en ML avancé', 55, 80.00, 32),
    (5, 'Quiz Génie Logiciel', 'Évaluez vos compétences en architecture logicielle', 45, 75.00, 25),
    (6, 'Quiz Python pour l\'IA', 'Validez votre maîtrise de Python pour l\'IA', 40, 70.00, 20)");
    echo "✓ Quiz de certification créés\n";
} catch (PDOException $e) {
    echo "⚠️  Quiz déjà existants ou erreur: " . $e->getMessage() . "\n";
}

// Associer les ressources existantes aux parcours
try {
    $pdo->exec("INSERT INTO path_resources (certification_path_id, resource_id, order_index, is_required) VALUES
    (1, 1, 1, TRUE), (1, 2, 2, TRUE), (1, 7, 3, TRUE),
    (2, 3, 1, TRUE), (2, 5, 2, TRUE),
    (3, 1, 1, TRUE), (3, 6, 2, TRUE), (3, 8, 3, TRUE),
    (4, 2, 1, TRUE), (4, 3, 2, TRUE),
    (5, 4, 1, TRUE),
    (6, 7, 1, TRUE), (6, 2, 2, TRUE)");
    echo "✓ Associations ressources-parcours créées\n";
} catch (PDOException $e) {
    echo "⚠️  Associations déjà existantes ou erreur: " . $e->getMessage() . "\n";
}

// Mettre à jour les progressions
try {
    $pdo->exec("UPDATE path_resources pr 
                JOIN certification_paths cp ON pr.certification_path_id = cp.id 
                SET pr.total_resources = (
                    SELECT COUNT(*) FROM path_resources 
                    WHERE certification_path_id = cp.id
                )");
    echo "✓ Progressions mises à jour\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur lors de la mise à jour des progressions: " . $e->getMessage() . "\n";
}

echo "\n✅ Système de certifications créé avec succès !\n";
echo "  - Tables créées : $tablesCreated\n";
echo "  - 6 parcours de certification créés\n";
echo "  - 6 quiz de validation créés\n";
echo "  - Ressources associées aux parcours\n";
echo "\nVotre plateforme est maintenant orientée ressources et certifications !\n";
?>
