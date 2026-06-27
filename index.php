<!DOCTYPE html>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/currency.php';
$db = getDB();

$featuredPackages = $db->query("SELECT * FROM packages WHERE is_active = 1 AND is_featured = 1 ORDER BY id ASC LIMIT 4")->fetchAll();

// Fetch tags for featured packages
$packageTags = [];
if (!empty($featuredPackages)) {
    $pkgIds = array_column($featuredPackages, 'id');
    $placeholders = implode(',', array_fill(0, count($pkgIds), '?'));
    $tagStmt = $db->prepare("SELECT pt.package_id, t.name FROM package_tags pt JOIN tags t ON pt.tag_id = t.id WHERE pt.package_id IN ($placeholders) ORDER BY t.name");
    $tagStmt->execute($pkgIds);
    while ($row = $tagStmt->fetch()) {
        $packageTags[$row['package_id']][] = $row['name'];
    }
}

$featuredDestinations = $db->query("SELECT * FROM destinations WHERE is_active = 1 AND is_featured = 1 ORDER BY id ASC LIMIT 4")->fetchAll();

$featuredGuides = $db->query("
    SELECT g.*, COALESCE(rc.review_count, 0) AS review_count
    FROM guides g
    LEFT JOIN (
        SELECT guide_id, COUNT(*) AS review_count
        FROM guide_reviews
        WHERE status = 'approved'
        GROUP BY guide_id
    ) rc ON g.id = rc.guide_id
    WHERE g.is_active = 1 AND g.is_featured = 1
    ORDER BY g.id ASC LIMIT 4
")->fetchAll();

$allTestimonials = $db->query("SELECT * FROM testimonials WHERE status = 'approved' ORDER BY is_featured DESC, id ASC")->fetchAll();

$stats = [
    'travelers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'packages' => $db->query("SELECT COUNT(*) FROM packages WHERE is_active = 1")->fetchColumn(),
    'destinations' => $db->query("SELECT COUNT(*) FROM destinations WHERE is_active = 1")->fetchColumn(),
    'guides' => $db->query("SELECT COUNT(*) FROM guides WHERE is_active = 1")->fetchColumn(),
];

$recentTravelers = $db->query("SELECT full_name, profile_photo FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 3")->fetchAll();

$testimonialStats = $db->query("SELECT COUNT(*) as total_reviews, ROUND(AVG(rating), 1) as avg_rating FROM testimonials WHERE status = 'approved'")->fetch();

$destinationCategories = $db->query("SELECT DISTINCT destination_category FROM packages WHERE is_active = 1 AND destination_category IS NOT NULL AND destination_category != '' ORDER BY destination_category")->fetchAll(PDO::FETCH_COLUMN);
?>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GlobeTrek - Explore Sri Lanka Like Never Before</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/navbar.css" />
    <link rel="stylesheet" href="css/footer.css" />
    <link rel="stylesheet" href="css/inquiries.css" />
    <link rel="stylesheet" href="css/review-modal.css" />
    <link rel="stylesheet" href="css/testimonial.css" />
</head>
<body class="home-page">
    <?php $basePath = ''; include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-bg">
            <img src="https://images.pexels.com/photos/35606861/pexels-photo-35606861.jpeg" alt="Sigiriya Rock Fortress Sri Lanka" />
            <div class="hero-overlay"></div>
        </div>
        <div class="hero-content">
            <div class="hero-text">
                <h1>Explore Sri Lanka<br>Like Never Before</h1>
                <p>Discover breathtaking beaches, misty mountains, wildlife safaris, ancient heritage sites, and unforgettable experiences.</p>
                <div class="hero-ctas">
                    <a href="pages/packages.php" class="cta-primary">Explore Packages</a>
                    <a href="pages/custom-trips.php" class="cta-outline">
                        <span class="material-symbols-outlined">map</span>
                        Customize Your Tour
                    </a>
                </div>
            </div>
            <div class="hero-stats-overlay">
                <div class="hero-stat-avatars">
                    <?php foreach ($recentTravelers as $traveler): ?>
                        <?php if (!empty($traveler['profile_photo'])): ?>
                            <img src="<?= htmlspecialchars($traveler['profile_photo']) ?>" alt="Traveler">
                        <?php else: ?>
                            <div class="avatar-placeholder"><?= mb_strtoupper(mb_substr(htmlspecialchars($traveler['full_name']), 0, 1)) ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="hero-stat-text">
                    <span class="hero-stat-number"><?= number_format($stats['travelers']) ?>+</span>
                    <span class="hero-stat-label">Happy Travelers</span>
                </div>
                <div class="hero-stat-rating">
                    <span class="stars">★★★★★</span>
                    <span class="rating-text"><?= $testimonialStats['avg_rating'] ?? '4.9' ?> (<?= number_format($testimonialStats['total_reviews'] ?? 2500) ?>+ Reviews)</span>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <form class="hero-search" method="get" action="pages/packages.php">
            <div class="search-field">
                <span class="material-symbols-outlined">location_on</span>
                <div class="search-input-wrap">
                    <label for="search-destination">Destination</label>
                    <select id="search-destination" name="destination[]">
                        <option value="">Where are you going?</option>
                        <?php foreach ($destinationCategories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="search-divider"></div>
            <div class="search-field search-field-dates">
                <span class="material-symbols-outlined">calendar_today</span>
                <div class="search-input-wrap">
                    <label>Check In - Check Out</label>
                    <input type="text" id="hero-date-range" placeholder="Select dates" readonly>
                    <input type="hidden" name="checkin" id="hero-checkin">
                    <input type="hidden" name="checkout" id="hero-checkout">
                </div>
            </div>
            <div class="search-divider"></div>
            <div class="search-field search-field-travelers">
                <span class="material-symbols-outlined">person</span>
                <div class="search-input-wrap">
                    <label>Travelers</label>
                    <input type="text" id="hero-travelers-display" value="2 Travelers" readonly>
                    <input type="hidden" name="travelers" id="hero-travelers" value="2">
                </div>
                <div class="travelers-popup" id="travelers-popup">
                    <div class="travelers-counter">
                        <span class="travelers-label">Travelers</span>
                        <div class="travelers-controls">
                            <button type="button" class="travelers-btn" data-action="decrease">-</button>
                            <span class="travelers-count" id="travelers-count">2</span>
                            <button type="button" class="travelers-btn" data-action="increase">+</button>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="search-btn">
                <span class="material-symbols-outlined">search</span>
                Search Tours
            </button>
        </form>

        <!-- Trust Badges -->
        <div class="trust-badges">
            <div class="trust-badge">
                <span class="material-symbols-outlined">savings</span>
                <div>
                    <strong>Best Price Guarantee</strong>
                    <span>Get the best deals always</span>
                </div>
            </div>
            <div class="trust-badge">
                <span class="material-symbols-outlined">groups</span>
                <div>
                    <strong>Expert Local Guides</strong>
                    <span>Professional & friendly</span>
                </div>
            </div>
            <div class="trust-badge">
                <span class="material-symbols-outlined">headset_mic</span>
                <div>
                    <strong>24/7 Customer Support</strong>
                    <span>We're here to help</span>
                </div>
            </div>
            <div class="trust-badge">
                <span class="material-symbols-outlined">event_available</span>
                <div>
                    <strong>Flexible Bookings</strong>
                    <span>Free cancellation options</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Destinations -->
    <section id="destinations" class="popular-destinations">
        <div class="section-header">
            <h2>Popular Destinations</h2>
            <a class="view-all-link" href="pages/destinations.php">View All Destinations</a>
        </div>
        <br>
        <div class="dest-filter-tabs">
            <button class="dest-tab active" data-filter="all">All</button>
            <button class="dest-tab" data-filter="Beach">Beach</button>
            <button class="dest-tab" data-filter="Adventure">Adventure</button>
            <button class="dest-tab" data-filter="Wildlife">Wildlife</button>
            <button class="dest-tab" data-filter="Cultural">Cultural</button>
            <button class="dest-tab" data-filter="Hill Country">Hill Country</button>
        </div>
        <div class="dest-cards">
            <?php foreach ($featuredDestinations as $dest): ?>
                <div class="dest-card" data-category="<?= htmlspecialchars($dest['category'] ?? 'Cultural') ?>">
                    <div class="dest-card-img">
                        <img src="<?= htmlspecialchars($dest['image']) ?>" alt="<?= htmlspecialchars($dest['name']) ?>" />
                        <span class="dest-badge"><?= htmlspecialchars(strtoupper($dest['category'] ?? 'Cultural')) ?></span>
                    </div>
                    <div class="dest-card-body">
                        <h3><?= htmlspecialchars($dest['name']) ?></h3>
                        <p class="dest-desc"><?= htmlspecialchars(mb_strimwidth($dest['description'] ?? '', 0, 80, '...')) ?></p>
                        <div class="dest-rating">
                            <span class="stars">★</span>
                            <span class="rating-num"><?= number_format($dest['rating'] ?? 4.5, 1) ?></span>
                            <span class="rating-count">(<?= number_format($dest['review_count'] ?? 100) ?>)</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Featured Tour Packages -->
    <section id="packages" class="featured-packages">
        <div class="section-header">
            <h2>Featured Tour Packages</h2>
            <a class="view-all-link" href="pages/packages.php">View All Packages</a>
        </div>
        <div class="pkg-cards">
            <?php
            $badges = ['BEST SELLER', 'POPULAR', 'TRENDING', ''];
            $fallbackTags = [
                ['Beach', 'Relaxation', 'Culture'],
                ['Hiking', 'Nature', 'Adventure'],
                ['Safari', 'Wildlife', 'Nature'],
                ['Heritage', 'Culture', 'History'],
            ];
            foreach ($featuredPackages as $i => $pkg):
                $badge = $badges[$i % 4];
                $tags = $packageTags[$pkg['id']] ?? $fallbackTags[$i % 4];
            ?>
                <div class="pkg-card">
                    <div class="pkg-card-img">
                        <img src="<?= htmlspecialchars($pkg['image']) ?>" alt="<?= htmlspecialchars($pkg['title']) ?>" />
                        <?php if ($badge): ?>
                            <span class="pkg-badge <?= strtolower(str_replace(' ', '-', $badge)) ?>"><?= $badge ?></span>
                        <?php endif; ?>
                        <button class="pkg-wishlist" aria-label="Add to wishlist">
                            <span class="material-symbols-outlined">favorite</span>
                        </button>
                    </div>
                    <div class="pkg-card-body">
                        <h3><?= htmlspecialchars($pkg['title']) ?></h3>
                        <p class="pkg-duration"><?= htmlspecialchars($pkg['duration_days'] . ' Days / ' . $pkg['duration_nights'] . ' Nights') ?></p>
                        <div class="pkg-tags">
                            <?php foreach ($tags as $tag): ?>
                                <span class="pkg-tag">
                                    <span class="material-symbols-outlined tag-icon">label</span>
                                    <?= $tag ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="pkg-footer">
                            <p class="pkg-price">From <?= formatPrice($pkg['price']) ?></p>
                            <a href="pages/package-details.php?id=<?= $pkg['id'] ?>" class="pkg-details-btn">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Popular Activities -->
    <section class="popular-activities">
        <div class="section-header">
            <h2>Popular Activities</h2>
            <a class="view-all-link" href="pages/packages.php">View All Activities</a>
        </div>
        <div class="activities-grid">
            <a class="activity-item" href="pages/packages.php">
                <span class="material-symbols-outlined">forest</span>
                <span class="activity-label">Safari Adventures</span>
            </a>
            <a class="activity-item" href="pages/packages.php">
                <span class="material-symbols-outlined">surfing</span>
                <span class="activity-label">Surfing</span>
            </a>
            <a class="activity-item" href="pages/packages.php">
                <span class="material-symbols-outlined">train</span>
                <span class="activity-label">Train Journeys</span>
            </a>
            <a class="activity-item" href="pages/packages.php">
                <span class="material-symbols-outlined">hiking</span>
                <span class="activity-label">Hiking & Trekking</span>
            </a>
            <a class="activity-item" href="pages/packages.php">
                <span class="material-symbols-outlined">water</span>
                <span class="activity-label">Whale Watching</span>
            </a>
            <a class="activity-item" href="pages/packages.php">
                <span class="material-symbols-outlined">temple_buddhist</span>
                <span class="activity-label">Cultural Tours</span>
            </a>
            <a class="activity-item" href="pages/packages.php">
                <span class="material-symbols-outlined">kayaking</span>
                <span class="activity-label">Water Sports</span>
            </a>
            <a class="activity-item" href="pages/packages.php">
                <span class="material-symbols-outlined">eco</span>
                <span class="activity-label">Tea Plantation Tours</span>
            </a>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-icon-circle">
                    <span class="material-symbols-outlined stat-icon">groups</span>
                </div>
                <div class="stat-text">
                    <span class="stat-number" data-target="<?= $stats['travelers'] ?>">0</span>
                    <span class="stat-label">Happy Travelers</span>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon-circle">
                    <span class="material-symbols-outlined stat-icon">luggage</span>
                </div>
                <div class="stat-text">
                    <span class="stat-number" data-target="<?= $stats['packages'] ?>">0</span>
                    <span class="stat-label">Tour Packages</span>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon-circle">
                    <span class="material-symbols-outlined stat-icon">location_on</span>
                </div>
                <div class="stat-text">
                    <span class="stat-number" data-target="<?= $stats['destinations'] ?>">0</span>
                    <span class="stat-label">Destinations</span>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon-circle">
                    <span class="material-symbols-outlined stat-icon">person</span>
                </div>
                <div class="stat-text">
                    <span class="stat-number" data-target="<?= $stats['guides'] ?>">0</span>
                    <span class="stat-label">Expert Guides</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Meet Our Expert Guides -->
    <section id="guides" class="expert-guides">
        <div class="section-header">
            <h2>Meet Our Expert Guides</h2>
            <a class="view-all-link" href="pages/guides.php">View All Guides</a>
        </div>
        <div class="guides-carousel">
            <div class="guides-track">
                <?php foreach ($featuredGuides as $guide): ?>
                    <div class="guide-card">
                        <div class="guide-photo">
                            <img src="<?= htmlspecialchars($guide['image']) ?>" alt="<?= htmlspecialchars($guide['name']) ?>" />
                        </div>
                        <div class="guide-info">
                            <h3><?= htmlspecialchars($guide['name']) ?></h3>
                            <p class="guide-specialty"><?= htmlspecialchars($guide['specialty']) ?></p>
                            <div class="guide-rating">
                                <span class="stars">★</span>
                                <span><?= number_format($guide['rating'] ?? 4.5, 1) ?></span>
                                <span class="review-count">(<?= number_format($guide['review_count'] ?? 0) ?>)</span>
                            </div>
                            <div class="guide-languages">
                                <?php
                                $langs = explode(',', $guide['languages'] ?? 'English, Sinhala');
                                foreach ($langs as $lang):
                                ?>
                                    <span class="lang-tag"><?= htmlspecialchars(trim($lang)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <p class="guide-experience"><?= ($guide['years_experience'] ?? 5) ?> Years Experience</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="guides-arrow guides-prev" aria-label="Previous">
                <span class="material-symbols-outlined">chevron_left</span>
            </button>
            <button class="guides-arrow guides-next" aria-label="Next">
                <span class="material-symbols-outlined">chevron_right</span>
            </button>
        </div>
    </section>

    <!-- What Our Travelers Say -->
    <section id="testimonials" class="testimonials-section">
        <div class="testimonials-inner">
            <div class="testimonials-left">
                <div class="testimonials-badge">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span>Trusted by travelers</span>
                </div>
                <h2>What Our Travelers Say</h2>
                <p class="testimonials-subtitle">See what travelers from around the world have to say about their Sri Lanka experience with GlobeTrek Adventures.</p>
                <div class="testimonials-dots">
                    <?php foreach ($allTestimonials as $i => $testimonial): ?>
                        <button class="testimonials-dot <?= $i === 0 ? 'active' : '' ?>" aria-label="View testimonial <?= $i + 1 ?>" data-index="<?= $i ?>"></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="testimonials-right">
                <?php foreach ($allTestimonials as $i => $testimonial): ?>
                    <div class="testimonial-card <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
                        <div class="testimonial-stars">
                            <?php for ($s = 0; $s < $testimonial['rating']; $s++): ?>
                                <span class="star">&#9733;</span>
                            <?php endfor; ?>
                            <?php for ($s = $testimonial['rating']; $s < 5; $s++): ?>
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

                <div class="testimonial-decor-bottom"></div>
                <div class="testimonial-decor-top"></div>
            </div>
        </div>

        <!-- Write a Review -->
        <div style="text-align:center;margin-top:3rem;">
            <?php if (isset($_SESSION['user_id'])): ?>
                <button onclick="openReviewModal(0)" class="testimonial-cta-btn" style="display:inline-flex;align-items:center;gap:0.4rem;">
                    <span class="material-symbols-outlined" style="font-size:1.2rem;">rate_review</span>
                    Write a Review
                </button>
            <?php else: ?>
                <a href="pages/login.php" class="testimonial-cta-btn" style="display:inline-flex;align-items:center;gap:0.4rem;text-decoration:none;">
                    <span class="material-symbols-outlined" style="font-size:1.2rem;">login</span>
                    Login to Write a Review
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Trusted Partners Section -->
    <section class="trusted-partners" aria-label="Trusted Partners">
        <h2>Our Trusted Partners</h2>
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

<?php include 'includes/review-modal.php'; ?>
<?php $basePath = ''; include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="js/script.js"></script>
    <script src="js/review-modal.js"></script>
    <script>
    (function() {
        var cards = document.querySelectorAll('.testimonials-section .testimonial-card');
        var dots = document.querySelectorAll('.testimonials-section .testimonials-dot');
        var currentIndex = 0;
        var interval = null;

        function showTestimonial(index) {
            cards.forEach(function(card) { card.classList.remove('active'); });
            dots.forEach(function(dot) { dot.classList.remove('active'); });
            cards[index].classList.add('active');
            dots[index].classList.add('active');
            currentIndex = index;
        }

        function nextTestimonial() {
            var next = (currentIndex + 1) % cards.length;
            showTestimonial(next);
        }

        function startAutoRotate() {
            if (interval) clearInterval(interval);
            interval = setInterval(nextTestimonial, 6000);
        }

        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                var index = parseInt(this.getAttribute('data-index'));
                showTestimonial(index);
                startAutoRotate();
            });
        });

        if (cards.length > 0) startAutoRotate();
    })();
    </script>
</body>
</html>
