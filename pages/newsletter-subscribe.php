<?php
session_start();
header('Content-Type: application/json');

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

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please enter your email address.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Check if already subscribed
$check = $db->prepare("SELECT id FROM newsletter_subscribers WHERE email = :email");
$check->execute([':email' => $email]);
if ($check->fetch()) {
    echo json_encode(['status' => 'success', 'message' => 'You are already subscribed!']);
    exit;
}

// Insert
$stmt = $db->prepare("INSERT INTO newsletter_subscribers (email) VALUES (:email)");
$stmt->execute([':email' => $email]);

echo json_encode(['status' => 'success', 'message' => 'Successfully subscribed!']);
