<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/currency.php';
$db = getDB();
$userId = $_SESSION['user_id'];
$bookingRef = isset($_GET['ref']) ? trim($_GET['ref']) : '';

if ($bookingRef === '') {
    header('Location: my-bookings.php');
    exit;
}

$stmt = $db->prepare(
    "SELECT b.*, p.title, p.image, p.price, p.description, p.duration_days, p.duration_nights, p.destination_category
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     WHERE b.booking_reference = :ref AND b.user_id = :user_id"
);
$stmt->execute([':ref' => $bookingRef, ':user_id' => $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: my-bookings.php');
    exit;
}

$payStmt = $db->prepare("SELECT * FROM payments WHERE booking_id = :bid ORDER BY created_at DESC LIMIT 1");
$payStmt->execute([':bid' => $booking['id']]);
$payment = $payStmt->fetch();

$guestCount = $booking['num_travellers'];
$basePrice = $booking['price'] * $guestCount;
$taxes = round($basePrice * 0.10);
$serviceFees = round($basePrice * 0.025);
$total = $basePrice + $taxes + $serviceFees;

function bd_status_badge(string $status): array
{
    $map = [
        'pending' => ['pending', 'Pending'],
        'confirmed' => ['confirmed', 'Confirmed'],
        'cancelled' => ['cancelled', 'Cancelled'],
    ];
    return $map[$status] ?? ['pending', ucfirst($status)];
}

[$badgeClass, $badgeLabel] = bd_status_badge($booking['status']);
$activePage = 'bookings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - GlobeTrek</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Winky+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/user-sidebar.css">
    <link rel="stylesheet" href="../css/booking-detail.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="usr-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <div class="usr-layout">
            <?php include '../includes/user-sidebar.php'; ?>

            <div class="usr-canvas">
                <div class="bd-header">
                    <a href="my-bookings.php" class="bd-back">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Back to Bookings
                    </a>
                    <h1>Booking Details</h1>
                    <p>Booking Reference: <strong><?= htmlspecialchars($booking['booking_reference']) ?></strong></p>
                </div>

                <div class="bd-grid">
                    <div class="bd-main">
                        <!-- Package Info -->
                        <div class="bd-card">
                            <div class="bd-card-header">
                                <h2>Package Information</h2>
                                <span class="mb-badge mb-badge-<?= $badgeClass ?>"><?= $badgeLabel ?></span>
                            </div>
                            <div class="bd-package">
                                <?php if ($booking['image']): ?>
                                    <img src="../<?= htmlspecialchars($booking['image']) ?>" alt="<?= htmlspecialchars($booking['title']) ?>" class="bd-package-img">
                                <?php endif; ?>
                                <div class="bd-package-info">
                                    <h3><?= htmlspecialchars($booking['title']) ?></h3>
                                    <p class="bd-meta">
                                        <span class="material-symbols-outlined">schedule</span>
                                        <?= $booking['duration_days'] ?> Days / <?= $booking['duration_nights'] ?> Nights
                                    </p>
                                    <?php if ($booking['destination_category']): ?>
                                        <p class="bd-meta">
                                            <span class="material-symbols-outlined">location_on</span>
                                            <?= htmlspecialchars($booking['destination_category']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Traveller Details -->
                        <div class="bd-card">
                            <h2>Traveller Details</h2>
                            <div class="bd-details-grid">
                                <div class="bd-detail">
                                    <span class="bd-detail-label">Full Name</span>
                                    <span class="bd-detail-value"><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></span>
                                </div>
                                <div class="bd-detail">
                                    <span class="bd-detail-label">Email</span>
                                    <span class="bd-detail-value"><?= htmlspecialchars($booking['email']) ?></span>
                                </div>
                                <div class="bd-detail">
                                    <span class="bd-detail-label">Phone</span>
                                    <span class="bd-detail-value"><?= htmlspecialchars($booking['phone'] ?? '—') ?></span>
                                </div>
                                <div class="bd-detail">
                                    <span class="bd-detail-label">Nationality</span>
                                    <span class="bd-detail-value"><?= htmlspecialchars($booking['nationality'] ?? '—') ?></span>
                                </div>
                                <div class="bd-detail">
                                    <span class="bd-detail-label">Number of Travellers</span>
                                    <span class="bd-detail-value"><?= $guestCount ?></span>
                                </div>
                                <?php if ($booking['special_requests']): ?>
                                    <div class="bd-detail full-width">
                                        <span class="bd-detail-label">Special Requests</span>
                                        <span class="bd-detail-value"><?= htmlspecialchars($booking['special_requests']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Payment Info -->
                        <?php if ($payment): ?>
                        <div class="bd-card">
                            <h2>Payment Information</h2>
                            <div class="bd-details-grid">
                                <div class="bd-detail">
                                    <span class="bd-detail-label">Transaction ID</span>
                                    <span class="bd-detail-value bd-mono"><?= htmlspecialchars($payment['transaction_id']) ?></span>
                                </div>
                                <div class="bd-detail">
                                    <span class="bd-detail-label">Payment Method</span>
                                    <span class="bd-detail-value"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['payment_method']))) ?></span>
                                </div>
                                <?php if ($payment['card_brand']): ?>
                                <div class="bd-detail">
                                    <span class="bd-detail-label">Card</span>
                                    <span class="bd-detail-value"><?= htmlspecialchars($payment['card_brand']) ?> ending in <?= htmlspecialchars($payment['card_last_four']) ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="bd-detail">
                                    <span class="bd-detail-label">Amount Paid</span>
                                    <span class="bd-detail-value bd-mono"><?= formatPrice($payment['amount'], 2) ?></span>
                                </div>
                                <div class="bd-detail">
                                    <span class="bd-detail-label">Payment Date</span>
                                    <span class="bd-detail-value"><?= date('d M Y, h:i A', strtotime($payment['created_at'])) ?></span>
                                </div>
                                <div class="bd-detail">
                                    <span class="bd-detail-label">Status</span>
                                    <span class="bd-detail-value">
                                        <span class="adm-status-badge adm-status-confirmed"><?= ucfirst(htmlspecialchars($payment['status'])) ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="bd-sidebar">
                        <!-- Price Summary -->
                        <div class="bd-card bd-summary-card">
                            <h2>Price Summary</h2>
                            <div class="bd-price-rows">
                                <div class="bd-price-row">
                                    <span>Base Price (<?= formatPrice($booking['price'], 2) ?> x <?= $guestCount ?>)</span>
                                    <span><?= formatPrice($basePrice, 2) ?></span>
                                </div>
                                <div class="bd-price-row">
                                    <span>Taxes (10%)</span>
                                    <span><?= formatPrice($taxes, 2) ?></span>
                                </div>
                                <div class="bd-price-row">
                                    <span>Service Fees</span>
                                    <span><?= formatPrice($serviceFees, 2) ?></span>
                                </div>
                            </div>
                            <div class="bd-price-total">
                                <span>Total</span>
                                <span><?= formatPrice($total, 2) ?></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="bd-card bd-actions-card">
                            <h2>Actions</h2>
                            <?php if ($booking['status'] === 'pending'): ?>
                                <a href="payment.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="settings-btn settings-btn-primary" style="width:100%;text-align:center;display:block;">Complete Payment</a>
                                <a href="cancel-booking.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="settings-btn settings-btn-danger" style="width:100%;text-align:center;display:block;margin-top:0.5rem;" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel Booking</a>
                            <?php elseif ($booking['status'] === 'confirmed'): ?>
                                <a href="payment.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="settings-btn settings-btn-primary" style="width:100%;text-align:center;display:block;">Modify Booking</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
</body>
</html>
