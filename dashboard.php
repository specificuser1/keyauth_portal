<?php
require 'config.php';
requireAuth();

$stats = [
    'apps' => $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
    'total_keys' => $pdo->query("SELECT COUNT(*) FROM keys")->fetchColumn(),
    'active_keys' => $pdo->query("SELECT COUNT(*) FROM keys WHERE status='active'")->fetchColumn(),
    'used_keys' => $pdo->query("SELECT COUNT(*) FROM keys WHERE status='used' OR last_used IS NOT NULL")->fetchColumn(),
    'blocked_keys' => $pdo->query("SELECT COUNT(*) FROM keys WHERE status='blocked'")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Dashboard</title>
    <style>body{font-family:sans-serif;background:#f8f9fa;padding:2rem} .card{background:#fff;padding:1.5rem;margin-bottom:1rem;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1)} .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem}</style>
</head>
<body>
    <h1>KeyAuth Portal</h1>
    <nav style="margin-bottom:1rem">
        <a href="dashboard.php">Dashboard</a> | 
        <a href="apps.php">Applications</a> | 
        <a href="keys.php">Keys</a> | 
        <a href="settings.php">Settings</a> | 
        <a href="login.php?logout=1">Logout</a>
    </nav>
    <div class="grid">
        <?php foreach($stats as $k => $v): ?>
            <div class="card"><h3><?=ucwords(str_replace('_',' ',$k))?></h3><p style="font-size:2rem;font-weight:bold"><?= $v ?></p></div>
        <?php endforeach; ?>
    </div>
</body>
</html>
