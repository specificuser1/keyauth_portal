<?php
require 'config.php';
requireAuth();

$appId = $_GET['app_id'] ?? null;
if (!$appId) die("Select an application first.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $keyId = $_POST['key_id'] ?? null;

    if ($action === 'create') {
        $label = trim($_POST['label'] ?? '');
        $hwid = $_POST['hwid_lock'] ? ($_POST['hwid'] ?? null) : null;
        $expiry = $_POST['expiry'] ? date('Y-m-d H:i:s', strtotime($_POST['expiry'])) : null;
        $uses = (int)($_POST['uses_left'] ?? -1);
        $keyCode = generateSecureKey($_POST['length'] ?? 32);

        $stmt = $pdo->prepare("INSERT INTO keys (app_id, key_code, label, hwid, hwid_lock, expiry, uses_left) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$appId, $keyCode, $label, $hwid, (int)$_POST['hwid_lock'], $expiry, $uses]);
    } elseif ($action && $keyId) {
        $updates = [];
        if ($action === 'pause') $updates['status'] = 'paused';
        elseif ($action === 'resume') $updates['status'] = 'active';
        elseif ($action === 'block') $updates['status'] = 'blocked';
        elseif ($action === 'reset_hwid') { $updates['hwid'] = null; $updates['hwid_lock'] = 0; }
        elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM keys WHERE id=?")->execute([$keyId]);
            header("Location: keys.php?app_id=$appId"); exit;
        }
        if ($updates) {
            $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($updates)));
            $updates['id'] = $keyId;
            $pdo->prepare("UPDATE keys SET $set WHERE id = :id")->execute($updates);
        }
    }
}

$keys = $pdo->prepare("SELECT * FROM keys WHERE app_id = ? ORDER BY created_at DESC");
$keys->execute([$appId]);
$keysList = $keys->fetchAll();
?>
<!-- Form & Table UI -->
<form method="POST" class="card">
    <input name="action" value="create" type="hidden">
    <input name="label" placeholder="Label (optional)">
    <input name="length" type="number" value="32" min="16" max="128">
    <input name="expiry" type="datetime-local">
    <input name="uses_left" type="number" placeholder="Unlimited = -1" value="-1">
    <label><input type="checkbox" name="hwid_lock"> HWID Lock</label>
    <input name="hwid" placeholder="HWID (if locked)">
    <button type="submit">Generate Key</button>
</form>

<table class="card" style="width:100%">
    <tr><th>Key</th><th>Label</th><th>Status</th><th>Expiry</th><th>HWID</th><th>Actions</th></tr>
    <?php foreach($keysList as $k): ?>
    <tr>
        <td><code><?= $k['key_code'] ?></code></td>
        <td><?= htmlspecialchars($k['label'] ?? '-') ?></td>
        <td><?= $k['status'] ?></td>
        <td><?= $k['expiry'] ? date('Y-m-d H:i', strtotime($k['expiry'])) : 'Never' ?></td>
        <td><?= $k['hwid_lock'] ? substr($k['hwid'],0,12).'...' : 'Off' ?></td>
        <td>
            <form method="POST" style="display:inline">
                <input type="hidden" name="key_id" value="<?=$k['id']?>">
                <button name="action" value="pause">Pause</button>
                <button name="action" value="resume">Resume</button>
                <button name="action" value="block">Block</button>
                <button name="action" value="reset_hwid">Reset HWID</button>
                <button name="action" value="delete" style="color:red">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
