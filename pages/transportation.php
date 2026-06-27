<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/currency.php';
$db = getDB();

$vehicleTypes = ['Three-Wheeler', 'Car', 'Bike', 'Minivan'];

$sortOptions = [
    'recommended' => 'Recommended',
    'price_asc'   => 'Price: Low to High',
    'price_desc'  => 'Price: High to Low',
    'rating'      => 'Rating'
];

$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortOptions) ? $_GET['sort'] : 'recommended';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 6;
$offset = ($page - 1) * $perPage;

$where = ["t.is_active = 1"];
$params = [];

if (!empty($_GET['type']) && is_array($_GET['type'])) {
    $types = array_filter($_GET['type'], fn($v) => in_array($v, $vehicleTypes));
    if ($types) {
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $where[] = "t.vehicle_type IN ($placeholders)";
        $params = array_merge($params, array_values($types));
    }
}

if (!empty($_GET['has_ac'])) {
    $where[] = "t.has_ac = 1";
}
if (!empty($_GET['has_driver'])) {
    $where[] = "t.has_driver = 1";
}
if (!empty($_GET['has_insurance'])) {
    $where[] = "t.has_insurance = 1";
}

if (!empty($_GET['location'])) {
    $where[] = "t.location LIKE ?";
    $params[] = '%' . $_GET['location'] . '%';
}

if (!empty($_GET['min_price'])) {
    $where[] = "t.price_per_day >= ?";
    $params[] = (float)$_GET['min_price'];
}
if (!empty($_GET['max_price'])) {
    $where[] = "t.price_per_day <= ?";
    $params[] = (float)$_GET['max_price'];
}

$whereSQL = implode(' AND ', $where);

$orderMap = [
    'recommended' => 't.is_featured DESC, t.id ASC',
    'price_asc'   => 't.price_per_day ASC',
    'price_desc'  => 't.price_per_day DESC',
    'rating'      => 't.rating DESC'
];
$orderSQL = $orderMap[$sort];

$countStmt = $db->prepare("SELECT COUNT(*) FROM transportations t WHERE $whereSQL");
$countStmt->execute($params);
$totalTransport = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalTransport / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("SELECT t.* FROM transportations t WHERE $whereSQL ORDER BY $orderSQL LIMIT ? OFFSET ?");
$allParams = array_merge($params, [$perPage, $offset]);
$stmt->execute($allParams);
$transportations = $stmt->fetchAll();

function buildQueryString($overrides = []) {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transportation - GlobeTrek</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/transportation.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="transportation-page">

    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <h1>Transportation</h1>
            <p>Find reliable local transport to explore Sri Lanka at your own pace. From tuk-tuks to SUVs, we've got you covered.</p>
        </div>

        <!-- Search Bar -->
        <form class="search-bar" method="get" action="">
            <div class="search-input-group">
                <div class="search-field" style="flex:2;">
                    <label>Location</label>
                    <input type="text" name="location" placeholder="Where are you headed?" value="<?= htmlspecialchars($_GET['location'] ?? '') ?>">
                </div>
                <button type="submit" class="search-btn">Search Transport</button>
            </div>
        </form>

        <form class="transportation-layout" method="get" action="">
            <?php if (!empty($_GET['location'])): ?>
                <input type="hidden" name="location" value="<?= htmlspecialchars($_GET['location']) ?>">
            <?php endif; ?>
            <!-- Sidebar Filters -->
            <aside class="filters-sidebar">
                <h2>Filters</h2>

                <div class="filter-group">
                    <h3>Vehicle Type</h3>
                    <?php foreach ($vehicleTypes as $type): ?>
                        <label>
                            <input type="checkbox" name="type[]" value="<?= htmlspecialchars($type) ?>" <?= (!empty($_GET['type']) && in_array($type, $_GET['type'])) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($type) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h3>Price Range (per day)</h3>
                    <div class="price-range">
                        <input type="text" name="min_price" placeholder="Min" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>">
                        <span class="separator">-</span>
                        <input type="text" name="max_price" placeholder="Max" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>">
                    </div>
                </div>

                <div class="filter-group">
                    <h3>Amenities</h3>
                    <label>
                        <input type="checkbox" name="has_ac" value="1" <?= (!empty($_GET['has_ac'])) ? 'checked' : '' ?>>
                        Air Conditioning
                    </label>
                    <label>
                        <input type="checkbox" name="has_driver" value="1" <?= (!empty($_GET['has_driver'])) ? 'checked' : '' ?>>
                        With Driver
                    </label>
                    <label>
                        <input type="checkbox" name="has_insurance" value="1" <?= (!empty($_GET['has_insurance'])) ? 'checked' : '' ?>>
                        Insurance Included
                    </label>
                </div>

                <button type="submit" class="apply-btn">Apply Filters</button>
                <a href="transportation.php" class="reset-btn" style="text-decoration:none; text-align:center; display:block;">Reset</a>
            </aside>

            <!-- Results -->
            <main class="flex-grow">
                <div class="results-toolbar">
                    <span class="results-count">Showing <?= $totalTransport ?> <?= $totalTransport === 1 ? 'vehicle' : 'vehicles' ?></span>
                    <select class="sort-select" name="sort" onchange="this.form.submit()">
                        <?php foreach ($sortOptions as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $sort === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="transportation-grid">
                    <?php if (empty($transportations)): ?>
                        <p style="text-align:center; color:#64748b; padding:3rem 0;">No vehicles match your filters. Try adjusting your criteria.</p>
                    <?php else: ?>
                        <?php foreach ($transportations as $t): ?>
                            <div class="transport-card">
                                <img class="card-image" src="<?= htmlspecialchars($basePath . $t['image']) ?>" alt="<?= htmlspecialchars($t['name']) ?>">
                                <div class="card-body">
                                    <div class="card-top">
                                        <h3><?= htmlspecialchars($t['name']) ?></h3>
                                        <span class="vehicle-badge <?= strtolower(str_replace('-', '', $t['vehicle_type'])) ?>"><?= htmlspecialchars($t['vehicle_type']) ?></span>
                                    </div>
                                    <div class="card-location">
                                        <span class="icon">&#128205;</span>
                                        <?= htmlspecialchars($t['location']) ?>
                                    </div>
                                    <div class="card-description">
                                        <?= htmlspecialchars($t['short_description'] ?? $t['description']) ?>
                                    </div>
                                    <div class="card-features">
                                        <?php if ($t['has_ac']): ?>
                                            <span class="feature-tag"><span class="check">&#10003;</span> AC</span>
                                        <?php endif; ?>
                                        <?php if ($t['has_driver']): ?>
                                            <span class="feature-tag"><span class="check">&#10003;</span> Driver</span>
                                        <?php endif; ?>
                                        <?php if ($t['has_insurance']): ?>
                                            <span class="feature-tag"><span class="check">&#10003;</span> Insurance</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <div class="card-price">
                                            <?= formatPrice($t['price_per_day'], 0) ?>
                                            <span>/ day</span>
                                        </div>
                                        <div class="card-rating">
                                            <span class="star">&#9733;</span>
                                            <span><?= number_format($t['rating'], 1) ?></span>
                                        </div>
                                        <a href="#" class="view-btn">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?= buildQueryString(['page' => $page - 1]) ?>">&lsaquo;</a>
                        <?php else: ?>
                            <span class="disabled">&lsaquo;</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= buildQueryString(['page' => $page + 1]) ?>">&rsaquo;</a>
                        <?php else: ?>
                            <span class="disabled">&rsaquo;</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </main>
        </form>
    </div>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

<script src="../js/script.js"></script>
</body>
</html>
