<?php
/**
 * File: pages/newsletter-subscribe.php
 * Purpose: AJAX endpoint for newsletter subscription - handles email subscription via POST request
 * Dependencies: config/csrf.php, config/database.php
 * Used By: Newsletter subscription forms in footer, homepage, and other pages
 * Parent Files: Any page with newsletter subscription form (footer.php, index.php, etc.)
 * Child Files: None
 * @package GlobeTrek\Pages
 */

require_once __DIR__ . '/../config/session.php';
// Set JSON response header for AJAX requests
header('Content-Type: application/json');

// === REQUEST METHOD VALIDATION ===
// Only allow POST requests for subscription
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

// === CSRF TOKEN VALIDATION ===
// Verify CSRF token to prevent cross-site request forgery
require_once __DIR__ . '/../config/csrf.php';
if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token']);
    exit;
}

// === EMAIL INPUT VALIDATION ===
$email = trim($_POST['email'] ?? '');

// Check if email is empty
if ($email === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please enter your email address.']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
    exit;
}

// === DATABASE OPERATIONS ===
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Check if email is already subscribed
$check = $db->prepare("SELECT id FROM newsletter_subscribers WHERE email = :email");
$check->execute([':email' => $email]);
if ($check->fetch()) {
    // Return success even if already subscribed to prevent email enumeration
    echo json_encode(['status' => 'success', 'message' => 'You are already subscribed!']);
    exit;
}

// Insert new subscriber into database
$stmt = $db->prepare("INSERT INTO newsletter_subscribers (email) VALUES (:email)");
$stmt->execute([':email' => $email]);

// Return success response
echo json_encode(['status' => 'success', 'message' => 'Successfully subscribed!']);