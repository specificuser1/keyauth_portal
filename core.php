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
            description TEXT DEFAULT '',
            status TEXT DEFAULT 'active',
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
            variables TEXT DEFAULT '{}',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME DEFAULT NULL,
            last_used DATETIME DEFAULT NULL,            FOREIGN KEY(app_id) REFERENCES applications(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS api_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            app_id INTEGER, key_id INTEGER, ip TEXT, hwid TEXT, status TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $defaults = [
        'portal_pass' => password_hash('password', PASSWORD_DEFAULT),
        'portal_title' => 'CordAuth',
        'webhook_url' => '',
        'api_rate_limit' => '30',
        'session_timeout' => '7200',
        'maintenance_mode' => '0'
    ];
    foreach ($defaults as $k => $v) {
        $pdo->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('$k', '$v')");
    }

    // Safe column migration
    $tables = ['applications' => ['description','status'], 'keys' => ['variables'], 'api_logs' => []];
    foreach ($tables as $tbl => $cols) {
        foreach ($cols as $col) {
            $exists = $pdo->query("PRAGMA table_info($tbl)")->fetchAll(PDO::FETCH_COLUMN, 1);
            if (!in_array($col, $exists)) {
                try { $pdo->exec("ALTER TABLE $tbl ADD COLUMN $col TEXT DEFAULT ''"); } catch(Exception $e) {}
            }
        }
    }
}

function isLoggedIn() { return !empty($_SESSION['ka_logged_in']); }
function requireLogin() { if (!isLoggedIn()) { header('Location: index.php'); exit; } }

function generateKey($len=16) {
    $c='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; $k='';
    for($i=0;$i<$len;$i++) $k.=$c[random_int(0,strlen($c)-1)];
    return $k;
}
function generateSecret() { return bin2hex(random_bytes(16)); }
function getStat($pdo,$q){ return $pdo->query($q)->fetchColumn(); }

function getChartData($pdo) {
    $days = $pdo->query("SELECT DISTINCT date(created_at) as d FROM keys ORDER BY d DESC LIMIT 7")->fetchAll(PDO::FETCH_COLUMN);
    $created = []; $used = [];
    foreach(array_reverse($days) as $d) {
        $created[] = $pdo->query("SELECT COUNT(*) FROM keys WHERE date(created_at)='$d'")->fetchColumn();
        $used[] = $pdo->query("SELECT COUNT(*) FROM keys WHERE last_used IS NOT NULL AND date(last_used)='$d'")->fetchColumn();
    }    return json_encode(['labels' => $days, 'created' => $created, 'used' => $used]);
}

function dispatchWebhook($url, $data) {
    if (!$url) return;
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode($data), CURLOPT_HTTPHEADER=>['Content-Type:application/json'], CURLOPT_TIMEOUT=>3, CURLOPT_RETURNTRANSFER=>true]);
    curl_exec($ch); curl_close($ch);
}
?>
