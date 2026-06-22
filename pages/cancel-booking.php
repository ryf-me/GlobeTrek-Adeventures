<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/logger.php';
$db = getDB();
$userId = $_SESSION['user_id'];
$bookingRef = isset($_GET['ref']) ? trim($_GET['ref']) : '';

if ($bookingRef === '') {
    header('Location: my-bookings.php');
    exit;
}

$stmt = $db->prepare(
    "SELECT id, status FROM bookings WHERE booking_reference = :ref AND user_id = :user_id"
);
$stmt->execute([':ref' => $bookingRef, ':user_id' => $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: my-bookings.php');
    exit;
}

if ($booking['status'] !== 'pending') {
    header('Location: my-bookings.php?error=only_pending');
    exit;
}

$updateStmt = $db->prepare(
    "UPDATE bookings SET status = 'cancelled' WHERE id = :id AND user_id = :user_id"
);
$updateStmt->execute([':id' => $booking['id'], ':user_id' => $userId]);

logActivity('booking_cancelled', 'booking', $booking['id'], 'Booking ' . $bookingRef . ' cancelled by user');

header('Location: my-bookings.php?cancelled=1');
exit;
