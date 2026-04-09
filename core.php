<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

define('DB_PATH', __DIR__ . '/data/portal.db');
define('DATA_DIR', __DIR__ . '/data');
$pdo = null;

if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

function getPDO() {
    global $pdo;
    if (!$pdo) {
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
            id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE NOT NULL,
            secret_key TEXT UNIQUE NOT NULL, description TEXT DEFAULT '',
            status TEXT DEFAULT 'active', expires_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS keys (
            id INTEGER PRIMARY KEY AUTOINCREMENT, app_id INTEGER NOT NULL,
            key_string TEXT UNIQUE NOT NULL, label TEXT DEFAULT '', note TEXT DEFAULT '',
            status TEXT DEFAULT 'active', hwid_locked INTEGER DEFAULT 0, hwid_value TEXT DEFAULT NULL,
            ip_locked INTEGER DEFAULT 0, ip_value TEXT DEFAULT NULL,
            max_uses INTEGER DEFAULT 0, usage_count INTEGER DEFAULT 0, cooldown INTEGER DEFAULT 0,
            variables TEXT DEFAULT '{}', created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME DEFAULT NULL, last_used DATETIME DEFAULT NULL,
            FOREIGN KEY(app_id) REFERENCES applications(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT, message TEXT, ip TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");
    
    $defaults = [
        'portal_pass' => password_hash('password', PASSWORD_DEFAULT),
        'portal_title' => 'CordAuth', 'theme_primary' => '#6366f1', 'theme_bg' => '#0b0f19', 'theme_card' => '#151b28',        'webhook_url' => '', 'api_rate_limit' => '30', 'session_timeout' => '7200', 'maintenance_mode' => '0',
        'auto_cleanup' => '1', 'logo_path' => ''
    ];
    foreach ($defaults as $k => $v) $pdo->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('$k', '$v')");
    
    // Safe migration
    $cols = ['keys' => ['variables'], 'activity_logs' => []];
    foreach ($cols as $tbl => $arr) foreach ($arr as $col) {
        if (!in_array($col, $pdo->query("PRAGMA table_info($tbl)")->fetchAll(PDO::FETCH_COLUMN,1))) {
            try { $pdo->exec("ALTER TABLE $tbl ADD COLUMN $col TEXT DEFAULT ''"); } catch(Exception $e) {}
        }
    }
    if ($pdo->query("SELECT value FROM settings WHERE key='auto_cleanup'")->fetchColumn() == '1') {
        $pdo->exec("DELETE FROM keys WHERE expires_at < datetime('now')");
        $pdo->exec("DELETE FROM activity_logs WHERE id < (SELECT MAX(id) FROM activity_logs) - 1000");
    }
}

function isLoggedIn() { return !empty($_SESSION['ka_logged_in']); }
function requireLogin() { if (!isLoggedIn()) { header('Location: index.php'); exit; } }
function verifyCSRF() { return $_POST['csrf'] ?? '' === ($_SESSION['csrf'] ?? ''); }

function generateKey($len=16) { $c='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; $k=''; for($i=0;$i<$len;$i++) $k.=$c[random_int(0,strlen($c)-1)]; return $k; }

function generateMaskedKey($mask) {
    $map = ['A' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'N' => '0123456789', 'X' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'];
    return preg_replace_callback('/([A-NX])/', function($m) use ($map) {
        return $map[$m[1]][random_int(0, strlen($map[$m[1]])-1)];
    }, $mask);
}

function logActivity($pdo, $type, $msg) {
    $pdo->prepare("INSERT INTO activity_logs (type, message, ip) VALUES (?, ?, ?)")->execute([$type, $msg, $_SERVER['REMOTE_ADDR']]);
}

function sendDiscordEmbed($type, $title, $desc, $fields=[], $color=0x6366f1) {
    $pdo = getPDO();
    $url = $pdo->query("SELECT value FROM settings WHERE key='webhook_url'")->fetchColumn();
    if (!$url) return;
    $data = [
        "content" => null,
        "embeds" => [[
            "title" => $title, "description" => $desc, "color" => $color,
            "fields" => $fields,
            "footer" => ["text" => "CordAuth Portal • ".date('Y-m-d H:i:s')],
            "timestamp" => date('c')
        ]]
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode($data), CURLOPT_HTTPHEADER=>['Content-Type:application/json'], CURLOPT_TIMEOUT=>2, CURLOPT_RETURNTRANSFER=>true]);    curl_exec($ch); curl_close($ch);
}

function uploadFile($file, $allowed=['image/png','image/jpeg','image/webp'], $max=2097152) {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $max) return false;
    if (!in_array(mime_content_type($file['tmp_name']), $allowed)) return false;
    $path = DATA_DIR . '/' . basename($file['name']);
    return move_uploaded_file($file['tmp_name'], $path) ? $path : false;
}

function getThemeCSS($pdo) {
    $s = $pdo->query("SELECT key, value FROM settings WHERE key IN ('theme_primary','theme_bg','theme_card')")->fetchAll(PDO::FETCH_KEY_PAIR);
    $logo = $pdo->query("SELECT value FROM settings WHERE key='logo_path'")->fetchColumn();
    $css = ":root{--primary:{$s['theme_primary']};--bg:{$s['theme_bg']};--card:{$s['theme_card']};--border:color-mix(in srgb, var(--card), #2a3549 60%);--text:#e2e8f0;--muted:#94a3b8;}";
    if ($logo && file_exists(DATA_DIR.'/'.$logo)) $css .= ".nav-logo{width:32px;height:32px;border-radius:6px;margin-right:8px;background:url('data/{$logo}') center/cover;}";
    return $css;
}

function getChartStats($pdo) {
    $status = $pdo->query("SELECT status, COUNT(*) as c FROM keys GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $hwid = $pdo->query("SELECT CASE WHEN hwid_value IS NOT NULL THEN 'Locked' ELSE 'Unlocked' END as st, COUNT(*) as c FROM keys GROUP BY st")->fetchAll(PDO::FETCH_KEY_PAIR);
    return ['status' => $status, 'hwid' => $hwid];
}
?>
