<?php
require 'core.php';
requireLogin();
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_app'])) {
        $name = trim($_POST['app_name']);
        $exp = $_POST['expires_at'] ?: null;
        $secret = generateSecret();
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO applications (name, secret_key, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$name, $secret, $exp]);
    }
    if (isset($_POST['delete_app'])) {
        $pdo->prepare("DELETE FROM applications WHERE id=?")->execute([$_POST['app_id']]);
    }
    header('Location: apps.php'); exit;
}

$now = time();
$apps = $pdo->query("SELECT *, CASE WHEN expires_at IS NOT NULL AND expires_at < datetime('now') THEN 'expired' ELSE 'active' END as app_status FROM applications ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Applications</title><style>:root{--bg:#0b0f19;--card:#151b28;--border:#2a3549;--text:#e2e8f0;--muted:#94a3b8;--primary:#3b82f6;--green:#22c55e;--yellow:#f59e0b;--red:#ef4444;--gray:#64748b}body{font-family:system-ui;background:var(--bg);color:var(--text);margin:0}.nav{background:var(--card);padding:1rem 2rem;display:flex;gap:1rem;align-items:center;border-bottom:1px solid var(--border)}.nav a{color:var(--muted);text-decoration:none;padding:.6rem 1rem;border-radius:8px}.nav a:hover,.nav a.active{background:#1e293b;color:#fff}.container{padding:2rem;max-width:1200px;margin:0 auto}table{width:100%;border-collapse:collapse;margin-top:1.5rem}th,td{padding:14px;border-bottom:1px solid var(--border);text-align:left}th{background:var(--card);color:var(--muted)}input,button{padding:10px;margin:5px 0;border-radius:8px;border:1px solid var(--border);background:#0b1120;color:#fff}button{background:var(--primary);cursor:pointer;font-weight:500}button.red{background:var(--red)}.form-box{background:var(--card);padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;border:1px solid var(--border)}.badge{padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600}.green{background:rgba(34,197,94,.15);color:var(--green)}.red{background:rgba(239,68,68,.15);color:var(--red)}</style></head>
<body>
<div class="nav"><a href="index.php">Dashboard</a><a href="apps.php" class="active">Applications</a><a href="keys.php">Keys</a><a href="settings.php">Settings</a><a href="index.php?logout" style="margin-left:auto;color:var(--red)">Logout</a></div>
<div class="container">
<h1>📦 Applications</h1>
<div class="form-box">
<form method="POST">
<input type="text" name="app_name" placeholder="App Name" required style="width:30%;display:inline-block">
<input type="datetime-local" name="expires_at" title="Optional expiry" style="width:35%;display:inline-block">
<button type="submit" name="create_app">➕ Create App</button>
</form>
</div>
<table>
<tr><th>Name</th><th>API Secret</th><th>Expiry</th><th>Status</th><th>Created</th><th>Actions</th></tr>
<?php foreach($apps as $app): ?>
<tr>
<td><?=htmlspecialchars($app['name'])?></td>
<td><code style="background:#0f172a;padding:4px 6px;border-radius:6px"><?=$app['secret_key']?></code></td>
<td><?=$app['expires_at'] ?: 'Never'?></td>
<td><span class="badge <?=$app['app_status']=='expired'?'red':'green'?>"><?=ucfirst($app['app_status'])?></span></td>
<td><?=date('Y-m-d H:i', strtotime($app['created_at']))?></td>
<td><form method="POST" style="display:inline" onsubmit="return confirm('Delete app & all keys?')"><input type="hidden" name="app_id" value="<?=$app['id']?>"><button type="submit" name="delete_app" class="red">🗑 Delete</button></form></td>
</tr>
<?php endforeach; ?>
</table>
</div>
</body>
</html>
