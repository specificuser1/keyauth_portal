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

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php'); exit;
}

if (!isLoggedIn()): ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Login</title><style>body{font-family:system-ui;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}form{background:#1e293b;padding:2rem;border-radius:12px;width:320px}input{width:100%;padding:10px;margin:10px 0;border-radius:6px;border:1px solid #334155;background:#0f172a;color:#fff}button{width:100%;padding:10px;background:#3b82f6;color:#fff;border:none;border-radius:6px;cursor:pointer}button:hover{background:#2563eb}.err{color:#ef4444;font-size:0.9rem}</style></head>
<body>
<form method="POST">
<h2 style="margin-top:0">🔐 Portal Login</h2>
<?php if(!empty($error)) echo "<p class='err'>$error</p>"; ?>
<input type="password" name="login_pass" placeholder="Portal Password" required>
<button type="submit">Login</button>
<p style="font-size:0.8rem;color:#94a3b8">Default: <code>password</code></p>
</form>
</body>
</html>
<?php exit; endif;

$pdo = getPDO();
$totalApps = getStat($pdo, "SELECT COUNT(*) FROM applications");
$totalKeys = getStat($pdo, "SELECT COUNT(*) FROM keys");
$activeKeys = getStat($pdo, "SELECT COUNT(*) FROM keys WHERE status='active'");
$usedKeys = getStat($pdo, "SELECT COUNT(*) FROM keys WHERE status='used'");
$expiredKeys = getStat($pdo, "SELECT COUNT(*) FROM keys WHERE expires_at < datetime('now')");
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Dashboard</title>
<style>body{font-family:system-ui;background:#0f172a;color:#e2e8f0;margin:0}.nav{background:#1e293b;padding:1rem;display:flex;gap:1rem;align-items:center}.nav a{color:#94a3b8;text-decoration:none;padding:0.5rem 1rem;border-radius:6px}.nav a:hover,.nav a.active{background:#334155;color:#fff}.container{padding:2rem}.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-top:1rem}.card{background:#1e293b;padding:1.5rem;border-radius:10px}.card h3{margin:0 0 0.5rem;font-size:1rem;color:#94a3b8}.card p{margin:0;font-size:2rem;font-weight:bold}.logout{margin-left:auto;color:#ef4444}</style>
</head>
<body>
<div class="nav">
<a href="index.php" class="active">Dashboard</a>
<a href="apps.php">Applications</a>
<a href="keys.php">Keys</a>
<a href="settings.php">Settings</a>
<a href="?logout" class="logout">Logout</a>
</div>
<div class="container">
<h1>📊 Portal Overview</h1>
<div class="cards">
<div class="card"><h3>📦 Applications</h3><p><?=$totalApps?></p></div>
<div class="card"><h3>🔑 Total Keys</h3><p><?=$totalKeys?></p></div>
<div class="card"><h3>✅ Active Keys</h3><p><?=$activeKeys?></p></div>
<div class="card"><h3>📉 Used/Expired</h3><p><?=$usedKeys + $expiredKeys?></p></div>
</div>
</div>
</body>
</html>
