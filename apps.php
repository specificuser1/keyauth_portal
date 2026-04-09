<?php
require 'core.php';
requireLogin();
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_app'])) {
        $name = trim($_POST['app_name']);
        $secret = generateSecret();
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO applications (name, secret_key) VALUES (?, ?)");
        $stmt->execute([$name, $secret]);
    }
    if (isset($_POST['delete_app'])) {
        $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$_POST['app_id']]);
    }
    header('Location: apps.php'); exit;
}

$apps = $pdo->query("SELECT * FROM applications ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Applications</title>
<style>/* same base styles as index */ body{font-family:system-ui;background:#0f172a;color:#e2e8f0;margin:0}.nav{background:#1e293b;padding:1rem;display:flex;gap:1rem;align-items:center}.nav a{color:#94a3b8;text-decoration:none;padding:0.5rem 1rem;border-radius:6px}.nav a:hover,.nav a.active{background:#334155;color:#fff}.container{padding:2rem}table{width:100%;border-collapse:collapse;margin-top:1rem}th,td{padding:12px;border-bottom:1px solid #334155;text-align:left}th{background:#1e293b;color:#94a3b8}input,button{padding:8px 12px;margin:4px 0;border-radius:6px;border:1px solid #334155;background:#0f172a;color:#fff}button{background:#3b82f6;cursor:pointer}button.red{background:#ef4444}.form-box{background:#1e293b;padding:1rem;border-radius:8px;margin-bottom:1rem}</style>
</head>
<body>
<div class="nav"><a href="index.php">Dashboard</a><a href="apps.php" class="active">Applications</a><a href="keys.php">Keys</a><a href="settings.php">Settings</a><a href="index.php?logout" style="margin-left:auto;color:#ef4444">Logout</a></div>
<div class="container">
<h1>📦 Applications</h1>
<div class="form-box">
<form method="POST">
<input type="text" name="app_name" placeholder="App Name" required>
<button type="submit" name="create_app">Create App</button>
</form>
</div>
<table>
<tr><th>Name</th><th>API Secret</th><th>Created</th><th>Actions</th></tr>
<?php foreach($apps as $app): ?>
<tr>
<td><?=htmlspecialchars($app['name'])?></td>
<td><code><?=$app['secret_key']?></code></td>
<td><?=date('Y-m-d H:i', strtotime($app['created_at']))?></td>
<td>
<form method="POST" style="display:inline" onsubmit="return confirm('Delete app & all its keys?')">
<input type="hidden" name="app_id" value="<?=$app['id']?>">
<button type="submit" name="delete_app" class="red">Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>
</body>
</html>
