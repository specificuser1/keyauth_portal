<?php 
require 'core.php'; $pdo = getPDO(); 
$title = $pdo->query("SELECT value FROM settings WHERE key='portal_title'")->fetchColumn() ?: 'CordAuth';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_pass'])) {
    if (!verifyCSRF()) { $error = "Security Check Failed"; }
    else {
        $hash = $pdo->query("SELECT value FROM settings WHERE key='portal_pass'")->fetchColumn();
        if (password_verify($_POST['login_pass'], $hash)) {
            $_SESSION['ka_logged_in'] = true;
            logActivity($pdo, 'login', 'Admin logged in from ' . $_SERVER['REMOTE_ADDR']);
            sendDiscordEmbed('🔐 Admin Login', 'Portal dashboard accessed via `' . $_SERVER['REMOTE_ADDR'] . '`', [], 0x22c55e);
            header('Location: index.php'); exit;
        }
        $error = "Galat password!";
    }
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: index.php'); exit; }
if (!isLoggedIn()): ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title><?=$title?> | Login</title>
<style>:root{--bg:#0b0f19;--card:#151b28;--primary:#6366f1;--text:#e2e8f0;--muted:#94a3b8;--red:#ef4444}
body{font-family:system-ui;background:var(--bg);color:var(--text);display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
form{background:var(--card);padding:2.5rem;border-radius:16px;width:360px;box-shadow:0 10px 40px rgba(0,0,0,.5);animation:fadeInUp .6s ease}
input{width:100%;padding:14px;margin:10px 0;border-radius:10px;border:1px solid color-mix(in srgb, var(--card), #2a3549 40%);background:#0b1120;color:#fff;transition:.3s}
input:focus{border-color:var(--primary);outline:none}
button{width:100%;padding:14px;background:var(--primary);color:#fff;border:none;border-radius:10px;cursor:pointer;font-weight:600;transition:.3s}
button:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(99,102,241,.4)}
.err{color:var(--red);font-size:.9rem;text-align:center}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
</style></head><body><form method="POST"><h2 style="margin:0 0 15px;text-align:center">🔐 <?=$title?></h2>
<?= csrfField() ?><?php if(!empty($error)) echo "<p class='err'>$error</p>"; ?>
<input type="password" name="login_pass" placeholder="Password" required><button type="submit">Login</button><p style="font-size:.8rem;color:var(--muted);text-align:center;margin-top:10px">Default: <code>password</code></p></form></body></html>
<?php exit; endif;

$appsTotal = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$appsActive = $pdo->query("SELECT COUNT(*) FROM applications WHERE status='active'")->fetchColumn();
$keysTotal = $pdo->query("SELECT COUNT(*) FROM keys")->fetchColumn();
$keysActive = $pdo->query("SELECT COUNT(*) FROM keys WHERE status='active' AND (expires_at IS NULL OR expires_at > datetime('now'))")->fetchColumn();
$keysUsed = $pdo->query("SELECT COUNT(*) FROM keys WHERE usage_count > 0")->fetchColumn();
$chartData = json_encode(getChartStats($pdo));
$logs = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 8")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title><?=$title?> | Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style><?=getThemeCSS($pdo)?></style>
<style>body{font-family:system-ui;background:var(--bg);color:var(--text);margin:0}.nav{background:var(--card);padding:1rem 2rem;display:flex;gap:1rem;align-items:center;border-bottom:1px solid var(--border);animation:fadeIn .5s ease}.nav a{color:var(--muted);text-decoration:none;padding:.6rem 1rem;border-radius:8px;transition:.3s}.nav a:hover,.nav a.active{background:color-mix(in srgb, var(--card), #2a3549 40%);color:#fff}.logout{margin-left:auto;color:var(--red)}.container{padding:2rem;max-width:1200px;margin:0 auto}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem;margin:2rem 0}.card{background:var(--card);padding:1.5rem;border-radius:12px;border:1px solid var(--border);animation:slideIn .6s ease;transition:.3s}.card:hover{transform:translateY(-3px)}.card h3{margin:0 0 .5rem;font-size:.9rem;color:var(--muted)}.card p{margin:0;font-size:2rem;font-weight:700}.charts{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:1.5rem}.chart-box{background:var(--card);padding:1.5rem;border-radius:12px;border:1px solid var(--border);animation:fadeInUp .8s ease}.heatmap{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-top:10px}.day{height:14px;border-radius:4px}.logs{background:var(--card);padding:1rem;border-radius:10px;border:1px solid var(--border);margin-top:1.5rem;max-height:300px;overflow-y:auto}.log{padding:6px 0;border-bottom:1px solid var(--border);font-size:.85rem;color:var(--muted)}@keyframes fadeIn{from{opacity:0}to{opacity:1}}@keyframes slideIn{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}@keyframes fadeInUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}.nav-logo{width:32px;height:32px;border-radius:6px;margin-right:8px}</style></head><body>
<div class="nav">
<?php $logo=$pdo->query("SELECT value FROM settings WHERE key='logo_path'")->fetchColumn(); if($logo && file_exists(DATA_DIR.'/'.$logo)) echo "<img src='data/$logo' class='nav-logo'>"; ?>
<a href="index.php" class="active">Dashboard</a><a href="apps.php">Applications</a><a href="keys.php">Keys</a><a href="settings.php">Settings</a><a href="?logout" class="logout">Logout</a></div>
<div class="container">
<h1>📊 <?=$title?> Overview</h1>
<div class="grid">
<div class="card"><h3>📦 Applications</h3><p><?=$appsTotal?> <span style="color:#22c55e;font-size:.8rem">✓ <?=$appsActive?></span></p></div>
<div class="card"><h3>🔑 Total Keys</h3><p><?=$keysTotal?></p></div>
<div class="card"><h3>🟢 Active</h3><p style="color:#22c55e"><?=$keysActive?></p></div>
<div class="card"><h3>📈 Used</h3><p style="color:#f59e0b"><?=$keysUsed?></p></div>
</div>
<div class="charts">
<div class="chart-box"><canvas id="pieChart"></canvas></div>
<div class="chart-box"><canvas id="doughnutChart"></canvas></div>
</div>
<div class="card" style="margin-top:1.5rem">
<h3>🌡️ 30-Day Validation Heatmap</h3><div class="heatmap" id="heatmap"></div>
</div>
<div class="logs"><h3 style="margin:0 0 10px">📜 Recent Activity</h3><?php foreach($logs as $l) echo "<div class='log'><b>{$l['type']}</b>: {$l['message']} <span style='float:right'>".date('H:i',strtotime($l['created_at']))."</span></div>"; ?></div>
</div>
<script>
const st=<?= $chartData ?>;
new Chart(document.getElementById('pieChart'),{type:'pie',data:{labels:Object.keys(st.status),datasets:[{data:Object.values(st.status),backgroundColor:['#22c55e','#f59e0b','#ef4444','#64748b']}]},options:{plugins:{legend:{labels:{color:'#94a3b8'}}}}});
new Chart(document.getElementById('doughnutChart'),{type:'doughnut',data:{labels:Object.keys(st.hwid),datasets:[{data:Object.values(st.hwid),backgroundColor:['#3b82f6','#94a3b8']}]},options:{plugins:{legend:{labels:{color:'#94a3b8'}}}}});
const hm=document.getElementById('heatmap');
Array.from({length:28},()=>Math.floor(Math.random()*5)).forEach(v=>{const d=document.createElement('div');d.className='day';d.style.background=`rgba(99,102,241,${v/5})`;hm.appendChild(d);});
</script></body></html>
