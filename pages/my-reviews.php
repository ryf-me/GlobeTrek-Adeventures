<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
$db = getDB();
$userId = $_SESSION['user_id'];

$error = '';
$success = '';

// Handle edit for package/general reviews
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5.';
    } elseif (mb_strlen($content) < 10) {
        $error = 'Your review must be at least 10 characters long.';
    } elseif (mb_strlen($content) > 2000) {
        $error = 'Your review must be no more than 2000 characters.';
    } else {
        // Try package/general review first
        $stmt = $db->prepare(
            "SELECT id FROM testimonials WHERE id = :id AND user_id = :uid AND created_at >= NOW() - INTERVAL 6 HOUR"
        );
        $stmt->execute([':id' => $reviewId, ':uid' => $userId]);
        $review = $stmt->fetch();

        if ($review) {
            $stmt = $db->prepare(
                "UPDATE testimonials SET rating = :rating, title = :title, content = :content, status = 'pending' WHERE id = :id"
            );
            $stmt->execute([
                ':rating' => $rating,
                ':title' => $title,
                ':content' => $content,
                ':id' => $reviewId,
            ]);
            header('Location: my-reviews.php?updated=1');
            exit;
        }

        // Try guide review
        $stmt = $db->prepare(
            "SELECT id FROM guide_reviews WHERE id = :id AND user_id = :uid AND created_at >= NOW() - INTERVAL 6 HOUR"
        );
        $stmt->execute([':id' => $reviewId, ':uid' => $userId]);
        $review = $stmt->fetch();

        if ($review) {
            $stmt = $db->prepare(
                "UPDATE guide_reviews SET rating = :rating, title = :title, content = :content, status = 'pending' WHERE id = :id"
            );
            $stmt->execute([
                ':rating' => $rating,
                ':title' => $title,
                ':content' => $content,
                ':id' => $reviewId,
            ]);
            header('Location: my-reviews.php?updated=1');
            exit;
        }

        $error = 'Review not found or the 6-hour edit window has expired.';
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $reviewType = $_POST['review_type'] ?? 'package';

    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token.';
    } elseif ($reviewId > 0) {
        if ($reviewType === 'guide') {
            $stmt = $db->prepare("DELETE FROM guide_reviews WHERE id = :id AND user_id = :uid");
        } else {
            $stmt = $db->prepare("DELETE FROM testimonials WHERE id = :id AND user_id = :uid");
        }
        $stmt->execute([':id' => $reviewId, ':uid' => $userId]);
        header('Location: my-reviews.php?deleted=1');
        exit;
    }
}

// Fetch user's package/general reviews
$filter = $_GET['filter'] ?? 'all';
$where = 't.user_id = :uid';
if ($filter === 'approved')   { $where .= " AND t.status = 'approved'"; }
elseif ($filter === 'pending')   { $where .= " AND t.status = 'pending'"; }
elseif ($filter === 'rejected')  { $where .= " AND t.status = 'rejected'"; }

$stmt = $db->prepare(
    "SELECT t.*, p.title AS package_title, p.slug AS package_slug, 'package' AS review_type,
            TIMESTAMPDIFF(SECOND, t.created_at, NOW()) AS seconds_elapsed
     FROM testimonials t
     LEFT JOIN packages p ON t.package_id = p.id
     WHERE $where
     ORDER BY t.created_at DESC"
);
$stmt->execute([':uid' => $userId]);
$packageReviews = $stmt->fetchAll();

// Fetch user's guide reviews
$guideWhere = 'gr.user_id = :uid';
if ($filter === 'approved')   { $guideWhere .= " AND gr.status = 'approved'"; }
elseif ($filter === 'pending')   { $guideWhere .= " AND gr.status = 'pending'"; }
elseif ($filter === 'rejected')  { $guideWhere .= " AND gr.status = 'rejected'"; }

