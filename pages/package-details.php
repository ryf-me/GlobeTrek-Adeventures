<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$packageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch package-specific approved reviews
$reviewStmt = $db->prepare(
    "SELECT t.*, u.full_name AS user_full_name, u.profile_photo AS user_avatar
     FROM testimonials t
     LEFT JOIN users u ON t.user_id = u.id
     WHERE t.package_id = :pid AND t.status = 'approved'
     ORDER BY t.created_at DESC
     LIMIT 10"
);
$reviewStmt->execute([':pid' => $packageId]);
$packageReviews = $reviewStmt->fetchAll();
$reviewCount = count($packageReviews);

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
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/package-details.css">
    <link rel="stylesheet" href="../css/inquiries.css">
    <link rel="stylesheet" href="../css/review-modal.css">
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
                    <a class="secondary-action" href="custom-trips.php">Customize Trip</a>
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

                <!-- Reviews Tab Content -->
                <article id="reviews" class="journey-card" style="display:none;">
                    <div class="journey-heading">
                        <span class="map-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </span>
                        <div>
                            <p class="eyebrow">Guest Reviews</p>
                            <h3>What Travelers Say About This Package</h3>
                        </div>
                    </div>

                    <?php if (empty($packageReviews)): ?>
                        <div style="text-align:center;padding:2rem 1rem;color:#888;">
                            <span class="material-symbols-outlined" style="font-size:40px;color:#ddd;display:block;margin-bottom:0.75rem;">rate_review</span>
                            <p style="margin-bottom:1rem;">No reviews yet for this package. Be the first to share your experience!</p>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:1rem;">
                            <?php foreach ($packageReviews as $pr): ?>
                                <div style="background:#f8f8f8;border-radius:8px;padding:1.25rem;">
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.5rem;">
                                        <div style="display:flex;align-items:center;gap:0.75rem;">
                                            <?php if (!empty($pr['user_avatar'])): ?>
                                                <img src="<?= htmlspecialchars($pr['user_avatar']) ?>" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                                            <?php endif; ?>
                                            <div>
                                                <strong style="font-size:0.9rem;color:#264653;"><?= htmlspecialchars($pr['reviewer_name']) ?></strong>
                                                <span style="font-size:0.75rem;color:#888;display:block;"><?= htmlspecialchars($pr['reviewer_country'] ?? '') ?></span>
                                            </div>
                                        </div>
                                        <span style="color:#f4a261;white-space:nowrap;">
                                            <?php for ($s = 0; $s < (int)$pr['rating']; $s++): ?>&#9733;<?php endfor; ?>
                                            <?php for ($s = (int)$pr['rating']; $s < 5; $s++): ?><span style="color:#ddd;">&#9733;</span><?php endfor; ?>
                                        </span>
                                    </div>
                                    <?php if ($pr['title']): ?>
                                        <h4 style="font-size:0.95rem;font-weight:600;color:#264653;margin-bottom:0.35rem;"><?= htmlspecialchars($pr['title']) ?></h4>
                                    <?php endif; ?>
                                    <p style="font-size:0.88rem;color:#555;line-height:1.6;font-style:italic;">"<?= htmlspecialchars($pr['content']) ?>"</p>
                                    <p style="font-size:0.75rem;color:#aaa;margin-top:0.5rem;"><?= date('M d, Y', strtotime($pr['created_at'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div style="text-align:center;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #e9e9e9;">
                            <button onclick="openReviewModal(<?= $packageId ?>)" class="testimonial-cta-btn" style="display:inline-flex;align-items:center;gap:0.4rem;">
                                <span class="material-symbols-outlined" style="font-size:1.2rem;">rate_review</span>
                                Write a Review
                            </button>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center;margin-top:1.5rem;">
                            <a href="login.php" class="testimonial-cta-btn" style="display:inline-flex;align-items:center;gap:0.4rem;text-decoration:none;">
                                <span class="material-symbols-outlined" style="font-size:1.2rem;">login</span>
                                Login to Write a Review
                            </a>
                        </div>
                    <?php endif; ?>
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

    <?php include __DIR__ . '/../includes/review-modal.php'; ?>
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script src="../js/review-modal.js"></script>
    <script>
    // Tab switching for package details
    document.addEventListener('DOMContentLoaded', function() {
        var tabs = document.querySelectorAll('.tabs a');
        var articles = {
            overview: document.getElementById('overview'),
            itinerary: document.getElementById('itinerary'),
            inclusions: document.getElementById('inclusions'),
            exclusions: document.getElementById('exclusions'),
            stays: document.getElementById('stays'),
            reviews: document.getElementById('reviews'),
        };

        // Hide all articles except the one that matches the URL hash
        function showTab(hash) {
            var targetId = hash.replace('#', '');
            Object.keys(articles).forEach(function(key) {
                var el = articles[key];
                if (el) el.style.display = (key === targetId) ? 'block' : 'none';
            });
            tabs.forEach(function(tab) {
                tab.classList.toggle('active', tab.getAttribute('href') === hash);
            });
        }

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                var href = this.getAttribute('href');
                history.replaceState(null, '', href);
                showTab(href);
            });
        });

        // Show initial tab from URL hash or default to overview
        var initialHash = window.location.hash || '#overview';
        showTab(initialHash);
    });
    </script>
</body>
</html>
