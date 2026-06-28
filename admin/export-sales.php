<?php
/**
 * File: admin/export-sales.php
 * Purpose: Export sales report data as CSV file for download.
 * Dependencies: session, config/database.php, config/currency.php
 * Used By: Admin users via reports.php export button
 * Parent Files: admin/reports.php (linked from export buttons)
 * Child Files: None (standalone export script)
 * @package GlobeTrek\Admin
 */

// === AUTHENTICATION CHECK ===
// Verify user is logged in and has admin role before allowing export
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
// Accept date range from query string or default to current month
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

// === FETCH BOOKINGS DATA ===
// Retrieve all bookings within date range with customer and package details
$r = $db->prepare(
    "SELECT b.booking_reference, u.full_name AS customer_name, u.email AS customer_email, p.title AS package_title,
            b.total_price, b.status, b.payment_method, b.num_travellers, b.created_at
     FROM bookings b
     LEFT JOIN users u ON b.user_id = u.id
     JOIN packages p ON b.package_id = p.id
     WHERE b.created_at BETWEEN :from AND :to
     ORDER BY b.created_at DESC"
);
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$bookings = $r->fetchAll();

// === SET CSV HEADERS ===
// Force browser to download as CSV file with UTF-8 encoding
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sales-report-' . $dateFrom . '-to-' . $dateTo . '.csv"');

// === WRITE CSV OUTPUT ===
$output = fopen('php://output', 'w');
// Add UTF-8 BOM (Byte Order Mark) for Excel compatibility on Windows
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// === CSV HEADER ROWS ===
// Report title and date range metadata
fputcsv($output, ['Sales Report', '', '', '', '', '', '', '', '']);
fputcsv($output, ['Date From', $dateFrom, '', 'Date To', $dateTo, '', '', '', '']);
fputcsv($output, []);

// Column headers with currency code from config
fputcsv($output, ['Booking Reference', 'Customer Name', 'Customer Email', 'Package', 'Travellers', 'Amount (' . CURRENCY_CODE . ')', 'Status', 'Payment Method', 'Date']);

// === DATA ROWS ===
// Write each booking as a CSV row
foreach ($bookings as $b) {
    fputcsv($output, [
        $b['booking_reference'],
        $b['customer_name'] ?? 'N/A',  // Fallback for null user names
        $b['customer_email'] ?? 'N/A',
        $b['package_title'],
        $b['num_travellers'],
        number_format($b['total_price'], 2),  // Format with 2 decimal places
        ucfirst($b['status']),  // Capitalize status for display
        ucfirst(str_replace('_', ' ', $b['payment_method'] ?? 'N/A')),  // Format payment method
        date('Y-m-d H:i', strtotime($b['created_at']))
    ]);
}

// === CALCULATE SUMMARY STATISTICS ===
// Aggregate revenue and count from confirmed bookings only
$totalRevenue = 0;
$confirmedCount = 0;
foreach ($bookings as $b) {
    if ($b['status'] === 'confirmed') {
        $totalRevenue += $b['total_price'];
        $confirmedCount++;
    }
}

// === SUMMARY ROWS ===
// Append summary section at end of CSV
fputcsv($output, []);
fputcsv($output, ['Summary']);
fputcsv($output, ['Total Bookings', count($bookings)]);
fputcsv($output, ['Confirmed Bookings', $confirmedCount]);
fputcsv($output, ['Total Revenue (' . CURRENCY_CODE . ')', number_format($totalRevenue, 2)]);

// === FINALIZE ===
fclose($output);
exit;
