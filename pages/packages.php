<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$stmt = $db->query("SELECT * FROM packages WHERE is_active = 1 ORDER BY is_featured DESC, id ASC");
$packages = $stmt->fetchAll();

$destinationsList = [
    'All Destinations',
    'Beaches & Coastal Getaways',
    'Cultural & Historical Sites',
    'Hill Country & Nature',
    'Wildlife & National Parks',
    'Urban & Cultural Capitals'
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
                <h2>Filter</h2>

                <div class="filter-group">
                    <h3>Destination</h3>
                    <?php foreach ($destinationsList as $dest): ?>
                        <label>
                            <input type="checkbox" name="destination[]" value="<?= htmlspecialchars($dest) ?>">
                            <?= htmlspecialchars($dest) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h3>Price Range</h3>
                    <?php foreach ($priceRanges as $range): ?>
                        <label>
                            <input type="checkbox" name="price[]" value="<?= htmlspecialchars($range) ?>">
                            <?= htmlspecialchars($range) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h3>Trip Duration</h3>
                    <?php foreach ($tripDurations as $dur): ?>
                        <label>
                            <input type="checkbox" name="duration[]" value="<?= htmlspecialchars($dur) ?>">
                            <?= htmlspecialchars($dur) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button class="reset-btn" type="reset">Reset</button>
            </aside>

            <!-- Package Cards -->
            <main class="packages-grid">
                <?php foreach ($packages as $pkg): ?>
                    <div class="package-card">
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
</body>
</html>
