<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = getDB();

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

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

$totalRevenue = 0;
$confirmedCount = 0;
foreach ($bookings as $b) {
    if ($b['status'] === 'confirmed') {
        $totalRevenue += $b['total_price'];
        $confirmedCount++;
    }
}

$pdfContent = '<!DOCTYPE html>
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
<p><strong>Total Revenue:</strong> Rs. ' . number_format($totalRevenue, 2) . '</p>
</div>
<h2>Booking Details</h2>
<table>
<tr><th>Reference</th><th>Customer</th><th>Package</th><th>Travellers</th><th>Amount</th><th>Status</th><th>Date</th></tr>';

foreach ($bookings as $b) {
    $pdfContent .= '<tr>
        <td>' . htmlspecialchars($b['booking_reference']) . '</td>
        <td>' . htmlspecialchars($b['customer_name'] ?? 'N/A') . '</td>
        <td>' . htmlspecialchars($b['package_title']) . '</td>
        <td>' . $b['num_travellers'] . '</td>
        <td>Rs. ' . number_format($b['total_price'], 2) . '</td>
        <td>' . ucfirst($b['status']) . '</td>
        <td>' . date('Y-m-d', strtotime($b['created_at'])) . '</td>
    </tr>';
}

$pdfContent .= '</table>
<div class="footer">Generated on ' . date('d M Y, h:i A') . ' - GlobeTrek Admin</div>
</body></html>';

header('Content-Type: text/html');
header('Content-Disposition: inline; filename="sales-report-' . $dateFrom . '-to-' . $dateTo . '.html"');
echo $pdfContent;
exit;
