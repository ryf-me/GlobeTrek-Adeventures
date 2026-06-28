<!DOCTYPE html>
<?php
/**
 * File: index.php
 * Purpose: Main homepage — the primary entry point for the GlobeTrek Adventures website
 *
 * This file renders the complete homepage with 10 major sections:
 *   1. Hero Section — Full-width hero with background image, headline, CTAs, and stats overlay
 *   2. Search Bar — Destination selector, date range picker, traveler counter
 *   3. Trust Badges — 4 value propositions (Best Price, Expert Guides, 24/7 Support, Flexible Bookings)
 *   4. Popular Destinations — Filterable destination cards with category tabs
 *   5. Featured Tour Packages — Package cards with badges, tags, prices, and wishlist buttons
 *   6. Popular Activities — 8 activity icons linking to packages
 *   7. Stats Section — Animated counters for travelers, packages, destinations, guides
 *   8. Expert Guides — Horizontal carousel with guide cards
 *   9. Testimonials — Auto-rotating testimonial cards with dot navigation
 *   10. Trusted Partners — Infinite-scroll logo marquee
 *
 * Dependencies:
 *   - config/database.php (for all database queries)
 *   - config/currency.php (for formatPrice())
 *   - includes/navbar.php (site-wide navigation)
 *   - includes/footer.php (site-wide footer)
 *   - includes/review-modal.php (review submission modal)
 *   - css/style.css, css/navbar.css, css/footer.css, css/inquiries.css, css/review-modal.css, css/testimonial.css
 *   - js/script.js (main frontend JavaScript)
 *   - js/review-modal.js (review modal logic)
 *   - flatpickr (date range picker library)
 *
 * Database Queries:
 *   - packages (featured, active)
 *   - package_tags + tags (for featured packages)
 *   - destinations (featured, active)
 *   - guides (featured, active, with review counts)
 *   - testimonials (approved)
 *   - users (traveler count, recent travelers)
 *   - packages (destination categories for search)
 *
 * @package GlobeTrek\Pages
 */

// === SESSION & CONFIGURATION ===
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/currency.php';

// === DATABASE CONNECTION ===
$db = getDB();

// =============================================================================
// DATABASE QUERIES
// =============================================================================

// --- Featured Packages (active, featured, limited to 4) ---
$featuredPackages = $db->query("SELECT * FROM packages WHERE is_active = 1 AND is_featured = 1 ORDER BY id ASC LIMIT 4")->fetchAll();

// --- Package Tags (batch-load all tags for featured packages) ---
// This avoids N+1 query problem by loading all tags in one query
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

// --- Featured Destinations (active, featured, limited to 4) ---
$featuredDestinations = $db->query("SELECT * FROM destinations WHERE is_active = 1 AND is_featured = 1 ORDER BY id ASC LIMIT 4")->fetchAll();

// --- Featured Guides (active, featured, with approved review counts) ---
// LEFT JOIN ensures guides with no reviews still appear
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

// --- All Approved Testimonials ---
// Ordered by featured first, then by ID
$allTestimonials = $db->query("SELECT * FROM testimonials WHERE status = 'approved' ORDER BY is_featured DESC, id ASC")->fetchAll();

// --- Statistics for Stats Section ---
// 4 separate queries for travelers, packages, destinations, guides counts
$stats = [
    'travelers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'packages' => $db->query("SELECT COUNT(*) FROM packages WHERE is_active = 1")->fetchColumn(),
    'destinations' => $db->query("SELECT COUNT(*) FROM destinations WHERE is_active = 1")->fetchColumn(),
    'guides' => $db->query("SELECT COUNT(*) FROM guides WHERE is_active = 1")->fetchColumn(),
];

// --- Recent Travelers (for hero section avatar display) ---
$recentTravelers = $db->query("SELECT full_name, profile_photo FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 3")->fetchAll();

// --- Testimonial Statistics (for hero section rating display) ---
$testimonialStats = $db->query("SELECT COUNT(*) as total_reviews, ROUND(AVG(rating), 1) as avg_rating FROM testimonials WHERE status = 'approved'")->fetch();

// --- Destination Categories (for search bar dropdown) ---
$destinationCategories = $db->query("SELECT DISTINCT destination_category FROM packages WHERE is_active = 1 AND destination_category IS NOT NULL AND destination_category != '' ORDER BY destination_category")->fetchAll(PDO::FETCH_COLUMN);
?>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GlobeTrek - Explore Sri Lanka Like Never Before</title>
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <!-- Flatpickr date range picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/navbar.css" />
    <link rel="stylesheet" href="css/footer.css" />
    <link rel="stylesheet" href="css/inquiries.css" />
    <link rel="stylesheet" href="css/review-modal.css" />
    <link rel="stylesheet" href="css/testimonial.css" />
