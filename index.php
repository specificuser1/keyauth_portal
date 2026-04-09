<?php
require 'core.php';
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_pass'])) {
    $hash = $pdo->query("SELECT value FROM settings WHERE key='portal_pass'")->fetchColumn();
    if (password_verify($_POST['login_pass'], $hash)) {
        $_SESSION['ka_logged_in'] = true;
        header('Location: index.php'); exit;
    }
    $error = "Galat password!";
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: index.php'); exit; }
if (!isLoggedIn()): ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Login</title><style>:root{--bg:#0b0f19;--card:#151b28;--text:#e2e8f0;--muted:#94a3b8;--primary:#3b82f6;--red:#ef4444;--green:#22c55e;--yellow:#f59e0b}body{font-family:system-ui;background:var(--bg);color:var(--text);display:flex;align-items:center;justify-content:center;height:100vh;margin:0}form{background:var(--card);padding:2rem;border-radius:16px;width:340px;box-shadow:0 8px 30px rgba(0,0,0,.4)}input{width:100%;padding:12px;margin:8px 0;border-radius:8px;border:1px solid #2a3549;background:#0b1120;color:#fff}button{width:100%;padding:12px;background:var(--primary);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600}.err{color:var(--red);font-size:.9rem}</style></head>
<body><form method="POST"><h2 style="margin:0 0 10px">🔐 Portal Login</h2><?php if(!empty($error)) echo "<p class='err'>$error</p>"; ?><input type="password" name="login_pass" placeholder="Password" required><button type="submit">Login</button><p style="font-size:.8rem;color:var(--muted)">Default: <code>password</code></p></form></body></html>
<?php exit; endif;

$now = time();
$totalApps = getStat($pdo, "SELECT COUNT(*) FROM applications");
$activeApps = getStat($pdo, "SELECT COUNT(*) FROM applications WHERE (expires_at IS NULL OR expires_at > datetime('now'))");
$totalKeys = getStat($pdo, "SELECT COUNT(*) FROM keys");
$activeKeys = getStat($pdo, "SELECT COUNT(*) FROM keys WHERE status='active' AND (expires_at IS NULL OR expires_at > datetime('now'))");
$usedKeys = getStat($pdo, "SELECT COUNT(*) FROM keys WHERE usage_count > 0");
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Dashboard</title><style>:root{--bg:#0b0f19;--card:#151b28;--border:#2a3549;--text:#e2e8f0;--muted:#94a3b8;--primary:#3b82f6;--green:#22c55e;--yellow:#f59e0b;--red:#ef4444;--gray:#64748b}body{font-family:system-ui;background:var(--bg);color:var(--text);margin:0}.nav{background:var(--card);padding:1rem 2rem;display:flex;gap:1rem;align-items:center;border-bottom:1px solid var(--border)}.nav a{color:var(--muted);text-decoration:none;padding:.6rem 1rem;border-radius:8px;transition:.2s}.nav a:hover,.nav a.active{background:#1e293b;color:#fff}.logout{margin-left:auto;color:var(--red)}.container{padding:2rem;max-width:1200px;margin:0 auto}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem;margin:2rem 0}.card{background:var(--card);padding:1.5rem;border-radius:12px;border:1px solid var(--border)}.card h3{margin:0 0 .5rem;font-size:.9rem;color:var(--muted)}.card p{margin:0;font-size:2rem;font-weight:700}.green{color:var(--green)}.yellow{color:var(--yellow)}.red{color:var(--red)}.gray{color:var(--gray)}</style></head>
<body>
<div class="nav"><a href="index.php" class="active">Dashboard</a><a href="apps.php">Applications</a><a href="keys.php">Keys</a><a href="settings.php">Settings</a><a href="?logout" class="logout">Logout</a></div>
<div class="container">
<h1>📊 Overview</h1>
<div class="grid">
<div class="card"><h3>📦 Total Apps</h3><p class="blue"><?=$totalApps?></p></div>
<div class="card"><h3>✅ Active Apps</h3><p class="green"><?=$activeApps?></p></div>
<div class="card"><h3>🔑 Total Keys</h3><p><?=$totalKeys?></p></div>
<div class="card"><h3>🟢 Active Keys</h3><p class="green"><?=$activeKeys?></p></div>
<div class="card"><h3>📈 Used Keys</h3><p class="yellow"><?=$usedKeys?></p></div>
</div>
</div>
</body>
</html>
