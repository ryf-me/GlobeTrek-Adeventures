<?php
/**
 * File: pages/ajax-packages.php
 * Purpose: AJAX endpoint for package search - returns DataTables-compatible
 *          JSON with server-side filtering by destination, price range,
 *          duration, and text search. Supports sorting and pagination.
 *          Also includes wishlist status for the current user.
 * Dependencies: config/database.php, config/currency.php
 * Used By: packages.php (DataTable AJAX source)
 * Parent Files: packages.php
 * Child Files: None (API endpoint, returns JSON only)
 * @package GlobeTrek\Pages
 */

// === CONFIGURATION & DEPENDENCIES ===
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/currency.php';
$db = getDB();

// Get current user ID for wishlist lookups (null if not logged in)
$userId = $_SESSION['user_id'] ?? null;

// === DATATABLES REQUEST PARAMETERS ===

// Draw counter: DataTables uses this to match requests with responses
$draw   = intval($_GET['draw'] ?? 1);

// Pagination: offset and length
$start  = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
// Cap at 100 to prevent excessive data loading
$length = min($length, 100);

// === SEARCH ===
// Global search value from DataTables search box
$searchValue = trim($_GET['search']['value'] ?? '');

// === ORDERING ===
// Map column index to database field name
$orderColumn = intval($_GET['order'][0]['column'] ?? 0);
$orderDir    = $_GET['order'][0]['dir'] === 'desc' ? 'DESC' : 'ASC';
$orderColumns = ['title', 'price', 'duration_days', 'destination_category'];
$orderField = $orderColumns[$orderColumn] ?? 'title';

// === FILTER VALUES ===
// These are passed from the sidebar checkboxes in packages.php
$selectedDestinations = $_GET['destination'] ?? [];
$selectedPrices       = $_GET['price'] ?? [];
$selectedDurations    = $_GET['duration'] ?? [];

// === BUILD DYNAMIC WHERE CLAUSE ===
// Start with base condition: only active packages
$where   = ["is_active = 1"];
$params  = [];

// Destination filter: IN clause for selected categories
if (!empty($selectedDestinations)) {
    $destPlaceholders = [];
    foreach ($selectedDestinations as $i => $dest) {
        $key = ':dest' . $i;
        $destPlaceholders[] = $key;
        $params[$key] = $dest;
    }
    $where[] = "destination_category IN (" . implode(',', $destPlaceholders) . ")";
}

// Price range filter: IN clause for selected price ranges
if (!empty($selectedPrices)) {
    $pricePlaceholders = [];
    foreach ($selectedPrices as $i => $pr) {
        $key = ':price' . $i;
        $pricePlaceholders[] = $key;
        $params[$key] = $pr;
    }
    $where[] = "price_range IN (" . implode(',', $pricePlaceholders) . ")";
}

// Duration filter: convert range labels to numeric conditions
// Each range is converted to a SQL condition on duration_days
if (!empty($selectedDurations)) {
    $durConditions = [];
    foreach ($selectedDurations as $dur) {
        if ($dur === '1-3 Days')    $durConditions[] = "(duration_days >= 1 AND duration_days <= 3)";
        elseif ($dur === '4-7 Days')   $durConditions[] = "(duration_days >= 4 AND duration_days <= 7)";
        elseif ($dur === '8-14 Days')  $durConditions[] = "(duration_days >= 8 AND duration_days <= 14)";
        elseif ($dur === '15+ Days')   $durConditions[] = "(duration_days >= 15)";
    }
    if (!empty($durConditions)) {
        // OR together: match any selected duration range
        $where[] = "(" . implode(' OR ', $durConditions) . ")";
    }
}

// Text search: LIKE query across title, description, and destination category
if ($searchValue !== '') {
    $where[] = "(title LIKE :search OR description LIKE :search2 OR destination_category LIKE :search3)";
    $params[':search']  = "%$searchValue%";
    $params[':search2'] = "%$searchValue%";
    $params[':search3'] = "%$searchValue%";
}

// Combine all WHERE conditions with AND
$whereSQL = 'WHERE ' . implode(' AND ', $where);

// === COUNT QUERIES ===

// Total records matching current filters (for DataTables pagination)
$countStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM packages $whereSQL");
$countStmt->execute($params);
$filteredTotal = (int)$countStmt->fetch()['cnt'];

// Total records across all active packages (unfiltered count)
$allStmt = $db->query("SELECT COUNT(*) AS cnt FROM packages WHERE is_active = 1");
$allTotal = (int)$allStmt->fetch()['cnt'];

// === FETCH PAGE DATA ===
// Execute the main query with sorting, pagination, and all filters
$dataStmt = $db->prepare(
    "SELECT * FROM packages $whereSQL ORDER BY $orderField $orderDir LIMIT :limit OFFSET :offset"
);
// Bind filter parameters individually (avoid PDO named parameter conflicts)
foreach ($params as $key => $val) {
    $dataStmt->bindValue($key, $val);
}
// Bind LIMIT and OFFSET as integers
$dataStmt->bindValue(':limit', $length, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $start, PDO::PARAM_INT);
$dataStmt->execute();
$packages = $dataStmt->fetchAll();

// === GET USER WISHLIST ===
// Batch query: fetch all package IDs in the user's wishlist
$userWishlist = [];
if ($userId) {
    $wStmt = $db->prepare("SELECT package_id FROM wishlist WHERE user_id = :uid AND package_id IS NOT NULL");
    $wStmt->execute([':uid' => $userId]);
    $userWishlist = array_column($wStmt->fetchAll(), 'package_id');
}

// === FORMAT DATA FOR DATATABLES ===
// Transform raw DB rows into the structure DataTables expects
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
        'price'                 => formatPrice($pkg['price']),
        'price_raw'             => (float)$pkg['price'],
        'destination_category'  => $pkg['destination_category'] ?? '',
        'difficulty_level'      => $pkg['difficulty_level'] ?? '',
        'image'                 => $imageUrl,
        'detail_url'            => $detailUrl,
        'is_featured'           => (int)$pkg['is_featured'],
        'wishlist'              => $isWishlisted,
    ];
}

// === RETURN JSON RESPONSE ===
// DataTables requires: draw, recordsTotal, recordsFiltered, and data array
header('Content-Type: application/json');
echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => $allTotal,
    'recordsFiltered' => $filteredTotal,
    'data'            => $data,
]);
