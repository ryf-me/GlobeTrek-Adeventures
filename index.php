<!DOCTYPE html>
<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

$featuredPackages = $db->query("SELECT * FROM packages WHERE is_active = 1 AND is_featured = 1 ORDER BY id ASC LIMIT 4")->fetchAll();

$featuredDestinations = $db->query("SELECT * FROM destinations WHERE is_active = 1 AND is_featured = 1 ORDER BY id ASC LIMIT 4")->fetchAll();

$featuredGuides = $db->query("SELECT * FROM guides WHERE is_active = 1 AND is_featured = 1 ORDER BY id ASC LIMIT 3")->fetchAll();

$featuredTestimonials = $db->query("SELECT * FROM testimonials WHERE status = 'approved' AND is_featured = 1 ORDER BY id ASC LIMIT 3")->fetchAll();

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
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/navbar.css" />
    <link rel="stylesheet" href="css/footer.css" />
    <link rel="stylesheet" href="css/inquiries.css" />
    <link rel="stylesheet" href="css/review-modal.css" />
</head>
<body class="home-page">
    <?php $basePath = ''; include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <video class="hero-video" autoplay muted loop playsinline>
            <source src="videos/hero-video.mp4" type="video/mp4"/>
        </video>
        <div class="hero-content">
            <h1>Discover the Magic of <span id="typewriter"></span><span id="typewriter-cursor" class="typewriter-cursor">|</span></h1>
            <p>From pristine southern beaches to misty hill country temples — experience the pearl of the Indian Ocean.</p>
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
        <div class="testimonials-inner">
            <!-- Left side: Heading and navigation -->
            <div class="testimonials-left">
                <div class="testimonials-badge">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span>Trusted by travelers</span>
                </div>
                <h2>What Our Travelers Say</h2>
                <p class="testimonials-subtitle">Don't just take our word for it. See what adventurers from around the world have to say about their Sri Lanka experience.</p>
                <div class="testimonials-dots">
                    <?php foreach ($featuredTestimonials as $i => $testimonial): ?>
                        <button class="testimonials-dot" aria-label="View testimonial <?= $i + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right side: Testimonial cards -->
            <div class="testimonials-right">
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
                        <div class="testimonial-quote">
                            <svg class="testimonial-quote-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>
                            <p class="testimonial-content">"<?= htmlspecialchars($testimonial['content']) ?>"</p>
                        </div>
                        <div class="testimonial-author">
                            <img src="<?= htmlspecialchars($testimonial['reviewer_avatar']) ?>" alt="<?= htmlspecialchars($testimonial['reviewer_name']) ?>" class="testimonial-avatar" />
                            <div class="testimonial-info">
                                <span class="testimonial-name"><?= htmlspecialchars($testimonial['reviewer_name']) ?></span>
                                <span class="testimonial-country"><?= htmlspecialchars($testimonial['reviewer_country']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Decorative elements -->
                <div class="testimonial-decor-bottom"></div>
                <div class="testimonial-decor-top"></div>
            </div>
        </div>
    </section>

    <!-- Write a Review CTA -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <section style="max-width:none;background:transparent;text-align:center;padding:1.5rem 2rem 3rem;background:#f5f7fa;">
        <p style="color:#666;margin-bottom:0.75rem;font-size:0.95rem;">Have you traveled with us? Share your experience!</p>
        <button onclick="openReviewModal(0)" class="testimonial-cta-btn" style="display:inline-flex;align-items:center;gap:0.4rem;">
            <span class="material-symbols-outlined" style="font-size:1.2rem;">rate_review</span>
            Write a Review
        </button>
    </section>
    <?php endif; ?>

    <!-- Trusted Partners Section -->
    <section class="trusted-partners" aria-label="Trusted Partners">
        <h2>Trusted Partners</h2>
        <div class="marquee-container">
            <div class="marquee-track">
                <div class="partner-logo"><img src="images/partners/aitken.png" alt="aitken spence travels"></div>
                <div class="partner-logo"><img src="images/partners/cylonroots.png" alt="ceylon roots"></div>
                <div class="partner-logo"><img src="images/partners/jetwing.png" alt="jetwing travels"></div>
                <div class="partner-logo"><img src="images/partners/Resplendent.png" alt="resplendent ceylon"></div>
                <div class="partner-logo"><img src="images/partners/walkertours.png" alt="walkers tours"></div>
                <div class="partner-logo"><img src="images/partners/blt.png" alt="blue lanka tours"></div>
                <div class="partner-logo"><img src="images/partners/TR.png" alt="tourradar"></div>
                <div class="partner-logo"><img src="images/partners/cylonex.png" alt="ceylon expeditions"></div>
                <div class="partner-logo"><img src="images/partners/aitken.png" alt="aitken spence travels"></div>
                <div class="partner-logo"><img src="images/partners/cylonroots.png" alt="ceylon roots"></div>
                <div class="partner-logo"><img src="images/partners/jetwing.png" alt="jetwing travels"></div>
                <div class="partner-logo"><img src="images/partners/Resplendent.png" alt="resplendent ceylon"></div>
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


<?php include 'includes/review-modal.php'; ?>
<?php $basePath = ''; include 'includes/footer.php'; ?>

    <script src="js/script.js"></script>
    <script src="js/review-modal.js"></script>
</body>
</html>
