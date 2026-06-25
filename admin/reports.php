<?php
$pageTitle = 'Sales Reports';
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

$stats = [];

$r = $db->prepare("SELECT COALESCE(SUM(total_price), 0) AS total FROM bookings WHERE status = 'confirmed' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$stats['total_revenue'] = (float)$r->fetch()['total'];

$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE status = 'confirmed' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$stats['total_bookings'] = (int)$r->fetch()['cnt'];

$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE status = 'pending' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$stats['pending_bookings'] = (int)$r->fetch()['cnt'];

$r = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE status = 'cancelled' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$stats['cancelled_bookings'] = (int)$r->fetch()['cnt'];

$stats['avg_booking_value'] = $stats['total_bookings'] > 0 ? $stats['total_revenue'] / $stats['total_bookings'] : 0;

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

$r = $db->prepare(
    "SELECT DATE(b.created_at) AS date, COUNT(*) AS cnt, SUM(b.total_price) AS revenue
     FROM bookings b
     WHERE b.status = 'confirmed' AND b.created_at BETWEEN :from AND :to
     GROUP BY DATE(b.created_at)
     ORDER BY date ASC"
);
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$byDate = $r->fetchAll();
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Sales Reports</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="export-sales.php?from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>" class="adm-btn adm-btn-secondary" target="_blank"><span class="material-symbols-outlined">download</span> CSV</a>
            <a href="export-sales-pdf.php?from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>" class="adm-btn adm-btn-secondary"><span class="material-symbols-outlined">picture_as_pdf</span> PDF</a>
        </div>
    </div>

    <div class="adm-content">
        <!-- Date Filter -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex; gap:0.5rem; align-items:center;">
                <label style="font-weight:500;">From:</label>
                <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>" style="padding:0.5rem; border:1px solid var(--adm-outline-variant); border-radius:6px;">
                <label style="font-weight:500;">To:</label>
                <input type="date" name="to" value="<?= htmlspecialchars($dateTo) ?>" style="padding:0.5rem; border:1px solid var(--adm-outline-variant); border-radius:6px;">
                <button type="submit" class="adm-btn adm-btn-primary" style="padding:0.5rem 1rem;">Filter</button>
            </form>
        </div>

        <!-- KPI Cards -->
        <div class="adm-stats">
            <div class="adm-stat-card">
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num">Rs.<?= number_format($stats['total_revenue'], 2) ?></div>
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
                    <div class="adm-stat-card-num">Rs.<?= number_format($stats['avg_booking_value'], 2) ?></div>
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
                    <div class="adm-stat-card-num"><?= $stats['cancelled_bookings'] ?></div>
                    <div class="adm-stat-card-label">Cancelled Bookings</div>
                </div>
            </div>
        </div>

        <!-- Revenue by Package -->
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
                                    <td class="cell-mono">Rs.<?= number_format($pkg['revenue'], 2) ?></td>
                                    <td><?= $stats['total_revenue'] > 0 ? round(($pkg['revenue'] / $stats['total_revenue']) * 100, 1) . '%' : '0%' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Revenue by Date -->
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
                                    <td class="cell-main"><?= date('d M Y', strtotime($day['date'])) ?></td>
                                    <td><?= $day['cnt'] ?></td>
                                    <td class="cell-mono">Rs.<?= number_format($day['revenue'], 2) ?></td>
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
