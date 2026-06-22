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
    "SELECT u.full_name, u.email, u.phone, u.country, u.city, u.gender, u.created_at,
            COUNT(b.id) AS booking_count, COALESCE(SUM(b.total_price), 0) AS total_spent
     FROM users u
     LEFT JOIN bookings b ON u.id = b.user_id AND b.status = 'confirmed' AND b.created_at BETWEEN :from AND :to
     WHERE u.created_at BETWEEN :from AND :to
     GROUP BY u.id, u.full_name, u.email, u.phone, u.country, u.city, u.gender, u.created_at
     ORDER BY total_spent DESC"
);
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$customers = $r->fetchAll();

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
<h1>GlobeTrek - Customer Report</h1>
<div class="summary">
<p><strong>Period:</strong> ' . htmlspecialchars($dateFrom) . ' to ' . htmlspecialchars($dateTo) . '</p>
<p><strong>New Users:</strong> ' . count($customers) . '</p>
</div>
<h2>Customer Details</h2>
<table>
<tr><th>Name</th><th>Email</th><th>Country</th><th>Gender</th><th>Joined</th><th>Bookings</th><th>Spent</th></tr>';

foreach ($customers as $c) {
    $pdfContent .= '<tr>
        <td>' . htmlspecialchars($c['full_name']) . '</td>
        <td>' . htmlspecialchars($c['email']) . '</td>
        <td>' . htmlspecialchars($c['country'] ?? '—') . '</td>
        <td>' . htmlspecialchars(ucfirst($c['gender'] ?? '—')) . '</td>
        <td>' . date('Y-m-d', strtotime($c['created_at'])) . '</td>
        <td>' . $c['booking_count'] . '</td>
        <td>Rs. ' . number_format($c['total_spent'], 2) . '</td>
    </tr>';
}

$pdfContent .= '</table>
<div class="footer">Generated on ' . date('d M Y, h:i A') . ' - GlobeTrek Admin</div>
</body></html>';

header('Content-Type: text/html');
header('Content-Disposition: inline; filename="customer-report-' . $dateFrom . '-to-' . $dateTo . '.html"');
echo $pdfContent;
exit;
