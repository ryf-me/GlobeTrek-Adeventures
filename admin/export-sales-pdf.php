<?php
/**
 * File: admin/export-sales-pdf.php
 * Purpose: Export sales report data as a styled PDF document using Dompdf.
 * Dependencies: vendor/autoload.php (Dompdf), config/database.php, config/currency.php
 * Used By: Admin users via reports.php PDF export button
 * Parent Files: admin/reports.php (linked from export buttons)
 * Child Files: None (standalone export script)
 * @package GlobeTrek\Admin
 */

// === AUTHENTICATION CHECK ===
// Verify admin access before allowing PDF export
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// === DEPENDENCIES ===
require_once __DIR__ . '/../vendor/autoload.php'; // Dompdf autoloader
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/currency.php';
$db = getDB();

// === DOMPDF NAMESPACE IMPORTS ===
use Dompdf\Dompdf;
use Dompdf\Options;

// === DATE RANGE FILTER ===
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

// === FETCH BOOKINGS DATA ===
$r = $db->prepare(
    "SELECT b.booking_reference, u.full_name AS customer_name, p.title AS package_title,
            b.total_price, b.status, b.payment_method, b.num_travellers, b.created_at
     FROM bookings b
     LEFT JOIN users u ON b.user_id = u.id
     JOIN packages p ON b.package_id = p.id
     WHERE b.created_at BETWEEN :from AND :to
     ORDER BY b.created_at DESC"
);
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$bookings = $r->fetchAll();

// === CALCULATE SUMMARY STATISTICS ===
$totalRevenue = 0;
$confirmedCount = 0;
foreach ($bookings as $b) {
    if ($b['status'] === 'confirmed') {
        $totalRevenue += $b['total_price'];
        $confirmedCount++;
    }
}

// === BUILD HTML DOCUMENT ===
// Construct complete HTML document with inline CSS for Dompdf rendering
$html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
h1 { color: #264653; font-size: 22px; border-bottom: 2px solid #264653; padding-bottom: 8px; }
h2 { color: #333; font-size: 16px; margin-top: 20px; }
.summary { background: #f5f7fa; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
.summary p { margin: 5px 0; font-size: 13px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
th { background: #264653; color: #fff; padding: 8px 6px; text-align: left; }
td { padding: 6px; border-bottom: 1px solid #ddd; }
tr:nth-child(even) { background: #f9f9f9; }
.footer { margin-top: 20px; font-size: 10px; color: #666; text-align: center; }
</style>
</head>
<body>
<h1>GlobeTrek - Sales Report</h1>
<div class="summary">
<p><strong>Period:</strong> ' . htmlspecialchars($dateFrom) . ' to ' . htmlspecialchars($dateTo) . '</p>
<p><strong>Total Bookings:</strong> ' . count($bookings) . '</p>
<p><strong>Confirmed Bookings:</strong> ' . $confirmedCount . '</p>
<p><strong>Total Revenue:</strong> ' . formatPrice($totalRevenue, 2) . '</p>
</div>
<h2>Booking Details</h2>
<table>
<tr><th>Reference</th><th>Customer</th><th>Package</th><th>Travellers</th><th>Amount</th><th>Status</th><th>Date</th></tr>';

// === DATA ROWS ===
// Append each booking as a table row in the HTML document
foreach ($bookings as $b) {
    $html .= '<tr>
        <td>' . htmlspecialchars($b['booking_reference']) . '</td>
        <td>' . htmlspecialchars($b['customer_name'] ?? 'N/A') . '</td>
        <td>' . htmlspecialchars($b['package_title']) . '</td>
        <td>' . $b['num_travellers'] . '</td>
        <td>' . formatPrice($b['total_price'], 2) . '</td>
        <td>' . ucfirst($b['status']) . '</td>
        <td>' . date('Y-m-d', strtotime($b['created_at'])) . '</td>
    </tr>';
}

// === FOOTER ===
$html .= '</table>
<div class="footer">Generated on ' . date('d M Y, h:i A') . ' - GlobeTrek Admin</div>
</body></html>';

// === DOMPDF CONFIGURATION ===
$options = new Options();
$options->set('isHtml5ParserEnabled', true);   // Enable HTML5 parser for modern HTML
$options->set('isRemoteEnabled', false);        // Disable remote resource loading (security)
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
// Landscape orientation provides wider table layout for report data
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// === OUTPUT PDF ===
$filename = 'sales-report-' . $dateFrom . '-to-' . $dateTo . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
// Cache-Control prevents browser caching of generated report
header('Cache-Control: no-cache, must-revalidate');
echo $dompdf->output();
exit;
