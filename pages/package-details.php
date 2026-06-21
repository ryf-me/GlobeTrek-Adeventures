<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$packageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $db->prepare("SELECT * FROM packages WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $packageId]);
$package = $stmt->fetch();

if (!$package) {
    echo '<!DOCTYPE html><html><head><title>Not Found</title></head><body><h1>Package not found.</h1><a href="packages.php">Back to Packages</a></body></html>';
    exit;
}

$overview = [
    'Day 1: Arrival and coastal welcome',
    'Day 2: Jungle trek and waterfall discovery',
    'Day 3: Cultural heritage and temple visit',
    'Day 4: Free time and sunset cruise',
    'Day 5: Departure',
];
if ($package['duration_days'] > 5) {
    for ($i = 6; $i <= $package['duration_days']; $i++) {
        $overview[] = "Day $i: Explore and discover";
    }
    $overview[] = "Day " . ($package['duration_days']) . ": Departure";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($package['title']) ?> - GlobeTrek</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/package-details.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="package-details-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main class="details-shell">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="../index.php#home">Home</a>
            <span aria-hidden="true">/</span>
            <a href="packages.php">Tour Packages</a>
            <span aria-hidden="true">/</span>
            <span><?= htmlspecialchars($package['title']) ?></span>
        </nav>

        <section class="package-hero" aria-label="<?= htmlspecialchars($package['title']) ?> package summary">
            <div class="hero-media">
                <img src="<?= htmlspecialchars($package['image']) ?>" alt="<?= htmlspecialchars($package['title']) ?> package image">
                <div class="hero-stamp">
                    <span><?= htmlspecialchars($package['destination_category']) ?></span>
                    <strong><?= htmlspecialchars($package['duration_days'] . ' curated days') ?></strong>
                </div>
            </div>

            <aside class="booking-panel" aria-label="Booking summary">
                <p class="eyebrow">Signature escape</p>
                <h1><?= htmlspecialchars($package['title']) ?></h1>

                <div class="meta-list">
                    <div>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7v5l3 2"></path>
                        </svg>
                        <span><?= htmlspecialchars($package['duration_days'] . ' Days / ' . $package['duration_nights'] . ' Nights') ?></span>
                    </div>
                    <div>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span>Sri Lanka</span>
                    </div>
                </div>

                <div class="category-group" aria-label="Categories">
                    <span class="section-label">Categories</span>
                    <div>
                        <span><?= htmlspecialchars($package['destination_category']) ?></span>
                        <span><?= htmlspecialchars($package['difficulty_level']) ?></span>
                    </div>
                </div>

                <div class="price-box">
                    <span class="section-label">Starting From</span>
                    <p>Rs.<?= number_format($package['price']) ?><small>/ per person</small></p>
                    <a class="primary-action" href="booking.php?id=<?= $package['id'] ?>">
                        Book Now
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 12h14"></path>
                            <path d="M13 6l6 6-6 6"></path>
                        </svg>
                    </a>
                    <a class="secondary-action" href="contact.php">Customize Trip</a>
                </div>
            </aside>
        </section>

        <div class="details-grid">
            <section class="tour-content" aria-label="Tour details">
                <div class="tour-intro">
                    <p class="eyebrow">About the Tour</p>
                    <h2>Salt air, jungle shade, and a soft landing every night.</h2>
                    <p><?= htmlspecialchars($package['description']) ?></p>
                </div>

                <div class="tabs" role="tablist" aria-label="Package sections">
                    <a class="active" href="#overview">Overview</a>
                    <a href="#itinerary">Itinerary</a>
                    <a href="#inclusions">Inclusions</a>
                    <a href="#exclusions">Exclusions</a>
                    <a href="#stays">Accommodations</a>
                    <a href="#reviews">Reviews</a>
                </div>

                <article id="overview" class="journey-card">
                    <div class="journey-heading">
                        <span class="map-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3z"></path>
                                <path d="M9 3v15"></path>
                                <path d="M15 6v15"></path>
                            </svg>
                        </span>
                        <div>
                            <p class="eyebrow">Journey Overview</p>
                            <h3> <?= htmlspecialchars($package['duration_days'] . ' days across coast, culture, and green country') ?></h3>
                        </div>
                    </div>

                    <ol class="day-list">
                        <?php foreach ($overview as $day): ?>
                            <li><?= htmlspecialchars($day) ?></li>
                        <?php endforeach; ?>
                    </ol>

                    <div class="route-visual" aria-label="Route visualization">
                        <img src="<?= htmlspecialchars($package['image']) ?>" alt="<?= htmlspecialchars($package['title']) ?> route highlight">
                        <div class="route-line" aria-hidden="true">
                            <span>Negombo</span>
                            <span>Rainforest</span>
                            <span>Galle Coast</span>
                        </div>
                    </div>
                </article>
            </section>

            <aside class="side-stack" aria-label="Package quick information">
                <section class="quick-info">
                    <h2>Quick Info</h2>
                    <div>
                        <span>Trip Type</span>
                        <strong>Guided Group</strong>
                    </div>
                    <div>
                        <span>Max People</span>
                        <strong><?= htmlspecialchars($package['max_group_size']) ?></strong>
                    </div>
                    <div>
                        <span>Difficulty</span>
                        <strong><?= htmlspecialchars($package['difficulty_level']) ?></strong>
                    </div>
                    <div>
                        <span>Price Range</span>
                        <strong><?= htmlspecialchars($package['price_range']) ?></strong>
                    </div>
                </section>

                <section class="support-card">
                    <svg viewBox="0 0 48 48" aria-hidden="true">
                        <path d="M10 29v-5a14 14 0 0 1 28 0v5"></path>
                        <path d="M10 28h6v10h-3a3 3 0 0 1-3-3v-7z"></path>
                        <path d="M32 28h6v7a3 3 0 0 1-3 3h-3V28z"></path>
                        <path d="M20 39h5a8 8 0 0 0 8-8"></path>
                        <path d="M19 22h.1M29 22h.1M20 29c2.5 2 5.5 2 8 0"></path>
                    </svg>
                    <h2>Need help booking?</h2>
                    <p>Our travel experts are available 24/7 for dates, upgrades, and route questions.</p>
                    <a href="contact.php">Contact Support</a>
                </section>
            </aside>
        </div>
    </main>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
</body>
</html>
