<?php
require 'core.php';
requireLogin();
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_pass'])) {
    $new = password_hash($_POST['new_pass'], PASSWORD_DEFAULT);
    $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('portal_pass', ?)")->execute([$new]);
    $msg = "Password updated successfully.";
}

$current = $pdo->query("SELECT value FROM settings WHERE key='portal_pass'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Settings</title>
<style>body{font-family:system-ui;background:#0f172a;color:#e2e8f0;margin:0}.nav{background:#1e293b;padding:1rem;display:flex;gap:1rem;align-items:center}.nav a{color:#94a3b8;text-decoration:none;padding:0.5rem 1rem;border-radius:6px}.nav a:hover,.nav a.active{background:#334155;color:#fff}.container{padding:2rem}.form-box{background:#1e293b;padding:1.5rem;border-radius:10px;margin-bottom:1rem}input,button{padding:10px;margin:5px 0;border-radius:6px;border:1px solid #334155;background:#0f172a;color:#fff;width:100%}button{background:#3b82f6;cursor:pointer}.msg{color:#22c55e;margin:10px 0}</style>
</head>
<body>
<div class="nav"><a href="index.php">Dashboard</a><a href="apps.php">Applications</a><a href="keys.php">Keys</a><a href="settings.php" class="active">Settings</a><a href="index.php?logout" style="margin-left:auto;color:#ef4444">Logout</a></div>
<div class="container">
<h1>⚙️ Portal Settings</h1>
<div class="form-box">
<form method="POST">
<label>Current Hash (for verification)</label>
<input type="text" value="<?=htmlspecialchars($current)?>" readonly>
<label>New Password</label>
<input type="password" name="new_pass" required>
<button type="submit" name="change_pass">Update Password</button>
<?php if(!empty($msg)) echo "<p class='msg'>$msg</p>"; ?>
</form>
</div>
</div>
</body>
</html>
