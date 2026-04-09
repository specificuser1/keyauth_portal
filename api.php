<?php
require 'core.php';
header('Content-Type: application/json');

$pdo = getPDO();
$input = $_GET ?: $_POST;
$appSecret = $input['secret'] ?? '';
$keyString = $input['key'] ?? '';
$hwid = $input['hwid'] ?? '';

$stmt = $pdo->prepare("SELECT k.*, a.secret_key FROM keys k JOIN applications a ON k.app_id = a.id WHERE k.key_string = ?");
$stmt->execute([$keyString]);
$key = $stmt->fetch();

if (!$key || $key['secret_key'] !== $appSecret) {
    echo json_encode(['success' => false, 'message' => 'Invalid key or app secret']); exit;
}

if ($key['status'] === 'blocked' || $key['status'] === 'paused') {
    echo json_encode(['success' => false, 'message' => 'Key is ' . $key['status']]); exit;
}

if ($key['expires_at'] && strtotime($key['expires_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Key expired']); exit;
}

if ($key['hwid_locked']) {
    if ($key['hwid_value']) {
        if ($key['hwid_value'] !== $hwid) {
            echo json_encode(['success' => false, 'message' => 'HWID mismatch']); exit;
        }
    } else {
        $pdo->prepare("UPDATE keys SET hwid_value = ?, status = 'used', last_used = CURRENT_TIMESTAMP WHERE id = ?")->execute([$hwid, $key['id']]);
    }
} else {
    $pdo->prepare("UPDATE keys SET last_used = CURRENT_TIMESTAMP WHERE id = ?")->execute([$key['id']]);
}

echo json_encode(['success' => true, 'message' => 'Authenticated', 'label' => $key['label']]);
?>
