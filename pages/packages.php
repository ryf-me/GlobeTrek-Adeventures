<?php
// Define tour packages data
$packages = [
    [
        'id'          => 1,
        'title'       => 'Island Escape',
        'duration'    => '5 Days / 4 Nights',
        'price'       => 75999,
        'image'       => 'https://images.unsplash.com/photo-1734279135115-6d8984e08206?q=80&w=800&auto=format&fit=crop',
        'destination' => 'Beaches & Coastal Getaways',
        'duration_days' => 5,
        'price_range' => '50000+'
    ],
    [
        'id'          => 2,
        'title'       => 'Mountain Explorer',
        'duration'    => '6 Days / 5 Nights',
        'price'       => 65999,
        'image'       => 'https://picsum.photos/seed/mountain/400/250',
        'destination' => 'Hill Country & Nature',
        'duration_days' => 6,
        'price_range' => '50000+'
    ],
    [
        'id'          => 3,
        'title'       => 'Beach Paradise',
        'duration'    => '4 Days / 3 Nights',
        'price'       => 39990,
        'image'       => 'https://picsum.photos/seed/beach/400/250',
        'destination' => 'Beaches & Coastal Getaways',
        'duration_days' => 4,
        'price_range' => '30000-49999'
    ],
    [
        'id'          => 4,
        'title'       => 'Cultural Discovery',
        'duration'    => '7 Days / 6 Nights',
        'price'       => 55990,
        'image'       => 'https://picsum.photos/seed/cultural/400/250',
        'destination' => 'Cultural & Historical Sites',
        'duration_days' => 7,
        'price_range' => '50000+'
    ],
    [
        'id'          => 5,
        'title'       => 'City Lights',
        'duration'    => '3 Days / 2 Nights',
        'price'       => 35490,
        'image'       => 'https://picsum.photos/seed/city/400/250',
        'destination' => 'Urban & Cultural Capitals',
        'duration_days' => 3,
        'price_range' => '30000-49999'
    ],
    [
        'id'          => 6,
        'title'       => 'Wild Safari',
        'duration'    => '5 Days / 4 Nights',
        'price'       => 82490,
        'image'       => 'https://picsum.photos/seed/safari/400/250',
        'destination' => 'Wildlife & National Parks',
        'duration_days' => 5,
        'price_range' => '50000+'
    ]
];

// Define filter options (for display in sidebar)
$destinations = [
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

        <div class="packages-layout">
            <!-- Sidebar Filters -->
            <aside class="filters-sidebar">
                <h2>Filter</h2>

                <!-- Destination -->
                <div class="filter-group">
                    <h3>Destination</h3>
                    <?php foreach ($destinations as $dest): ?>
                        <label>
                            <input type="checkbox" name="destination[]" value="<?= htmlspecialchars($dest) ?>">
                            <?= htmlspecialchars($dest) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Price Range -->
                <div class="filter-group">
                    <h3>Price Range</h3>
                    <?php foreach ($priceRanges as $range): ?>
                        <label>
                            <input type="checkbox" name="price[]" value="<?= htmlspecialchars($range) ?>">
                            <?= htmlspecialchars($range) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Trip Duration -->
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
                    <?php $detailsUrl = $pkg['id'] === 1 ? 'package-details.php?id=1' : '#'; ?>
                    <div class="package-card">
                        <img src="<?= htmlspecialchars($pkg['image']) ?>" alt="<?= htmlspecialchars($pkg['title']) ?>">
                        <div class="card-content">
                            <h3><?= htmlspecialchars($pkg['title']) ?></h3>
                            <div class="duration"><?= htmlspecialchars($pkg['duration']) ?></div>
                            <div class="price">
                                From Rs.<?= number_format($pkg['price']) ?>
                            </div>
                            <a href="<?= htmlspecialchars($detailsUrl) ?>" class="view-btn">View Details</a>
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
