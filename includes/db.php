<?php
// It is highly recommended to use a library like phpdotenv for managing environment variables.
// If phpdotenv is not installed or cannot be used, ensure these environment variables are set
// in your web server configuration (e.g., Apache, Nginx, or php-fpm).

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_DATABASE') ?: 'finansmart';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: 'mv16082005';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
     // Log the error for debugging, but show a generic message to the user
     error_log('Database connection error: ' . $e->getMessage());
     echo 'Erro de conexão com o banco de dados. Por favor, tente novamente mais tarde.';
     exit;
}
?>