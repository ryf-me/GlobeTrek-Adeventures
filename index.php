<!DOCTYPE html>
<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

$featuredPackages = $db->query("SELECT * FROM packages WHERE is_active = 1 AND is_featured = 1 ORDER BY id ASC LIMIT 4")->fetchAll();

$featuredDestinations = $db->query("SELECT * FROM destinations WHERE is_active = 1 AND is_featured = 1 ORDER BY id ASC LIMIT 4")->fetchAll();

$featuredGuides = $db->query("SELECT * FROM guides WHERE is_active = 1 AND is_featured = 1 ORDER BY id ASC LIMIT 3")->fetchAll();

$featuredTestimonials = $db->query("SELECT * FROM testimonials WHERE is_approved = 1 AND is_featured = 1 ORDER BY id ASC LIMIT 3")->fetchAll();

$stats = [
    'travelers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'packages' => $db->query("SELECT COUNT(*) FROM packages WHERE is_active = 1")->fetchColumn(),
    'destinations' => $db->query("SELECT COUNT(*) FROM destinations WHERE is_active = 1")->fetchColumn(),
    'guides' => $db->query("SELECT COUNT(*) FROM guides WHERE is_active = 1")->fetchColumn(),
];
?>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Globe Trek Adventures</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/navbar.css" />
    <link rel="stylesheet" href="css/footer.css" />
