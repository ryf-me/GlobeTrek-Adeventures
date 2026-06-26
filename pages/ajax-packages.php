<?php
/**
 * AJAX Package Search Endpoint
 *
 * Returns package data in DataTables-compatible JSON format.
 * Supports server-side filtering by destination, price range, duration, and text search.
 */

session_start();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$userId = $_SESSION['user_id'] ?? null;

// DataTables parameters
$draw   = intval($_GET['draw'] ?? 1);
$start  = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
$length = min($length, 100); // Cap at 100

// Search
$searchValue = trim($_GET['search']['value'] ?? '');

// Order
$orderColumn = intval($_GET['order'][0]['column'] ?? 0);
$orderDir    = $_GET['order'][0]['dir'] === 'desc' ? 'DESC' : 'ASC';
$orderColumns = ['title', 'price', 'duration_days', 'destination_category'];
$orderField = $orderColumns[$orderColumn] ?? 'title';

// Filters
$selectedDestinations = $_GET['destination'] ?? [];
$selectedPrices       = $_GET['price'] ?? [];
$selectedDurations    = $_GET['duration'] ?? [];

$where   = ["is_active = 1"];
$params  = [];

// Destination filter
if (!empty($selectedDestinations)) {
    $destPlaceholders = [];
    foreach ($selectedDestinations as $i => $dest) {
        $key = ':dest' . $i;
        $destPlaceholders[] = $key;
        $params[$key] = $dest;
    }
    $where[] = "destination_category IN (" . implode(',', $destPlaceholders) . ")";
}

// Price range filter
if (!empty($selectedPrices)) {
    $pricePlaceholders = [];
    foreach ($selectedPrices as $i => $pr) {
        $key = ':price' . $i;
        $pricePlaceholders[] = $key;
        $params[$key] = $pr;
    }
    $where[] = "price_range IN (" . implode(',', $pricePlaceholders) . ")";
}

// Duration filter
if (!empty($selectedDurations)) {
    $durConditions = [];
    foreach ($selectedDurations as $dur) {
        if ($dur === '1-3 Days')    $durConditions[] = "(duration_days >= 1 AND duration_days <= 3)";
        elseif ($dur === '4-7 Days')   $durConditions[] = "(duration_days >= 4 AND duration_days <= 7)";
        elseif ($dur === '8-14 Days')  $durConditions[] = "(duration_days >= 8 AND duration_days <= 14)";
        elseif ($dur === '15+ Days')   $durConditions[] = "(duration_days >= 15)";
    }
    if (!empty($durConditions)) {
        $where[] = "(" . implode(' OR ', $durConditions) . ")";
    }
}

// Text search
if ($searchValue !== '') {
    $where[] = "(title LIKE :search OR description LIKE :search2 OR destination_category LIKE :search3)";
    $params[':search']  = "%$searchValue%";
    $params[':search2'] = "%$searchValue%";
    $params[':search3'] = "%$searchValue%";
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Total records (filtered)
$countStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM packages $whereSQL");
$countStmt->execute($params);
$filteredTotal = (int)$countStmt->fetch()['cnt'];

// Total records (all)
$allStmt = $db->query("SELECT COUNT(*) AS cnt FROM packages WHERE is_active = 1");
$allTotal = (int)$allStmt->fetch()['cnt'];

// Fetch data
$dataStmt = $db->prepare(
    "SELECT * FROM packages $whereSQL ORDER BY $orderField $orderDir LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $val) {
    $dataStmt->bindValue($key, $val);
}
$dataStmt->bindValue(':limit', $length, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $start, PDO::PARAM_INT);
$dataStmt->execute();
$packages = $dataStmt->fetchAll();

// Get user wishlist
$userWishlist = [];
if ($userId) {
    $wStmt = $db->prepare("SELECT package_id FROM wishlist WHERE user_id = :uid");
    $wStmt->execute([':uid' => $userId]);
    $userWishlist = array_column($wStmt->fetchAll(), 'package_id');
}

// Format data for DataTables
$data = [];
foreach ($packages as $pkg) {
    $isWishlisted = in_array($pkg['id'], $userWishlist);
    $basePath = '../';
    $imageUrl = htmlspecialchars($basePath . $pkg['image']);
    $detailUrl = 'package-details.php?id=' . $pkg['id'];

    $data[] = [
        'id'                    => $pkg['id'],
        'title'                 => $pkg['title'],
        'duration'              => $pkg['duration_days'] . ' Days / ' . $pkg['duration_nights'] . ' Nights',
        'price'                 => 'Rs.' . number_format($pkg['price']),
        'price_raw'             => (float)$pkg['price'],
        'destination_category'  => $pkg['destination_category'] ?? '',
        'difficulty_level'      => $pkg['difficulty_level'] ?? '',
        'image'                 => $imageUrl,
        'detail_url'            => $detailUrl,
        'is_featured'           => (int)$pkg['is_featured'],
        'wishlist'              => $isWishlisted,
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => $allTotal,
    'recordsFiltered' => $filteredTotal,
    'data'            => $data,
]);
