<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/currency.php';
$db = getDB();

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

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

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sales-report-' . $dateFrom . '-to-' . $dateTo . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['Sales Report', '', '', '', '', '', '', '', '']);
fputcsv($output, ['Date From', $dateFrom, '', 'Date To', $dateTo, '', '', '', '']);
fputcsv($output, []);

fputcsv($output, ['Booking Reference', 'Customer Name', 'Customer Email', 'Package', 'Travellers', 'Amount (' . CURRENCY_CODE . ')', 'Status', 'Payment Method', 'Date']);

foreach ($bookings as $b) {
    fputcsv($output, [
        $b['booking_reference'],
        $b['customer_name'] ?? 'N/A',
        $b['customer_email'] ?? 'N/A',
        $b['package_title'],
        $b['num_travellers'],
        number_format($b['total_price'], 2),
        ucfirst($b['status']),
        ucfirst(str_replace('_', ' ', $b['payment_method'] ?? 'N/A')),
        date('Y-m-d H:i', strtotime($b['created_at']))
    ]);
}

$totalRevenue = 0;
$confirmedCount = 0;
foreach ($bookings as $b) {
    if ($b['status'] === 'confirmed') {
        $totalRevenue += $b['total_price'];
        $confirmedCount++;
    }
}

fputcsv($output, []);
fputcsv($output, ['Summary']);
fputcsv($output, ['Total Bookings', count($bookings)]);
fputcsv($output, ['Confirmed Bookings', $confirmedCount]);
fputcsv($output, ['Total Revenue (' . CURRENCY_CODE . ')', number_format($totalRevenue, 2)]);

fclose($output);
exit;
