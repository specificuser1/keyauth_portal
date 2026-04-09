<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

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
            expires_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS keys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            app_id INTEGER NOT NULL,
            key_string TEXT UNIQUE NOT NULL,
            label TEXT DEFAULT '',
            note TEXT DEFAULT '',
            status TEXT DEFAULT 'active',
            hwid_locked INTEGER DEFAULT 0,
            hwid_value TEXT DEFAULT NULL,
            ip_locked INTEGER DEFAULT 0,
            ip_value TEXT DEFAULT NULL,
            max_uses INTEGER DEFAULT 0,
            usage_count INTEGER DEFAULT 0,
            cooldown INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME DEFAULT NULL,
            last_used DATETIME DEFAULT NULL,
            FOREIGN KEY(app_id) REFERENCES applications(id) ON DELETE CASCADE
        );
    ");
    
    $pdo->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('portal_pass', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')");

    // Safe Migration for old DBs
    $cols = ['applications' => ['expires_at'], 'keys' => ['note','ip_locked','ip_value','max_uses','usage_count','cooldown']];
    foreach ($cols as $table => $addCols) {
        foreach ($addCols as $col) {
            $info = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);
            if (!in_array($col, $info)) {
                try { $pdo->exec("ALTER TABLE $table ADD COLUMN $col TEXT DEFAULT NULL"); } catch(Exception $e) {}
            }
        }
    }
}

function isLoggedIn() { return !empty($_SESSION['ka_logged_in']); }
function requireLogin() { if (!isLoggedIn()) { header('Location: index.php'); exit; } }

function generateKey($length = 16) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $key = '';
    for ($i = 0; $i < $length; $i++) $key .= $chars[random_int(0, strlen($chars) - 1)];
    return $key;
}
function generateSecret() { return bin2hex(random_bytes(16)); }

function getStat($pdo, $query) { return $pdo->query($query)->fetchColumn(); }

function getStatusBadge($status, $expires_at, $now) {
    if ($status === 'blocked') return '<span class="badge red">Blocked</span>';
    if ($status === 'paused') return '<span class="badge yellow">Paused</span>';
    if ($expires_at && strtotime($expires_at) < $now) return '<span class="badge gray">Expired</span>';
    return '<span class="badge green">Active</span>';
}
?>
