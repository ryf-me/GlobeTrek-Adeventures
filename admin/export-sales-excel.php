<?php
/**
 * Export Sales Report to Excel (XLSX)
 *
 * Generates a styled Excel spreadsheet with summary + detail sheets.
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

$spreadsheet = new Spreadsheet();

// --- Summary Sheet ---
$summary = $spreadsheet->getActiveSheet();
$summary->setTitle('Summary');

// Header
$summary->mergeCells('A1:E1');
$summary->setCellValue('A1', 'GlobeTrek Adventures — Sales Report');
$summary->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$summary->getStyle('A1')->getAlignment()->setHorizontal('center');

$summary->mergeCells('A2:E2');
$summary->setCellValue('A2', 'Period: ' . date('d M Y', strtotime($dateFrom)) . ' — ' . date('d M Y', strtotime($dateTo)));
$summary->getStyle('A2')->getAlignment()->setHorizontal('center');

// KPIs
$r = $db->prepare("SELECT COALESCE(SUM(total_price), 0) AS total FROM bookings WHERE status = 'confirmed' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$totalRevenue = (float)$r->fetch()['total'];

$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE status = 'confirmed' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$totalBookings = (int)$r->fetch()['cnt'];

$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE status = 'cancelled' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$cancelled = (int)$r->fetch()['cnt'];

$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$allBookings = (int)$r->fetch()['cnt'];

$avgValue = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;
$cancelRate = $allBookings > 0 ? round(($cancelled / $allBookings) * 100, 1) : 0;

$kpis = [
    ['Metric', 'Value'],
    ['Total Revenue', 'Rs.' . number_format($totalRevenue, 2)],
    ['Confirmed Bookings', $totalBookings],
    ['Average Booking Value', 'Rs.' . number_format($avgValue, 2)],
    ['Cancellation Rate', $cancelRate . '%'],
];

$styleHeader = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
    'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF264653']],
    'alignment' => ['horizontal' => 'center'],
];

foreach ($kpis as $rowIdx => $row) {
    $rowNum = $rowIdx + 4;
    foreach ($row as $colIdx => $val) {
        $summary->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $val);
    }
    if ($rowIdx === 0) {
        $summary->getStyle("A{$rowNum}:B{$rowNum}")->applyFromArray($styleHeader);
    }
}

// --- Detail Sheet ---
$detail = $spreadsheet->createSheet();
$detail->setTitle('Detail');

// Headers
$headers = ['Booking Ref', 'Customer', 'Email', 'Package', 'Travellers', 'Amount', 'Status', 'Payment', 'Date'];
foreach ($headers as $colIdx => $header) {
    $detail->setCellValueByColumnAndRow($colIdx + 1, 1, $header);
}
$detail->getStyle('A1:I1')->applyFromArray($styleHeader);

// Data
$r = $db->prepare(
    "SELECT b.booking_reference, b.first_name, b.last_name, b.email, p.title AS package_title,
            b.num_travellers, b.total_price, b.status, b.payment_method, b.created_at
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     WHERE b.created_at BETWEEN :from AND :to
     ORDER BY b.created_at DESC"
);
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$rows = $r->fetchAll();

$rowNum = 2;
foreach ($rows as $row) {
    $detail->setCellValueByColumnAndRow(1, $rowNum, $row['booking_reference']);
    $detail->setCellValueByColumnAndRow(2, $rowNum, $row['first_name'] . ' ' . $row['last_name']);
    $detail->setCellValueByColumnAndRow(3, $rowNum, $row['email']);
    $detail->setCellValueByColumnAndRow(4, $rowNum, $row['package_title']);
    $detail->setCellValueByColumnAndRow(5, $rowNum, (int)$row['num_travellers']);
    $detail->setCellValueByColumnAndRow(6, $rowNum, (float)$row['total_price']);
    $detail->setCellValueByColumnAndRow(7, $rowNum, ucfirst($row['status']));
    $detail->setCellValueByColumnAndRow(8, $rowNum, ucfirst(str_replace('_', ' ', $row['payment_method'] ?? '')));
    $detail->setCellValueByColumnAndRow(9, $rowNum, date('d M Y', strtotime($row['created_at'])));
    $rowNum++;
}

// Auto-width
foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $col) {
    $detail->getColumnDimension($col)->setAutoSize(true);
}

// Output
$filename = 'GlobeTrek_Sales_Report_' . $dateFrom . '_to_' . $dateTo . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
