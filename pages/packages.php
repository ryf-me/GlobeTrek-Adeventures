<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$userId = $_SESSION['user_id'] ?? null;
$userWishlist = [];
if ($userId) {
    $wStmt = $db->prepare("SELECT package_id FROM wishlist WHERE user_id = :uid");
    $wStmt->execute([':uid' => $userId]);
    $userWishlist = array_column($wStmt->fetchAll(), 'package_id');
}

$selectedDestinations = isset($_GET['destination']) ? (array)$_GET['destination'] : [];
$selectedPrices = isset($_GET['price']) ? (array)$_GET['price'] : [];
$selectedDurations = isset($_GET['duration']) ? (array)$_GET['duration'] : [];

$where = "WHERE is_active = 1";
$params = [];

if (!empty($selectedDestinations)) {
    $destPlaceholders = [];
    foreach ($selectedDestinations as $i => $dest) {
        $key = ':dest' . $i;
        $destPlaceholders[] = $key;
        $params[$key] = $dest;
    }
    $where .= " AND destination_category IN (" . implode(',', $destPlaceholders) . ")";
}

if (!empty($selectedPrices)) {
    $pricePlaceholders = [];
    foreach ($selectedPrices as $i => $pr) {
        $key = ':price' . $i;
        $pricePlaceholders[] = $key;
        $params[$key] = $pr;
    }
    $where .= " AND price_range IN (" . implode(',', $pricePlaceholders) . ")";
}

if (!empty($selectedDurations)) {
    $durConditions = [];
    foreach ($selectedDurations as $i => $dur) {
        if ($dur === '1-3 Days') {
            $durConditions[] = "(duration_days >= 1 AND duration_days <= 3)";
        } elseif ($dur === '4-7 Days') {
            $durConditions[] = "(duration_days >= 4 AND duration_days <= 7)";
        } elseif ($dur === '8-14 Days') {
            $durConditions[] = "(duration_days >= 8 AND duration_days <= 14)";
        } elseif ($dur === '15+ Days') {
            $durConditions[] = "(duration_days >= 15)";
        }
    }
    if (!empty($durConditions)) {
        $where .= " AND (" . implode(' OR ', $durConditions) . ")";
    }
}

$stmt = $db->prepare("SELECT * FROM packages $where ORDER BY is_featured DESC, id ASC");
$stmt->execute($params);
$packages = $stmt->fetchAll();

$destinationsList = [
    'Southern & Western Beaches',
    'Cultural Triangle & Temples',
    'Hill Country & Tea Country',
    'National Parks & Wildlife',
    'Colombo & City Experiences'
];

$priceRanges = [
    'Rs.0 - Rs.9,999',
    'Rs.10,000 - Rs.29,999',
    'Rs.30,000 - Rs.49,999',
    'Rs.50,000+'
];

$tripDurations = [
    '1-3 Days',
    '4-7 Days',
    '8-14 Days',
    '15+ Days'
];

$hasFilters = !empty($selectedDestinations) || !empty($selectedPrices) || !empty($selectedDurations);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Packages</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/packages.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="packages-page">
    
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <div class="page-container">
        <h1>Tour Packages</h1>

        <a href="custom-trips.php" class="customize-trip-banner">
            <div class="customize-trip-text">
                <h2>Can't find the perfect trip?</h2>
                <p>Let our travel experts design a bespoke itinerary tailored to your preferences.</p>
            </div>
            <span class="customize-trip-btn">Customize Trip</span>
        </a>

        <div class="packages-layout">
            <!-- Sidebar Filters -->
            <aside class="filters-sidebar">
                <form method="get" action="packages.php">
                    <h2>Filter</h2>

                    <div class="filter-group">
                        <h3>Destination</h3>
                        <?php foreach ($destinationsList as $dest): ?>
                            <label>
                                <input type="checkbox" name="destination[]" value="<?= htmlspecialchars($dest) ?>" <?= in_array($dest, $selectedDestinations) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($dest) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="filter-group">
                        <h3>Price Range</h3>
                        <?php foreach ($priceRanges as $range): ?>
                            <label>
                                <input type="checkbox" name="price[]" value="<?= htmlspecialchars($range) ?>" <?= in_array($range, $selectedPrices) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($range) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="filter-group">
                        <h3>Trip Duration</h3>
                        <?php foreach ($tripDurations as $dur): ?>
                            <label>
                                <input type="checkbox" name="duration[]" value="<?= htmlspecialchars($dur) ?>" <?= in_array($dur, $selectedDurations) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($dur) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="filter-btn">Apply Filters</button>
                    <?php if ($hasFilters): ?>
                        <a href="packages.php" class="reset-btn">Reset</a>
                    <?php else: ?>
                        <button class="reset-btn" type="button" disabled>Reset</button>
                    <?php endif; ?>
                </form>
            </aside>

            <!-- Package Cards -->
            <main class="packages-grid">
                <?php foreach ($packages as $pkg): ?>
                    <div class="package-card">
                        <?php if ($userId): ?>
                            <button class="wishlist-btn <?= in_array($pkg['id'], $userWishlist) ? 'active' : '' ?>" data-package-id="<?= $pkg['id'] ?>" title="<?= in_array($pkg['id'], $userWishlist) ? 'Remove from Wishlist' : 'Add to Wishlist' ?>">
                                <span class="material-symbols-outlined">favorite</span>
                            </button>
                        <?php endif; ?>
                        <img src="<?= htmlspecialchars($pkg['image']) ?>" alt="<?= htmlspecialchars($pkg['title']) ?>">
                        <div class="card-content">
                            <h3><?= htmlspecialchars($pkg['title']) ?></h3>
                            <div class="duration"><?= htmlspecialchars($pkg['duration_days'] . ' Days / ' . $pkg['duration_nights'] . ' Nights') ?></div>
                            <div class="price">
                                From Rs.<?= number_format($pkg['price']) ?>
                            </div>
                            <a href="package-details.php?id=<?= $pkg['id'] ?>" class="view-btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </main>
        </div>
    </div>
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

<script src="../js/script.js"></script>
<script>
document.querySelectorAll('.wishlist-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var packageId = this.getAttribute('data-package-id');
        var btn = this;
        var formData = new FormData();
        formData.append('package_id', packageId);
        formData.append('csrf_token', csrfToken);

        fetch('wishlist-toggle.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'added') {
                btn.classList.add('active');
                btn.title = 'Remove from Wishlist';
            } else if (data.status === 'removed') {
                btn.classList.remove('active');
                btn.title = 'Add to Wishlist';
            } else if (data.status === 'error') {
                alert(data.message || 'Please log in to use the wishlist.');
            }
        })
        .catch(function() {
            alert('An error occurred. Please try again.');
        });
    });
});
</script>
</body>
</html>
