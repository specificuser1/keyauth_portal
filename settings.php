<?php
require 'core.php';
requireLogin();
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_pass'])) {
    $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('portal_pass', ?)")->execute([password_hash($_POST['new_pass'], PASSWORD_DEFAULT)]);
    $msg = "✅ Password updated successfully.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Settings</title><style>:root{--bg:#0b0f19;--card:#151b28;--border:#2a3549;--text:#e2e8f0;--muted:#94a3b8;--primary:#3b82f6;--red:#ef4444}body{font-family:system-ui;background:var(--bg);color:var(--text);margin:0}.nav{background:var(--card);padding:1rem 2rem;display:flex;gap:1rem;align-items:center;border-bottom:1px solid var(--border)}.nav a{color:var(--muted);text-decoration:none;padding:.6rem 1rem;border-radius:8px}.nav a:hover,.nav a.active{background:#1e293b;color:#fff}.container{padding:2rem;max-width:800px;margin:0 auto}.form-box{background:var(--card);padding:2rem;border-radius:12px;border:1px solid var(--border)}input,button{padding:12px;margin:8px 0;border-radius:8px;border:1px solid var(--border);background:#0b1120;color:#fff;width:100%}button{background:var(--primary);cursor:pointer;font-weight:600}.msg{color:var(--green);margin:10px 0}</style></head>
<body>
<div class="nav"><a href="index.php">Dashboard</a><a href="apps.php">Applications</a><a href="keys.php">Keys</a><a href="settings.php" class="active">Settings</a><a href="index.php?logout" style="margin-left:auto;color:var(--red)">Logout</a></div>
<div class="container">
<h1>⚙️ Portal Settings</h1>
<div class="form-box">
<form method="POST">
<label>🔒 New Password</label>
<input type="password" name="new_pass" required placeholder="Enter new password">
<button type="submit" name="change_pass">Update Password</button>
<?php if(!empty($msg)) echo "<p class='msg'>$msg</p>"; ?>
</form>
</div>
</div>
</body>
</html>
