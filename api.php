<?php
require 'core.php';
header('Content-Type: application/json');
$pdo = getPDO();

$input = $_GET ?: $_POST;
$appSecret = $input['secret'] ?? '';
$keyString = $input['key'] ?? '';
$hwid = $input['hwid'] ?? '';
$clientIp = $input['ip'] ?? $_SERVER['REMOTE_ADDR'];

$stmt = $pdo->prepare("SELECT k.*, a.secret_key, a.expires_at as app_expires FROM keys k JOIN applications a ON k.app_id = a.id WHERE k.key_string = ?");
$stmt->execute([$keyString]);
$key = $stmt->fetch();

if (!$key || $key['secret_key'] !== $appSecret) {
    echo json_encode(['success' => false, 'message' => 'Invalid key or app secret']); exit;
}
if ($key['app_expires'] && strtotime($key['app_expires']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Application expired']); exit;
}
if ($key['status'] === 'blocked' || $key['status'] === 'paused') {
    echo json_encode(['success' => false, 'message' => "Key is {$key['status']}"]); exit;
}
if ($key['expires_at'] && strtotime($key['expires_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Key expired']); exit;
}
if ($key['max_uses'] > 0 && $key['usage_count'] >= $key['max_uses']) {
    echo json_encode(['success' => false, 'message' => 'Max usage limit reached']); exit;
}
if ($key['cooldown'] > 0 && $key['last_used']) {
    $diff = time() - strtotime($key['last_used']);
    if ($diff < $key['cooldown']) {
        echo json_encode(['success' => false, 'message' => "Cooldown active. Wait " . ($key['cooldown'] - $diff) . "s"]); exit;
    }
}

// HWID & IP Validation/Binding
if ($key['hwid_locked']) {
    if ($key['hwid_value']) {
        if ($key['hwid_value'] !== $hwid) { echo json_encode(['success' => false, 'message' => 'HWID mismatch']); exit; }
    } else {
        $pdo->prepare("UPDATE keys SET hwid_value=? WHERE id=?")->execute([$hwid, $key['id']]);
    }
}
if ($key['ip_locked']) {
    if ($key['ip_value']) {
        if ($key['ip_value'] !== $clientIp) { echo json_encode(['success' => false, 'message' => 'IP mismatch']); exit; }
    } else {
        $pdo->prepare("UPDATE keys SET ip_value=? WHERE id=?")->execute([$clientIp, $key['id']]);
    }
}

// Update stats
$pdo->prepare("UPDATE keys SET usage_count = usage_count + 1, last_used = CURRENT_TIMESTAMP WHERE id=?")->execute([$key['id']]);

echo json_encode([
    'success' => true,
    'message' => 'Authenticated',
    'label' => $key['label'],
    'note' => $key['note'],
    'remaining_uses' => $key['max_uses'] > 0 ? $key['max_uses'] - ($key['usage_count'] + 1) : '∞',
    'hwid_locked' => (bool)$key['hwid_locked'],
    'ip_locked' => (bool)$key['ip_locked']
]);
?>
