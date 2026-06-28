<?php
/**
 * File: admin/export-customers.php
 * Purpose: Export customer report data as CSV file for download.
 * Dependencies: session, config/database.php, config/currency.php
 * Used By: Admin users via customer-reports.php export button
 * Parent Files: admin/customer-reports.php (linked from export buttons)
 * Child Files: None (standalone export script)
 * @package GlobeTrek\Admin
 */

// === AUTHENTICATION CHECK ===
// Verify admin access before allowing customer data export
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// === DATABASE CONNECTION ===
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/currency.php';
$db = getDB();

// === DATE RANGE FILTER ===
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

// === FETCH CUSTOMER DATA ===
// LEFT JOIN on bookings ensures customers with no bookings still appear
// Booking aggregation uses conditional date filter on the JOIN clause (not WHERE)
// This ensures user creation date filter works independently of booking dates
$r = $db->prepare(
    "SELECT u.full_name, u.email, u.phone, u.country, u.city, u.gender, u.created_at AS joined,
            COUNT(b.id) AS booking_count, COALESCE(SUM(b.total_price), 0) AS total_spent
     FROM users u
     LEFT JOIN bookings b ON u.id = b.user_id AND b.status = 'confirmed' AND b.created_at BETWEEN :bfrom AND :bto
     WHERE u.created_at BETWEEN :ufrom AND :uto
     GROUP BY u.id, u.full_name, u.email, u.phone, u.country, u.city, u.gender, u.created_at
     ORDER BY total_spent DESC"
);
$r->execute([':bfrom' => $dateFrom . ' 00:00:00', ':bto' => $dateTo . ' 23:59:59', ':ufrom' => $dateFrom . ' 00:00:00', ':uto' => $dateTo . ' 23:59:59']);
$customers = $r->fetchAll();

// === SET CSV HEADERS ===
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customer-report-' . $dateFrom . '-to-' . $dateTo . '.csv"');

// === WRITE CSV OUTPUT ===
$output = fopen('php://output', 'w');
// UTF-8 BOM for Excel Windows compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// === CSV HEADER ROWS ===
fputcsv($output, ['Customer Report']);
fputcsv($output, ['Date From', $dateFrom, '', 'Date To', $dateTo]);
fputcsv($output, []);

// Column headers with currency code
fputcsv($output, ['Name', 'Email', 'Phone', 'Country', 'City', 'Gender', 'Joined', 'Bookings (Period)', 'Total Spent (' . CURRENCY_CODE . ')']);

// === DATA ROWS ===
foreach ($customers as $c) {
    fputcsv($output, [
        $c['full_name'],
        $c['email'],
        $c['phone'] ?? '',       // Nullable fields use empty string fallback
        $c['country'] ?? '',
        $c['city'] ?? '',
        ucfirst($c['gender'] ?? ''),  // Capitalize gender for display
        date('Y-m-d', strtotime($c['joined'])),
        $c['booking_count'],
        number_format($c['total_spent'], 2)  // Format currency with 2 decimals
    ]);
}

// === SUMMARY ROW ===
fputcsv($output, []);
fputcsv($output, ['Total New Users', count($customers)]);

// === FINALIZE ===
fclose($output);
exit;