</head>
<body class="home-page">
    <!-- === NAVIGATION BAR === -->
    <?php $basePath = ''; include 'includes/navbar.php'; ?>

    <!-- ===================================================================== -->
    <!-- HERO SECTION -->
    <!-- Full-width hero with background image, headline, CTAs, and stats overlay -->
    <!-- ===================================================================== -->
    <section id="home" class="hero">
        <!-- Background image with gradient overlay -->
        <div class="hero-bg">
            <img src="https://images.pexels.com/photos/35606861/pexels-photo-35606861.jpeg" alt="Sigiriya Rock Fortress Sri Lanka" />
            <div class="hero-overlay"></div>
        </div>

        <div class="hero-content">
            <!-- Left: Headline and CTA buttons -->
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

            <!-- Right: Stats overlay with avatars, traveler count, and rating -->
            <div class="hero-stats-overlay">
                <!-- Recent traveler avatars -->
                <div class="hero-stat-avatars">
                    <?php foreach ($recentTravelers as $traveler): ?>
                        <?php if (!empty($traveler['profile_photo'])): ?>
                            <img src="<?= htmlspecialchars($traveler['profile_photo']) ?>" alt="Traveler">
                        <?php else: ?>
                            <!-- Show initial if no profile photo -->
                            <div class="avatar-placeholder"><?= mb_strtoupper(mb_substr(htmlspecialchars($traveler['full_name']), 0, 1)) ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <!-- Traveler count -->
                <div class="hero-stat-text">
                    <span class="hero-stat-number"><?= number_format($stats['travelers']) ?>+</span>
                    <span class="hero-stat-label">Happy Travelers</span>
                </div>
                <!-- Average rating from testimonials -->
                <div class="hero-stat-rating">
                    <span class="stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                    <span class="rating-text"><?= $testimonialStats['avg_rating'] ?? '4.9' ?> (<?= number_format($testimonialStats['total_reviews'] ?? 2500) ?>+ Reviews)</span>
                </div>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SEARCH BAR -->
        <!-- Destination selector, date range picker, traveler counter -->
        <!-- ===================================================================== -->
        <form class="hero-search" method="get" action="pages/packages.php">
            <!-- Destination dropdown -->
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

            <!-- Date range picker (Flatpickr) -->
            <div class="search-field search-field-dates">
                <span class="material-symbols-outlined">calendar_today</span>
                <div class="search-input-wrap">
                    <label>Check In - Check Out</label>
                    <input type="text" id="hero-date-range" placeholder="Select dates" readonly>
                    <!-- Hidden inputs populated by Flatpickr -->
                    <input type="hidden" name="checkin" id="hero-checkin">
                    <input type="hidden" name="checkout" id="hero-checkout">
                </div>
            </div>
            <div class="search-divider"></div>

            <!-- Traveler counter with popup -->
            <div class="search-field search-field-travelers">
                <span class="material-symbols-outlined">person</span>
                <div class="search-input-wrap">
                    <label>Travelers</label>
                    <input type="text" id="hero-travelers-display" value="2 Travelers" readonly>
                    <input type="hidden" name="travelers" id="hero-travelers" value="2">
                </div>
                <!-- Travelers increment/decrement popup -->
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

            <!-- Search button -->
            <button type="submit" class="search-btn">
                <span class="material-symbols-outlined">search</span>
                Search Tours
            </button>
        </form>

        <!-- ===================================================================== -->
        <!-- TRUST BADGES -->
        <!-- 4 value propositions displayed below the search bar -->
        <!-- ===================================================================== -->
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

    <!-- ===================================================================== -->
    <!-- POPULAR DESTINATIONS -->
    <!-- Filterable destination cards with category tabs (All, Beach, Adventure, etc.) -->
    <!-- ===================================================================== -->
    <section id="destinations" class="popular-destinations">
        <div class="section-header">
            <h2>Popular Destinations</h2>
            <a class="view-all-link" href="pages/destinations.php">View All Destinations</a>
        </div>
        <br>
        <!-- Category filter tabs -->
        <div class="dest-filter-tabs">
            <button class="dest-tab active" data-filter="all">All</button>
            <button class="dest-tab" data-filter="Beach">Beach</button>
            <button class="dest-tab" data-filter="Adventure">Adventure</button>
            <button class="dest-tab" data-filter="Wildlife">Wildlife</button>
            <button class="dest-tab" data-filter="Cultural">Cultural</button>
            <button class="dest-tab" data-filter="Hill Country">Hill Country</button>
        </div>
        <!-- Destination cards grid -->
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
                            <span class="stars">&#9733;</span>
                            <span class="rating-num"><?= number_format($dest['rating'] ?? 4.5, 1) ?></span>
                            <span class="rating-count">(<?= number_format($dest['review_count'] ?? 100) ?>)</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- FEATURED TOUR PACKAGES -->
    <!-- Package cards with badges, tags, prices, and wishlist buttons -->
    <!-- ===================================================================== -->
    <section id="packages" class="featured-packages">
        <div class="section-header">
            <h2>Featured Tour Packages</h2>
            <a class="view-all-link" href="pages/packages.php">View All Packages</a>
        </div>
        <div class="pkg-cards">
            <?php
            // Badge labels and fallback tags for each package position
            $badges = ['BEST SELLER', 'POPULAR', 'TRENDING', ''];
            $fallbackTags = [
                ['Beach', 'Relaxation', 'Culture'],
                ['Hiking', 'Nature', 'Adventure'],
                ['Safari', 'Wildlife', 'Nature'],
                ['Heritage', 'Culture', 'History'],
            ];

            foreach ($featuredPackages as $i => $pkg):
                $badge = $badges[$i % 4];
                // Use database tags if available, otherwise use fallback tags
                $tags = $packageTags[$pkg['id']] ?? $fallbackTags[$i % 4];
            ?>
                <div class="pkg-card">
                    <div class="pkg-card-img">
                        <img src="<?= htmlspecialchars($pkg['image']) ?>" alt="<?= htmlspecialchars($pkg['title']) ?>" />
                        <?php if ($badge): ?>
                            <span class="pkg-badge <?= strtolower(str_replace(' ', '-', $badge)) ?>"><?= $badge ?></span>
                        <?php endif; ?>
                        <!-- Wishlist heart button -->
                        <button class="pkg-wishlist" aria-label="Add to wishlist">
                            <span class="material-symbols-outlined">favorite</span>
                        </button>
                    </div>
                    <div class="pkg-card-body">
                        <h3><?= htmlspecialchars($pkg['title']) ?></h3>
                        <p class="pkg-duration"><?= htmlspecialchars($pkg['duration_days'] . ' Days / ' . $pkg['duration_nights'] . ' Nights') ?></p>
                        <!-- Package tags -->
                        <div class="pkg-tags">
                            <?php foreach ($tags as $tag): ?>
                                <span class="pkg-tag">
                                    <span class="material-symbols-outlined tag-icon">label</span>
                                    <?= $tag ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <!-- Price and details button -->
                        <div class="pkg-footer">
                            <p class="pkg-price">From <?= formatPrice($pkg['price']) ?></p>
                            <a href="pages/package-details.php?id=<?= $pkg['id'] ?>" class="pkg-details-btn">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- POPULAR ACTIVITIES -->
    <!-- 8 activity icons linking to the packages page -->
    <!-- ===================================================================== -->
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

    <!-- ===================================================================== -->
    <!-- STATS SECTION -->
    <!-- Animated counters triggered by IntersectionObserver -->
    <!-- ===================================================================== -->
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

    <!-- ===================================================================== -->
    <!-- EXPERT GUIDES -->
    <!-- Horizontal carousel with guide cards (photo, name, specialty, rating, languages) -->
    <!-- ===================================================================== -->
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
                                <span class="stars">&#9733;</span>
                                <span><?= number_format($guide['rating'] ?? 4.5, 1) ?></span>
                                <span class="review-count">(<?= number_format($guide['review_count'] ?? 0) ?>)</span>
                            </div>
                            <!-- Language tags -->
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
            <!-- Carousel navigation arrows -->
            <button class="guides-arrow guides-prev" aria-label="Previous">
                <span class="material-symbols-outlined">chevron_left</span>
            </button>
            <button class="guides-arrow guides-next" aria-label="Next">
                <span class="material-symbols-outlined">chevron_right</span>
            </button>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- TESTIMONIALS -->
    <!-- Auto-rotating testimonial cards with dot navigation -->
    <!-- ===================================================================== -->
    <section id="testimonials" class="testimonials-section">
        <div class="testimonials-inner">
            <!-- Left: Header and dot navigation -->
            <div class="testimonials-left">
                <div class="testimonials-badge">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span>Trusted by travelers</span>
                </div>
                <h2>What Our Travelers Say</h2>
                <p class="testimonials-subtitle">See what travelers from around the world have to say about their Sri Lanka experience with GlobeTrek Adventures.</p>
                <!-- Dot navigation (one dot per testimonial) -->
                <div class="testimonials-dots">
                    <?php foreach ($allTestimonials as $i => $testimonial): ?>
                        <button class="testimonials-dot <?= $i === 0 ? 'active' : '' ?>" aria-label="View testimonial <?= $i + 1 ?>" data-index="<?= $i ?>"></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right: Testimonial cards -->
            <div class="testimonials-right">
                <?php foreach ($allTestimonials as $i => $testimonial): ?>
                    <div class="testimonial-card <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
                        <!-- Star rating -->
                        <div class="testimonial-stars">
                            <?php for ($s = 0; $s < $testimonial['rating']; $s++): ?>
                                <span class="star">&#9733;</span>
                            <?php endfor; ?>
                            <?php for ($s = $testimonial['rating']; $s < 5; $s++): ?>
                                <span class="star star-empty">&#9733;</span>
                            <?php endfor; ?>
                        </div>
                        <!-- Quote content -->
                        <div class="testimonial-quote">
                            <svg class="testimonial-quote-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>
                            <p class="testimonial-content">"<?= htmlspecialchars($testimonial['content']) ?>"</p>
                        </div>
                        <!-- Author info -->
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

        <!-- Write a Review CTA -->
        <div style="text-align:center;margin-top:3rem;">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Logged-in users can open the review modal -->
                <button onclick="openReviewModal(0)" class="testimonial-cta-btn" style="display:inline-flex;align-items:center;gap:0.4rem;">
                    <span class="material-symbols-outlined" style="font-size:1.2rem;">rate_review</span>
                    Write a Review
                </button>
            <?php else: ?>
                <!-- Guest users are redirected to login -->
                <a href="pages/login.php" class="testimonial-cta-btn" style="display:inline-flex;align-items:center;gap:0.4rem;text-decoration:none;">
                    <span class="material-symbols-outlined" style="font-size:1.2rem;">login</span>
                    Login to Write a Review
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- TRUSTED PARTNERS -->
    <!-- Infinite-scroll logo marquee with 8 partner logos -->
    <!-- ===================================================================== -->
    <section class="trusted-partners" aria-label="Trusted Partners">
        <h2>Our Trusted Partners</h2>
        <div class="marquee-container">
            <div class="marquee-track">
                <!-- Partner logos (duplicated for seamless infinite scroll) -->
                <div class="partner-logo"><img src="images/partners/aitken.png" alt="aitken spence travels"></div>
                <div class="partner-logo"><img src="images/partners/cylonroots.png" alt="ceylon roots"></div>
                <div class="partner-logo"><img src="images/partners/jetwing.png" alt="jetwing travels"></div>
                <div class="partner-logo"><img src="images/partners/Resplendent.png" alt="resplendent ceylon"></div>
                <div class="partner-logo"><img src="images/partners/walkertours.png" alt="walkers tours"></div>
                <div class="partner-logo"><img src="images/partners/blt.png" alt="blue lanka tours"></div>
                <div class="partner-logo"><img src="images/partners/TR.png" alt="tourradar"></div>
                <div class="partner-logo"><img src="images/partners/cylonex.png" alt="ceylon expeditions"></div>
                <!-- Duplicate set for seamless loop -->
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

