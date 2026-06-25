<?php
$pageTitle = 'Customer Reports';
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

$stats = [];

$r = $db->prepare("SELECT COUNT(*) AS cnt FROM users WHERE created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$stats['new_users'] = (int)$r->fetch()['cnt'];

$r = $db->query("SELECT COUNT(*) AS cnt FROM users");
$stats['total_users'] = (int)$r->fetch()['cnt'];

$r = $db->prepare("SELECT COUNT(DISTINCT user_id) AS cnt FROM bookings WHERE status = 'confirmed' AND created_at BETWEEN :from AND :to");
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$stats['active_bookers'] = (int)$r->fetch()['cnt'];

$r = $db->prepare(
    "SELECT u.id, u.full_name, u.email, u.country, COUNT(b.id) AS booking_count, COALESCE(SUM(b.total_price), 0) AS total_spent
     FROM users u
     LEFT JOIN bookings b ON u.id = b.user_id AND b.status = 'confirmed' AND b.created_at BETWEEN :from AND :to
     GROUP BY u.id, u.full_name, u.email, u.country
     HAVING booking_count > 0
     ORDER BY total_spent DESC
     LIMIT 10"
);
$r->execute([':from' => $dateFrom . ' 00:00:00', ':to' => $dateTo . ' 23:59:59']);
$topCustomers = $r->fetchAll();

$r = $db->prepare(
    "SELECT country, COUNT(*) AS cnt FROM users WHERE country IS NOT NULL AND country != '' GROUP BY country ORDER BY cnt DESC LIMIT 10"
);
$r->execute();
$byCountry = $r->fetchAll();

$r = $db->prepare(
    "SELECT gender, COUNT(*) AS cnt FROM users WHERE gender IS NOT NULL AND gender != '' GROUP BY gender ORDER BY cnt DESC"
);
$r->execute();
$byGender = $r->fetchAll();

$r = $db->prepare(
    "SELECT DATE(created_at) AS date, COUNT(*) AS cnt FROM users WHERE created_at BETWEEN :from AND :to GROUP BY DATE(created_at) ORDER BY date ASC"
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
            <h1 class="adm-topbar-title">Customer Reports</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="export-customers.php?from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>" class="adm-btn adm-btn-secondary" target="_blank"><span class="material-symbols-outlined">download</span> CSV</a>
            <a href="export-customers-pdf.php?from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>" class="adm-btn adm-btn-secondary"><span class="material-symbols-outlined">picture_as_pdf</span> PDF</a>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex; gap:0.5rem; align-items:center;">
                <label style="font-weight:500;">From:</label>
                <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>" style="padding:0.5rem; border:1px solid var(--adm-outline-variant); border-radius:6px;">
                <label style="font-weight:500;">To:</label>
                <input type="date" name="to" value="<?= htmlspecialchars($dateTo) ?>" style="padding:0.5rem; border:1px solid var(--adm-outline-variant); border-radius:6px;">
                <button type="submit" class="adm-btn adm-btn-primary" style="padding:0.5rem 1rem;">Filter</button>
            </form>
        </div>

        <div class="adm-stats">
            <div class="adm-stat-card">
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= $stats['total_users'] ?></div>
                    <div class="adm-stat-card-label">Total Users</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= $stats['new_users'] ?></div>
                    <div class="adm-stat-card-label">New Users (Period)</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= $stats['active_bookers'] ?></div>
                    <div class="adm-stat-card-label">Active Bookers (Period)</div>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="adm-form-card">
            <h2>Top Customers by Spending</h2>
            <?php if (empty($topCustomers)): ?>
                <div class="adm-empty">
                    <span class="material-symbols-outlined adm-empty-icon">group</span>
                    <h2>No booking data</h2>
                    <p>No customers with confirmed bookings in this period.</p>
                </div>
            <?php else: ?>
                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Country</th>
                                <th>Bookings</th>
                                <th>Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topCustomers as $c): ?>
                                <tr>
                                    <td class="cell-main"><?= htmlspecialchars($c['full_name']) ?></td>
                                    <td><?= htmlspecialchars($c['email']) ?></td>
                                    <td><?= htmlspecialchars($c['country'] ?? '—') ?></td>
                                    <td><?= $c['booking_count'] ?></td>
                                    <td class="cell-mono">Rs.<?= number_format($c['total_spent'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Demographics -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
            <div class="adm-form-card">
                <h2>Users by Country</h2>
                <?php if (empty($byCountry)): ?>
                    <p style="color:var(--adm-secondary);">No data available.</p>
                <?php else: ?>
                    <div class="adm-table-wrap">
                        <table class="adm-table">
                            <thead><tr><th>Country</th><th>Users</th></tr></thead>
                            <tbody>
                                <?php foreach ($byCountry as $c): ?>
                                    <tr>
                                        <td class="cell-main"><?= htmlspecialchars($c['country']) ?></td>
                                        <td><?= $c['cnt'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="adm-form-card">
                <h2>Users by Gender</h2>
                <?php if (empty($byGender)): ?>
                    <p style="color:var(--adm-secondary);">No data available.</p>
                <?php else: ?>
                    <div class="adm-table-wrap">
                        <table class="adm-table">
                            <thead><tr><th>Gender</th><th>Users</th></tr></thead>
                            <tbody>
                                <?php foreach ($byGender as $g): ?>
                                    <tr>
                                        <td class="cell-main"><?= htmlspecialchars(ucfirst($g['gender'])) ?></td>
                                        <td><?= $g['cnt'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- User Registrations by Date -->
        <div class="adm-form-card">
            <h2>New User Registrations</h2>
            <?php if (empty($byDate)): ?>
                <div class="adm-empty">
                    <span class="material-symbols-outlined adm-empty-icon">person_add</span>
                    <h2>No registrations</h2>
                </div>
            <?php else: ?>
                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead><tr><th>Date</th><th>New Users</th></tr></thead>
                        <tbody>
                            <?php foreach ($byDate as $d): ?>
                                <tr>
                                    <td class="cell-main"><?= date('d M Y', strtotime($d['date'])) ?></td>
                                    <td><?= $d['cnt'] ?></td>
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
