<?php
// config.php - Railway compatible version
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => getenv('RAILWAY_ENVIRONMENT') === 'production',
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);

// Database connection - Railway env vars support
$host = getenv('MYSQL_HOST') ?: '127.0.0.1';
$port = getenv('MYSQL_PORT') ?: '3306';
$db   = getenv('MYSQL_DATABASE') ?: 'keyauth_portal';
$user = getenv('MYSQL_USER') ?: 'root';
$pass = getenv('MYSQL_PASSWORD') ?: '';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false
    ]);
} catch(PDOException $e) {
    error_log("DB Connection Failed: " . $e->getMessage());
    if (getenv('RAILWAY_ENVIRONMENT') !== 'production') {
        die("Database connection error: " . $e->getMessage());
    }
    die("Service temporarily unavailable.");
}

// ... baaki functions same rahenge ...
?>
