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

    <!-- Benefits Section -->
    <section id="about" class="benefits" aria-label="Why travelers book with Globe Trek Adventures">
        <div class="benefits-grid">
            <div class="benefit-item">
                <svg class="benefit-icon" viewBox="0 0 48 48" aria-hidden="true">
                    <path d="M24 4l4.2 3.6 5.5-1 2.1 5.2 5.2 2.1-1 5.5 3.6 4.2-3.6 4.2 1 5.5-5.2 2.1-2.1 5.2-5.5-1L24 44l-4.2-3.6-5.5 1-2.1-5.2L7 34.1l1-5.5L4.4 24 8 19.8 7 14.3l5.2-2.1 2.1-5.2 5.5 1L24 4z" />
                    <path d="M16 24.5l5 5L32.5 18" />
                </svg>
                <h3>Best Price Guarantee</h3>
                <p>We offer the best prices for amazing trips</p>
            </div>
            <div class="benefit-item">
                <svg class="benefit-icon" viewBox="0 0 48 48" aria-hidden="true">
                    <path d="M10 28v-6a14 14 0 0 1 28 0v6" />
                    <path d="M10 26h6v12h-4a2 2 0 0 1-2-2V26z" />
                    <path d="M32 26h6v10a2 2 0 0 1-2 2h-4V26z" />
                    <path d="M20 38h5a7 7 0 0 0 7-7" />
                    <path d="M17 20h.1" />
                    <path d="M31 20h.1" />
                    <path d="M20 27a7 7 0 0 0 8 0" />
                </svg>
                <h3>24/7 Support</h3>
                <p>We are here to help you anytime</p>
            </div>
            <div class="benefit-item">
                <svg class="benefit-icon" viewBox="0 0 48 48" aria-hidden="true">
                    <rect x="12" y="20" width="24" height="20" rx="3" />
                    <path d="M18 20v-6a6 6 0 0 1 12 0v6" />
                    <path d="M24 29v4" />
                    <circle cx="24" cy="28" r="2" />
                </svg>
                <h3>Secure Booking</h3>
                <p>Book with confidence using secure payments</p>
            </div>
            <div class="benefit-item">
                <svg class="benefit-icon" viewBox="0 0 48 48" aria-hidden="true">
                    <path d="M15 22v18H8V22h7z" />
                    <path d="M15 38h20.4a4 4 0 0 0 3.8-2.8l4-12A4 4 0 0 0 39.4 18H29l1.4-7.2A4 4 0 0 0 26.5 6H25L15 21.8V38z" />
                </svg>
                <h3>Trusted by Travelers</h3>
                <p>Thousands of happy customers</p>
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
