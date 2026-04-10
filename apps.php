<?php require 'core.php'; requireLogin(); $pdo=getPDO();
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verifyCSRF()) { header('Location: apps.php?err=CSRF'); exit; }
    if(isset($_POST['create_app'])){
        $pdo->prepare("INSERT OR IGNORE INTO applications (name,secret_key,description,expires_at) VALUES (?,?,?,?)")
            ->execute([trim($_POST['app_name']), generateSecret(), trim($_POST['app_desc']??''), $_POST['expires_at']?:null]);
        logActivity($pdo, 'app_create', "Created app: {$_POST['app_name']}");
        sendDiscordEmbed('📦 App Created', "Name: `{$_POST['app_name']}`\nDescription: `".($_POST['app_desc']?:'None')."`", [], 0x22c55e);
    }
    if(isset($_POST['bulk_status'])){
        $ids = $_POST['app_ids'] ?? []; $st = $_POST['new_status'];
        if(!empty($ids)) $pdo->prepare("UPDATE applications SET status=? WHERE id IN (".implode(',',array_fill(0,count($ids),'?')).")")->execute([$st, ...$ids]);
    }
    if(isset($_POST['export_all'])){
        $keys = $pdo->query("SELECT key_string,label,expires_at FROM keys ORDER BY created_at DESC")->fetchAll();
        $out = "Key,Label,Expires\n"; foreach($keys as $k) $out.="\"{$k['key_string']}\",\"{$k['label']}\",{$k['expires_at']}\n";
        header('Content-Type:text/csv'); header('Content-Disposition:attachment;filename=all_keys.csv'); echo $out; exit;
    }
    header('Location: apps.php'); exit;
}
$apps = $pdo->query("SELECT *, CASE WHEN expires_at IS NOT NULL AND expires_at < datetime('now') THEN 'expired' ELSE status END as real_status FROM applications ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>CordAuth | Apps</title><style><?=getThemeCSS($pdo)?></style>
<style>body{font-family:system-ui;background:var(--bg);color:var(--text);margin:0}.nav{background:var(--card);padding:1rem 2rem;display:flex;gap:1rem;align-items:center;border-bottom:1px solid var(--border)}.nav a{color:var(--muted);text-decoration:none;padding:.6rem 1rem;border-radius:8px;transition:.3s}.nav a:hover,.nav a.active{background:color-mix(in srgb, var(--card), #2a3549 40%);color:#fff}.container{padding:2rem;max-width:1200px;margin:0 auto}table{width:100%;border-collapse:collapse;margin-top:1.5rem}th,td{padding:14px;border-bottom:1px solid var(--border);text-align:left;animation:fadeInUp .5s ease}th{background:var(--card);color:var(--muted)}input,select,button{padding:10px;margin:5px 0;border-radius:8px;border:1px solid var(--border);background:#0b1120;color:#fff}button{background:var(--primary);cursor:pointer;transition:.3s}button:hover{transform:translateY(-2px)}button.sm{padding:6px 10px;font-size:.8rem;margin:2px}.form-box{background:var(--card);padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;border:1px solid var(--border);animation:slideIn .6s ease}.badge{padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600}.green{background:rgba(34,197,94,.15);color:#22c55e}.yellow{background:rgba(245,158,11,.15);color:#f59e0b}.red{background:rgba(239,68,68,.15);color:#ef4444}@keyframes fadeInUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}@keyframes slideIn{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}</style></head><body>
<div class="nav"><a href="index.php">Dashboard</a><a href="apps.php" class="active">Applications</a><a href="keys.php">Keys</a><a href="settings.php">Settings</a><a href="index.php?logout" style="margin-left:auto;color:#ef4444">Logout</a></div>
<div class="container"><h1>📦 Applications</h1>
<?php if(isset($_GET['err'])) echo "<p style='color:var(--red)'>⚠️ Security Check Failed. Please refresh.</p>"; ?>
<div class="form-box"><form method="POST" style="display:grid;grid-template-columns:1fr 2fr 1fr auto;gap:10px;align-items:end"><?= csrfField() ?>
<div><label>Name</label><input type="text" name="app_name" required></div><div><label>Description</label><input type="text" name="app_desc" placeholder="Optional notes"></div><div><label>Expires At</label><input type="datetime-local" name="expires_at"></div><button type="submit" name="create_app">➕ Create</button></form></div>
<form method="POST" style="display:flex;gap:10px;margin:10px 0"><?= csrfField() ?><select name="new_status"><option value="active">Active</option><option value="maintenance">Maintenance</option><option value="disabled">Disabled</option></select><button type="submit" name="bulk_status">🔄 Bulk Status</button><button type="submit" name="export_all">📥 Export All Keys</button></form>
<table><tr><th><input type="checkbox" id="selAll"></th><th>Name</th><th>Secret</th><th>Description</th><th>Status</th><th>Expiry</th><th>Keys</th><th>Actions</th></tr>
<?php foreach($apps as $a): $cnt=$pdo->prepare("SELECT COUNT(*) FROM keys WHERE app_id=?"); $cnt->execute([$a['id']]); ?>
<tr><td><input type="checkbox" class="app-cb" name="app_ids[]" value="<?=$a['id']?>"></td><td><?=htmlspecialchars($a['name'])?></td><td><code style="background:#0f172a;padding:4px 6px;border-radius:6px"><?=$a['secret_key']?></code></td><td><?=htmlspecialchars($a['description'])?></td><td><span class="badge <?=$a['real_status']=='active'?'green':($a['real_status']=='maintenance'?'yellow':'red')?>"><?=ucfirst($a['real_status'])?></span></td><td><?=$a['expires_at']?:'Never'?></td><td><?=$cnt->fetchColumn()?></td><td><a href="keys.php?app_id=<?=$a['id']?>" style="color:var(--muted)">🔑 View</a></td></tr>
<?php endforeach; ?>
</table></div><script>document.getElementById('selAll').onclick=e=>document.querySelectorAll('.app-cb').forEach(c=>c.checked=e.target.checked);</script></body></html>
