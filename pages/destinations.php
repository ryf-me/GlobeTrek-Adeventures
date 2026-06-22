<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$stmt = $db->prepare("SELECT * FROM destinations WHERE is_active = 1 ORDER BY is_featured DESC, name ASC");
$stmt->execute();
$destinations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinations - Globe Trek</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/destinations.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="destinations-page">

    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <div class="dest-hero">
        <h1>Explore Sri Lanka</h1>
        <p>Discover the beauty, culture, and adventure that await you across the island paradise.</p>
    </div>

    <div class="page-container">
        <?php if (empty($destinations)): ?>
            <div class="dest-empty">
                <span class="material-symbols-outlined">location_off</span>
                <h2>No destinations available</h2>
                <p>Check back soon for exciting new destinations.</p>
            </div>
        <?php else: ?>
            <div class="destinations-grid">
                <?php foreach ($destinations as $dest): ?>
                    <a href="destination-details.php?slug=<?= htmlspecialchars($dest['slug']) ?>" class="dest-card">
                        <img class="dest-card-img" src="<?= htmlspecialchars($dest['image']) ?>" alt="<?= htmlspecialchars($dest['name']) ?>">
                        <div class="dest-card-body">
                            <?php if ($dest['is_featured']): ?>
                                <span class="dest-badge">Featured</span>
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($dest['name']) ?></h3>
                            <p class="dest-card-desc"><?= htmlspecialchars(mb_strimwidth($dest['description'] ?? '', 0, 120, '...')) ?></p>
                            <span class="dest-card-link">
                                Explore
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

<script src="../js/script.js"></script>
</body>
</html>
