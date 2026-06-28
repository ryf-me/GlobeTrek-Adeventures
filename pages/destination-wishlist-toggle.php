<?php
/**
 * File: pages/destination-wishlist-toggle.php
 * Purpose: AJAX endpoint to add or remove a destination from the user's wishlist.
 *          Returns JSON response; used by heart/favorite buttons on destination listings.
 *          Operates on destination_id (unlike wishlist-toggle.php which uses package_id).
 * Dependencies: config/database.php, config/csrf.php
 * Used By: JavaScript on destination listing/detail pages
 * Parent Files: None (AJAX-only endpoint, no HTML output)
 * Child Files: None
 * @package GlobeTrek\Pages
 */

require_once __DIR__ . '/../config/session.php';

// All responses are JSON — set header early for consistent output
header('Content-Type: application/json');

// === AUTH CHECK ===
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// === METHOD CHECK ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

// === CSRF VALIDATION ===
require_once __DIR__ . '/../config/csrf.php';
if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token']);
    exit;
}

// === DATABASE ===
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// === VALIDATE INPUT ===
$destinationId = (int)($_POST['destination_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($destinationId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid destination']);
    exit;
}

// === TOGGLE LOGIC (wrapped in try-catch for safe error handling) ===
try {
    // Check if already in wishlist
    $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = :uid AND destination_id = :did LIMIT 1");
    $stmt->execute([':uid' => $userId, ':did' => $destinationId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Remove from wishlist
        $delStmt = $db->prepare("DELETE FROM wishlist WHERE id = :id");
        $delStmt->execute([':id' => $existing['id']]);
        echo json_encode(['status' => 'removed']);
    } else {
        // Add to wishlist
        try {
            $insStmt = $db->prepare("INSERT INTO wishlist (user_id, destination_id) VALUES (:uid, :did)");
            $insStmt->execute([':uid' => $userId, ':did' => $destinationId]);
            echo json_encode(['status' => 'added']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                // 23000 = integrity constraint violation. Check if it's a duplicate (race condition) or FK failure.
                $check = $db->prepare("SELECT id FROM wishlist WHERE user_id = :uid AND destination_id = :did");
                $check->execute([':uid' => $userId, ':did' => $destinationId]);
                if ($check->fetch()) {
                    echo json_encode(['status' => 'added']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid destination.']);
                }
            } else {
                throw $e;
            }
        }
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred. Please try again.']);
}
