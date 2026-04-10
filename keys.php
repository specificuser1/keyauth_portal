<?php require 'core.php'; requireLogin(); $pdo=getPDO();
$page=max(1,(int)($_GET['p']??1)); $limit=50; $offset=($page-1)*$limit;
$search=trim($_GET['q']??''); $appFilter=trim($_GET['app_id']??'');
$where='WHERE 1=1'; $params=[];
if($appFilter){$where.=' AND k.app_id=?'; $params[]=$appFilter;}
if($search){$where.=' AND (k.key_string LIKE ? OR k.label LIKE ? OR k.note LIKE ?)'; $params=array_merge($params,["%$search%","%$search%","%$search%"]);}
$count=$pdo->prepare("SELECT COUNT(*) FROM keys k $where"); $count->execute($params); $total=$count->fetchColumn(); $pages=ceil($total/$limit);

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verifyCSRF()) { header('Location: keys.php?err=CSRF'); exit; }
    $a=$_POST['action']??'';
    if($a==='generate'){
        $mask=$_POST['mask']??''; $len=max(8,min(32,(int)($_POST['length']??16))); $cnt=max(1,min(50,(int)($_POST['count']??1)));
        $stmt=$pdo->prepare("INSERT INTO keys (app_id,key_string,label,note,hwid_locked,ip_locked,max_uses,cooldown,expires_at,variables) VALUES (?,?,?,?,?,?,?,?,?,?)");
        for($i=0;$i<$cnt;$i++){
            $key=$mask?generateMaskedKey($mask):generateKey($len);
            $vars=json_encode(array_filter(['var1'=>$_POST['var1']??'','var2'=>$_POST['var2']??'']));
            $stmt->execute([$_POST['app_id'],$key,$_POST['label']??'',$_POST['note']??'',isset($_POST['hwid_lock'])?1:0,isset($_POST['ip_lock'])?1:0,(int)$_POST['max_uses'],(int)$_POST['cooldown'],$_POST['expires_at']?:null,$vars]);
        }
        logActivity($pdo, 'key_gen', "Generated $cnt keys for app ID {$_POST['app_id']}");
    } elseif($a==='import'){
        $lines=array_filter(array_map('trim', explode("\n", $_POST['import_text'])));
        $stmt=$pdo->prepare("INSERT OR IGNORE INTO keys (app_id,key_string) VALUES (?,?)");
        foreach($lines as $l) if($l) $stmt->execute([$_POST['app_id'], $l]);
    } else {
        $id=$_POST['key_id'];
        match($a){
            'delete'=>$pdo->prepare("DELETE FROM keys WHERE id=?")->execute([$id]),
            'pause'=>$pdo->prepare("UPDATE keys SET status='paused' WHERE id=?")->execute([$id]),
            'resume'=>$pdo->prepare("UPDATE keys SET status='active' WHERE id=?")->execute([$id]),
            'block'=>$pdo->prepare("UPDATE keys SET status='blocked' WHERE id=?")->execute([$id]),
            'reset_hwid'=>$pdo->prepare("UPDATE keys SET hwid_value=NULL WHERE id=?")->execute([$id]),
            'reset_ip'=>$pdo->prepare("UPDATE keys SET ip_value=NULL WHERE id=?")->execute([$id]),
            'reset_uses'=>$pdo->prepare("UPDATE keys SET usage_count=0 WHERE id=?")->execute([$id]),
            default=>null
        };
    }
    header('Location: keys.php'); exit;
}

