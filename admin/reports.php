<?php
/**
 * File: admin/reports.php
 * Purpose: Admin sales reports page with KPIs, revenue by package, top destinations, and daily breakdown.
 * Dependencies: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php, config/database.php, config/currency.php
 * Used By: Admin users navigating via admin sidebar; linked from export-sales.php, export-sales-pdf.php, export-sales-excel.php
 * Parent Files: None (entry point for sales reports)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

// === PAGE SETUP ===
$pageTitle = 'Sales Reports';
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

// === DATE RANGE FILTER ===
// Default to current month start through today; accept override from query string
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

// === KPI STATISTICS ===
$stats = [];

// Total revenue from confirmed bookings within date range
$r = $db->prepare("SELECT COALESCE(SUM(total_price), 0) AS total FROM bookings WHERE status = 'confirmed' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$stats['total_revenue'] = (float)$r->fetch()['total'];

// Count of confirmed bookings
$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE status = 'confirmed' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$stats['total_bookings'] = (int)$r->fetch()['cnt'];

// Count of pending bookings (awaiting confirmation)
$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE status = 'pending' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$stats['pending_bookings'] = (int)$r->fetch()['cnt'];

// Count of cancelled bookings
$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE status = 'cancelled' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$stats['cancelled_bookings'] = (int)$r->fetch()['cnt'];

// Total bookings (all statuses) for cancellation rate calculation
$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$stats['all_bookings'] = (int)$r->fetch()['cnt'];

// === CALCULATED METRICS ===
// Average booking value = revenue / confirmed bookings (guard against division by zero)
$stats['avg_booking_value'] = $stats['total_bookings'] > 0 ? $stats['total_revenue'] / $stats['total_bookings'] : 0;
// Cancellation rate = (cancelled / total) * 100, rounded to 1 decimal
$stats['cancellation_rate'] = $stats['all_bookings'] > 0 ? round(($stats['cancelled_bookings'] / $stats['all_bookings']) * 100, 1) : 0;

// === REVENUE BY PACKAGE ===
// Aggregate confirmed booking revenue grouped by travel package
$r = $db->prepare(
    "SELECT p.title, COUNT(b.id) AS booking_count, SUM(b.total_price) AS revenue
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     WHERE b.status = 'confirmed' AND b.created_at BETWEEN :from AND :to
     GROUP BY b.package_id, p.title
     ORDER BY revenue DESC"
);
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$byPackage = $r->fetchAll();

// === DAILY REVENUE BREAKDOWN ===
// Revenue and booking count per day within the date range
$r = $db->prepare(
    "SELECT DATE(b.created_at) AS date, COUNT(*) AS cnt, SUM(b.total_price) AS revenue
     FROM bookings b
     WHERE b.status = 'confirmed' AND b.created_at BETWEEN :from AND :to
     GROUP BY DATE(b.created_at)
     ORDER BY date ASC"
);
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$byDate = $r->fetchAll();

// === TOP DESTINATIONS ===
// Top 5 destinations by booking count (limited for dashboard readability)
$r = $db->prepare(
    "SELECT p.destination_category, COUNT(b.id) AS booking_count, SUM(b.total_price) AS revenue
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     WHERE b.status = 'confirmed' AND b.created_at BETWEEN :from AND :to
     GROUP BY p.destination_category
     ORDER BY booking_count DESC
     LIMIT 5"
);
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$topDestinations = $r->fetchAll();


?>

<!-- === ADMIN LAYOUT === -->
<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Sales Reports</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <!-- Export links pass current date range to export scripts -->
            <a href="export-sales.php?from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>" class="adm-btn adm-btn-secondary" target="_blank"><span class="material-symbols-outlined">download</span> CSV</a>
            <a href="export-sales-pdf.php?from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>" class="adm-btn adm-btn-secondary"><span class="material-symbols-outlined">picture_as_pdf</span> PDF</a>
            <a href="export-sales-excel.php?from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>" class="adm-btn adm-btn-secondary"><span class="material-symbols-outlined">table_view</span> Excel</a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === DATE FILTER === -->
        <!-- Date range form allows filtering reports by custom period -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex; gap:0.5rem; align-items:center;">
                <label style="font-weight:500;">From:</label>
                <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>" style="padding:0.5rem; border:1px solid var(--adm-outline-variant); border-radius:6px;">
                <label style="font-weight:500;">To:</label>
                <input type="date" name="to" value="<?= htmlspecialchars($dateTo) ?>" style="padding:0.5rem; border:1px solid var(--adm-outline-variant); border-radius:6px;">
                <button type="submit" class="adm-btn adm-btn-primary" style="padding:0.5rem 1rem;">Filter</button>
            </form>
        </div>

        <!-- === KPI CARDS === -->
        <!-- Key performance indicators displayed as stat cards -->
        <div class="adm-stats">
            <div class="adm-stat-card">
                <div class="adm-stat-card-info">
                    <!-- formatPrice is a helper function for currency formatting -->
                    <div class="adm-stat-card-num"><?= formatPrice($stats['total_revenue'], 2) ?></div>
                    <div class="adm-stat-card-label">Total Revenue</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= $stats['total_bookings'] ?></div>
                    <div class="adm-stat-card-label">Confirmed Bookings</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= formatPrice($stats['avg_booking_value'], 2) ?></div>
                    <div class="adm-stat-card-label">Average Booking Value</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= $stats['pending_bookings'] ?></div>
                    <div class="adm-stat-card-label">Pending Bookings</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= $stats['cancellation_rate'] ?>%</div>
                    <div class="adm-stat-card-label">Cancellation Rate</div>
                </div>
            </div>
        </div>



        <!-- === TOP DESTINATIONS TABLE === -->
        <div class="adm-form-card">
            <h2>Top Destinations</h2>
            <?php if (empty($topDestinations)): ?>
                <div class="adm-empty">
                    <span class="material-symbols-outlined adm-empty-icon">travel_explore</span>
                    <h2>No data for this period</h2>
                </div>
            <?php else: ?>
                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>Destination</th>
                                <th>Bookings</th>
                                <th>Revenue</th>
                                <!-- Percentage of total revenue column -->
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topDestinations as $dest): ?>
                                <tr>
                                    <!-- Fallback to 'Uncategorized' if destination_category is empty/null -->
                                    <td class="cell-main"><?= htmlspecialchars($dest['destination_category'] ?: 'Uncategorized') ?></td>
                                    <td><?= $dest['booking_count'] ?></td>
                                    <td class="cell-mono"><?= formatPrice($dest['revenue'], 2) ?></td>
                                    <!-- Calculate percentage share of total revenue; guard against division by zero -->
                                    <td><?= $stats['total_revenue'] > 0 ? round(($dest['revenue'] / $stats['total_revenue']) * 100, 1) . '%' : '0%' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- === REVENUE BY PACKAGE TABLE === -->
        <div class="adm-form-card">
            <h2>Revenue by Package</h2>
            <?php if (empty($byPackage)): ?>
                <div class="adm-empty">
                    <span class="material-symbols-outlined adm-empty-icon">luggage</span>
                    <h2>No data for this period</h2>
                    <p>No confirmed bookings found in the selected date range.</p>
                </div>
            <?php else: ?>
                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th>Bookings</th>
                                <th>Revenue</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($byPackage as $pkg): ?>
                                <tr>
                                    <td class="cell-main"><?= htmlspecialchars($pkg['title']) ?></td>
                                    <td><?= $pkg['booking_count'] ?></td>
                                    <td class="cell-mono"><?= formatPrice($pkg['revenue'], 2) ?></td>
                                    <td><?= $stats['total_revenue'] > 0 ? round(($pkg['revenue'] / $stats['total_revenue']) * 100, 1) . '%' : '0%' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- === DAILY REVENUE TABLE === -->
        <div class="adm-form-card">
            <h2>Daily Revenue</h2>
            <?php if (empty($byDate)): ?>
                <div class="adm-empty">
                    <span class="material-symbols-outlined adm-empty-icon">calendar_today</span>
                    <h2>No data for this period</h2>
                </div>
            <?php else: ?>
                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Bookings</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($byDate as $day): ?>
                                <tr>
                                    <!-- Format date string to human-readable format -->
                                    <td class="cell-main"><?= date('d M Y', strtotime($day['date'])) ?></td>
                                    <td><?= $day['cnt'] ?></td>
                                    <td class="cell-mono"><?= formatPrice($day['revenue'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
