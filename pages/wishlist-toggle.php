<?php
/**
 * File: pages/wishlist-toggle.php
 * Purpose: AJAX endpoint to add or remove a package from the user's wishlist.
 *          Returns JSON response; used by heart/favorite buttons on package listings.
 * Dependencies: config/database.php, config/csrf.php
 * Used By: JavaScript on package listing pages, package detail pages
 * Parent Files: None (AJAX-only endpoint, no HTML output)
 * Child Files: None
 * @package GlobeTrek\Pages
 */

require_once __DIR__ . '/../config/session.php';

// All responses are JSON — set header early for consistent output
header('Content-Type: application/json');

// === AUTH CHECK ===
// Must be logged in; return JSON error instead of redirect since this is an AJAX endpoint
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// === METHOD CHECK ===
// Only POST requests are allowed for state-changing operations
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

// === CSRF VALIDATION ===
// Token must be included in the POST body for AJAX requests
require_once __DIR__ . '/../config/csrf.php';
if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token']);
    exit;
}

// === DATABASE ===
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// === VALIDATE INPUT ===
// Cast to int to prevent injection; reject non-positive values
$packageId = (int)($_POST['package_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($packageId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid package']);
    exit;
}

// === TOGGLE LOGIC ===
// Check if the package is already in the user's wishlist
$stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = :uid AND package_id = :pid");
$stmt->execute([':uid' => $userId, ':pid' => $packageId]);
$existing = $stmt->fetch();

if ($existing) {
    // Package exists in wishlist → remove it
    $delStmt = $db->prepare("DELETE FROM wishlist WHERE id = :id");
    $delStmt->execute([':id' => $existing['id']]);
    echo json_encode(['status' => 'removed']);
} else {
    // Package not in wishlist → add it
    $insStmt = $db->prepare("INSERT INTO wishlist (user_id, package_id) VALUES (:uid, :pid)");
    $insStmt->execute([':uid' => $userId, ':pid' => $packageId]);
    echo json_encode(['status' => 'added']);
}