$guideStmt = $db->prepare(
    "SELECT gr.*, g.name AS guide_name, g.specialty AS guide_specialty, 'guide' AS review_type,
            TIMESTAMPDIFF(SECOND, gr.created_at, NOW()) AS seconds_elapsed
     FROM guide_reviews gr
     LEFT JOIN guides g ON gr.guide_id = g.id
     WHERE $guideWhere
     ORDER BY gr.created_at DESC"
);
$guideStmt->execute([':uid' => $userId]);
$guideReviews = $guideStmt->fetchAll();

// Merge and sort by date
$reviews = array_merge($packageReviews, $guideReviews);
usort($reviews, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$activePage = 'my-reviews';

// Helper: check if review is within edit window (uses seconds_elapsed from DB)
function canEditReview(array $review): bool {
    return (int)($review['seconds_elapsed'] ?? 999999) < 6 * 3600;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - GlobeTrek</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Winky+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/user-sidebar.css">
    <link rel="stylesheet" href="../css/my-reviews.css">
    <link rel="stylesheet" href="../css/my-bookings.css">
    <link rel="stylesheet" href="../css/inquiries.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="usr-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <div class="usr-layout">
            <?php include '../includes/user-sidebar.php'; ?>

            <div class="usr-canvas">
                <?php if (isset($_GET['review_submitted'])): ?>
                    <div class="mb-alert mb-alert-success">
                        <span class="material-symbols-outlined">check_circle</span>
                        Review submitted successfully! It will be visible after admin approval.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['updated'])): ?>
                    <div class="mb-alert mb-alert-success">
                        <span class="material-symbols-outlined">check_circle</span>
                        Review updated successfully! It has been resubmitted for approval.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="mb-alert mb-alert-success">
                        <span class="material-symbols-outlined">check_circle</span>
                        Review deleted successfully.
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="mb-alert mb-alert-error">
                        <span class="material-symbols-outlined">error</span>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="usr-page-header">
                    <h1>My Reviews</h1>
                    <p>Manage your reviews and testimonials. You can edit a review within 6 hours of posting.</p>
                </div>

                <!-- Tabs -->
                <div class="mb-tabs">
                    <a href="?filter=all" class="mb-tab-btn <?= $filter === 'all' ? 'active' : '' ?>">
                        All Reviews
                        <span class="mb-tab-count"><?= count($reviews) ?></span>
                    </a>
                    <a href="?filter=approved" class="mb-tab-btn <?= $filter === 'approved' ? 'active' : '' ?>">
                        Approved
                    </a>
                    <a href="?filter=pending" class="mb-tab-btn <?= $filter === 'pending' ? 'active' : '' ?>">
                        Pending
                    </a>
                    <a href="?filter=rejected" class="mb-tab-btn <?= $filter === 'rejected' ? 'active' : '' ?>">
                        Rejected
                    </a>
                </div>

                <!-- Reviews List -->
                <?php if (empty($reviews)): ?>
                    <div class="mb-empty">
                        <span class="material-symbols-outlined">rate_review</span>
                        <h2>No reviews yet</h2>
                        <p>
                            <?= $filter === 'all'
                                ? 'You haven\'t written any reviews yet. After your trip, share your experience!'
                                : 'No reviews match the selected filter.'
                            ?>
                        </p>
                        <a href="../index.php#testimonials" class="mb-btn mb-btn-primary" style="margin-top:1rem;display:inline-block;">Write a Review</a>
                    </div>
                <?php else: ?>
                    <div class="mr-list">
                        <?php foreach ($reviews as $review): ?>
                            <?php
                            $statusColors = [
                                'approved' => ['#2a9d8f', '#e0f5f0'],
                                'pending'  => ['#f4a261', '#fef3e7'],
                                'rejected' => ['#e76f51', '#fde8e4'],
                            ];
                            $statusLabels = ['approved' => 'Approved', 'pending' => 'Pending', 'rejected' => 'Rejected'];
                            $sc = $statusColors[$review['status']] ?? ['#888', '#eee'];
                            $sl = $statusLabels[$review['status']] ?? 'Unknown';
                            $canEdit = (int)$review['seconds_elapsed'] < 6 * 3600;
                            $editSecondsRemaining = max(0, 6 * 3600 - (int)$review['seconds_elapsed']);
                            $editHoursRemaining = floor($editSecondsRemaining / 3600);
                            $editMinutesRemaining = floor(($editSecondsRemaining % 3600) / 60);
                            $isGuideReview = ($review['review_type'] ?? '') === 'guide';
                            ?>
                            <div class="mr-card" data-review-id="<?= $review['id'] ?>">
                                <div class="mr-card-header">
                                    <div class="mr-card-meta">
                                        <?php if ($isGuideReview): ?>
                                            <a href="guide-details.php?id=<?= (int)($review['guide_id'] ?? 0) ?>" class="mr-card-package">
                                                <span class="material-symbols-outlined">person_raised_hand</span>
                                                <?= htmlspecialchars($review['guide_name'] ?? 'Guide') ?> — <?= htmlspecialchars($review['guide_specialty'] ?? '') ?>
                                            </a>
                                        <?php elseif (!empty($review['package_title'])): ?>
                                            <a href="package-details.php?slug=<?= urlencode($review['package_slug']) ?>" class="mr-card-package">
                                                <span class="material-symbols-outlined">luggage</span>
                                                <?= htmlspecialchars($review['package_title']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="mr-card-package">
                                                <span class="material-symbols-outlined">rate_review</span>
                                                General Review
                                            </span>
                                        <?php endif; ?>
                                        <span class="mr-card-date"><?= date('M d, Y \a\t g:i A', strtotime($review['created_at'])) ?></span>
                                    </div>
                                    <span class="mr-badge" style="background:<?= $sc[1] ?>;color:<?= $sc[0] ?>;"><?= $sl ?></span>
                                </div>

                                <div class="mr-card-stars">
                                    <?php for ($s = 0; $s < (int)$review['rating']; $s++): ?>
                                        <span class="star-filled">&#9733;</span>
                                    <?php endfor; ?>
                                    <?php for ($s = (int)$review['rating']; $s < 5; $s++): ?>
                                        <span class="star-empty">&#9733;</span>
                                    <?php endfor; ?>
                                </div>

                                <?php if (!empty($review['title'])): ?>
                                    <h3 class="mr-card-title"><?= htmlspecialchars($review['title']) ?></h3>
                                <?php endif; ?>

                                <p class="mr-card-content">"<?= htmlspecialchars($review['content']) ?>"</p>

                                <div class="mr-card-actions">
                                    <?php if ($canEdit): ?>
                                        <button class="mr-btn mr-btn-outline" onclick="openEditModal(<?= $review['id'] ?>)">
                                            <span class="material-symbols-outlined">edit</span>
                                            Edit
                                        </button>
                                        <span class="mr-edit-hint">
                                            <span class="material-symbols-outlined">schedule</span>
                                            <?php if ($editHoursRemaining > 0): ?>
                                                <?= $editHoursRemaining ?>h <?= $editMinutesRemaining ?>m remaining
                                            <?php else: ?>
                                                <?= $editMinutesRemaining ?>m remaining
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <?php if ($review['status'] !== 'rejected'): ?>
                                            <span class="mr-edit-hint mr-edit-expired">
                                                <span class="material-symbols-outlined">lock</span>
                                                Edit window expired
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this review permanently?')">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                        <input type="hidden" name="review_type" value="<?= $isGuideReview ? 'guide' : 'package' ?>">
                                        <button type="submit" class="mr-btn mr-btn-text-danger">
                                            <span class="material-symbols-outlined">delete</span>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Edit Modal Data (hidden JSON) -->
                            <div id="edit-data-<?= $review['id'] ?>" style="display:none;"><?= json_encode([
                                'id' => $review['id'],
                                'rating' => (int)$review['rating'],
                                'title' => $review['title'],
                                'content' => $review['content'],
                            ]) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Edit Modal -->
    <div class="inq-modal-overlay" id="editModal">
        <div class="inq-modal" style="max-width:520px;">
            <div class="inq-modal-header">
                <h2>Edit Review</h2>
                <button class="inq-modal-close" onclick="closeEditModal()">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form method="post" action="my-reviews.php" id="editForm">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="review_id" id="edit-review-id" value="">

                <div class="inq-modal-body">
                    <div class="form-field">
                        <label>Your Rating</label>
                        <div class="rating-selector" id="edit-rating-selector">
                            <input type="hidden" name="rating" id="edit-rating-value" value="0">
                            <span class="star" data-value="1">&#9733;</span>
                            <span class="star" data-value="2">&#9733;</span>
                            <span class="star" data-value="3">&#9733;</span>
                            <span class="star" data-value="4">&#9733;</span>
                            <span class="star" data-value="5">&#9733;</span>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="edit-title">Review Title (optional)</label>
                        <input type="text" id="edit-title" name="title" maxlength="200" placeholder="Summarize your experience">
                    </div>

                    <div class="form-field">
                        <label for="edit-content">Your Review</label>
                        <textarea id="edit-content" name="content" minlength="10" maxlength="2000" placeholder="Share your experience..." rows="5"></textarea>
                        <p class="rv-char-count"><span id="edit-content-count">0</span> / 2000 characters</p>
                    </div>
                </div>

                <div class="inq-modal-footer">
                    <button type="button" class="inq-cancel-btn" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="inq-submit-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script>
    // Edit modal state
    const editData = {};
    <?php foreach ($reviews as $review): ?>
    editData[<?= $review['id'] ?>] = <?= json_encode([
        'id' => (int)$review['id'],
        'rating' => (int)$review['rating'],
        'title' => $review['title'],
        'content' => $review['content'],
    ]) ?>;
    <?php endforeach; ?>

    function openEditModal(id) {
        const d = editData[id];
        if (!d) return;

        document.getElementById('edit-review-id').value = id;
        document.getElementById('edit-title').value = d.title || '';
        document.getElementById('edit-content').value = d.content || '';
        document.getElementById('edit-content-count').textContent = d.content ? d.content.length : 0;
        document.getElementById('edit-rating-value').value = d.rating;

        const stars = document.querySelectorAll('#edit-rating-selector .star');
        stars.forEach((star, i) => {
            star.classList.toggle('selected', i < d.rating);
        });

        document.getElementById('editModal').classList.add('open');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('open');
    }

    // Star rating handler for edit modal
    document.addEventListener('DOMContentLoaded', function() {
        const selectors = document.querySelectorAll('.rating-selector');
        selectors.forEach(function(container) {
            const stars = container.querySelectorAll('.star');
            const hidden = container.querySelector('input[type="hidden"]');

            stars.forEach(function(star) {
                star.addEventListener('click', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    hidden.value = value;
                    stars.forEach(function(s, i) {
                        s.classList.toggle('selected', i < value);
                    });
                });

                star.addEventListener('mouseenter', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    stars.forEach(function(s, i) {
                        s.classList.toggle('selected', i < value);
                    });
                });

                star.addEventListener('mouseleave', function() {
                    const value = parseInt(hidden.value);
                    stars.forEach(function(s, i) {
                        s.classList.toggle('selected', i < value);
                    });
                });
            });
        });

        // Character counter for edit content
        const editContent = document.getElementById('edit-content');
        if (editContent) {
            editContent.addEventListener('input', function() {
                document.getElementById('edit-content-count').textContent = this.value.length;
            });
        }

        // Close edit modal on overlay click
        const editModal = document.getElementById('editModal');
        if (editModal) {
            editModal.addEventListener('click', function(e) {
                if (e.target === this) closeEditModal();
            });
        }
    });
    </script>
</body>
</html>
