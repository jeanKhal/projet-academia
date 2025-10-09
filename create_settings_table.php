<?php
require_once 'includes/functions.php';

$pdo = getDB();

try {
    // Création de la table settings
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Insertion des paramètres par défaut
    $default_settings = [
        'site_name' => 'Académie IA',
        'site_description' => 'Plateforme d\'apprentissage en Intelligence Artificielle',
        'contact_email' => 'contact@academie-ia.com',
        'maintenance_mode' => '0',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => '587',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'max_login_attempts' => '5',
        'session_timeout' => '30',
        'password_min_length' => '8',
        'require_email_verification' => '1'
    ];
    
    foreach ($default_settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
    
    echo "✅ Table settings créée avec succès et paramètres par défaut insérés !\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur lors de la création de la table settings : " . $e->getMessage() . "\n";
}
?>
