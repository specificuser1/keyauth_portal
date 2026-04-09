<?php
require 'config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['app_name'] ?? '');
    if ($name) {
        $apiKey = generateSecureKey(64);
        $stmt = $pdo->prepare("INSERT INTO applications (name, api_key) VALUES (?, ?)");
        $stmt->execute([$name, $apiKey]);
    }
}

$apps = $pdo->query("SELECT * FROM applications ORDER BY created_at DESC")->fetchAll();
?>
<!-- HTML form & table omitted for brevity. Use same dashboard styling -->
<form method="POST" class="card"><input name="app_name" placeholder="Application Name" required><button>Create App</button></form>
<table class="card" style="width:100%">
    <tr><th>Name</th><th>API Key</th><th>Status</th><th>Actions</th></tr>
    <?php foreach($apps as $app): ?>
    <tr>
        <td><?=htmlspecialchars($app['name'])?></td>
        <td><code><?= $app['api_key'] ?></code> <button onclick="navigator.clipboard.writeText('<?=$app['api_key']?>')">📋</button></td>
        <td><?=$app['status']?></td>
        <td>
            <a href="settings.php?reset_app=<?=$app['id']?>">Reset API</a> | 
            <a href="keys.php?app_id=<?=$app['id']?>">Manage Keys</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
