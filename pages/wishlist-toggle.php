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

require_once __DIR__ . '/../config/database.php';
$db = getDB();

$packageId = (int)($_POST['package_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($packageId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid package']);
    exit;
}

$stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = :uid AND package_id = :pid");
$stmt->execute([':uid' => $userId, ':pid' => $packageId]);
$existing = $stmt->fetch();

if ($existing) {
    $delStmt = $db->prepare("DELETE FROM wishlist WHERE id = :id");
    $delStmt->execute([':id' => $existing['id']]);
    echo json_encode(['status' => 'removed']);
} else {
    $insStmt = $db->prepare("INSERT INTO wishlist (user_id, package_id) VALUES (:uid, :pid)");
    $insStmt->execute([':uid' => $userId, ':pid' => $packageId]);
    echo json_encode(['status' => 'added']);
}
