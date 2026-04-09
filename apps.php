<?php require 'core.php'; requireLogin(); $pdo=getPDO();
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(isset($_POST['create_app'])){
    $name=trim($_POST['app_name']); $desc=trim($_POST['app_desc']??''); $exp=$_POST['expires_at']?:null;
    $pdo->prepare("INSERT OR IGNORE INTO applications (name,secret_key,description,expires_at) VALUES (?,?,?,?)")->execute([$name,generateSecret(),$desc,$exp]);
  }
  if(isset($_POST['toggle_status'])) $pdo->prepare("UPDATE applications SET status=? WHERE id=?")->execute([$_POST['new_status'],$_POST['app_id']]);
  if(isset($_POST['delete_app'])) $pdo->prepare("DELETE FROM applications WHERE id=?")->execute([$_POST['app_id']]);
  if(isset($_POST['export_app'])){
    $keys=$pdo->prepare("SELECT key_string,label,expires_at FROM keys WHERE app_id=?"); $keys->execute([$_POST['app_id']]);
    $out="Key,Label,Expires\n"; foreach($keys as $k) $out.="{$k['key_string']},\"{$k['label']}\",{$k['expires_at']}\n";
    header('Content-Type:text/csv'); header('Content-Disposition:attachment;filename=app_keys.csv'); echo $out; exit;
  }
  header('Location: apps.php'); exit;
}
$apps=$pdo->query("SELECT *, CASE WHEN expires_at IS NOT NULL AND expires_at < datetime('now') THEN 'expired' ELSE status END as real_status FROM applications ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>CordAuth | Apps</title>
<style>:root{--bg:#0b0f19;--card:#151b28;--border:#2a3549;--text:#e2e8f0;--muted:#94a3b8;--primary:#6366f1;--green:#22c55e;--yellow:#f59e0b;--red:#ef4444}
body{font-family:system-ui;background:var(--bg);color:var(--text);margin:0}
.nav{background:var(--card);padding:1rem 2rem;display:flex;gap:1rem;align-items:center;border-bottom:1px solid var(--border)}
.nav a{color:var(--muted);text-decoration:none;padding:.6rem 1rem;border-radius:8px;transition:.3s}
.nav a:hover,.nav a.active{background:#1e293b;color:#fff}
.container{padding:2rem;max-width:1200px;margin:0 auto}
table{width:100%;border-collapse:collapse;margin-top:1.5rem}
th,td{padding:14px;border-bottom:1px solid var(--border);text-align:left;animation:fadeInUp .5s ease}
th{background:var(--card);color:var(--muted)}
input,select,button{padding:10px;margin:5px 0;border-radius:8px;border:1px solid var(--border);background:#0b1120;color:#fff}
button{background:var(--primary);cursor:pointer;transition:.3s} button:hover{transform:translateY(-2px)}
button.red{background:var(--red)} button.sm{padding:6px 10px;font-size:.8rem;margin:2px}
.form-box{background:var(--card);padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;border:1px solid var(--border);animation:slideIn .6s ease}
.badge{padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.green{background:rgba(34,197,94,.15);color:var(--green)} .yellow{background:rgba(245,158,11,.15);color:var(--yellow)} .red{background:rgba(239,68,68,.15);color:var(--red)}
@keyframes fadeInUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
@keyframes slideIn{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
</style></head><body>
<div class="nav"><a href="index.php">Dashboard</a><a href="apps.php" class="active">Applications</a><a href="keys.php">Keys</a><a href="settings.php">Settings</a><a href="index.php?logout" style="margin-left:auto;color:var(--red)">Logout</a></div>
<div class="container"><h1>📦 Applications</h1>
<div class="form-box"><form method="POST" style="display:grid;grid-template-columns:1fr 2fr 1fr auto;gap:10px;align-items:end">
<div><label>Name</label><input type="text" name="app_name" required></div>
<div><label>Description</label><input type="text" name="app_desc" placeholder="Optional notes"></div>
<div><label>Expires At</label><input type="datetime-local" name="expires_at"></div>
<button type="submit" name="create_app">➕ Create</button></form></div>
<table><tr><th>Name</th><th>Secret</th><th>Description</th><th>Status</th><th>Expiry</th><th>Keys</th><th>Actions</th></tr>
<?php foreach($apps as $a): ?>
<tr><td><?=htmlspecialchars($a['name'])?></td><td><code style="background:#0f172a;padding:4px 6px;border-radius:6px"><?=$a['secret_key']?></code></td>
<td><?=htmlspecialchars($a['description'])?></td><td>
<form method="POST" style="display:inline"><input type="hidden" name="app_id" value="<?=$a['id']?>">
<select name="new_status" onchange="this.form.submit()" style="padding:4px 8px">
<option value="active" <?=$a['real_status']=='active'?'selected':''?>>Active</option>
<option value="maintenance" <?=$a['real_status']=='maintenance'?'selected':''?>>Maintenance</option>
<option value="disabled" <?=$a['real_status']=='disabled'?'selected':''?>>Disabled</option></select>
<input type="hidden" name="toggle_status" value="1"></form></td>
<td><?=$a['expires_at']?:'Never'?></td><td><?=$pdo->prepare("SELECT COUNT(*) FROM keys WHERE app_id=?")->execute([$a['id']]); echo $pdo->query("SELECT COUNT(*) FROM keys WHERE app_id={$a['id']}")->fetchColumn()?></td>
<td><form method="POST" style="display:inline"><input type="hidden" name="app_id" value="<?=$a['id']?>"><button class="sm" name="export_app">📥 Export</button></form>
<form method="POST" style="display:inline" onsubmit="return confirm('Delete app & all keys?')"><input type="hidden" name="app_id" value="<?=$a['id']?>"><button class="sm red" name="delete_app">🗑</button></form></td></tr>
<?php endforeach; ?></table></div></body></html>
