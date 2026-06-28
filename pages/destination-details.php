<?php
/**
 * File: pages/destination-details.php
 * Purpose: Displays detailed information for a single destination, identified by slug query parameter. Shows hero image, featured badge, and full description.
 * Dependencies: config/database.php, includes/navbar.php, includes/footer.php, css/destinations.css, js/script.js
 * Used By: destinations.php (linked from destination cards)
 * Parent Files: destinations.php
 * Child Files: None
 * @package GlobeTrek\Pages
 */
session_start();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// === SLUG VALIDATION ===
// Redirect to listings if slug is empty — prevents unnecessary DB query.
$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    header('Location: destinations.php');
    exit;
}

// === FETCH DESTINATION BY SLUG ===
// Only active destinations are accessible; inactive ones redirect to listings.
$stmt = $db->prepare("SELECT * FROM destinations WHERE slug = :slug AND is_active = 1");
$stmt->execute([':slug' => $slug]);
$dest = $stmt->fetch();

if (!$dest) {
    header('Location: destinations.php');
    exit;
}

$pageTitle = htmlspecialchars($dest['name']) . ' - Destinations';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/destinations.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="destinations-page">

    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <!-- === DESTINATION HERO === -->
    <div class="dest-detail-hero">
        <img src="<?= htmlspecialchars($basePath . $dest['image']) ?>" alt="<?= htmlspecialchars($dest['name']) ?>">
        <div class="dest-detail-hero-overlay">
            <?php if ($dest['is_featured']): ?>
                <span class="dest-badge">Featured</span>
            <?php endif; ?>
            <h1><?= htmlspecialchars($dest['name']) ?></h1>
            <div class="dest-detail-meta">
                <span class="material-symbols-outlined">location_on</span>
                <span>Sri Lanka</span>
            </div>
        </div>
    </div>

    <!-- === DESTINATION CONTENT === -->
    <div class="dest-detail-content">
        <a href="destinations.php" class="dest-detail-back">
            <span class="material-symbols-outlined">arrow_back</span>
            All Destinations
        </a>

        <h2>About <?= htmlspecialchars($dest['name']) ?></h2>
        <!-- nl2br converts newlines to <br> for preserving paragraph formatting -->
        <p><?= nl2br(htmlspecialchars($dest['description'] ?? 'No description available.')) ?></p>
    </div>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

<script src="../js/script.js"></script>
</body>
</html>
