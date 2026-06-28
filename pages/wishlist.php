<?php
/**
 * File: pages/wishlist.php
 * Purpose: Displays the authenticated user's saved package wishlist with options
 *          to remove items and navigate to booking pages.
 * Dependencies: config/database.php, config/csrf.php, config/currency.php, js/script.js
 * Used By: User sidebar navigation (user-sidebar.php)
 * Parent Files: None (standalone page rendered in browser)
 * Child Files: Includes navbar.php, user-sidebar.php, footer.php
 * @package GlobeTrek\Pages
 */

session_start();

// === AUTH GUARD ===
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// === DATABASE & CONFIG ===
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/currency.php';
$db = getDB();
$userId = $_SESSION['user_id'];

// === HANDLE REMOVE FROM WISHLIST ===
// POST action triggered by the delete button on each wishlist card
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_wishlist_id'])) {
    // CSRF validation — redirect with error if token is invalid
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        header('Location: wishlist.php?error=token');
        exit;
    }

    // Cast to int to prevent SQL injection; user_id check ensures users can only remove their own items
    $wishlistId = (int) $_POST['remove_wishlist_id'];
    $stmt = $db->prepare("DELETE FROM wishlist WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $wishlistId, ':user_id' => $userId]);
    header('Location: wishlist.php');
    exit;
}

// === FETCH WISHLIST ITEMS ===
// UNION query fetches both package and destination wishlists in one result set.
// A 'type' column distinguishes them for conditional rendering in the template.
$stmt = $db->prepare(
    "SELECT w.id AS wishlist_id, w.created_at AS added_at, 'package' AS type,
            p.id AS item_id, p.title, p.slug, p.image, p.price,
            p.duration_days, p.duration_nights, NULL AS region, NULL AS category, NULL AS rating, NULL AS review_count
     FROM wishlist w
     JOIN packages p ON w.package_id = p.id
     WHERE w.user_id = :uid1
     UNION ALL
     SELECT w.id AS wishlist_id, w.created_at AS added_at, 'destination' AS type,
            d.id AS item_id, d.name AS title, d.slug, d.image, NULL AS price,
            NULL AS duration_days, NULL AS duration_nights, d.region, d.category, d.rating, d.review_count
     FROM wishlist w
     JOIN destinations d ON w.destination_id = d.id
     WHERE w.user_id = :uid2
     ORDER BY added_at DESC"
);
$stmt->execute([':uid1' => $userId, ':uid2' => $userId]);
$items = $stmt->fetchAll();

$itemCount = count($items);
$activePage = 'wishlist';

// === HELPER FUNCTIONS ===

/**
 * Format price for wishlist display (no decimal places).
 */
function wl_format_price(float $price): string
{
    return formatPrice($price, 0);
}

/**
 * Build a duration string like "5 Days / 4 Nights".
 */
function wl_duration_string(int $days, int $nights): string
{
    return $days . ' Days' . ($nights > 0 ? ' / ' . $nights . ' Nights' : '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - GlobeTrek</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Winky+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/user-sidebar.css">
    <link rel="stylesheet" href="../css/wishlist.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="usr-page">
    <!-- === NAVBAR === -->
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <div class="usr-layout">
            <!-- === SIDEBAR === -->
            <?php $activePage = 'wishlist'; include '../includes/user-sidebar.php'; ?>

            <!-- === MAIN CONTENT === -->
            <div class="usr-canvas">
                <div class="usr-page-header">
                    <h1>My Wishlist</h1>
                    <p>Manage your saved adventures and dream destinations.</p>
                </div>

                <?php if ($itemCount > 0): ?>
                    <!-- === ITEM COUNT HEADER === -->
                    <div class="wl-header-row">
                        <div></div>
                        <span class="wl-count">
                            <span class="material-symbols-outlined">favorite</span>
                            <?= $itemCount ?> <?= $itemCount === 1 ? 'Item' : 'Items' ?> Saved
                        </span>
                    </div>

                    <!-- === WISHLIST GRID === -->
                    <div class="wl-grid">
                        <?php foreach ($items as $item): ?>
                            <article class="wl-card" data-wishlist-id="<?= (int) $item['wishlist_id'] ?>">
                                <!-- Item image or placeholder -->
                                <div class="wl-card-img">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="../<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="wl-placeholder">
                                            <span class="material-symbols-outlined">image</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($item['type'] === 'destination'): ?>
                                        <span class="wl-card-badge">Destination</span>
                                    <?php endif; ?>
                                </div>

                                <div class="wl-card-body">
                                    <div class="wl-card-top">
                                        <h3 class="wl-card-title"><?= htmlspecialchars($item['title']) ?></h3>
                                        <!-- Remove from wishlist form with CSRF protection -->
                                        <form method="POST" class="wl-remove-form">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="remove_wishlist_id" value="<?= (int) $item['wishlist_id'] ?>">
                                            <button type="submit" class="wl-card-remove" title="Remove from wishlist">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        </form>
                                    </div>

                                    <?php if ($item['type'] === 'package'): ?>
                                        <div class="wl-card-duration">
                                            <span class="material-symbols-outlined">schedule</span>
                                            <?= wl_duration_string((int) $item['duration_days'], (int) $item['duration_nights']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="wl-card-duration">
                                            <span class="material-symbols-outlined">location_on</span>
                                            <?= htmlspecialchars($item['region'] ?? '') ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="wl-card-footer">
                                        <?php if ($item['type'] === 'package'): ?>
                                            <div class="wl-card-price-row">
                                                <span class="wl-card-price"><?= wl_format_price((float) $item['price']) ?></span>
                                                <span class="wl-card-per-person">Per Person</span>
                                            </div>
                                            <a href="booking.php?package=<?= urlencode($item['slug']) ?>" class="wl-card-book">Book Now</a>
                                        <?php else: ?>
                                            <div class="wl-card-price-row">
                                                <span class="wl-card-price"><?= number_format((float) $item['rating'], 1) ?> ★</span>
                                                <span class="wl-card-per-person"><?= number_format((int) $item['review_count']) ?> reviews</span>
                                            </div>
                                            <a href="destination-details.php?slug=<?= urlencode($item['slug']) ?>" class="wl-card-book">View Details</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- === EMPTY STATE === -->
                    <div class="wl-empty">
                        <span class="material-symbols-outlined">favorite</span>
                        <h2>Your wishlist is empty</h2>
                        <p>Looks like you haven't saved any adventures yet. Start exploring our world-class tours and destinations.</p>
                        <div class="wl-empty-links">
                            <a href="packages.php" class="wl-empty-link">Browse Packages</a>
                            <a href="destinations.php" class="wl-empty-link wl-empty-link-alt">Browse Destinations</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- === FOOTER === -->
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script>
    // === WISHLIST REMOVE ANIMATION ===
    // Adds a CSS class for exit animation, then submits the form after 300ms
    (function () {
        var removeForms = document.querySelectorAll('.wl-remove-form');
        removeForms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var card = form.closest('.wl-card');
                if (card) {
                    e.preventDefault();
                    card.classList.add('removing');
                    setTimeout(function () {
                        form.submit();
                    }, 300);
                }
            });
        });
    })();
    </script>
</body>
</html>
