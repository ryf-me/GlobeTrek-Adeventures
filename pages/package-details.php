<?php
$package = [
    'id' => 1,
    'title' => 'Island Escape',
    'duration' => '5 Days / 4 Nights',
    'location' => 'Sri Lanka',
    'rating' => '4.8',
    'reviews' => '124 Reviews',
    'price' => 75999,
    'categories' => ['Adventure', 'Beach', 'Nature'],
    'hero_image' => 'https://images.unsplash.com/photo-1734279135115-6d8984e08206?q=80&w=1600&auto=format&fit=crop',
    'gallery_image' => 'https://images.unsplash.com/photo-1519566335946-e6f65f0f4fdf?q=80&w=1100&auto=format&fit=crop',
    'description' => "Immerse yourself in the breathtaking beauty of Sri Lanka with our exclusive Island Escape package. Designed for thrill-seekers and nature lovers alike, this 5-day journey takes you from pristine beaches to lush tropical jungles. Experience local culture, wildlife encounters, and unparalleled relaxation in carefully selected accommodations.",
    'overview' => [
        'Day 1: Arrival and coastal welcome',
        'Day 2: Jungle trek and waterfall discovery',
        'Day 3: Cultural heritage and temple visit',
        'Day 4: Free time and sunset cruise',
        'Day 5: Departure',
    ],
    'quick_info' => [
        'Trip Type' => 'Guided Group',
        'Max People' => '12',
        'Difficulty' => 'Moderate',
        'Best Season' => 'Nov - Apr',
    ],
];
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
                <img src="<?= htmlspecialchars($package['hero_image']) ?>" alt="Tropical Sri Lankan coastline for the Island Escape package">
                <div class="hero-stamp">
                    <span>Coastal route</span>
                    <strong>5 curated days</strong>
                </div>
            </div>

            <aside class="booking-panel" aria-label="Booking summary">
                <p class="eyebrow">Signature escape</p>
                <h1><?= htmlspecialchars($package['title']) ?></h1>

                <div class="rating-row" aria-label="<?= htmlspecialchars($package['rating']) ?> out of 5 stars from <?= htmlspecialchars($package['reviews']) ?>">
                    <span class="stars" aria-hidden="true">★★★★★</span>
                    <span><?= htmlspecialchars($package['rating']) ?> (<?= htmlspecialchars($package['reviews']) ?>)</span>
                </div>

                <div class="meta-list">
                    <div>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7v5l3 2"></path>
                        </svg>
                        <span><?= htmlspecialchars($package['duration']) ?></span>
                    </div>
                    <div>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span><?= htmlspecialchars($package['location']) ?></span>
                    </div>
                </div>

                <div class="category-group" aria-label="Categories">
                    <span class="section-label">Categories</span>
                    <div>
                        <?php foreach ($package['categories'] as $category): ?>
                            <span><?= htmlspecialchars($category) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="price-box">
                    <span class="section-label">Starting From</span>
                    <p>Rs.<?= number_format($package['price']) ?><small>/ per person</small></p>
                    <a class="primary-action" href="booking.php">
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
                            <h3>Five days across coast, culture, and green country</h3>
                        </div>
                    </div>

                    <ol class="day-list">
                        <?php foreach ($package['overview'] as $day): ?>
                            <li><?= htmlspecialchars($day) ?></li>
                        <?php endforeach; ?>
                    </ol>

                    <div class="route-visual" aria-label="Route visualization from coast to jungle and heritage sites">
                        <img src="<?= htmlspecialchars($package['gallery_image']) ?>" alt="Sri Lankan beach and ocean route highlight">
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
                    <?php foreach ($package['quick_info'] as $label => $value): ?>
                        <div>
                            <span><?= htmlspecialchars($label) ?></span>
                            <strong><?= htmlspecialchars($value) ?></strong>
                        </div>
                    <?php endforeach; ?>
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
