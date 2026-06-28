<?php
/**
 * File: admin/export-sales-excel.php
 * Purpose: Export sales report as a styled Excel (XLSX) file with summary and detail sheets.
 * Dependencies: admin/includes/header.php, vendor/autoload.php (PhpSpreadsheet), config/database.php, config/currency.php
 * Used By: Admin users via reports.php Excel export button
 * Parent Files: admin/reports.php (linked from export buttons)
 * Child Files: admin/includes/header.php (for auth and session)
 * @package GlobeTrek\Admin
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../vendor/autoload.php';

// === PHPSPREADSHEET IMPORTS ===
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// === ADMIN-ONLY ACCESS ===
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// === DATE RANGE FILTER ===
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

// === CREATE SPREADSHEET ===
$spreadsheet = new Spreadsheet();

// === SUMMARY SHEET ===
// First sheet contains KPI metrics for quick overview
$summary = $spreadsheet->getActiveSheet();
$summary->setTitle('Summary');

// === TITLE HEADER ===
// Merge cells for wide title and apply styling
$summary->mergeCells('A1:E1');
$summary->setCellValue('A1', 'GlobeTrek Adventures — Sales Report');
$summary->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$summary->getStyle('A1')->getAlignment()->setHorizontal('center');

// Subtitle with date range
$summary->mergeCells('A2:E2');
$summary->setCellValue('A2', 'Period: ' . date('d M Y', strtotime($dateFrom)) . ' — ' . date('d M Y', strtotime($dateTo)));
$summary->getStyle('A2')->getAlignment()->setHorizontal('center');

// === CALCULATE KPIs ===
// Total revenue from confirmed bookings
$r = $db->prepare("SELECT COALESCE(SUM(total_price), 0) AS total FROM bookings WHERE status = 'confirmed' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$totalRevenue = (float)$r->fetch()['total'];

// Confirmed booking count
$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE status = 'confirmed' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$totalBookings = (int)$r->fetch()['cnt'];

// Cancelled booking count
$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE status = 'cancelled' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$cancelled = (int)$r->fetch()['cnt'];

// Total bookings (all statuses)
$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$allBookings = (int)$r->fetch()['cnt'];

// === DERIVED METRICS ===
$avgValue = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;
$cancelRate = $allBookings > 0 ? round(($cancelled / $allBookings) * 100, 1) : 0;

// === KPI DATA ARRAY ===
// Structured for easy iteration when writing to cells
$kpis = [
    ['Metric', 'Value'],
    ['Total Revenue', formatPrice($totalRevenue, 2)],
    ['Confirmed Bookings', $totalBookings],
    ['Average Booking Value', formatPrice($avgValue, 2)],
    ['Cancellation Rate', $cancelRate . '%'],
];

// === HEADER STYLE ===
// Reusable style for table headers: white text on dark background
$styleHeader = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
    'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF264653']],
    'alignment' => ['horizontal' => 'center'],
];

// === WRITE KPI ROWS ===
// Row 1 is header, rows 2+ are data; start at row 4 to leave space for title
foreach ($kpis as $rowIdx => $row) {
    $rowNum = $rowIdx + 4;
    foreach ($row as $colIdx => $val) {
        $summary->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $val);
    }
    // Apply header style only to the header row (rowIdx 0)
    if ($rowIdx === 0) {
        $summary->getStyle("A{$rowNum}:B{$rowNum}")->applyFromArray($styleHeader);
    }
}

// === DETAIL SHEET ===
// Second sheet contains individual booking records
$detail = $spreadsheet->createSheet();
$detail->setTitle('Detail');

// === COLUMN HEADERS ===
$headers = ['Booking Ref', 'Customer', 'Email', 'Package', 'Travellers', 'Amount', 'Status', 'Payment', 'Date'];
foreach ($headers as $colIdx => $header) {
    $detail->setCellValueByColumnAndRow($colIdx + 1, 1, $header);
}
// Apply header styling to first row
$detail->getStyle('A1:I1')->applyFromArray($styleHeader);

// === FETCH DETAIL DATA ===
// Query with first_name/last_name from bookings table (not users table)
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

// === WRITE DATA ROWS ===
// Populate detail sheet with booking records starting at row 2
$rowNum = 2;
foreach ($rows as $row) {
    $detail->setCellValueByColumnAndRow(1, $rowNum, $row['booking_reference']);
    // Concatenate first and last name for full customer name
    $detail->setCellValueByColumnAndRow(2, $rowNum, $row['first_name'] . ' ' . $row['last_name']);
    $detail->setCellValueByColumnAndRow(3, $rowNum, $row['email']);
    $detail->setCellValueByColumnAndRow(4, $rowNum, $row['package_title']);
    // Cast to appropriate types for Excel formatting
    $detail->setCellValueByColumnAndRow(5, $rowNum, (int)$row['num_travellers']);
    $detail->setCellValueByColumnAndRow(6, $rowNum, (float)$row['total_price']);
    $detail->setCellValueByColumnAndRow(7, $rowNum, ucfirst($row['status']));
    // Format payment method: replace underscores with spaces
    $detail->setCellValueByColumnAndRow(8, $rowNum, ucfirst(str_replace('_', ' ', $row['payment_method'] ?? '')));
    $detail->setCellValueByColumnAndRow(9, $rowNum, date('d M Y', strtotime($row['created_at'])));
    $rowNum++;
}

// === AUTO-FIT COLUMN WIDTHS ===
// Set each column to auto-size based on content
foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $col) {
    $detail->getColumnDimension($col)->setAutoSize(true);
}

// === OUTPUT XLSX FILE ===
// Force browser download with proper MIME type
$filename = 'GlobeTrek_Sales_Report_' . $dateFrom . '_to_' . $dateTo . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
// No-cache headers ensure fresh export each time
header('Cache-Control: no-cache, no-store, must-revalidate');

// Write directly to output stream (no temp file needed)
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
