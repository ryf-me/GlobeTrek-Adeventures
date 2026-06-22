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
    "SELECT u.full_name, u.email, u.phone, u.country, u.city, u.gender, u.created_at AS joined,
            COUNT(b.id) AS booking_count, COALESCE(SUM(b.total_price), 0) AS total_spent
     FROM users u
     LEFT JOIN bookings b ON u.id = b.user_id AND b.status = 'confirmed' AND b.created_at BETWEEN :from AND :to
     WHERE u.created_at BETWEEN :from AND :to
     GROUP BY u.id, u.full_name, u.email, u.phone, u.country, u.city, u.gender, u.created_at
     ORDER BY total_spent DESC"
);
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$customers = $r->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customer-report-' . $dateFrom . '-to-' . $dateTo . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['Customer Report']);
fputcsv($output, ['Date From', $dateFrom, '', 'Date To', $dateTo]);
fputcsv($output, []);

fputcsv($output, ['Name', 'Email', 'Phone', 'Country', 'City', 'Gender', 'Joined', 'Bookings (Period)', 'Total Spent (Rs.)']);

foreach ($customers as $c) {
    fputcsv($output, [
        $c['full_name'],
        $c['email'],
        $c['phone'] ?? '',
        $c['country'] ?? '',
        $c['city'] ?? '',
        ucfirst($c['gender'] ?? ''),
        date('Y-m-d', strtotime($c['joined'])),
        $c['booking_count'],
        number_format($c['total_spent'], 2)
    ]);
}

fputcsv($output, []);
fputcsv($output, ['Total New Users', count($customers)]);

fclose($output);
exit;
