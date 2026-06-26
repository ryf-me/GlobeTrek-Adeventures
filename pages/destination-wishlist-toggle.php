<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

// CSRF validation
require_once __DIR__ . '/../config/csrf.php';
if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = getDB();

$destinationId = (int)($_POST['destination_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($destinationId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid destination']);
    exit;
}

$stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = :uid AND destination_id = :did");
$stmt->execute([':uid' => $userId, ':did' => $destinationId]);
$existing = $stmt->fetch();

if ($existing) {
    $delStmt = $db->prepare("DELETE FROM wishlist WHERE id = :id");
    $delStmt->execute([':id' => $existing['id']]);
    echo json_encode(['status' => 'removed']);
} else {
    $insStmt = $db->prepare("INSERT INTO wishlist (user_id, destination_id) VALUES (:uid, :did)");
    $insStmt->execute([':uid' => $userId, ':did' => $destinationId]);
    echo json_encode(['status' => 'added']);
}
