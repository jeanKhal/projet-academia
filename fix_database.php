<?php
// Script de réparation complète de la base de données
echo "Réparation complète de la base de données Academy IA...\n";

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

// Créer les tables une par une
$tables = [
    'users' => "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('student', 'teacher', 'admin') DEFAULT 'student',
        bio TEXT,
        avatar VARCHAR(255),
        last_login DATETIME,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    'courses' => "CREATE TABLE courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        instructor VARCHAR(255) NOT NULL,
        duration VARCHAR(100) NOT NULL,
        level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'intermediate',
        category ENUM('embedded-systems', 'artificial-intelligence', 'machine-learning', 'deep-learning', 'software-engineering') NOT NULL,
        enrolled_students INT DEFAULT 0,
        modules_count INT DEFAULT 0,
        image_url VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    'modules' => "CREATE TABLE modules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        content TEXT,
        order_index INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )",
    
    'lessons' => "CREATE TABLE lessons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        order_index INT DEFAULT 0,
        duration_minutes INT DEFAULT 30,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
    )",
    
    'resources' => "CREATE TABLE resources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        type ENUM('document', 'video', 'code', 'book', 'presentation', 'dataset') NOT NULL,
        category ENUM('embedded-systems', 'artificial-intelligence', 'machine-learning', 'deep-learning', 'software-engineering', 'mathematics', 'programming') NOT NULL,
        file_size VARCHAR(50),
        file_url VARCHAR(255),
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        author VARCHAR(255) NOT NULL,
        downloads INT DEFAULT 0,
        views INT DEFAULT 0,
        tags JSON,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'user_courses' => "CREATE TABLE user_courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        progress_percentage DECIMAL(5,2) DEFAULT 0.00,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_course (user_id, course_id)
    )",
    
    'forum_posts' => "CREATE TABLE forum_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        category VARCHAR(100),
        tags JSON,
        views INT DEFAULT 0,
        likes INT DEFAULT 0,
        is_solved BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    'forum_replies' => "CREATE TABLE forum_replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        is_solution BOOLEAN DEFAULT FALSE,
        likes INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    'quizzes' => "CREATE TABLE quizzes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        time_limit_minutes INT DEFAULT 30,
        passing_score DECIMAL(5,2) DEFAULT 70.00,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )",
    
    'quiz_questions' => "CREATE TABLE quiz_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quiz_id INT NOT NULL,
        question TEXT NOT NULL,
        question_type ENUM('multiple_choice', 'true_false', 'text') DEFAULT 'multiple_choice',
        options JSON,
        correct_answer VARCHAR(255),
        points INT DEFAULT 1,
        order_index INT DEFAULT 0,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    )",
    
    'quiz_results' => "CREATE TABLE quiz_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        quiz_id INT NOT NULL,
        score DECIMAL(5,2),
        time_taken_minutes INT,
        passed BOOLEAN,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    )",
    
    'notifications' => "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

echo "\nCréation des tables :\n";
$tablesCreated = 0;

