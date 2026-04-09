<?php
require 'core.php';
requireLogin();
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate') {
        $appId = $_POST['app_id'];
        $label = trim($_POST['label'] ?? '');
        $length = (int)($_POST['length'] ?? 16);
        $expire = $_POST['expire'] ?: null;
        $hwid = isset($_POST['hwid_lock']) ? 1 : 0;
        $key = generateKey($length);
        $stmt = $pdo->prepare("INSERT INTO keys (app_id, key_string, label, hwid_locked, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$appId, $key, $label, $hwid, $expire]);
    }
    elseif (in_array($action, ['delete','pause','resume','block','reset_hwid','extend','reduce'])) {
        $id = $_POST['key_id'];
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM keys WHERE id=?")->execute([$id]);
        } elseif ($action === 'pause') {
            $pdo->prepare("UPDATE keys SET status='paused' WHERE id=?")->execute([$id]);
        } elseif ($action === 'resume') {
            $pdo->prepare("UPDATE keys SET status='active' WHERE id=?")->execute([$id]);
        } elseif ($action === 'block') {
            $pdo->prepare("UPDATE keys SET status='blocked' WHERE id=?")->execute([$id]);
        } elseif ($action === 'reset_hwid') {
            $pdo->prepare("UPDATE keys SET hwid_value=NULL WHERE id=?")->execute([$id]);
        } elseif ($action === 'extend') {
            $days = (int)($_POST['days'] ?? 30);
            $pdo->prepare("UPDATE keys SET expires_at = datetime(expires_at, '+{$days} days') WHERE id=?")->execute([$id]);
        } elseif ($action === 'reduce') {
            $days = (int)($_POST['days'] ?? 30);
            $pdo->prepare("UPDATE keys SET expires_at = datetime(expires_at, '-{$days} days') WHERE id=?")->execute([$id]);
        }
    }
    header('Location: keys.php'); exit;
}

$apps = $pdo->query("SELECT id, name FROM applications ORDER BY name")->fetchAll();
$filterApp = $_GET['app_id'] ?? '';
$where = $filterApp ? "WHERE app_id = $filterApp" : "";
$keys = $pdo->query("SELECT k.*, a.name as app_name FROM keys k JOIN applications a ON k.app_id = a.id $where ORDER BY k.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Keys</title>
<style>/* reuse base styles */ body{font-family:system-ui;background:#0f172a;color:#e2e8f0;margin:0}.nav{background:#1e293b;padding:1rem;display:flex;gap:1rem;align-items:center}.nav a{color:#94a3b8;text-decoration:none;padding:0.5rem 1rem;border-radius:6px}.nav a:hover,.nav a.active{background:#334155;color:#fff}.container{padding:2rem}table{width:100%;border-collapse:collapse;margin-top:1rem}th,td{padding:10px;border-bottom:1px solid #334155;text-align:left}th{background:#1e293b;color:#94a3b8}input,select,button{padding:8px;margin:4px 0;border-radius:6px;border:1px solid #334155;background:#0f172a;color:#fff}button{background:#3b82f6;cursor:pointer}.btn-sm{padding:4px 8px;font-size:0.8rem;margin:2px}.form-box{background:#1e293b;padding:1rem;border-radius:8px;margin-bottom:1rem}.status{padding:4px 8px;border-radius:4px;font-size:0.8rem}.status.active{background:#22c55e;color:#000}.status.paused{background:#f59e0b;color:#000}.status.blocked{background:#ef4444;color:#fff}.status.used{background:#64748b;color:#fff}.status.expired{background:#475569;color:#fff}</style></head>
<body>
<div class="nav"><a href="index.php">Dashboard</a><a href="apps.php">Applications</a><a href="keys.php" class="active">Keys</a><a href="settings.php">Settings</a><a href="index.php?logout" style="margin-left:auto;color:#ef4444">Logout</a></div>
<div class="container">
<h1>🔑 Key Management</h1>
<div class="form-box">
<form method="POST">
<select name="app_id" required><option value="">Select App</option><?php foreach($apps as $a) echo "<option value='{$a['id']}'>{$a['name']}</option>"; ?></select>
<input type="text" name="label" placeholder="Label/Name">
<input type="number" name="length" value="16" min="8" max="32" placeholder="Length">
<input type="datetime-local" name="expire" title="Leave empty for no expiry">
<label style="display:inline-flex;align-items:center;gap:5px;margin:5px 0"><input type="checkbox" name="hwid_lock" style="width:auto"> Lock to HWID</label>
<button type="submit" name="action" value="generate">Generate Key</button>
</form>
</div>

<div style="margin:1rem 0">
<a href="keys.php" style="color:#94a3b8;text-decoration:none">All Keys</a>
<?php foreach($apps as $a) echo " | <a href='keys.php?app_id={$a['id']}' style='color:#94a3b8;text-decoration:none'>{$a['name']}</a>"; ?>
</div>

<table>
<tr><th>Key</th><th>App</th><th>Label</th><th>Status</th><th>HWID</th><th>Expires</th><th>Actions</th></tr>
<?php foreach($keys as $k): 
$st = $k['status'];
if($k['expires_at'] && strtotime($k['expires_at']) < time()) $st = 'expired';
?>
<tr>
<td><code><?=$k['key_string']?></code></td>
<td><?=$k['app_name']?></td>
<td><?=htmlspecialchars($k['label'])?></td>
<td><span class="status <?=$st?>"><?=ucfirst($st)?></span></td>
<td><?=$k['hwid_locked'] ? ($k['hwid_value'] ?: 'Not Bound') : 'Off'?></td>
<td><?=$k['expires_at'] ?: 'Never'?></td>
<td>
<form method="POST" style="display:inline">
<input type="hidden" name="key_id" value="<?=$k['id']?>">
<?php if($st==='active'): ?>
<button class="btn-sm" name="action" value="pause">Pause</button>
<button class="btn-sm" name="action" value="block">Block</button>
<?php elseif($st==='paused'): ?>
<button class="btn-sm" name="action" value="resume">Resume</button>
<?php elseif($st==='blocked'): ?>
<button class="btn-sm" name="action" value="resume">Unblock</button>
<?php endif; ?>
<?php if($k['hwid_value']): ?>
<button class="btn-sm" name="action" value="reset_hwid">Reset HWID</button>
<?php endif; ?>
<button class="btn-sm" name="action" value="extend">+30d</button>
<button class="btn-sm" name="action" value="reduce">-30d</button><button class="btn-sm red" name="action" value="delete" onclick="return confirm('Delete key?')">Del</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>
</body>
</html>
