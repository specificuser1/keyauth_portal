<?php require 'core.php'; requireLogin(); $pdo=getPDO();
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!verifyCSRF()) die("CSRF Error");
  foreach(['portal_pass','portal_title','theme_primary','theme_bg','theme_card','webhook_url','api_rate_limit','session_timeout','maintenance_mode','auto_cleanup'] as $k){
    if(isset($_POST[$k])){ $v=$_POST[$k]==='portal_pass'?password_hash($_POST[$k],PASSWORD_DEFAULT):$_POST[$k]; $pdo->prepare("INSERT OR REPLACE INTO settings (key,value) VALUES (?,?)")->execute([$k,$v]); }
  }
  if(!empty($_FILES['logo']['tmp_name'])){
    $path=uploadFile($_FILES['logo']);
    if($path){ $pdo->prepare("INSERT OR REPLACE INTO settings (key,value) VALUES ('logo_path',?)")->execute([basename($path)]); logActivity($pdo,'logo','Logo updated'); }
  }
  if(isset($_POST['test_webhook'])){
    sendDiscordEmbed('test','🧪 Webhook Test','✅ Connection successful! CordAuth portal is live.','#22c55e');
    $msg="✅ Webhook test sent to Discord.";
  }
  $msg="✅ Settings updated successfully.";
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>CordAuth | Settings</title><style><?=getThemeCSS($pdo)?></style>
<style>body{font-family:system-ui;background:var(--bg);color:var(--text);margin:0}.nav{background:var(--card);padding:1rem 2rem;display:flex;gap:1rem;align-items:center;border-bottom:1px solid var(--border)}.nav a{color:var(--muted);text-decoration:none;padding:.6rem 1rem;border-radius:8px;transition:.3s}.nav a:hover,.nav a.active{background:color-mix(in srgb, var(--card), #2a3549 40%);color:#fff}.container{padding:2rem;max-width:900px;margin:0 auto}.form-box{background:var(--card);padding:2rem;border-radius:12px;border:1px solid var(--border);margin-bottom:1.5rem;animation:slideIn .6s ease}.grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}input,select,button{padding:12px;margin:8px 0;border-radius:8px;border:1px solid var(--border);background:#0b1120;color:#fff;width:100%}button{background:var(--primary);cursor:pointer;font-weight:600;transition:.3s} button:hover{transform:translateY(-2px)} button.red{background:#ef4444} button.green{background:#22c55e}label{display:block;margin-bottom:5px;color:var(--muted);font-size:.9rem}.msg{color:#22c55e;margin:10px 0}@keyframes slideIn{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}</style></head><body>
<div class="nav"><a href="index.php">Dashboard</a><a href="apps.php">Applications</a><a href="keys.php">Keys</a><a href="settings.php" class="active">Settings</a><a href="index.php?logout" style="margin-left:auto;color:#ef4444">Logout</a></div>
<div class="container"><h1>⚙️ Settings</h1>
<form method="POST" enctype="multipart/form-data" class="grid">
<div class="form-box"><h3>🎨 Theme & Branding</h3><label>Logo (PNG/JPG/WebP, max 2MB)</label><input type="file" name="logo" accept="image/*"><label>Portal Title</label><input type="text" name="portal_title" value="<?=$pdo->query("SELECT value FROM settings WHERE key='portal_title'")->fetchColumn()?>"><label>Primary Color</label><input type="color" name="theme_primary" value="<?=$pdo->query("SELECT value FROM settings WHERE key='theme_primary'")->fetchColumn()?>"><label>Background</label><input type="color" name="theme_bg" value="<?=$pdo->query("SELECT value FROM settings WHERE key='theme_bg'")->fetchColumn()?>"><label>Card</label><input type="color" name="theme_card" value="<?=$pdo->query("SELECT value FROM settings WHERE key='theme_card'")->fetchColumn()?>"></div>
<div class="form-box"><h3>🌐 API & Discord</h3><label>Webhook URL</label><input type="url" name="webhook_url" value="<?=$pdo->query("SELECT value FROM settings WHERE key='webhook_url'")->fetchColumn()?>"><button type="submit" name="test_webhook" class="green">🧪 Test Webhook</button><label>Rate Limit (req/min)</label><input type="number" name="api_rate_limit" value="<?=$pdo->query("SELECT value FROM settings WHERE key='api_rate_limit'")->fetchColumn()?>"></div>
<div class="form-box"><h3>🔐 Security</h3><label>Portal Password</label><input type="password" name="portal_pass" placeholder="Leave empty to keep current"><label>Session Timeout (s)</label><input type="number" name="session_timeout" value="<?=$pdo->query("SELECT value FROM settings WHERE key='session_timeout'")->fetchColumn()?>"><label>Maintenance Mode</label><select name="maintenance_mode"><option value="0" <?=$pdo->query("SELECT value FROM settings WHERE key='maintenance_mode'")->fetchColumn()=='0'?'selected':''?>>Off</option><option value="1" <?=$pdo->query("SELECT value FROM settings WHERE key='maintenance_mode'")->fetchColumn()=='1'?'selected':''?>>On</option></select><label>Auto-Cleanup Expired Keys</label><select name="auto_cleanup"><option value="1" <?=$pdo->query("SELECT value FROM settings WHERE key='auto_cleanup'")->fetchColumn()=='1'?'selected':''?>>Enabled</option><option value="0">Disabled</option></select></div>
<div class="form-box"><h3>🖥️ System Info</h3><p>PHP: <?=phpversion()?><br>DB: <?=filesize(DB_PATH)/1024?> KB<br>Logs: <?=$pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn()?><br>Uptime: <?=gmdate("d H:i", time()-filemtime(__FILE__))?></p></div>
<button type="submit" style="grid-column:span 2;margin-top:10px">💾 Save All</button><input type="hidden" name="csrf" value="<?=$_SESSION['csrf']?>">
<?php if(!empty($msg)) echo "<p class='msg' style='grid-column:span 2'>$msg</p>"; ?>
</form></div></body></html>
