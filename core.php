<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

// SQLite DB Path
define('DB_PATH', __DIR__ . '/data/portal.db');
$pdo = null;

function getPDO() {
    global $pdo;
    if (!$pdo) {
        if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0755, true);
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        initDB($pdo);
    }
    return $pdo;
}

function initDB($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT);
        CREATE TABLE IF NOT EXISTS applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL,
            secret_key TEXT UNIQUE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS keys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            app_id INTEGER NOT NULL,
            key_string TEXT UNIQUE NOT NULL,
            label TEXT DEFAULT '',
            status TEXT DEFAULT 'active',
            hwid_locked INTEGER DEFAULT 0,
            hwid_value TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME DEFAULT NULL,
            last_used DATETIME DEFAULT NULL,
            FOREIGN KEY(app_id) REFERENCES applications(id) ON DELETE CASCADE
        );
    ");
    // Default portal password hash (change in settings)
    $pdo->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('portal_pass', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')");
}

function isLoggedIn() {
    return !empty($_SESSION['ka_logged_in']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function generateKey($length = 16) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $key = '';
    for ($i = 0; $i < $length; $i++) $key .= $chars[random_int(0, strlen($chars) - 1)];
    return $key;
}

function generateSecret() {
    return bin2hex(random_bytes(16));
}

function getStat($pdo, $query) {
    return $pdo->query($query)->fetchColumn();
}
?>