foreach ($tables as $tableName => $createSQL) {
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

// Insérer les données de test
echo "\nInsertion des données de test :\n";

// Utilisateurs de test
try {
    $pdo->exec("INSERT INTO users (full_name, email, password, role) VALUES
    ('Étudiant Test', 'student@academy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
    ('Enseignant Test', 'teacher@academy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher'),
    ('Admin Test', 'admin@academy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')");
    echo "✓ Utilisateurs de test insérés\n";
} catch (PDOException $e) {
    echo "⚠️  Utilisateurs déjà existants ou erreur: " . $e->getMessage() . "\n";
}

// Cours de test
try {
    $pdo->exec("INSERT INTO courses (title, description, instructor, duration, level, category, enrolled_students, modules_count) VALUES
    ('Introduction aux Systèmes Embarqués', 'Découvrez les fondamentaux des systèmes embarqués, de l\'architecture matérielle aux logiciels temps réel.', 'Dr. Marie Dubois', '12 semaines', 'beginner', 'embedded-systems', 45, 8),
    ('Intelligence Artificielle Fondamentale', 'Maîtrisez les concepts de base de l\'IA : algorithmes de recherche, logique floue et systèmes experts.', 'Prof. Jean Martin', '16 semaines', 'intermediate', 'artificial-intelligence', 78, 12),
    ('Machine Learning Avancé', 'Apprenez les techniques avancées de machine learning : réseaux de neurones, SVM, et ensemble methods.', 'Dr. Sophie Bernard', '14 semaines', 'advanced', 'machine-learning', 32, 10),
    ('Deep Learning avec TensorFlow', 'Plongez dans le deep learning avec TensorFlow : CNNs, RNNs, et architectures modernes.', 'Prof. Pierre Durand', '18 semaines', 'advanced', 'deep-learning', 28, 15),
    ('Génie Logiciel et Architecture', 'Développez des applications robustes avec les meilleures pratiques du génie logiciel.', 'Dr. Anne Moreau', '10 semaines', 'intermediate', 'software-engineering', 56, 9),
    ('Programmation Temps Réel', 'Maîtrisez la programmation temps réel pour les systèmes embarqués critiques.', 'Prof. Michel Leroy', '8 semaines', 'advanced', 'embedded-systems', 23, 6)");
    echo "✓ Cours de test insérés\n";
} catch (PDOException $e) {
    echo "⚠️  Cours déjà existants ou erreur: " . $e->getMessage() . "\n";
}

// Ressources de test
try {
    $pdo->exec("INSERT INTO resources (title, description, type, category, file_size, author, downloads, views, tags) VALUES
    ('Guide Complet des Systèmes Embarqués', 'Un guide détaillé couvrant tous les aspects des systèmes embarqués, de la conception à l\'implémentation.', 'document', 'embedded-systems', '2.5 MB', 'Dr. Marie Dubois', 156, 342, '[\"systèmes embarqués\", \"microcontrôleurs\", \"RTOS\", \"programmation C\"]'),
    ('Introduction au Machine Learning - Cours Vidéo', 'Série de vidéos couvrant les fondamentaux du machine learning avec des exemples pratiques.', 'video', 'machine-learning', '450 MB', 'Prof. Jean Martin', 89, 234, '[\"machine learning\", \"python\", \"scikit-learn\", \"algorithmes\"]'),
    ('Code Source - Projet Deep Learning', 'Implémentation complète d\'un réseau de neurones convolutif pour la classification d\'images.', 'code', 'deep-learning', '15 MB', 'Dr. Sophie Bernard', 67, 189, '[\"deep learning\", \"tensorflow\", \"CNN\", \"classification d\'images\"]'),
    ('Architecture Logicielle - Patterns et Bonnes Pratiques', 'Livre électronique sur les patterns d\'architecture et les bonnes pratiques en génie logiciel.', 'book', 'software-engineering', '8.2 MB', 'Dr. Anne Moreau', 123, 298, '[\"architecture\", \"patterns\", \"bonnes pratiques\", \"design patterns\"]'),
    ('Présentation - Intelligence Artificielle Avancée', 'Support de cours sur les techniques avancées d\'intelligence artificielle et leurs applications.', 'presentation', 'artificial-intelligence', '3.1 MB', 'Prof. Pierre Durand', 78, 156, '[\"IA\", \"algorithmes\", \"logique floue\", \"systèmes experts\"]'),
    ('Dataset - Données de Capteurs IoT', 'Collection de données de capteurs IoT pour l\'analyse et le traitement de signaux.', 'dataset', 'embedded-systems', '25 MB', 'Prof. Michel Leroy', 45, 98, '[\"IoT\", \"capteurs\", \"données\", \"analyse\"]'),
    ('Tutoriel Python pour l\'IA', 'Guide pratique pour utiliser Python dans les projets d\'intelligence artificielle.', 'document', 'artificial-intelligence', '1.8 MB', 'Dr. Sophie Bernard', 234, 567, '[\"python\", \"IA\", \"tutoriel\", \"programmation\"]'),
    ('Vidéo - Programmation Temps Réel', 'Cours vidéo sur la programmation temps réel pour les systèmes embarqués critiques.', 'video', 'embedded-systems', '320 MB', 'Prof. Michel Leroy', 34, 87, '[\"temps réel\", \"systèmes critiques\", \"RTOS\", \"programmation\"]')");
    echo "✓ Ressources de test insérées\n";
} catch (PDOException $e) {
    echo "⚠️  Ressources déjà existantes ou erreur: " . $e->getMessage() . "\n";
}

// Inscriptions de test
try {
    $pdo->exec("INSERT INTO user_courses (user_id, course_id, progress_percentage) VALUES
    (1, 1, 25.50),
    (1, 2, 0.00),
    (2, 3, 75.00),
    (2, 4, 100.00)");
    echo "✓ Inscriptions de test insérées\n";
} catch (PDOException $e) {
    echo "⚠️  Inscriptions déjà existantes ou erreur: " . $e->getMessage() . "\n";
}

echo "\n✅ Réparation de la base de données terminée !\n";
echo "  - Tables créées : $tablesCreated\n";
echo "  - Données de test insérées\n";
echo "\nVotre application est maintenant prête à être utilisée !\n";
?>
