<?php
/**
 * File: includes/review-modal.php
 * Purpose: Reusable review submission modal for package and guide reviews
 *
 * This file provides:
 *   1. Modal overlay with form for submitting reviews
 *   2. Review type selector (General, Package, Guide)
 *   3. Dynamic package and guide dropdown selectors
 *   4. Interactive star rating widget
 *   5. Character counter for review content
 *   6. CSRF-protected form submission
 *
 * Dependencies:
 *   - config/csrf.php (for csrf_field() — must be loaded before including this file)
 *   - config/database.php (for getDB() — loaded via function_exists guard)
 *   - js/review-modal.js (must be loaded after this file)
 *   - css/review-modal.css (must be loaded in the page head)
 *
 * Used By:
 *   - index.php (homepage)
 *   - pages/package-details.php
 *   - pages/guide-details.php
 *
 * Parent Files: Any page that includes this file must set $basePath
 * Child Files: None (no includes — uses function_exists guard for DB)
 *
 * Required Variables:
 *   - $basePath (string): Relative path to project root
 *
 * Required in Scope:
 *   - csrf.php loaded (for csrf_field())
 *
 * Usage:
 *   // 1. Include the CSS in the page head:
 *   <link rel="stylesheet" href="<?= $basePath ?>css/review-modal.css">
 *
 *   // 2. Add a button to open the modal:
 *   <button onclick="openReviewModal(0)">Write a Review</button>
 *
 *   // 3. Include the modal HTML:
 *   <?php include $basePath . 'includes/review-modal.php'; ?>
 *
 *   // 4. Include the JavaScript:
 *   <script src="<?= $basePath ?>js/review-modal.js"></script>
 *
 * @package GlobeTrek\Includes
 */

// =============================================================================
// DATABASE DATA FETCHING (with graceful degradation)
// =============================================================================
// Fetch active packages and guides for the dropdown selectors
// Uses function_exists('getDB') guard so the modal works even without a database
$rvPackages = [];
$rvGuides = [];
if (function_exists('getDB')) {
    try {
        $rvDb = getDB();

        // Fetch all active packages for the package selector dropdown
        // is_active = 1 means only published packages are shown
        $rvPkgs = $rvDb->query("SELECT id, title FROM packages WHERE is_active = 1 ORDER BY title ASC")->fetchAll();
        if ($rvPkgs) $rvPackages = $rvPkgs;

        // Fetch all active guides for the guide selector dropdown
        $rvGds = $rvDb->query("SELECT id, name, specialty FROM guides WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        if ($rvGds) $rvGuides = $rvGds;
    } catch (Exception $e) {
        // Silently fail — dropdowns will be empty but the modal still renders
    }
}
?>

<!-- === REVIEW MODAL OVERLAY === -->
<!-- Hidden by default, shown via JavaScript openReviewModal() function -->
<div class="inq-modal-overlay" id="reviewModal">
    <div class="inq-modal rv-modal">

        <!-- === MODAL HEADER === -->
        <div class="inq-modal-header">
            <h2>Write a Review</h2>
            <!-- Close button calls JavaScript closeReviewModal() -->
            <button class="inq-modal-close" onclick="closeReviewModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- === REVIEW FORM === -->
        <!-- POSTs to submit-review.php with CSRF protection -->
        <form method="post" action="<?= $basePath ?>pages/submit-review.php" id="reviewForm">
            <!-- CSRF token hidden field -->
            <?php csrf_field(); ?>

            <!-- Hidden inputs for package_id and guide_id -->
            <!-- These are populated by JavaScript when the user selects a type/entity -->
            <!-- Default value of 0 means "no selection" -->
            <input type="hidden" name="package_id" id="rv-package-id" value="0">
            <input type="hidden" name="guide_id" id="rv-guide-id" value="0">

            <div class="inq-modal-body">

                <!-- === REVIEW TYPE SELECTOR === -->
                <!-- Determines which entity selectors to show -->
                <!-- onchange calls JavaScript onReviewTypeChange() to toggle visibility -->
                <div class="form-field">
                    <label for="rv-review-type">I'm reviewing a <span style="color:#e76f51;">*</span></label>
                    <select id="rv-review-type" name="review_type" onchange="onReviewTypeChange()">
                        <option value="general">General Experience</option>
                        <option value="package">Package</option>
                        <option value="guide">Guide</option>
                    </select>
                </div>

                <!-- === PACKAGE SELECTOR (hidden by default) === -->
                <!-- Shown when review_type = "package" -->
                <!-- Populated from $rvPackages fetched from the database -->
                <div class="form-field" id="rv-package-field" style="display:none;">
                    <label for="rv-package-select">Select Package <span style="color:#e76f51;">*</span></label>
                    <select id="rv-package-select" name="package_id_select">
                        <option value="0">— Choose a package —</option>
                        <?php foreach ($rvPackages as $pkg): ?>
                            <option value="<?= (int)$pkg['id'] ?>"><?= htmlspecialchars($pkg['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- === GUIDE SELECTOR (hidden by default) === -->
                <!-- Shown when review_type = "guide" -->
                <!-- Populated from $rvGuides fetched from the database -->
                <div class="form-field" id="rv-guide-field" style="display:none;">
                    <label for="rv-guide-select">Select Guide <span style="color:#e76f51;">*</span></label>
                    <select id="rv-guide-select" name="guide_id_select">
                        <option value="0">— Choose a guide —</option>
                        <?php foreach ($rvGuides as $g): ?>
                            <option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['name']) ?> — <?= htmlspecialchars($g['specialty']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Hint text (populated by JavaScript based on review type) -->
                <p id="rv-package-hint" style="font-size:0.85rem;color:#888;margin-bottom:1rem;"></p>

                <!-- === STAR RATING WIDGET === -->
                <!-- Interactive 5-star rating selector -->
                <!-- Hidden input stores the selected value (0-5) -->
                <!-- JavaScript handles click/hover/mouseleave events -->
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

                <!-- === REVIEW TITLE (optional) === -->
                <div class="form-field">
                    <label for="rv-title">Review Title <span style="color:#888;font-weight:400;">(optional)</span></label>
                    <input type="text" id="rv-title" name="title" maxlength="200" placeholder="Summarize your experience">
                </div>

                <!-- === REVIEW CONTENT (required) === -->
                <!-- Min 10 / Max 2000 characters -->
                <!-- Live character counter updated by JavaScript -->
                <div class="form-field">
                    <label for="rv-content">Your Review <span style="color:#e76f51;">*</span></label>
                    <textarea id="rv-content" name="content" minlength="10" maxlength="2000" placeholder="Share your experience with other travelers..." rows="5"></textarea>
                    <p class="rv-char-count"><span id="rv-content-count">0</span> / 2000 characters</p>
                </div>
            </div>

            <!-- === MODAL FOOTER === -->
            <div class="inq-modal-footer">
                <button type="button" class="inq-cancel-btn" onclick="closeReviewModal()">Cancel</button>
                <button type="submit" class="inq-submit-btn" id="rv-submit-btn">Submit Review</button>
            </div>
        </form>
    </div>
</div>
