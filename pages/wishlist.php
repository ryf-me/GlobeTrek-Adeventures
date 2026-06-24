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

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_wishlist_id'])) {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        header('Location: wishlist.php?error=token');
        exit;
    }

    $wishlistId = (int) $_POST['remove_wishlist_id'];
    $stmt = $db->prepare("DELETE FROM wishlist WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $wishlistId, ':user_id' => $userId]);
    header('Location: wishlist.php');
    exit;
}

// Fetch wishlist items with package details
$stmt = $db->prepare(
    "SELECT w.id AS wishlist_id, w.created_at AS added_at, p.*
     FROM wishlist w
     JOIN packages p ON w.package_id = p.id
     WHERE w.user_id = :user_id
     ORDER BY w.created_at DESC"
);
$stmt->execute([':user_id' => $userId]);
$items = $stmt->fetchAll();

$itemCount = count($items);
$activePage = 'wishlist';

function wl_format_price(float $price): string
{
    return '$' . number_format($price, 0);
}

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
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <div class="usr-layout">
            <?php $activePage = 'wishlist'; include '../includes/user-sidebar.php'; ?>

            <div class="usr-canvas">
                <div class="usr-page-header">
                    <h1>My Wishlist</h1>
                    <p>Manage your saved adventures and dream destinations.</p>
                </div>

                <?php if ($itemCount > 0): ?>
                    <div class="wl-header-row">
                        <div></div>
                        <span class="wl-count">
                            <span class="material-symbols-outlined">favorite</span>
                            <?= $itemCount ?> <?= $itemCount === 1 ? 'Item' : 'Items' ?> Saved
                        </span>
                    </div>

                    <div class="wl-grid">
                        <?php foreach ($items as $item): ?>
                            <article class="wl-card" data-wishlist-id="<?= (int) $item['wishlist_id'] ?>">
                                <div class="wl-card-img">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="../<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="wl-placeholder">
                                            <span class="material-symbols-outlined">image</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="wl-card-body">
                                    <div class="wl-card-top">
                                        <h3 class="wl-card-title"><?= htmlspecialchars($item['title']) ?></h3>
                                        <form method="POST" class="wl-remove-form">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="remove_wishlist_id" value="<?= (int) $item['wishlist_id'] ?>">
                                            <button type="submit" class="wl-card-remove" title="Remove from wishlist">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="wl-card-duration">
                                        <span class="material-symbols-outlined">schedule</span>
                                        <?= wl_duration_string((int) $item['duration_days'], (int) $item['duration_nights']) ?>
                                    </div>

                                    <div class="wl-card-footer">
                                        <div class="wl-card-price-row">
                                            <span class="wl-card-price"><?= wl_format_price((float) $item['price']) ?></span>
                                            <span class="wl-card-per-person">Per Person</span>
                                        </div>
                                        <a href="booking.php?package=<?= urlencode($item['slug']) ?>" class="wl-card-book">Book Now</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="wl-empty">
                        <span class="material-symbols-outlined">favorite</span>
                        <h2>Your wishlist is empty</h2>
                        <p>Looks like you haven't saved any adventures yet. Start exploring our world-class tours.</p>
                        <a href="packages.php" class="wl-empty-link">Browse Packages</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script>
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
