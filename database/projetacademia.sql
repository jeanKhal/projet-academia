-- Création de la base de données
CREATE DATABASE IF NOT EXISTS academy_ia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE academy_ia;

-- Table des utilisateurs
CREATE TABLE users (
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
);

-- Table des cours
CREATE TABLE courses (
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
);

-- Table des modules
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content TEXT,
    order_index INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Table des leçons
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    order_index INT DEFAULT 0,
    duration_minutes INT DEFAULT 30,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);

-- Table des ressources
CREATE TABLE resources (
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
);

-- Table des inscriptions aux cours
CREATE TABLE user_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_course (user_id, course_id)
);

-- Table des posts du forum
CREATE TABLE forum_posts (
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
);

-- Table des réponses du forum
CREATE TABLE forum_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    is_solution BOOLEAN DEFAULT FALSE,
    likes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des quiz
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    time_limit_minutes INT DEFAULT 30,
    passing_score DECIMAL(5,2) DEFAULT 70.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Table des questions de quiz
CREATE TABLE quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'text') DEFAULT 'multiple_choice',
    options JSON,
    correct_answer VARCHAR(255),
    points INT DEFAULT 1,
    order_index INT DEFAULT 0,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Table des résultats de quiz
CREATE TABLE quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score DECIMAL(5,2),
    time_taken_minutes INT,
    passed BOOLEAN,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Table des notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insertion des données de test

