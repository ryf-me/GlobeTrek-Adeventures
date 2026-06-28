<?php
/**
 * File: admin/export-customers-pdf.php
 * Purpose: Export customer report data as a styled PDF document using Dompdf.
 * Dependencies: vendor/autoload.php (Dompdf), config/database.php, config/currency.php
 * Used By: Admin users via customer-reports.php PDF export button
 * Parent Files: admin/customer-reports.php (linked from export buttons)
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

// === FETCH CUSTOMER DATA ===
// Same query structure as export-customers.php for consistency
$r = $db->prepare(
    "SELECT u.full_name, u.email, u.phone, u.country, u.city, u.gender, u.created_at,
            COUNT(b.id) AS booking_count, COALESCE(SUM(b.total_price), 0) AS total_spent
     FROM users u
     LEFT JOIN bookings b ON u.id = b.user_id AND b.status = 'confirmed' AND b.created_at BETWEEN :bfrom AND :bto
     WHERE u.created_at BETWEEN :ufrom AND :uto
     GROUP BY u.id, u.full_name, u.email, u.phone, u.country, u.city, u.gender, u.created_at
     ORDER BY total_spent DESC"
);
$r->execute([':bfrom' => $dateFrom . ' 00:00:00', ':bto' => $dateTo . ' 23:59:59', ':ufrom' => $dateFrom . ' 00:00:00', ':uto' => $dateTo . ' 23:59:59']);
$customers = $r->fetchAll();

// === BUILD HTML DOCUMENT ===
// Complete HTML with inline CSS for Dompdf rendering
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
<h1>GlobeTrek - Customer Report</h1>
<div class="summary">
<p><strong>Period:</strong> ' . htmlspecialchars($dateFrom) . ' to ' . htmlspecialchars($dateTo) . '</p>
<p><strong>New Users:</strong> ' . count($customers) . '</p>
</div>
<h2>Customer Details</h2>
<table>
<tr><th>Name</th><th>Email</th><th>Country</th><th>Gender</th><th>Joined</th><th>Bookings</th><th>Spent</th></tr>';

// === DATA ROWS ===
// Append each customer as a table row
foreach ($customers as $c) {
    $html .= '<tr>
        <td>' . htmlspecialchars($c['full_name']) . '</td>
        <td>' . htmlspecialchars($c['email']) . '</td>
        <td>' . htmlspecialchars($c['country'] ?? '—') . '</td>
        <td>' . htmlspecialchars(ucfirst($c['gender'] ?? '—')) . '</td>
        <td>' . date('Y-m-d', strtotime($c['created_at'])) . '</td>
        <td>' . $c['booking_count'] . '</td>
        <td>' . formatPrice($c['total_spent'], 2) . '</td>
    </tr>';
}

// === FOOTER ===
$html .= '</table>
<div class="footer">Generated on ' . date('d M Y, h:i A') . ' - GlobeTrek Admin</div>
</body></html>';

// === DOMPDF CONFIGURATION ===
$options = new Options();
$options->set('isHtml5ParserEnabled', true);   // HTML5 parser support
$options->set('isRemoteEnabled', false);        // Security: block external resources
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
// Landscape A4 for wider table layout
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// === OUTPUT PDF ===
$filename = 'customer-report-' . $dateFrom . '-to-' . $dateTo . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
// Prevent caching of dynamically generated report
header('Cache-Control: no-cache, must-revalidate');
echo $dompdf->output();
exit;
