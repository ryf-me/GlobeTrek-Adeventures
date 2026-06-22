<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = getDB();
$userId = $_SESSION['user_id'];

// Fetch user's bookings with package details
$stmt = $db->prepare(
    "SELECT b.*, p.title, p.image, p.price, p.duration_days, p.duration_nights
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     WHERE b.user_id = :user_id
     ORDER BY b.created_at DESC"
);
$stmt->execute([':user_id' => $userId]);
$allBookings = $stmt->fetchAll();

// Split into upcoming and past
$upcoming = [];
$past = [];
$today = date('Y-m-d');

foreach ($allBookings as $booking) {
    $isPast = false;

    if ($booking['status'] === 'cancelled') {
        $isPast = true;
    } elseif ($booking['travel_date'] && $booking['duration_days']) {
        $endDate = date('Y-m-d', strtotime($booking['travel_date'] . ' + ' . $booking['duration_days'] . ' days'));
        if ($endDate < $today) {
            $isPast = true;
        }
    } elseif ($booking['travel_date'] === null && $booking['status'] === 'confirmed') {
        // No travel date set yet — treat as upcoming
        $isPast = false;
    }

    if ($isPast) {
        $past[] = $booking;
    } else {
        $upcoming[] = $booking;
    }
}

// Tab filter
$tab = $_GET['tab'] ?? 'upcoming';
if (!in_array($tab, ['upcoming', 'past'])) {
    $tab = 'upcoming';
}
$activeBookings = $tab === 'upcoming' ? $upcoming : $past;

$activePage = 'bookings';

function mb_status_badge(string $status, ?string $travelDate, ?int $durationDays): array
{
    $today = date('Y-m-d');

    if ($status === 'cancelled') {
        return ['cancelled', 'Cancelled'];
    }

    if ($status === 'pending') {
        return ['pending', 'Pending'];
    }

    if ($status === 'confirmed') {
        if ($travelDate && $durationDays) {
            $endDate = date('Y-m-d', strtotime($travelDate . ' + ' . $durationDays . ' days'));
            if ($endDate < $today) {
                return ['completed', 'Completed'];
            }
        }
        return ['confirmed', 'Confirmed'];
    }

    return ['pending', ucfirst($status)];
}

function mb_format_date_range(?string $travelDate, ?int $days): string
{
    if (!$travelDate) {
        return 'Dates to be confirmed';
    }
    $start = date('d M Y', strtotime($travelDate));
    if ($days) {
        $end = date('d M Y', strtotime($travelDate . ' + ' . $days . ' days'));
        return $start . ' - ' . $end;
    }
    return $start;
}

