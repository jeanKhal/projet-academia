<?php
require_once 'config/database.php';

echo "=== Vérification des cours dans la base de données ===\n\n";

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

// Vérifier si la table courses existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'courses'");
    if ($stmt->rowCount() == 0) {
        echo "⚠️  La table 'courses' n'existe pas. Création en cours...\n";
        
        $pdo->exec("CREATE TABLE courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            subtitle VARCHAR(255),
            description TEXT,
            category VARCHAR(100) NOT NULL,
            level VARCHAR(50) NOT NULL,
            duration VARCHAR(50),
            instructor VARCHAR(255),
            price DECIMAL(10,2) DEFAULT 0.00,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        echo "✓ Table 'courses' créée avec succès\n";
    } else {
        echo "✓ Table 'courses' existe déjà\n";
    }
} catch (PDOException $e) {
    echo "⚠️  Erreur lors de la vérification/création de la table: " . $e->getMessage() . "\n";
}

// Vérifier le nombre de cours existants
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
    $count = $stmt->fetch()['count'];
    echo "📊 Nombre de cours dans la base de données : $count\n";
    
    if ($count == 0) {
        echo "⚠️  Aucun cours trouvé. Ajout de cours de test...\n";
        
        // Ajouter des cours de test
        $courses = [
            [
                'title' => 'Introduction à l\'Intelligence Artificielle',
                'subtitle' => 'Les bases de l\'IA moderne',
                'description' => 'Découvrez les fondamentaux de l\'intelligence artificielle, du machine learning et des réseaux de neurones. Ce cours vous donnera une base solide pour comprendre et utiliser l\'IA.',
                'category' => 'artificial-intelligence',
                'level' => 'beginner',
                'duration' => '8 semaines',
                'instructor' => 'Dr. Marie Dubois',
                'price' => 0.00
            ],
            [
                'title' => 'Machine Learning Avancé',
                'subtitle' => 'Algorithms et applications pratiques',
                'description' => 'Maîtrisez les algorithmes de machine learning les plus utilisés : régression, classification, clustering et deep learning avec des projets concrets.',
                'category' => 'machine-learning',
                'level' => 'intermediate',
                'duration' => '12 semaines',
                'instructor' => 'Prof. Jean Martin',
                'price' => 0.00
            ],
            [
                'title' => 'Deep Learning avec TensorFlow',
                'subtitle' => 'Réseaux de neurones profonds',
                'description' => 'Apprenez à construire et entraîner des réseaux de neurones profonds avec TensorFlow et Keras pour la reconnaissance d\'images et le traitement du langage naturel.',
                'category' => 'deep-learning',
                'level' => 'advanced',
                'duration' => '10 semaines',
                'instructor' => 'Dr. Sophie Chen',
                'price' => 0.00
            ],
            [
                'title' => 'Systèmes Embarqués',
                'subtitle' => 'Programmation microcontrôleurs',
                'description' => 'Développez des systèmes embarqués avec Arduino et Raspberry Pi. Apprenez la programmation en C/C++ et l\'électronique numérique.',
                'category' => 'embedded-systems',
                'level' => 'intermediate',
                'duration' => '14 semaines',
                'instructor' => 'Ing. Pierre Durand',
                'price' => 0.00
            ],
            [
                'title' => 'Génie Logiciel Moderne',
                'subtitle' => 'Méthodes agiles et DevOps',
                'description' => 'Découvrez les méthodologies de développement moderne : Scrum, Kanban, CI/CD, Docker et Kubernetes pour des projets d\'entreprise.',
                'category' => 'software-engineering',
                'level' => 'intermediate',
                'duration' => '16 semaines',
                'instructor' => 'M. Thomas Bernard',
                'price' => 0.00
            ],
            [
                'title' => 'Python pour la Data Science',
                'subtitle' => 'Analyse et visualisation de données',
                'description' => 'Maîtrisez Python pour l\'analyse de données avec pandas, numpy, matplotlib et scikit-learn. Apprenez à extraire des insights de vos données.',
                'category' => 'artificial-intelligence',
                'level' => 'beginner',
                'duration' => '6 semaines',
                'instructor' => 'Dr. Ana Rodriguez',
                'price' => 0.00
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO courses (title, subtitle, description, category, level, duration, instructor, price, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)");
        
        foreach ($courses as $course) {
            $stmt->execute([
                $course['title'],
                $course['subtitle'],
                $course['description'],
                $course['category'],
                $course['level'],
                $course['duration'],
                $course['instructor'],
                $course['price']
            ]);
        }
        
        echo "✓ " . count($courses) . " cours de test ajoutés avec succès\n";
    }
    
    // Afficher les cours existants
    echo "\n📚 Cours disponibles :\n";
    $stmt = $pdo->query("SELECT id, title, category, level, instructor FROM courses ORDER BY created_at DESC");
    $courses = $stmt->fetchAll();
    
    foreach ($courses as $course) {
        echo "- ID: {$course['id']} | {$course['title']} ({$course['category']}, {$course['level']}) par {$course['instructor']}\n";
    }
    
} catch (PDOException $e) {
    echo "⚠️  Erreur lors de la vérification des cours: " . $e->getMessage() . "\n";
}

echo "\n✅ Vérification terminée !\n";
echo "Vous pouvez maintenant accéder à http://localhost:8000/courses.php pour voir les cours.\n";
?>
