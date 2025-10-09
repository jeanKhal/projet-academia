<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'academy_ia');
define('DB_USER', 'root');
define('DB_PASS', '');

// Options PDO
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
]);
?>
