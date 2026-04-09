<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);

$data = json_decode(file_get_contents('php://input'), true);
$apiKey = $data['api_key'] ?? '';
$keyCode = $data['key'] ?? '';
$hwid = $data['hwid'] ?? null;

if (!$apiKey || !$keyCode) jsonResponse(['error' => 'Missing api_key or key'], 400);

// Verify App
$appStmt = $pdo->prepare("SELECT id, status FROM applications WHERE api_key = ?");
$appStmt->execute([$apiKey]);
$app = $appStmt->fetch();
if (!$app || $app['status'] !== 'active') jsonResponse(['error' => 'Invalid/Disabled API Key'], 403);

// Verify Key
$keyStmt = $pdo->prepare("SELECT * FROM keys WHERE key_code = ? AND app_id = ?");
$keyStmt->execute([$keyCode, $app['id']]);
$key = $keyStmt->fetch();

if (!$key) jsonResponse(['error' => 'Key not found'], 404);
if ($key['status'] !== 'active') jsonResponse(['error' => "Key is {$key['status']}"], 403);
if ($key['expiry'] && new DateTime() > new DateTime($key['expiry'])) {
    $pdo->prepare("UPDATE keys SET status='expired' WHERE id=?")->execute([$key['id']]);
    jsonResponse(['error' => 'Key expired'], 403);
}
if ($key['hwid_lock']) {
    if (!$hwid) jsonResponse(['error' => 'HWID required'], 400);
    if (!$key['hwid']) {
        $pdo->prepare("UPDATE keys SET hwid = ? WHERE id = ?")->execute([$hwid, $key['id']]);
    } elseif ($key['hwid'] !== $hwid) {
        jsonResponse(['error' => 'HWID mismatch'], 403);
    }
}
if ($key['uses_left'] !== -1 && $key['uses_left'] <= 0) {
    $pdo->prepare("UPDATE keys SET status='used' WHERE id=?")->execute([$key['id']]);
    jsonResponse(['error' => 'Max uses reached'], 403);
}

// Update Usage
$pdo->prepare("UPDATE keys SET last_used = NOW(), uses_left = CASE WHEN uses_left != -1 THEN uses_left - 1 ELSE -1 END WHERE id = ?")
    ->execute([$key['id']]);

jsonResponse(['success' => true, 'message' => 'Authenticated', 'expires' => $key['expiry'] ?? null]);
?>