-- Utilisateurs de test
INSERT INTO users (full_name, email, password, role) VALUES
('Étudiant Test', 'student@academy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Enseignant Test', 'teacher@academy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher'),
('Admin Test', 'admin@academy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Cours de test
INSERT INTO courses (title, description, instructor, duration, level, category, enrolled_students, modules_count) VALUES
('Introduction aux Systèmes Embarqués', 'Découvrez les fondamentaux des systèmes embarqués, de l\'architecture matérielle aux logiciels temps réel.', 'Dr. Marie Dubois', '12 semaines', 'beginner', 'embedded-systems', 45, 8),
('Intelligence Artificielle Fondamentale', 'Maîtrisez les concepts de base de l\'IA : algorithmes de recherche, logique floue et systèmes experts.', 'Prof. Jean Martin', '16 semaines', 'intermediate', 'artificial-intelligence', 78, 12),
('Machine Learning Avancé', 'Apprenez les techniques avancées de machine learning : réseaux de neurones, SVM, et ensemble methods.', 'Dr. Sophie Bernard', '14 semaines', 'advanced', 'machine-learning', 32, 10),
('Deep Learning avec TensorFlow', 'Plongez dans le deep learning avec TensorFlow : CNNs, RNNs, et architectures modernes.', 'Prof. Pierre Durand', '18 semaines', 'advanced', 'deep-learning', 28, 15),
('Génie Logiciel et Architecture', 'Développez des applications robustes avec les meilleures pratiques du génie logiciel.', 'Dr. Anne Moreau', '10 semaines', 'intermediate', 'software-engineering', 56, 9),
('Programmation Temps Réel', 'Maîtrisez la programmation temps réel pour les systèmes embarqués critiques.', 'Prof. Michel Leroy', '8 semaines', 'advanced', 'embedded-systems', 23, 6);

-- Ressources de test
INSERT INTO resources (title, description, type, category, file_size, author, downloads, views, tags) VALUES
('Guide Complet des Systèmes Embarqués', 'Un guide détaillé couvrant tous les aspects des systèmes embarqués, de la conception à l\'implémentation.', 'document', 'embedded-systems', '2.5 MB', 'Dr. Marie Dubois', 156, 342, '["systèmes embarqués", "microcontrôleurs", "RTOS", "programmation C"]'),
('Introduction au Machine Learning - Cours Vidéo', 'Série de vidéos couvrant les fondamentaux du machine learning avec des exemples pratiques.', 'video', 'machine-learning', '450 MB', 'Prof. Jean Martin', 89, 234, '["machine learning", "python", "scikit-learn", "algorithmes"]'),
('Code Source - Projet Deep Learning', 'Implémentation complète d\'un réseau de neurones convolutif pour la classification d\'images.', 'code', 'deep-learning', '15 MB', 'Dr. Sophie Bernard', 67, 189, '["deep learning", "tensorflow", "CNN", "classification d\'images"]'),
('Architecture Logicielle - Patterns et Bonnes Pratiques', 'Livre électronique sur les patterns d\'architecture et les bonnes pratiques en génie logiciel.', 'book', 'software-engineering', '8.2 MB', 'Dr. Anne Moreau', 123, 298, '["architecture", "patterns", "bonnes pratiques", "design patterns"]'),
('Présentation - Intelligence Artificielle Avancée', 'Support de cours sur les techniques avancées d\'intelligence artificielle et leurs applications.', 'presentation', 'artificial-intelligence', '3.1 MB', 'Prof. Pierre Durand', 78, 156, '["IA", "algorithmes", "logique floue", "systèmes experts"]'),
('Dataset - Données de Capteurs IoT', 'Collection de données de capteurs IoT pour l\'analyse et le traitement de signaux.', 'dataset', 'embedded-systems', '25 MB', 'Prof. Michel Leroy', 45, 98, '["IoT", "capteurs", "données", "analyse"]'),
('Tutoriel Python pour l\'IA', 'Guide pratique pour utiliser Python dans les projets d\'intelligence artificielle.', 'document', 'artificial-intelligence', '1.8 MB', 'Dr. Sophie Bernard', 234, 567, '["python", "IA", "tutoriel", "programmation"]'),
('Vidéo - Programmation Temps Réel', 'Cours vidéo sur la programmation temps réel pour les systèmes embarqués critiques.', 'video', 'embedded-systems', '320 MB', 'Prof. Michel Leroy', 34, 87, '["temps réel", "systèmes critiques", "RTOS", "programmation"]'),

-- Vidéos pour la série "Python pour tous"
('1. Bienvenue dans Python', 'Introduction complète au langage Python et à ses applications dans le développement moderne.', 'video', 'programming', '15.2 MB', 'videos/1.Bienvenue dans Python.mp4', 'Dr. Sophie Bernard', 0, 0, '["python", "introduction", "programmation", "développement"]'),
('2. Fichiers exercices', 'Guide pour télécharger et utiliser les fichiers d\'exercices du cours Python.', 'video', 'programming', '12.8 MB', 'videos/2.Fichiers exercices.mp4', 'Dr. Sophie Bernard', 0, 0, '["python", "exercices", "fichiers", "pratique"]'),
('3. Présentations Python', 'Présentation détaillée du langage Python et de ses fonctionnalités principales.', 'video', 'programming', '18.5 MB', 'videos/3.Presentations Python.mp4', 'Dr. Sophie Bernard', 0, 0, '["python", "présentation", "programmation", "développement"]'),
('4. Utilisation de l\'environnement Jupyter & NoteBook', 'Apprendre à utiliser Jupyter Notebook pour le développement Python interactif et la science des données.', 'video', 'programming', '22.3 MB', 'videos/4.Utilisation de l\'environnement Jupyter & NoteBook.mp4', 'Dr. Sophie Bernard', 0, 0, '["python", "jupyter", "notebook", "développement", "science des données"]');

-- Inscriptions de test
INSERT INTO user_courses (user_id, course_id, progress_percentage) VALUES
(1, 1, 25.50),
(1, 2, 0.00),
(2, 3, 75.00),
(2, 4, 100.00);

-- Posts du forum de test
INSERT INTO forum_posts (user_id, title, content, category, tags) VALUES
(1, 'Question sur les microcontrôleurs', 'Bonjour, j\'ai une question concernant la programmation des microcontrôleurs Arduino. Quelqu\'un peut-il m\'aider ?', 'embedded-systems', '["arduino", "microcontrôleur", "programmation"]'),
(2, 'Problème avec TensorFlow', 'Je rencontre des difficultés avec l\'installation de TensorFlow. Quelqu\'un a-t-il une solution ?', 'deep-learning', '["tensorflow", "installation", "python"]'),
(1, 'Ressources pour débuter en IA', 'Quelles sont les meilleures ressources pour commencer l\'apprentissage de l\'intelligence artificielle ?', 'artificial-intelligence', '["débutant", "ressources", "IA"]');

-- Réponses du forum de test
INSERT INTO forum_replies (post_id, user_id, content) VALUES
(1, 2, 'Je peux vous aider avec Arduino. Pouvez-vous préciser votre question ?'),
(1, 3, 'Voici un excellent tutoriel pour débuter avec Arduino...'),
(2, 1, 'J\'ai eu le même problème. Essayez cette commande...'),
(3, 2, 'Je recommande de commencer par ces ressources...');

-- Index pour optimiser les performances
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_courses_category ON courses(category);
CREATE INDEX idx_courses_level ON courses(level);
CREATE INDEX idx_resources_type ON resources(type);
CREATE INDEX idx_resources_category ON resources(category);
CREATE INDEX idx_user_courses_user ON user_courses(user_id);
CREATE INDEX idx_user_courses_course ON user_courses(course_id);
CREATE INDEX idx_forum_posts_category ON forum_posts(category);
CREATE INDEX idx_forum_posts_user ON forum_posts(user_id);
