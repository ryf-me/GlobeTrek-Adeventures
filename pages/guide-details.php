<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<?php
/**
 * File: pages/guide-details.php
 * Purpose: Displays a single guide's full profile including bio, stats, rating, and approved user reviews. Allows logged-in users to write reviews via modal.
 * Dependencies: config/database.php, includes/navbar.php, includes/footer.php, includes/review-modal.php, css/guide-details.css, css/inquiries.css, css/review-modal.css, js/review-modal.js
 * Used By: guides.php (linked from guide cards)
 * Parent Files: guides.php
 * Child Files: includes/review-modal.php (included for review form)
 * @package GlobeTrek\Pages
 */

// === GUIDE ID EXTRACTION ===
// Cast to int to ensure only numeric IDs are used in queries.
$guideId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// === FETCH GUIDE ===
// Only active guides are accessible; show error page if not found.
$stmt = $db->prepare("SELECT * FROM guides WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $guideId]);
$guide = $stmt->fetch();

if (!$guide) {
    echo '<!DOCTYPE html><html><head><title>Not Found</title></head><body><h1>Guide not found.</h1><a href="guides.php">Back to Guides</a></body></html>';
    exit;
}

// === FETCH APPROVED REVIEWS ===
// Only approved reviews are shown publicly. Limited to 20 most recent.
// Joins with users table to get reviewer display name and avatar.
$reviewStmt = $db->prepare(
    "SELECT gr.*, u.full_name AS user_full_name, u.profile_photo AS user_avatar
     FROM guide_reviews gr
     LEFT JOIN users u ON gr.user_id = u.id
     WHERE gr.guide_id = :gid AND gr.status = 'approved'
     ORDER BY gr.created_at DESC
     LIMIT 20"
);
$reviewStmt->execute([':gid' => $guideId]);
$guideReviews = $reviewStmt->fetchAll();
$reviewCount = count($guideReviews);

// === CALCULATE AVERAGE RATING ===
// Aggregate query for display in the hero stats section.
$avgStmt = $db->prepare(
    "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM guide_reviews WHERE guide_id = :gid AND status = 'approved'"
);
$avgStmt->execute([':gid' => $guideId]);
$stats = $avgStmt->fetch();
$avgRating = $stats['avg_rating'] ? round((float)$stats['avg_rating'], 1) : 0;
$totalReviews = (int)$stats['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($guide['name']) ?> - Guide Profile - GlobeTrek</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/guide-details.css">
    <link rel="stylesheet" href="../css/inquiries.css">
    <link rel="stylesheet" href="../css/review-modal.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
</head>
<body class="guide-details-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main class="guide-details-shell">
        <!-- === BREADCRUMBS === -->
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="../index.php#home">Home</a>
            <span aria-hidden="true">/</span>
            <a href="guides.php">Guides</a>
            <span aria-hidden="true">/</span>
            <span><?= htmlspecialchars($guide['name']) ?></span>
        </nav>

        <!-- === GUIDE HERO SECTION === -->
        <section class="guide-hero">
            <div class="guide-hero-media">
                <img src="<?= htmlspecialchars($basePath . $guide['image']) ?>" alt="<?= htmlspecialchars($guide['name']) ?>">
            </div>
            <div class="guide-hero-info">
                <span class="guide-region-badge">
                    <span class="material-symbols-outlined">location_on</span>
                    <?= htmlspecialchars($guide['region']) ?>
                </span>
                <h1><?= htmlspecialchars($guide['name']) ?></h1>
                <p class="guide-specialty-label"><?= htmlspecialchars($guide['specialty']) ?></p>
                <p class="guide-bio"><?= htmlspecialchars($guide['description']) ?></p>

                <!-- Rating and review count stats -->
                <div class="guide-stats-row">
                    <div class="guide-stat">
                        <span class="guide-stat-num"><?= $avgRating > 0 ? number_format($avgRating, 1) : '—' ?></span>
                        <span class="guide-stat-label">Rating</span>
                        <span class="guide-stars">
                            <?php for ($s = 0; $s < 5; $s++): ?>
                                <span class="star <?= $s < round($avgRating) ? 'star-filled' : 'star-empty' ?>">&#9733;</span>
                            <?php endfor; ?>
                        </span>
                    </div>
                    <div class="guide-stat">
                        <span class="guide-stat-num"><?= $totalReviews ?></span>
                        <span class="guide-stat-label">Reviews</span>
                    </div>
                </div>

                <!-- Review CTA: button for logged-in users, link for guests -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button onclick="openReviewModalGuide(<?= $guideId ?>)" class="guide-review-btn">
                        <span class="material-symbols-outlined">rate_review</span>
                        Write a Review
                    </button>
                <?php else: ?>
                    <a href="login.php" class="guide-review-btn">
                        <span class="material-symbols-outlined">login</span>
                        Login to Write a Review
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <!-- === REVIEWS SECTION === -->
        <section class="guide-reviews-section">
            <div class="guide-reviews-header">
                <h2>Reviews About <?= htmlspecialchars($guide['name']) ?></h2>
                <span class="guide-review-count"><?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?></span>
            </div>

            <!-- Success message after review submission -->
            <?php if (isset($_GET['review_submitted'])): ?>
                <div class="mb-alert mb-alert-success">
                    <span class="material-symbols-outlined">check_circle</span>
                    Review submitted successfully! It will be visible after admin approval.
                </div>
            <?php endif; ?>
            <!-- Error message display -->
            <?php if (isset($_GET['error'])): ?>
                <div class="mb-alert mb-alert-error">
                    <span class="material-symbols-outlined">error</span>
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($guideReviews)): ?>
                <!-- Empty state when no reviews exist -->
                <div class="guide-reviews-empty">
                    <span class="material-symbols-outlined">rate_review</span>
                    <p>No reviews yet for this guide. Be the first to share your experience!</p>
                </div>
            <?php else: ?>
                <!-- Reviews list -->
                <div class="guide-reviews-list">
                    <?php foreach ($guideReviews as $review): ?>
                        <div class="guide-review-card">
                            <div class="guide-review-card-header">
                                <div class="guide-review-author">
                                    <?php if (!empty($review['reviewer_avatar'])): ?>
                                        <img src="<?= htmlspecialchars($review['reviewer_avatar']) ?>" alt="" class="guide-review-avatar">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
                                        <?php if (!empty($review['reviewer_country'])): ?>
                                            <span class="guide-review-country"><?= htmlspecialchars($review['reviewer_country']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="guide-review-meta">
                                    <span class="guide-review-stars">
                                        <?php for ($s = 0; $s < (int)$review['rating']; $s++): ?>
                                            <span class="star star-filled">&#9733;</span>
                                        <?php endfor; ?>
                                        <?php for ($s = (int)$review['rating']; $s < 5; $s++): ?>
                                            <span class="star star-empty">&#9733;</span>
                                        <?php endfor; ?>
                                    </span>
                                    <span class="guide-review-date"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                                </div>
                            </div>
                            <?php if ($review['title']): ?>
                                <h4 class="guide-review-title"><?= htmlspecialchars($review['title']) ?></h4>
                            <?php endif; ?>
                            <p class="guide-review-content">"<?= htmlspecialchars($review['content']) ?>"</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Review modal component (included for logged-in users to submit reviews) -->
    <?php include __DIR__ . '/../includes/review-modal.php'; ?>
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script src="../js/review-modal.js"></script>
    <script>
    // === GUIDE REVIEW MODAL HANDLER ===
    // Pre-configures the shared review modal for guide-specific reviews.
    // Sets the review type, guide ID, and hidden select field before opening.
    function openReviewModalGuide(guideId) {
        var typeSelect = document.getElementById('rv-review-type');
        if (typeSelect) {
            typeSelect.value = 'guide';
            onReviewTypeChange();
        }
        var guideInput = document.getElementById('rv-guide-id');
        if (guideInput) guideInput.value = guideId;
        var guideSelect = document.getElementById('rv-guide-select');
        if (guideSelect) {
            guideSelect.value = guideId;
        }
        openReviewModal(0);
    }
    </script>
</body>
</html>
