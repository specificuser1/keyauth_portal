<?php require 'core.php'; $pdo=getPDO(); $title=$pdo->query("SELECT value FROM settings WHERE key='portal_title'")->fetchColumn()?:'CordAuth';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['login_pass'])){
    $hash=$pdo->query("SELECT value FROM settings WHERE key='portal_pass'")->fetchColumn();
    if(password_verify($_POST['login_pass'],$hash)){ $_SESSION['ka_logged_in']=true; header('Location: index.php'); exit; }
    $error="Galat password!";
}
if(isset($_GET['logout'])){ session_destroy(); header('Location: index.php'); exit; }
if(!isLoggedIn()): ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title><?=$title?> | Login</title>
<style>:root{--bg:#0b0f19;--card:#151b28;--border:#2a3549;--text:#e2e8f0;--muted:#94a3b8;--primary:#6366f1;--red:#ef4444}
body{font-family:system-ui;background:var(--bg);color:var(--text);display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
form{background:var(--card);padding:2.5rem;border-radius:16px;width:360px;box-shadow:0 10px 40px rgba(0,0,0,.5);animation:fadeInUp .6s ease}
input{width:100%;padding:14px;margin:10px 0;border-radius:10px;border:1px solid var(--border);background:#0b1120;color:#fff;transition:.3s}
input:focus{border-color:var(--primary);outline:none}
button{width:100%;padding:14px;background:var(--primary);color:#fff;border:none;border-radius:10px;cursor:pointer;font-weight:600;transition:.3s}
button:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(99,102,241,.4)}
.err{color:var(--red);font-size:.9rem;text-align:center}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
</style></head><body>
<form method="POST"><h2 style="margin:0 0 15px;text-align:center">🔐 <?=$title?></h2>
<?php if(!empty($error)) echo "<p class='err'>$error</p>"; ?>
<input type="password" name="login_pass" placeholder="Portal Password" required>
<button type="submit">Login</button><p style="font-size:.8rem;color:var(--muted);text-align:center;margin-top:10px">Default: <code>password</code></p></form></body></html>
<?php exit; endif;
$pdo=getPDO(); $now=time(); $chartData=getChartData($pdo);
$stats=[
  'apps'=>['total'=>getStat($pdo,"SELECT COUNT(*) FROM applications"),'active'=>getStat($pdo,"SELECT COUNT(*) FROM applications WHERE status='active'")],
  'keys'=>['total'=>getStat($pdo,"SELECT COUNT(*) FROM keys"),'active'=>getStat($pdo,"SELECT COUNT(*) FROM keys WHERE status='active'"),'used'=>getStat($pdo,"SELECT COUNT(*) FROM keys WHERE usage_count>0")]
];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title><?=$title?> | Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>:root{--bg:#0b0f19;--card:#151b28;--border:#2a3549;--text:#e2e8f0;--muted:#94a3b8;--primary:#6366f1;--green:#22c55e;--yellow:#f59e0b;--red:#ef4444}
body{font-family:system-ui;background:var(--bg);color:var(--text);margin:0}
.nav{background:var(--card);padding:1rem 2rem;display:flex;gap:1rem;align-items:center;border-bottom:1px solid var(--border);animation:fadeIn .5s ease}
.nav a{color:var(--muted);text-decoration:none;padding:.6rem 1rem;border-radius:8px;transition:.3s}
.nav a:hover,.nav a.active{background:#1e293b;color:#fff}
.logout{margin-left:auto;color:var(--red)}
.container{padding:2rem;max-width:1200px;margin:0 auto}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem;margin:2rem 0}
.card{background:var(--card);padding:1.5rem;border-radius:12px;border:1px solid var(--border);animation:slideIn .6s ease;transition:.3s}
.card:hover{transform:translateY(-3px);border-color:var(--primary)}
.card h3{margin:0 0 .5rem;font-size:.9rem;color:var(--muted)}
.card p{margin:0;font-size:2rem;font-weight:700}
.chart-box{background:var(--card);padding:1.5rem;border-radius:12px;border:1px solid var(--border);margin-top:1.5rem;animation:fadeInUp .8s ease}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideIn{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
@keyframes fadeInUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
</style></head><body>
<div class="nav"><a href="index.php" class="active">Dashboard</a><a href="apps.php">Applications</a><a href="keys.php">Keys</a><a href="settings.php">Settings</a><a href="?logout" class="logout">Logout</a></div>
<div class="container">
<h1>📊 <?=$title?> Overview</h1>
<div class="grid">
<div class="card"><h3>📦 Applications</h3><p><?=$stats['apps']['total']?> <span style="color:var(--green);font-size:.8rem">✓ <?=$stats['apps']['active']?></span></p></div>
<div class="card"><h3>🔑 Total Keys</h3><p><?=$stats['keys']['total']?></p></div>
<div class="card"><h3>🟢 Active</h3><p style="color:var(--green)"><?=$stats['keys']['active']?></p></div>
<div class="card"><h3>📈 Used</h3><p style="color:var(--yellow)"><?=$stats['keys']['used']?></p></div>
</div>
<div class="chart-box"><canvas id="analyticsChart"></canvas></div>
</div>
<script>
const ctx=document.getElementById('analyticsChart').getContext('2d');
const data=<?=$chartData?>;
new Chart(ctx,{type:'line',data:{labels:data.labels,datasets:[
{label:'Keys Created',data:data.created,borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,.1)',tension:.4,fill:true},
{label:'Validations',data:data.used,borderColor:'#22c55e',backgroundColor:'rgba(34,197,94,.1)',tension:.4,fill:true}
]},options:{responsive:true,plugins:{legend:{labels:{color:'#94a3b8'}}},scales:{x:{ticks:{color:'#94a3b8'}},y:{ticks:{color:'#94a3b8'}}}}});
</script></body></html>