function mb_format_price(float $price): string
{
    return 'Rs.' . number_format($price, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - GlobeTrek</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Winky+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/user-sidebar.css">
    <link rel="stylesheet" href="../css/my-bookings.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="usr-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <div class="usr-layout">
            <?php $activePage = 'bookings'; include '../includes/user-sidebar.php'; ?>

            <div class="usr-canvas">
                <?php if (isset($_GET['cancelled'])): ?>
                    <div class="mb-alert mb-alert-success">
                        <span class="material-symbols-outlined">check_circle</span>
                        Booking has been cancelled successfully.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] === 'only_pending'): ?>
                    <div class="mb-alert mb-alert-error">
                        <span class="material-symbols-outlined">error</span>
                        Only pending bookings can be cancelled.
                    </div>
                <?php endif; ?>

                <div class="usr-page-header">
                    <h1>My Bookings</h1>
                    <p>View and manage your current and past travel arrangements.</p>
                </div>

                <!-- Tabs -->
                <div class="mb-tabs">
                    <a href="?tab=upcoming" class="mb-tab-btn <?= $tab === 'upcoming' ? 'active' : '' ?>">
                        Upcoming Trips
                        <span class="mb-tab-count"><?= count($upcoming) ?></span>
                    </a>
                    <a href="?tab=past" class="mb-tab-btn <?= $tab === 'past' ? 'active' : '' ?>">
                        Past Trips
                        <span class="mb-tab-count"><?= count($past) ?></span>
                    </a>
                </div>

                <!-- Bookings List -->
                <?php if (empty($activeBookings)): ?>
                    <div class="mb-empty">
                        <span class="material-symbols-outlined">flight_takeoff</span>
                        <h2>No <?= $tab === 'upcoming' ? 'upcoming' : 'past' ?> trips</h2>
                        <p>
                            <?= $tab === 'upcoming'
                                ? 'You don\'t have any upcoming trips booked. Explore our packages to plan your next adventure.'
                                : 'You haven\'t completed any trips yet. Your travel history will appear here.'
                            ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="mb-list">
                        <?php foreach ($activeBookings as $booking): ?>
                            <?php
                                [$badgeClass, $badgeLabel] = mb_status_badge(
                                    $booking['status'],
                                    $booking['travel_date'],
                                    $booking['duration_days']
                                );
                                $isMuted = ($badgeClass === 'completed' || $badgeClass === 'cancelled');
                                $dateRange = mb_format_date_range($booking['travel_date'], $booking['duration_days']);
                                $imgPath = $booking['image'] ? '../' . htmlspecialchars($booking['image']) : '';
                            ?>
                            <div class="mb-card <?= $isMuted ? 'muted' : '' ?>">
                                <!-- Image -->
                                <div class="mb-card-img">
                                    <?php if ($imgPath): ?>
                                        <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($booking['title']) ?>">
                                    <?php else: ?>
                                        <div class="mb-placeholder">
                                            <span class="material-symbols-outlined mb-img-icon">image</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Body -->
                                <div class="mb-card-body">
                                    <div>
                                        <div class="mb-card-top">
                                            <div>
                                                <div class="mb-card-ref">Booking ID: <?= htmlspecialchars($booking['booking_reference']) ?></div>
                                                <h3 class="mb-card-title <?= $isMuted ? 'muted' : '' ?>"><?= htmlspecialchars($booking['title']) ?></h3>
                                            </div>
                                            <span class="mb-badge mb-badge-<?= $badgeClass ?>"><?= $badgeLabel ?></span>
                                        </div>

                                        <div class="mb-card-meta">
                                            <div class="mb-meta-item">
                                                <span class="material-symbols-outlined">calendar_today</span>
                                                <?= $dateRange ?>
                                            </div>
                                            <div class="mb-meta-item">
                                                <span class="material-symbols-outlined">payments</span>
                                                <strong><?= mb_format_price($booking['total_price']) ?></strong>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="mb-card-actions">
                                        <?php if ($badgeClass === 'confirmed'): ?>
                                            <a href="booking-detail.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="mb-btn mb-btn-primary">View Details</a>
                                            <a href="payment.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="mb-btn mb-btn-outline">Modify Booking</a>
                                            <button class="mb-btn-text" onclick="alert('Invoice download coming soon')">
                                                <span class="material-symbols-outlined">download</span>
                                                Invoice
                                            </button>
                                        <?php elseif ($badgeClass === 'pending'): ?>
                                            <a href="booking-detail.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="mb-btn mb-btn-primary">View Details</a>
                                            <a href="payment.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="mb-btn mb-btn-outline">Complete Payment</a>
                                            <button class="mb-btn-text danger" onclick="if(confirm('Are you sure you want to cancel this booking?')) window.location.href='cancel-booking.php?ref=<?= urlencode($booking['booking_reference']) ?>'">
                                                <span class="material-symbols-outlined">cancel</span>
                                                Cancel
                                            </button>
                                        <?php elseif ($badgeClass === 'completed'): ?>
                                            <a href="booking-detail.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="mb-btn mb-btn-outline">Review Experience</a>
                                            <a href="packages.php?rebook=<?= urlencode($booking['package_id']) ?>" class="mb-btn mb-btn-outline">Rebook Package</a>
                                        <?php elseif ($badgeClass === 'cancelled'): ?>
                                            <a href="booking-detail.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="mb-btn mb-btn-outline">View Details</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
</body>
</html>
