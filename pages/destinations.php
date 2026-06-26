<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Filter parameters
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$selectedRegion = isset($_GET['region']) ? trim($_GET['region']) : '';
$selectedCategory = isset($_GET['category']) ? trim($_GET['category']) : '';
$sortBy = isset($_GET['sort']) ? trim($_GET['sort']) : 'popular';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build query
$where = "WHERE is_active = 1";
$params = [];

if ($search !== '') {
    $where .= " AND (name LIKE :search OR description LIKE :search2 OR region LIKE :search3)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
}

if ($selectedRegion !== '') {
    $where .= " AND region = :region";
    $params[':region'] = $selectedRegion;
}

if ($selectedCategory !== '') {
    $where .= " AND category = :category";
    $params[':category'] = $selectedCategory;
}

// Sort
$orderBy = "ORDER BY is_featured DESC, review_count DESC";
if ($sortBy === 'rating') {
    $orderBy = "ORDER BY rating DESC, review_count DESC";
} elseif ($sortBy === 'name') {
    $orderBy = "ORDER BY name ASC";
} elseif ($sortBy === 'reviews') {
    $orderBy = "ORDER BY review_count DESC";
}

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM destinations $where");
$countStmt->execute($params);
$totalDestinations = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalDestinations / $perPage));

// Fetch destinations
$stmt = $db->prepare("SELECT * FROM destinations $where $orderBy LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$destinations = $stmt->fetchAll();

// Sidebar: category counts
$catStmt = $db->prepare("SELECT category, COUNT(*) as cnt FROM destinations WHERE is_active = 1 AND category IS NOT NULL GROUP BY category ORDER BY cnt DESC");
$catStmt->execute();
$categories = $catStmt->fetchAll();

// All regions for dropdown
$regionStmt = $db->prepare("SELECT DISTINCT region FROM destinations WHERE is_active = 1 AND region IS NOT NULL ORDER BY region");
$regionStmt->execute();
$regions = $regionStmt->fetchAll(PDO::FETCH_COLUMN);

// Total active count
$totalStmt = $db->query("SELECT COUNT(*) FROM destinations WHERE is_active = 1");
$totalActive = (int)$totalStmt->fetchColumn();

    // Wishlist for logged-in users
    $userId = $_SESSION['user_id'] ?? null;
    $userWishlist = [];
    if ($userId) {
        $wStmt = $db->prepare("SELECT destination_id FROM wishlist WHERE user_id = :uid AND destination_id IS NOT NULL");
        $wStmt->execute([':uid' => $userId]);
        $userWishlist = array_column($wStmt->fetchAll(), 'destination_id');
    }

// Helper to build query string preserving filters
function buildQueryString($overrides = []) {
    $params = array_merge($_GET, $overrides);
    // Remove empty values
    $params = array_filter($params, function($v) { return $v !== '' && $v !== null; });
    return http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinations - GlobeTrek</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/destinations.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body class="destinations-page">

    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="dest-hero">
        <img class="dest-hero-bg" src="https://images.pexels.com/photos/29813527/pexels-photo-29813527.jpeg" alt="Sri Lanka scenic view" />
        <div class="dest-hero-overlay"></div>
        <div class="dest-hero-content">
            <div class="dest-hero-text">
                <span class="dest-hero-subtitle">EXPLORE SRI LANKA</span>
                <h1>Destinations</h1>
                <p>From golden beaches and misty mountains to ancient wonders and vibrant cities — discover the beauty of Sri Lanka.</p>
            </div>
            <div class="dest-hero-stats">
                <div class="dest-hero-stat">
                    <span class="material-symbols-outlined">location_on</span>
                    <div>
                        <strong>50+</strong>
                        <span>Destinations</span>
                    </div>
                </div>
                <div class="dest-hero-stat">
                    <span class="material-symbols-outlined">landscape</span>
                    <div>
                        <strong>Diverse</strong>
                        <span>Landscapes</span>
                    </div>
                </div>
                <div class="dest-hero-stat">
                    <span class="material-symbols-outlined">photo_camera</span>
                    <div>
                        <strong>Endless</strong>
                        <span>Experiences</span>
                    </div>
                </div>
                <div class="dest-hero-stat">
                    <span class="material-symbols-outlined">favorite</span>
                    <div>
                        <strong>Unforgettable</strong>
                        <span>Memories</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Breadcrumb -->
    <div class="dest-breadcrumb-wrap">
        <div class="dest-breadcrumb">
            <a href="../index.php">Home</a>
            <span class="material-symbols-outlined">chevron_right</span>
            <span>Destinations</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="dest-filter-bar">
        <form method="get" action="destinations.php" class="dest-filter-form">
            <div class="dest-search-input">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search destinations..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="dest-filter-select">
                <select name="region">
                    <option value="">All Regions</option>
                    <?php foreach ($regions as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= $selectedRegion === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="dest-filter-select">
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= htmlspecialchars($c['category']) ?>" <?= $selectedCategory === $c['category'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="dest-filter-btn">
                <span class="material-symbols-outlined">filter_list</span>
                Filter
            </button>
        </form>
        <div class="dest-filter-meta">
            <span class="dest-result-count">Showing <?= $totalDestinations > 0 ? (($offset + 1) . '-' . min($offset + $perPage, $totalDestinations)) : '0' ?> of <?= $totalActive ?>+ destinations</span>
            <div class="dest-sort">
                <label>Sort by:</label>
                <select>
                    <option value="popular" <?= $sortBy === 'popular' ? 'selected' : '' ?>>Popular</option>
                    <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Top Rated</option>
                    <option value="reviews" <?= $sortBy === 'reviews' ? 'selected' : '' ?>>Most Reviewed</option>
                    <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dest-content-wrap">
        <main class="dest-main">
            <?php if (empty($destinations)): ?>
                <div class="dest-empty">
                    <span class="material-symbols-outlined">location_off</span>
                    <h2>No destinations found</h2>
                    <p>Try adjusting your search or filters to find what you're looking for.</p>
                    <a href="destinations.php" class="dest-clear-filters">Clear Filters</a>
                </div>
            <?php else: ?>
                <div class="dest-grid">
                    <?php foreach ($destinations as $dest): ?>
                        <a href="destination-details.php?slug=<?= htmlspecialchars($dest['slug']) ?>" class="dest-card">
                            <div class="dest-card-img-wrap">
                                <img class="dest-card-img" src="<?= htmlspecialchars($basePath . $dest['image']) ?>" alt="<?= htmlspecialchars($dest['name']) ?>">
                                <span class="dest-card-badge"><?= htmlspecialchars(strtoupper($dest['category'] ?? '')) ?></span>
                                <button class="dest-card-wishlist <?= in_array($dest['id'], $userWishlist) ? 'active' : '' ?>" data-id="<?= $dest['id'] ?>" title="Add to Wishlist" onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(this);">
                                    <span class="material-symbols-outlined">favorite</span>
                                </button>
                            </div>
                            <div class="dest-card-body">
                                <h3><?= htmlspecialchars($dest['name']) ?></h3>
                                <div class="dest-card-location">
                                    <span class="material-symbols-outlined">location_on</span>
                                    <?= htmlspecialchars($dest['region'] ?? '') ?>
                                </div>
                                <p class="dest-card-desc"><?= htmlspecialchars(mb_strimwidth($dest['description'] ?? '', 0, 100, '...')) ?></p>
                                <div class="dest-card-rating">
                                    <span class="dest-stars">
                                        <?php
                                        $rating = (float)($dest['rating'] ?? 0);
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($i <= $rating): ?>
                                                <span class="material-symbols-outlined filled">star</span>
                                            <?php elseif ($i - $rating < 1): ?>
                                                <span class="material-symbols-outlined filled">star_half</span>
                                            <?php else: ?>
                                                <span class="material-symbols-outlined">star</span>
                                            <?php endif;
                                        endfor; ?>
                                    </span>
                                    <strong><?= number_format($rating, 1) ?></strong>
                                    <span class="dest-review-count">(<?= number_format($dest['review_count'] ?? 0) ?> reviews)</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="dest-pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="dest-page-btn dest-page-prev">
                                <span class="material-symbols-outlined">chevron_left</span>
                            </a>
                        <?php endif; ?>
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        if ($start > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="dest-page-btn">1</a>
                            <?php if ($start > 2): ?>
                                <span class="dest-page-ellipsis">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="dest-page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <?php if ($end < $totalPages): ?>
                            <?php if ($end < $totalPages - 1): ?>
                                <span class="dest-page-ellipsis">...</span>
                            <?php endif; ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="dest-page-btn"><?= $totalPages ?></a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="dest-page-btn dest-page-next">
                                <span class="material-symbols-outlined">chevron_right</span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>

        <!-- Sidebar -->
        <aside class="dest-sidebar">
            <!-- Explore on Map -->
            <div class="dest-sidebar-card dest-map-card">
                <h3><span class="material-symbols-outlined">explore</span> Explore on Map</h3>
                <div id="dest-map" class="dest-map"></div>
            </div>

            <!-- Browse by Category -->
            <div class="dest-sidebar-card">
                <h3><span class="material-symbols-outlined">category</span> Browse by Category</h3>
                <div class="dest-category-list">
                    <a href="destinations.php" class="dest-category-item <?= $selectedCategory === '' ? 'active' : '' ?>">
                        <span>All Destinations</span>
                        <span class="dest-cat-count"><?= $totalActive ?>+</span>
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="destinations.php?<?= http_build_query(array_merge($_GET, ['category' => $cat['category'], 'page' => ''])) ?>" class="dest-category-item <?= $selectedCategory === $cat['category'] ? 'active' : '' ?>">
                            <span><?= htmlspecialchars($cat['category']) ?></span>
                            <span class="dest-cat-count"><?= $cat['cnt'] ?>+</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Need Help Choosing? -->
            <div class="dest-sidebar-card dest-help-card">
                <h3>Need Help Choosing?</h3>
                <p>Our travel experts are here to help you find the perfect place.</p>
                <a href="contact.php" class="dest-help-btn">
                    <span class="material-symbols-outlined">chat</span>
                    Chat with an Expert
                </a>
            </div>
        </aside>
    </div>

    <!-- Newsletter Section -->
    <section class="dest-newsletter">
        <div class="dest-newsletter-inner">
            <div class="dest-newsletter-icon">
                <span class="material-symbols-outlined">mail</span>
            </div>
            <div class="dest-newsletter-text">
                <h3>Get Travel Inspiration & Exclusive Offers</h3>
                <p>Subscribe to our newsletter and be the first to know about exciting deals and new destinations.</p>
            </div>
            <form id="newsletter-form" class="dest-newsletter-form">
                <input type="email" id="newsletter-email" placeholder="Enter your email" required>
                <button type="submit" class="dest-newsletter-btn">Subscribe</button>
            </form>
        </div>
    </section>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../js/script.js"></script>
    <script>
    // Leaflet Map
    (function() {
        var map = L.map('dest-map', { zoomControl: false, scrollWheelZoom: false, dragging: false }).setView([7.8731, 80.7718], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        var destinations = [
            <?php
            $mapStmt = $db->query("SELECT name, slug, region, category FROM destinations WHERE is_active = 1");
            $mapDests = $mapStmt->fetchAll();
            $coords = [
                'Central Province' => [7.8731, 80.7718],
                'Southern Province' => [6.0535, 80.2210],
                'Uva Province' => [6.9847, 81.0564],
                'North Central Province' => [8.3114, 80.4037],
                'Eastern Province' => [7.7964, 81.5284],
            ];
            foreach ($mapDests as $md):
                $pos = $coords[$md['region']] ?? [7.8731, 80.7718];
                // Add slight randomness
                $lat = $pos[0] + (mt_rand(-30, 30) / 100);
                $lng = $pos[1] + (mt_rand(-30, 30) / 100);
            ?>
                [<?= $lat ?>, <?= $lng ?>, <?= json_encode($md['name']) ?>, <?= json_encode($md['slug']) ?>],
            <?php endforeach; ?>
        ];

        var markerIcon = L.divIcon({
            className: 'dest-map-marker',
            html: '<span class="material-symbols-outlined">location_on</span>',
            iconSize: [24, 24],
            iconAnchor: [12, 24]
        });

        destinations.forEach(function(d) {
            L.marker([d[0], d[1]], { icon: markerIcon })
                .addTo(map)
                .bindPopup('<a href="destination-details.php?slug=' + d[3] + '">' + d[2] + '</a>');
        });
    })();

    // Newsletter form
    document.getElementById('newsletter-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var email = document.getElementById('newsletter-email').value;
        var btn = this.querySelector('.dest-newsletter-btn');
        btn.textContent = 'Subscribing...';
        btn.disabled = true;

        var formData = new FormData();
        formData.append('email', email);
        formData.append('csrf_token', csrfToken);

        fetch('newsletter-subscribe.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                btn.textContent = 'Subscribed!';
                document.getElementById('newsletter-email').value = '';
                setTimeout(function() { btn.textContent = 'Subscribe'; btn.disabled = false; }, 3000);
            } else {
                btn.textContent = 'Subscribe';
                btn.disabled = false;
                alert(data.message || 'Something went wrong. Please try again.');
            }
        })
        .catch(function() {
            btn.textContent = 'Subscribe';
            btn.disabled = false;
            alert('An error occurred. Please try again.');
        });
    });

    // Wishlist toggle
    function toggleWishlist(btn) {
        var destId = btn.getAttribute('data-id');
        var formData = new FormData();
        formData.append('destination_id', destId);
        formData.append('csrf_token', csrfToken);

        fetch('destination-wishlist-toggle.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'added') {
                btn.classList.add('active');
            } else if (data.status === 'removed') {
                btn.classList.remove('active');
            } else if (data.status === 'error') {
                alert(data.message || 'Please log in to use the wishlist.');
            }
        })
        .catch(function() {
            alert('An error occurred. Please try again.');
        });
    }

    // Sort change
    document.querySelector('.dest-sort select').addEventListener('change', function() {
        var form = document.querySelector('.dest-filter-form');
        var params = new URLSearchParams(new FormData(form));
        params.set('sort', this.value);
        params.delete('page');
        window.location.search = params.toString();
    });
    </script>
</body>
</html>
