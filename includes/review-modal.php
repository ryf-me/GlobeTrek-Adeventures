<?php
/**
 * Review Submission Modal
 *
 * Reusable modal include for submitting reviews.
 * Include on any page where users can write a review.
 *
 * Required in scope:
 *   - csrf.php loaded (for csrf_field())
 *   - $basePath set (relative path back to root, e.g. '' or '../')
 *
 * Usage:
 *   <link rel="stylesheet" href="<?= $basePath ?>css/review-modal.css">
 *   ...
 *   <button onclick="openReviewModal(0)">Write a Review</button>
 *   ...
 *   <?php include $basePath . 'includes/review-modal.php'; ?>
 *   ...
 *   <script src="<?= $basePath ?>js/review-modal.js"></script>
 */
?>
<div class="inq-modal-overlay" id="reviewModal">
    <div class="inq-modal rv-modal">
        <div class="inq-modal-header">
            <h2>Write a Review</h2>
            <button class="inq-modal-close" onclick="closeReviewModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="post" action="<?= $basePath ?>pages/submit-review.php" id="reviewForm">
            <?php csrf_field(); ?>
            <input type="hidden" name="package_id" id="rv-package-id" value="0">

            <div class="inq-modal-body">
                <!-- Package hint -->
                <p id="rv-package-hint" style="font-size:0.85rem;color:#888;margin-bottom:1rem;"></p>

                <!-- Rating -->
                <div class="form-field">
                    <label>Your Rating <span style="color:#e76f51;">*</span></label>
                    <div class="rating-selector" id="rv-rating-selector">
                        <input type="hidden" name="rating" id="rv-rating-value" value="0">
                        <span class="star" data-value="1">&#9733;</span>
                        <span class="star" data-value="2">&#9733;</span>
                        <span class="star" data-value="3">&#9733;</span>
                        <span class="star" data-value="4">&#9733;</span>
                        <span class="star" data-value="5">&#9733;</span>
                    </div>
                </div>

                <!-- Title -->
                <div class="form-field">
                    <label for="rv-title">Review Title <span style="color:#888;font-weight:400;">(optional)</span></label>
                    <input type="text" id="rv-title" name="title" maxlength="200" placeholder="Summarize your experience">
                </div>

                <!-- Content -->
                <div class="form-field">
                    <label for="rv-content">Your Review <span style="color:#e76f51;">*</span></label>
                    <textarea id="rv-content" name="content" minlength="10" maxlength="2000" placeholder="Share your experience with other travelers..." rows="5"></textarea>
                    <p class="rv-char-count"><span id="rv-content-count">0</span> / 2000 characters</p>
                </div>
            </div>

            <div class="inq-modal-footer">
                <button type="button" class="inq-cancel-btn" onclick="closeReviewModal()">Cancel</button>
                <button type="submit" class="inq-submit-btn" id="rv-submit-btn">Submit Review</button>
            </div>
        </form>
    </div>
</div>
