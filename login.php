<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->execute([$_POST['username'] ?? '']);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.php');
        exit;
    }
    $error = "Invalid credentials!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Login</title></head>
<body style="font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;background:#f5f5f5">
<form method="POST" style="background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1)">
    <h2>Portal Login</h2>
    <?php if(!empty($error)) echo "<p style='color:red'>$error</p>"; ?>
    <input name="username" placeholder="Username" required style="width:100%;margin-bottom:10px;padding:8px"><br>
    <input name="password" type="password" placeholder="Password" required style="width:100%;margin-bottom:10px;padding:8px"><br>
    <button type="submit" style="width:100%;padding:10px;background:#007bff;color:#fff;border:none;cursor:pointer">Login</button>
</form>
</body>
</html>