</head>
<body class="home-page">
    <?php $basePath = ''; include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <video class="hero-video" autoplay muted loop playsinline>
            <source src="videos/hero-video.mp4" type="video/mp4"/>
        </video>
        <div class="hero-content">
            <h1>Discover Your Next Adventure</h1>
            <p>From pristine beaches to bustling cityscapes – we bring the world to you.</p>
            <button class="cta" onclick="scrollToSection('packages')">Explore Packages</button>
        </div>
    </section>

    <!-- Destinations Section -->
    <section id="destinations" class="destinations">
        <div class="section-heading">
            <h2>Explore Sri Lanka</h2>
            <a class="view-all-btn" href="pages/destinations.php">View All</a>
        </div>
        <div class="cards">
            <?php foreach ($featuredDestinations as $dest): ?>
                <div class="card destination-card">
                    <img src="<?= htmlspecialchars($dest['image']) ?>" alt="<?= htmlspecialchars($dest['name']) ?>" />
                    <div class="card-content">
                        <h3><?= htmlspecialchars($dest['name']) ?></h3>
                        <p class="destination-desc"><?= htmlspecialchars(mb_strimwidth($dest['description'] ?? '', 0, 80, '...')) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Packages Section -->
    <section id="packages" class="packages">
        <div class="section-heading">
            <h2>Popular Tour Packages</h2>
            <a class="view-all-btn" href="pages/packages.php">View All</a>
        </div>
        <div class="cards">
            <?php foreach ($featuredPackages as $pkg): ?>
                <div class="card">
                    <img src="<?= htmlspecialchars($pkg['image']) ?>" alt="<?= htmlspecialchars($pkg['title']) ?>" />
                    <div class="card-content">
                        <h3><?= htmlspecialchars($pkg['title']) ?></h3>
                        <p class="duration"><?= htmlspecialchars($pkg['duration_days'] . ' Days / ' . $pkg['duration_nights'] . ' Nights') ?></p>
                        <div class="package-actions">
                            <p class="price">From Rs.<?= number_format($pkg['price']) ?></p>
                            <a href="pages/package-details.php?id=<?= $pkg['id'] ?>" class="details-btn">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number" data-target="<?= $stats['travelers'] ?>">0</span>
                <span class="stat-label">Happy Travelers</span>
            </div>
            <div class="stat-item">
                <span class="stat-number" data-target="<?= $stats['packages'] ?>">0</span>
                <span class="stat-label">Tour Packages</span>
            </div>
            <div class="stat-item">
                <span class="stat-number" data-target="<?= $stats['destinations'] ?>">0</span>
                <span class="stat-label">Destinations</span>
            </div>
            <div class="stat-item">
                <span class="stat-number" data-target="<?= $stats['guides'] ?>">0</span>
                <span class="stat-label">Expert Guides</span>
            </div>
        </div>
    </section>

    <!-- Guides Section -->
    <section id="guides" class="home-guides">
        <div class="section-heading">
            <h2>Meet Our Expert Guides</h2>
            <a class="view-all-btn" href="pages/guides.php">View All</a>
        </div>
        <div class="guides-grid">
            <?php foreach ($featuredGuides as $guide): ?>
                <div class="guide-card-home">
                    <div class="guide-avatar">
                        <img src="<?= htmlspecialchars($guide['image']) ?>" alt="<?= htmlspecialchars($guide['name']) ?>" />
                    </div>
                    <h3><?= htmlspecialchars($guide['name']) ?></h3>
                    <p class="guide-specialty"><?= htmlspecialchars($guide['specialty']) ?></p>
                    <p class="guide-desc"><?= htmlspecialchars(mb_strimwidth($guide['description'] ?? '', 0, 90, '...')) ?></p>
                    <a href="<?= htmlspecialchars($guide['profile_link']) ?>" class="guide-profile-btn">View Profile</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials">
        <div class="section-heading">
            <h2>What Our Travelers Say</h2>
        </div>
        <div class="testimonials-grid">
            <?php foreach ($featuredTestimonials as $testimonial): ?>
                <div class="testimonial-card">
                    <div class="testimonial-stars">
                        <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                            <span class="star">&#9733;</span>
                        <?php endfor; ?>
                        <?php for ($i = $testimonial['rating']; $i < 5; $i++): ?>
                            <span class="star star-empty">&#9733;</span>
                        <?php endfor; ?>
                    </div>
                    <h3 class="testimonial-title"><?= htmlspecialchars($testimonial['title']) ?></h3>
                    <p class="testimonial-content">"<?= htmlspecialchars($testimonial['content']) ?>"</p>
                    <div class="testimonial-author">
                        <img src="<?= htmlspecialchars($testimonial['reviewer_avatar']) ?>" alt="<?= htmlspecialchars($testimonial['reviewer_name']) ?>" class="testimonial-avatar" />
                        <div class="testimonial-info">
                            <span class="testimonial-name"><?= htmlspecialchars($testimonial['reviewer_name']) ?></span>
                            <span class="testimonial-country"><?= htmlspecialchars($testimonial['reviewer_country']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Trusted Partners Section -->
    <section class="trusted-partners" aria-label="Trusted Partners">
        <h2>Trusted Partners</h2>
        <div class="marquee-container">
            <div class="marquee-track">
                <div class="partner-logo"><img src="images/partners/aitken.png" alt="aitken spence travels"></div>
                <div class="partner-logo"><img src="images/partners/cylonroots.png" alt="ceylon roots"></div>
                <div class="partner-logo"><img src="images/partners/jetwing.png" alt="jetwing travels"></div>
                <div class="partner-logo"><img src="images/partners/replendent.png" alt="resplendent ceylon"></div>
                <div class="partner-logo"><img src="images/partners/walkertours.png" alt="walkers tours"></div>
                <div class="partner-logo"><img src="images/partners/blt.png" alt="blue lanka tours"></div>
                <div class="partner-logo"><img src="images/partners/TR.png" alt="tourradar"></div>
                <div class="partner-logo"><img src="images/partners/cylonex.png" alt="ceylon expeditions"></div>
                <div class="partner-logo"><img src="images/partners/aitken.png" alt="aitken spence travels"></div>
                <div class="partner-logo"><img src="images/partners/cylonroots.png" alt="ceylon roots"></div>
                <div class="partner-logo"><img src="images/partners/jetwing.png" alt="jetwing travels"></div>
                <div class="partner-logo"><img src="images/partners/replendent.png" alt="resplendent ceylon"></div>
                <div class="partner-logo"><img src="images/partners/walkertours.png" alt="walkers tours"></div>
                <div class="partner-logo"><img src="images/partners/blt.png" alt="blue lanka tours"></div>
                <div class="partner-logo"><img src="images/partners/TR.png" alt="tourradar"></div>
                <div class="partner-logo"><img src="images/partners/cylonex.png" alt="ceylon expeditions"></div>
            </div>
        </div>
    </section>
    

    <!-- Custom Trip CTA Section -->
    <section class="custom-trip-cta">
        <div class="cta-content">
            <h2>Can't Find What You're Looking For?</h2>
            <p>Let us design a personalized itinerary tailored to your dreams, preferences, and budget. Our travel experts will craft the perfect journey just for you.</p>
            <a href="pages/custom-trips.php" class="cta-btn">Plan My Custom Trip</a>
        </div>
    </section>


<?php $basePath = ''; include 'includes/footer.php'; ?>

    <script src="js/script.js"></script>
</body>
</html>
