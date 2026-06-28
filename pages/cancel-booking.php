<?php
/**
 * File: pages/cancel-booking.php
 * Purpose: Handles booking cancellation. Verifies the booking belongs to the logged-in user, checks it is in 'pending' status, updates it to 'cancelled', logs the activity, and redirects with success/error feedback.
 * Dependencies: config/database.php, config/logger.php
 * Used By: booking-detail.php (linked from "Cancel Booking" button)
 * Parent Files: booking-detail.php
 * Child Files: None
 * @package GlobeTrek\Pages
 */
session_start();

// === AUTH CHECK ===
// Only logged-in users can cancel bookings.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/logger.php';
$db = getDB();
$userId = $_SESSION['user_id'];

// === BOOKING REFERENCE VALIDATION ===
// Require a non-empty booking reference.
$bookingRef = isset($_GET['ref']) ? trim($_GET['ref']) : '';

if ($bookingRef === '') {
    header('Location: my-bookings.php');
    exit;
}

// === FETCH BOOKING ===
// Verify the booking exists and belongs to the current user.
$stmt = $db->prepare(
    "SELECT id, status FROM bookings WHERE booking_reference = :ref AND user_id = :user_id"
);
$stmt->execute([':ref' => $bookingRef, ':user_id' => $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: my-bookings.php');
    exit;
}

// === STATUS CHECK ===
// Only pending bookings can be cancelled; confirmed/cancelled ones are rejected.
if ($booking['status'] !== 'pending') {
    header('Location: my-bookings.php?error=only_pending');
    exit;
}

// === UPDATE BOOKING STATUS ===
// Set status to 'cancelled' with user_id check for additional security.
$updateStmt = $db->prepare(
    "UPDATE bookings SET status = 'cancelled' WHERE id = :id AND user_id = :user_id"
);
$updateStmt->execute([':id' => $booking['id'], ':user_id' => $userId]);

// === LOG ACTIVITY ===
// Record the cancellation for audit trail.
logActivity('booking_cancelled', 'booking', $booking['id'], 'Booking ' . $bookingRef . ' cancelled by user');

// === REDIRECT WITH SUCCESS ===
header('Location: my-bookings.php?cancelled=1');
exit;
