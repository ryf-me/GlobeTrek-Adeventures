<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$propertyTypes = ['Hotel', 'Villa', 'Boutique', 'Resort'];

$amenityOptions = [
    'has_wifi'      => 'Wi-Fi',
    'has_pool'      => 'Pool',
    'has_spa'       => 'Spa',
    'has_restaurant'=> 'Restaurant',
    'has_fitness'   => 'Fitness Center'
];

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

$where = ["a.is_active = 1"];
$params = [];

if (!empty($_GET['type']) && is_array($_GET['type'])) {
    $types = array_filter($_GET['type'], fn($v) => in_array($v, $propertyTypes));
    if ($types) {
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $where[] = "a.property_type IN ($placeholders)";
        $params = array_merge($params, array_values($types));
    }
}

$amenityFilters = [];
foreach ($amenityOptions as $col => $label) {
    if (!empty($_GET[$col])) {
        $amenityFilters[] = $col;
    }
}
foreach ($amenityFilters as $col) {
    $where[] = "$col = 1";
}

if (!empty($_GET['destination'])) {
    $where[] = "a.location LIKE ?";
    $params[] = '%' . $_GET['destination'] . '%';
}

$whereSQL = implode(' AND ', $where);

$orderMap = [
    'recommended' => 'a.is_featured DESC, a.id ASC',
    'price_asc'   => 'a.price_per_night ASC',
    'price_desc'  => 'a.price_per_night DESC',
    'rating'      => 'a.rating DESC'
];
$orderSQL = $orderMap[$sort];

$countStmt = $db->prepare("SELECT COUNT(*) FROM accommodations a WHERE $whereSQL");
$countStmt->execute($params);
$totalAccommodations = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalAccommodations / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("SELECT a.* FROM accommodations a WHERE $whereSQL ORDER BY $orderSQL LIMIT ? OFFSET ?");
$allParams = array_merge($params, [$perPage, $offset]);
$stmt->execute($allParams);
$accommodations = $stmt->fetchAll();

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
    <title>Accommodations - GlobeTrek</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/accommodations.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="accommodations-page">

    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <h1>Accommodations</h1>
            <p>Discover curated stays across Sri Lanka, from boutique hotels in Galle Fort to eco-lodges in the hill country and beachfront resorts on the southern coast.</p>
        </div>

        <form class="accommodations-layout" method="get" action="">
            <!-- Sidebar Filters -->
            <aside class="filters-sidebar">
                <h2>Filters</h2>

                <div class="filter-group">
                    <h3>Destination</h3>
                    <input type="text" name="destination" placeholder="Where to?" value="<?= htmlspecialchars($_GET['destination'] ?? '') ?>">
                </div>

                <div class="filter-group">
                    <h3>Property Type</h3>
                    <?php foreach ($propertyTypes as $type): ?>
                        <label>
                            <input type="checkbox" name="type[]" value="<?= htmlspecialchars($type) ?>" <?= (!empty($_GET['type']) && in_array($type, $_GET['type'])) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($type) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h3>Amenities</h3>
                    <?php foreach ($amenityOptions as $col => $label): ?>
                        <label>
                            <input type="checkbox" name="<?= $col ?>" value="1" <?= (!empty($_GET[$col])) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="apply-btn">Apply Filters</button>
                <a href="accommodations.php" class="reset-btn" style="text-decoration:none; text-align:center; display:block;">Reset</a>
            </aside>

            <!-- Results -->
            <main class="flex-grow">
                <div class="results-toolbar">
                    <span class="results-count">Showing <?= $totalAccommodations ?> <?= $totalAccommodations === 1 ? 'property' : 'properties' ?></span>
                    <select class="sort-select" name="sort" onchange="this.form.submit()">
                        <?php foreach ($sortOptions as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $sort === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="accommodations-grid">
                    <?php if (empty($accommodations)): ?>
                        <p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:3rem 0;">No accommodations match your filters. Try adjusting your criteria.</p>
                    <?php else: ?>
                        <?php foreach ($accommodations as $acc): ?>
                            <div class="accommodation-card">
                                <img class="card-image" src="<?= htmlspecialchars($basePath . $acc['image']) ?>" alt="<?= htmlspecialchars($acc['name']) ?>">
                                <div class="card-body">
                                    <div class="card-top">
                                        <h3><?= htmlspecialchars($acc['name']) ?></h3>
                                        <div class="card-rating">
                                            <span class="star">&#9733;</span>
                                            <span><?= number_format($acc['rating'], 1) ?></span>
                                        </div>
                                    </div>
                                    <div class="card-location">
                                        <span class="icon">&#128205;</span>
                                        <?= htmlspecialchars($acc['location']) ?>
                                    </div>
                                    <div class="card-amenities">
                                        <?php if ($acc['has_wifi']): ?>
                                            <span class="amenity-icon" title="Wi-Fi">&#128246;</span>
                                        <?php endif; ?>
                                        <?php if ($acc['has_pool']): ?>
                                            <span class="amenity-icon" title="Pool">&#127946;</span>
                                        <?php endif; ?>
                                        <?php if ($acc['has_spa']): ?>
                                            <span class="amenity-icon" title="Spa">&#9749;</span>
                                        <?php endif; ?>
                                        <?php if ($acc['has_restaurant']): ?>
                                            <span class="amenity-icon" title="Restaurant">&#127860;</span>
                                        <?php endif; ?>
                                        <?php if ($acc['has_fitness']): ?>
                                            <span class="amenity-icon" title="Fitness Center">&#127947;</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <div class="card-price">
                                            Rs.<?= number_format($acc['price_per_night'], 0) ?>
                                            <span>/ night</span>
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