$apps=$pdo->query("SELECT id,name FROM applications ORDER BY name")->fetchAll();
$keys=$pdo->prepare("SELECT k.*,a.name as app_name FROM keys k JOIN applications a ON k.app_id=a.id $where ORDER BY k.created_at DESC LIMIT $limit OFFSET $offset");
$keys->execute($params); $keys=$keys->fetchAll(); $now=time();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>CordAuth | Keys</title><style><?=getThemeCSS($pdo)?></style>
<style>body{font-family:system-ui;background:var(--bg);color:var(--text);margin:0}.nav{background:var(--card);padding:1rem 2rem;display:flex;gap:1rem;align-items:center;border-bottom:1px solid var(--border)}.nav a{color:var(--muted);text-decoration:none;padding:.6rem 1rem;border-radius:8px;transition:.3s}.nav a:hover,.nav a.active{background:color-mix(in srgb, var(--card), #2a3549 40%);color:#fff}.container{padding:2rem;max-width:1400px;margin:0 auto}table{width:100%;border-collapse:collapse;margin-top:1.5rem;font-size:.9rem}th,td{padding:12px;border-bottom:1px solid var(--border);text-align:left;animation:fadeInUp .5s ease}th{background:var(--card);color:var(--muted)}input,select,button,textarea{padding:10px;margin:5px 0;border-radius:8px;border:1px solid var(--border);background:#0b1120;color:#fff}button{background:var(--primary);cursor:pointer;transition:.3s}button:hover{transform:translateY(-2px)}button.sm{padding:6px 10px;font-size:.8rem;margin:2px}button.red{background:#ef4444}.form-box{background:var(--card);padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;border:1px solid var(--border);animation:slideIn .6s ease}.badge{padding:4px 8px;border-radius:20px;font-size:.75rem;font-weight:600}.green{background:rgba(34,197,94,.15);color:#22c55e}.yellow{background:rgba(245,158,11,.15);color:#f59e0b}.red{background:rgba(239,68,68,.15);color:#ef4444}.gray{background:rgba(100,116,139,.15);color:#64748b}.grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.pagination{margin-top:1rem;display:flex;gap:5px;justify-content:center}.pagination a{padding:8px 12px;background:var(--card);color:var(--text);text-decoration:none;border-radius:6px;border:1px solid var(--border)}.pagination a.active{background:var(--primary);color:#fff}@keyframes fadeInUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}@keyframes slideIn{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}</style></head><body>
<div class="nav"><a href="index.php">Dashboard</a><a href="apps.php">Applications</a><a href="keys.php" class="active">Keys</a><a href="settings.php">Settings</a><a href="index.php?logout" style="margin-left:auto;color:#ef4444">Logout</a></div>
<div class="container"><h1>🔑 Key Management</h1>
<?php if(isset($_GET['err'])) echo "<p style='color:var(--red)'>⚠️ Security Check Failed. Please refresh.</p>"; ?>
<form method="GET" style="display:flex;gap:10px;margin-bottom:1.5rem"><input type="text" name="q" placeholder="Search key/label/note..." value="<?=htmlspecialchars($search)?>"><select name="app_id"><option value="">All Apps</option><?php foreach($apps as $a) echo "<option value='{$a['id']}' ".($a['id']==$appFilter?'selected':'').">{$a['name']}</option>"; ?></select><button type="submit">🔍 Filter</button></form>
<div class="form-box"><form method="POST" class="grid-4"><?= csrfField() ?>
<div><label>App</label><select name="app_id" required><?php foreach($apps as $a) echo "<option value='{$a['id']}'>{$a['name']}</option>"; ?></select></div>
<div><label>Mask (A=Alpha, N=Num, X=Alnum)</label><input type="text" name="mask" placeholder="CORA-XXXX-XXXX-XXXX"></div>
<div><label>Length</label><input type="number" name="length" value="16" min="8" max="32"></div>
<div><label>Count</label><input type="number" name="count" value="1" min="1" max="50"></div>
<div><label>Expires</label><input type="datetime-local" name="expires_at"></div>
<div><label>Var1</label><input type="text" name="var1" placeholder="custom=value"></div>
<div><label>Var2</label><input type="text" name="var2" placeholder="custom=value"></div>
<div style="grid-column:span 4;display:flex;gap:15px;align-items:center"><label><input type="checkbox" name="hwid_lock" style="width:auto"> HWID</label><label><input type="checkbox" name="ip_lock" style="width:auto"> IP</label><label>Max Uses <input type="number" name="max_uses" value="0" min="0" style="width:60px;display:inline"></label><label>Cooldown(s) <input type="number" name="cooldown" value="0" min="0" style="width:60px;display:inline"></label></div>
<button type="submit" name="action" value="generate" style="grid-column:span 4;margin-top:10px">⚡ Generate Keys</button></form></div>
<div class="form-box"><h3>📥 Import Keys (One per line)</h3><form method="POST"><?= csrfField() ?><select name="app_id" required><?php foreach($apps as $a) echo "<option value='{$a['id']}'>{$a['name']}</option>"; ?></select><textarea name="import_text" rows="3" placeholder="KEY-XXXX\nKEY-YYYY..."></textarea><button type="submit" name="action" value="import">Upload Keys</button></form></div>
<table><tr><th>Key</th><th>App</th><th>Label/Note</th><th>Status</th><th>HWID/IP</th><th>Uses</th><th>Vars</th><th>Expires</th><th>Actions</th></tr>
<?php foreach($keys as $k): $st=$k['status']; if($k['expires_at']&&strtotime($k['expires_at'])<$now) $st='expired'; $cls=$st=='active'?'green':($st=='paused'?'yellow':($st=='blocked'?'red':'gray')); ?>
<tr><td><code style="background:#0f172a;padding:4px 6px;border-radius:6px"><?=$k['key_string']?></code></td><td><?=$k['app_name']?></td><td><?=htmlspecialchars($k['label'])?><br><small style="color:var(--muted)"><?=htmlspecialchars($k['note'])?></small></td><td><span class="badge <?=$cls?>"><?=ucfirst($st)?></span></td><td>HW: <?=$k['hwid_value']?:($k['hwid_locked']?'🔒':'Off')?><br>IP: <?=$k['ip_value']?:($k['ip_locked']?'🔒':'Off')?></td><td><?=$k['usage_count']?><?=$k['max_uses']>0?"/{$k['max_uses']}":""?></td><td><small><?=htmlspecialchars($k['variables'])!=='{}'?htmlspecialchars($k['variables']):'—'?></small></td><td><?=$k['expires_at']?:'♾️'?></td><td><form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="key_id" value="<?=$k['id']?>"><?php if($st=='active'):?><button class="sm" name="action" value="pause">⏸</button><button class="sm" name="action" value="block">🚫</button><?php else:?><button class="sm" name="action" value="resume">▶</button><?php endif;?><?php if($k['hwid_value']):?><button class="sm" name="action" value="reset_hwid">🔄H</button><?php endif;?><?php if($k['ip_value']):?><button class="sm" name="action" value="reset_ip">🔄I</button><?php endif;?><button class="sm" name="action" value="reset_uses">🔢</button><button class="sm red" name="action" value="delete" onclick="return confirm('Delete key?')">🗑</button></form></td></tr><?php endforeach; ?>
</table><div class="pagination"><?php for($i=1;$i<=$pages;$i++): $qs=$_GET; $qs['p']=$i; ?><a href="?<?=http_build_query($qs)?>" class="<?=$i==$page?'active':''?>"><?=$i?></a><?php endfor; ?></div></div></body></html>
