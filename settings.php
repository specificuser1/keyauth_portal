<?php require 'core.php'; requireLogin(); $pdo=getPDO();
if($_SERVER['REQUEST_METHOD']==='POST'){
  foreach(['portal_pass','portal_title','webhook_url','api_rate_limit','session_timeout','maintenance_mode'] as $k){
    if(isset($_POST[$k])){
      $v=$_POST[$k]==='portal_pass'?password_hash($_POST[$k],PASSWORD_DEFAULT):$_POST[$k];
      $pdo->prepare("INSERT OR REPLACE INTO settings (key,value) VALUES (?,?)")->execute([$k,$v]);
    }
  }
  if(isset($_POST['backup_db'])){
    $db=DB_PATH; header('Content-Type:application/octet-stream'); header('Content-Disposition:attachment;filename=cordauth_backup_'.date('Y-m-d_H-i').'.db');
    readfile($db); exit;
  }
  if(isset($_POST['clear_logs'])) $pdo->exec("DELETE FROM api_logs");
  $msg="✅ Settings updated successfully.";
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>CordAuth | Settings</title>
<style>:root{--bg:#0b0f19;--card:#151b28;--border:#2a3549;--text:#e2e8f0;--muted:#94a3b8;--primary:#6366f1;--green:#22c55e;--red:#ef4444}
body{font-family:system-ui;background:var(--bg);color:var(--text);margin:0}
.nav{background:var(--card);padding:1rem 2rem;display:flex;gap:1rem;align-items:center;border-bottom:1px solid var(--border)}
.nav a{color:var(--muted);text-decoration:none;padding:.6rem 1rem;border-radius:8px;transition:.3s}
.nav a:hover,.nav a.active{background:#1e293b;color:#fff}
.container{padding:2rem;max-width:900px;margin:0 auto}
.form-box{background:var(--card);padding:2rem;border-radius:12px;border:1px solid var(--border);margin-bottom:1.5rem;animation:slideIn .6s ease}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
input,select,button{padding:12px;margin:8px 0;border-radius:8px;border:1px solid var(--border);background:#0b1120;color:#fff;width:100%}
button{background:var(--primary);cursor:pointer;font-weight:600;transition:.3s} button:hover{transform:translateY(-2px)}
button.red{background:var(--red)} button.green{background:var(--green)}
label{display:block;margin-bottom:5px;color:var(--muted);font-size:.9rem}
.msg{color:var(--green);margin:10px 0}
@keyframes slideIn{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
</style></head><body>
<div class="nav"><a href="index.php">Dashboard</a><a href="apps.php">Applications</a><a href="keys.php">Keys</a><a href="settings.php" class="active">Settings</a><a href="index.php?logout" style="margin-left:auto;color:var(--red)">Logout</a></div>
<div class="container"><h1>⚙️ Settings</h1>
<form method="POST" class="grid">
<div class="form-box"><h3>🔐 Security</h3><label>Portal Password</label><input type="password" name="portal_pass" placeholder="Leave empty to keep current">
<label>Session Timeout (seconds)</label><input type="number" name="session_timeout" value="<?=$pdo->query("SELECT value FROM settings WHERE key='session_timeout'")->fetchColumn()?>">
<label>Maintenance Mode</label><select name="maintenance_mode"><option value="0" <?=$pdo->query("SELECT value FROM settings WHERE key='maintenance_mode'")->fetchColumn()=='0'?'selected':''?>>Off</option><option value="1" <?=$pdo->query("SELECT value FROM settings WHERE key='maintenance_mode'")->fetchColumn()=='1'?'selected':''?>>On</option></select></div>
<div class="form-box"><h3>🌐 API & Webhooks</h3><label>Webhook URL</label><input type="url" name="webhook_url" value="<?=$pdo->query("SELECT value FROM settings WHERE key='webhook_url'")->fetchColumn()?>">
<label>Rate Limit (req/min)</label><input type="number" name="api_rate_limit" value="<?=$pdo->query("SELECT value FROM settings WHERE key='api_rate_limit'")->fetchColumn()?>"></div>
<div class="form-box"><h3>🏷️ Branding</h3><label>Portal Title</label><input type="text" name="portal_title" value="<?=$pdo->query("SELECT value FROM settings WHERE key='portal_title'")->fetchColumn()?>"></div>
<div class="form-box"><h3>🛠️ Tools</h3><button type="submit" name="backup_db" class="green">📥 Backup DB</button><button type="submit" name="clear_logs" class="red">🗑 Clear API Logs</button></div>
<button type="submit" style="grid-column:span 2;margin-top:10px">💾 Save All Settings</button>
<?php if(!empty($msg)) echo "<p class='msg' style='grid-column:span 2'>$msg</p>"; ?>
</form></div></body></html>
