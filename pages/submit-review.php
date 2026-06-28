<?php
/**
 * File: pages/submit-review.php
 * Purpose: Handles POST submission of user reviews/testimonials for packages.
 *          Validates eligibility (must have a completed booking), enforces rate limits,
 *          and auto-fills reviewer profile data. Reviews start as 'pending' for admin approval.
 * Dependencies: config/database.php, config/csrf.php, config/rate-limiter.php
 * Used By: Review submission forms on package detail pages and my-reviews.php
 * Parent Files: None (form action target; redirects after processing)
 * Child Files: None (includes only config files)
 * @package GlobeTrek\Pages
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/rate-limiter.php';
$db = getDB();

// === AUTH GUARD ===
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// === REFERER FOR ERROR REDIRECT ===
// Redirect back to the page the user came from if validation fails
$referer = $_SERVER['HTTP_REFERER'] ?? 'my-reviews.php';
$error = '';
$success = '';

// === HANDLE POST SUBMISSION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    }
    // Rate limiting: max 3 reviews per hour per user to prevent spam
    elseif (!checkRateLimit('submit_review', 3, 3600, false)) {
        $error = 'You have submitted too many reviews. Please try again later.';
    } else {
        // === PARSE AND SANITIZE INPUT ===
        $rating = (int)($_POST['rating'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $packageId = (int)($_POST['package_id'] ?? 0);

        // === VALIDATION ===
        if ($rating < 1 || $rating > 5) {
            $error = 'Please select a rating between 1 and 5.';
        } elseif (mb_strlen($content) < 10) {
            $error = 'Your review must be at least 10 characters long.';
        } elseif (mb_strlen($content) > 2000) {
            $error = 'Your review must be no more than 2000 characters.';
        } elseif (mb_strlen($title) > 200) {
            $error = 'Title must be no more than 200 characters.';
        } else {
            // === ELIGIBILITY CHECK ===
            // User must have at least one confirmed booking with a travel date in the past
            // This ensures only actual customers can leave reviews
            $bookingStmt = $db->prepare(
                "SELECT COUNT(*) FROM bookings WHERE user_id = :uid AND status = 'confirmed' AND travel_date <= CURDATE()"
            );
            $bookingStmt->execute([':uid' => $_SESSION['user_id']]);
            $hasBooking = (int)$bookingStmt->fetchColumn() > 0;

            if (!$hasBooking) {
                $error = 'You must have at least one completed trip (confirmed booking with travel date in the past) to submit a review.';
            } else {
                // === FETCH USER PROFILE DATA ===
                // Reviewer name, avatar, and country are auto-filled from profile — not user-supplied
                $stmt = $db->prepare("SELECT full_name, profile_photo, country, city FROM users WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $_SESSION['user_id']]);
                $user = $stmt->fetch();

                if (!$user) {
                    $error = 'User profile not found.';
                } else {
                    $reviewerName = $user['full_name'];
                    $reviewerAvatar = $user['profile_photo'] ?? '';
                    $reviewerCountry = $user['country'] ?? ($user['city'] ?? '');

                    // === VALIDATE PACKAGE ID ===
                    // If a package_id was provided, verify it exists and is active
                    // If invalid, fall back to a general review (package_id = null)
                    if ($packageId > 0) {
                        $pkgStmt = $db->prepare("SELECT id FROM packages WHERE id = :id AND is_active = 1");
                        $pkgStmt->execute([':id' => $packageId]);
                        if (!$pkgStmt->fetch()) {
                            $packageId = 0;
                        }
                    } else {
                        $packageId = null;
                    }

                    // === INSERT REVIEW ===
                    // Status starts as 'pending' — requires admin approval before being displayed
                    $stmt = $db->prepare(
                        "INSERT INTO testimonials (user_id, package_id, reviewer_name, reviewer_country, reviewer_avatar, rating, title, content, status, is_featured)
                         VALUES (:uid, :pid, :name, :country, :avatar, :rating, :title, :content, 'pending', 0)"
                    );
                    $stmt->execute([
                        ':uid' => $_SESSION['user_id'],
                        ':pid' => $packageId,
                        ':name' => $reviewerName,
                        ':country' => $reviewerCountry,
                        ':avatar' => $reviewerAvatar,
                        ':rating' => $rating,
                        ':title' => $title,
                        ':content' => $content,
                    ]);

                    header('Location: my-reviews.php?review_submitted=1');
                    exit;
                }
            }
        }
    }
}

// === ERROR REDIRECT ===
// If execution reaches here, an error occurred — redirect back to the originating page
header('Location: ' . $referer . '?error=' . urlencode($error));
exit;
