<?php
/**
 * Submit Review Handler
 *
 * Handles POST submission of user reviews/testimonials.
 * Only logged-in users with at least one completed/confirmed booking can submit.
 * User name, avatar, and country are auto-filled from their profile.
 * Reviews start as 'pending' status.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/rate-limiter.php';
$db = getDB();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$referer = $_SERVER['HTTP_REFERER'] ?? 'my-reviews.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    }
    // Rate limiting: 3 reviews per hour per user
    elseif (!checkRateLimit('submit_review', 3, 3600, false)) {
        $error = 'You have submitted too many reviews. Please try again later.';
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $packageId = (int)($_POST['package_id'] ?? 0);

        // Validation
        if ($rating < 1 || $rating > 5) {
            $error = 'Please select a rating between 1 and 5.';
        } elseif (mb_strlen($content) < 10) {
            $error = 'Your review must be at least 10 characters long.';
        } elseif (mb_strlen($content) > 2000) {
            $error = 'Your review must be no more than 2000 characters.';
        } elseif (mb_strlen($title) > 200) {
            $error = 'Title must be no more than 200 characters.';
        } else {
            // Check user has at least one confirmed booking with travel date passed
            $bookingStmt = $db->prepare(
                "SELECT COUNT(*) FROM bookings WHERE user_id = :uid AND status = 'confirmed' AND travel_date <= CURDATE()"
            );
            $bookingStmt->execute([':uid' => $_SESSION['user_id']]);
            $hasBooking = (int)$bookingStmt->fetchColumn() > 0;

            if (!$hasBooking) {
                $error = 'You must have at least one completed trip (confirmed booking with travel date in the past) to submit a review.';
            } else {
                // Fetch user profile data
                $stmt = $db->prepare("SELECT full_name, profile_photo, country, city FROM users WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $_SESSION['user_id']]);
                $user = $stmt->fetch();

                if (!$user) {
                    $error = 'User profile not found.';
                } else {
                    $reviewerName = $user['full_name'];
                    $reviewerAvatar = $user['profile_photo'] ?? '';
                    $reviewerCountry = $user['country'] ?? ($user['city'] ?? '');

                    // Validate package_id if provided
                    if ($packageId > 0) {
                        $pkgStmt = $db->prepare("SELECT id FROM packages WHERE id = :id AND is_active = 1");
                        $pkgStmt->execute([':id' => $packageId]);
                        if (!$pkgStmt->fetch()) {
                            $packageId = 0;
                        }
                    } else {
                        $packageId = null;
                    }

                    // Insert review
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

// If we get here, there was an error — redirect back
header('Location: ' . $referer . '?error=' . urlencode($error));
exit;