<!-- === INCLUDES === -->
<?php include 'includes/review-modal.php'; ?>
<?php $basePath = ''; include 'includes/footer.php'; ?>

    <!-- === JAVASCRIPT === -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="js/script.js"></script>
    <script src="js/review-modal.js"></script>

    <!-- ===================================================================== -->
    <!-- TESTIMONIAL AUTO-ROTATION -->
    <!-- Automatically cycles through testimonials every 6 seconds -->
    <!-- ===================================================================== -->
    <script>
    (function() {
        // Get all testimonial cards and navigation dots
        var cards = document.querySelectorAll('.testimonials-section .testimonial-card');
        var dots = document.querySelectorAll('.testimonials-section .testimonials-dot');
        var currentIndex = 0;
        var interval = null;

        // Show a specific testimonial by index
        function showTestimonial(index) {
            cards.forEach(function(card) { card.classList.remove('active'); });
            dots.forEach(function(dot) { dot.classList.remove('active'); });
            cards[index].classList.add('active');
            dots[index].classList.add('active');
            currentIndex = index;
        }

        // Advance to the next testimonial (wraps around)
        function nextTestimonial() {
            var next = (currentIndex + 1) % cards.length;
            showTestimonial(next);
        }

        // Start auto-rotation (6-second interval)
        function startAutoRotate() {
            if (interval) clearInterval(interval);
            interval = setInterval(nextTestimonial, 6000);
        }

        // Dot click handlers
        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                var index = parseInt(this.getAttribute('data-index'));
                showTestimonial(index);
                startAutoRotate(); // Reset timer on manual navigation
            });
        });

        // Start auto-rotation if there are testimonials
        if (cards.length > 0) startAutoRotate();
    })();
    </script>
</body>
</html>
