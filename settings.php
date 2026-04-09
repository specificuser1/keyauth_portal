<?php
require 'config.php';
requireAuth();

if ($_GET['reset_app'] ?? false) {
    $newKey = generateSecureKey(64);
    $pdo->prepare("UPDATE applications SET api_key = ? WHERE id = ?")->execute([$newKey, $_GET['reset_app']]);
    header("Location: apps.php?msg=api_reset"); exit;
}

$settings = $pdo->query("SELECT * FROM settings WHERE id=1")->fetch();
?>
<form method="POST" class="card">
    <h3>Portal Settings</h3>
    <input name="portal_name" value="<?=htmlspecialchars($settings['portal_name'])?>">
    <input name="default_key_length" type="number" value="<?=$settings['default_key_length']?>">
    <button type="submit" name="save_settings">Save</button>
</form>

<form method="POST" class="card">
    <h3>Reset Admin Password</h3>
    <input name="new_pass" type="password" placeholder="New Password" required>
    <button type="submit" name="reset_pass">Update Password</button>
</form>

<?php
if ($_POST['save_settings'] ?? false) {
    $pdo->prepare("UPDATE settings SET portal_name=?, default_key_length=? WHERE id=1")
        ->execute([$_POST['portal_name'], (int)$_POST['default_key_length']]);
}
if ($_POST['reset_pass'] ?? false) {
    $hash = password_hash($_POST['new_pass'], PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash=? WHERE username='admin'")->execute([$hash]);
    echo "<p style='color:green'>Password updated!</p>";
}
?>
