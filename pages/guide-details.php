<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$guideId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch guide
$stmt = $db->prepare("SELECT * FROM guides WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $guideId]);
$guide = $stmt->fetch();

if (!$guide) {
    echo '<!DOCTYPE html><html><head><title>Not Found</title></head><body><h1>Guide not found.</h1><a href="guides.php">Back to Guides</a></body></html>';
    exit;
}

// Fetch approved guide reviews
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

// Calculate average rating
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
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="../index.php#home">Home</a>
            <span aria-hidden="true">/</span>
            <a href="guides.php">Guides</a>
            <span aria-hidden="true">/</span>
            <span><?= htmlspecialchars($guide['name']) ?></span>
        </nav>

        <!-- Guide Hero -->
        <section class="guide-hero">
            <div class="guide-hero-media">
                <img src="<?= htmlspecialchars($guide['image']) ?>" alt="<?= htmlspecialchars($guide['name']) ?>">
            </div>
            <div class="guide-hero-info">
                <span class="guide-region-badge">
                    <span class="material-symbols-outlined">location_on</span>
                    <?= htmlspecialchars($guide['region']) ?>
                </span>
                <h1><?= htmlspecialchars($guide['name']) ?></h1>
                <p class="guide-specialty-label"><?= htmlspecialchars($guide['specialty']) ?></p>
                <p class="guide-bio"><?= htmlspecialchars($guide['description']) ?></p>

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

        <!-- Reviews Section -->
        <section class="guide-reviews-section">
            <div class="guide-reviews-header">
                <h2>Reviews About <?= htmlspecialchars($guide['name']) ?></h2>
                <span class="guide-review-count"><?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?></span>
            </div>

            <?php if (isset($_GET['review_submitted'])): ?>
                <div class="mb-alert mb-alert-success">
                    <span class="material-symbols-outlined">check_circle</span>
                    Review submitted successfully! It will be visible after admin approval.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="mb-alert mb-alert-error">
                    <span class="material-symbols-outlined">error</span>
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($guideReviews)): ?>
                <div class="guide-reviews-empty">
                    <span class="material-symbols-outlined">rate_review</span>
                    <p>No reviews yet for this guide. Be the first to share your experience!</p>
                </div>
            <?php else: ?>
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

    <?php include __DIR__ . '/../includes/review-modal.php'; ?>
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script src="../js/review-modal.js"></script>
    <script>
    // Open review modal pre-configured for guide review
    function openReviewModalGuide(guideId) {
        // Set the review type to 'guide'
        var typeSelect = document.getElementById('rv-review-type');
        if (typeSelect) {
            typeSelect.value = 'guide';
            onReviewTypeChange();
        }
        // Set the guide ID
        var guideInput = document.getElementById('rv-guide-id');
        if (guideInput) guideInput.value = guideId;
        // Set the hidden guide_id_select for form submission
        var guideSelect = document.getElementById('rv-guide-select');
        if (guideSelect) {
            guideSelect.value = guideId;
        }
        // Open the modal
        openReviewModal(0);
    }
    </script>
</body>
</html>
