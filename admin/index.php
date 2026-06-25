<?php
/**
 * Admin Dashboard
 *
 * Displays key performance indicators (users, bookings, revenue, etc.),
 * Chart.js visualizations (revenue trends, booking status, user growth),
 * and recent activity tables for bookings and inquiries.
 * Staff members are redirected to staff-dashboard.php.
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

// Redirect staff to their own dashboard
if (($_SESSION['user_role'] ?? '') === 'staff') {
    header('Location: staff-dashboard.php');
    exit;
}

include __DIR__ . '/includes/sidebar.php';

// --- KPIs ---
$stats = [];
$res = $db->query("SELECT COUNT(*) AS cnt FROM users");
$stats['users'] = (int)$res->fetch()['cnt'];

$res = $db->query("SELECT COUNT(*) AS cnt FROM packages WHERE is_active = 1");
$stats['packages'] = (int)$res->fetch()['cnt'];

$res = $db->query("SELECT COUNT(*) AS cnt FROM bookings");
$stats['bookings'] = (int)$res->fetch()['cnt'];

$res = $db->query("SELECT COALESCE(SUM(total_price), 0) AS total FROM bookings WHERE status = 'confirmed'");
$stats['revenue'] = (float)$res->fetch()['total'];

$res = $db->query("SELECT COUNT(*) AS cnt FROM inquiries WHERE status IN ('open','waiting_for_response','under_review')");
$stats['open_inquiries'] = (int)$res->fetch()['cnt'];

$res = $db->query("SELECT COUNT(*) AS cnt FROM custom_trip_requests WHERE status = 'pending'");
$stats['pending_trips'] = (int)$res->fetch()['cnt'];

$res = $db->query("SELECT COUNT(*) AS cnt FROM contact_messages WHERE is_read = 0");
$stats['unread_contacts'] = (int)$res->fetch()['cnt'];

$res = $db->query("SELECT COUNT(*) AS cnt FROM newsletter_subscriptions WHERE is_active = 1");
$stats['subscribers'] = (int)$res->fetch()['cnt'];

$res = $db->query("SELECT COUNT(*) AS cnt FROM staff_profiles");
$stats['total_staff'] = (int)$res->fetch()['cnt'];

$res = $db->query("SELECT COUNT(*) AS cnt FROM staff_profiles WHERE is_available = 1");
$stats['available_staff'] = (int)$res->fetch()['cnt'];

$res = $db->query("SELECT COUNT(*) AS cnt FROM staff_assignments");
$stats['total_assignments'] = (int)$res->fetch()['cnt'];

$res = $db->query("SELECT COUNT(*) AS cnt FROM staff_assignments WHERE entity_type = 'booking'");
$stats['booking_assignments'] = (int)$res->fetch()['cnt'];

$res = $db->query("SELECT COUNT(*) AS cnt FROM staff_assignments WHERE entity_type = 'inquiry'");
$stats['inquiry_assignments'] = (int)$res->fetch()['cnt'];

// --- Recent Bookings ---
$recentBookings = $db->query(
    "SELECT b.*, u.full_name AS user_name, p.title AS package_title
     FROM bookings b
     LEFT JOIN users u ON b.user_id = u.id
     LEFT JOIN packages p ON b.package_id = p.id
     ORDER BY b.created_at DESC LIMIT 5"
)->fetchAll();

// --- Recent Inquiries ---
$recentInquiries = $db->query(
    "SELECT i.*, u.full_name AS user_name
     FROM inquiries i
     LEFT JOIN users u ON i.user_id = u.id
     ORDER BY i.created_at DESC LIMIT 5"
)->fetchAll();

// --- Revenue by month (last 6 months) ---
$revenueData = $db->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(total_price) AS revenue, COUNT(*) AS cnt
     FROM bookings WHERE status = 'confirmed'
     GROUP BY month ORDER BY month DESC LIMIT 6"
)->fetchAll();
$revenueData = array_reverse($revenueData);

// --- Bookings by status ---
$bookingStatus = $db->query(
    "SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status"
)->fetchAll();
$bookingStatusMap = [];
foreach ($bookingStatus as $row) { $bookingStatusMap[$row['status']] = (int)$row['cnt']; }

// --- Users registered over time (last 6 months) ---
$userGrowth = $db->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS cnt
     FROM users GROUP BY month ORDER BY month DESC LIMIT 6"
)->fetchAll();
$userGrowth = array_reverse($userGrowth);
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Dashboard</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="../index.php" class="adm-topbar-link" target="_blank">
                <span class="material-symbols-outlined">open_in_new</span>
                <span>View Site</span>
            </a>
            <a href="logout.php" class="adm-topbar-link">
                <span class="material-symbols-outlined">logout</span>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-stat-grid">
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">group</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= number_format($stats['users']) ?></div>
                    <div class="adm-stat-card-label">Total Users</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">flight_takeoff</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= number_format($stats['bookings']) ?></div>
                    <div class="adm-stat-card-label">Total Bookings</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">payments</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num">Rs.<?= number_format($stats['revenue'], 2) ?></div>
                    <div class="adm-stat-card-label">Revenue</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">luggage</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= number_format($stats['packages']) ?></div>
                    <div class="adm-stat-card-label">Active Packages</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">chat</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= number_format($stats['open_inquiries']) ?></div>
                    <div class="adm-stat-card-label">Open Inquiries</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">route</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= number_format($stats['pending_trips']) ?></div>
                    <div class="adm-stat-card-label">Pending Custom Trips</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">mail</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= number_format($stats['unread_contacts']) ?></div>
                    <div class="adm-stat-card-label">Unread Messages</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">campaign</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= number_format($stats['subscribers']) ?></div>
                    <div class="adm-stat-card-label">Newsletter Subscribers</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">badge</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= number_format($stats['total_staff']) ?></div>
                    <div class="adm-stat-card-label">Total Staff</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">check_circle</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= number_format($stats['available_staff']) ?></div>
                    <div class="adm-stat-card-label">Available Staff</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">assignment_ind</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= number_format($stats['total_assignments']) ?></div>
                    <div class="adm-stat-card-label">Active Assignments</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">flight_takeoff</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= number_format($stats['booking_assignments']) ?></div>
                    <div class="adm-stat-card-label">Booking Assignments</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">chat</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= number_format($stats['inquiry_assignments']) ?></div>
                    <div class="adm-stat-card-label">Inquiry Assignments</div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="adm-chart-grid">
            <div class="adm-chart-card">
                <h3>Revenue (Last 6 Months)</h3>
                <canvas id="revenueChart"></canvas>
            </div>
            <div class="adm-chart-card">
                <h3>Bookings by Status</h3>
                <canvas id="bookingsChart"></canvas>
            </div>
            <div class="adm-chart-card">
                <h3>User Registrations (Last 6 Months)</h3>
                <canvas id="usersChart"></canvas>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="adm-page-header">
            <h2>Recent Bookings</h2>
            <a href="bookings.php" class="adm-btn adm-btn-secondary">View All</a>
        </div>
        <?php if (empty($recentBookings)): ?>
            <div class="adm-empty" style="padding:2rem;">
                <p>No bookings yet.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap" style="margin-bottom:2rem;">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>User</th>
                            <th>Package</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $b): ?>
                            <tr>
                                <td class="cell-mono"><?= htmlspecialchars($b['booking_reference']) ?></td>
                                <td class="cell-main"><?= htmlspecialchars($b['user_name'] ?? 'Guest') ?></td>
                                <td><?= htmlspecialchars($b['package_title'] ?? 'N/A') ?></td>
                                <td class="cell-mono">Rs.<?= number_format($b['total_price'], 2) ?></td>
                                <td>
                                    <span class="adm-status-badge adm-status-<?= $b['status'] ?>">
                                        <span class="adm-badge-dot"></span>
                                        <?= ucfirst(htmlspecialchars($b['status'])) ?>
                                    </span>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($b['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Recent Inquiries -->
        <div class="adm-page-header">
            <h2>Recent Inquiries</h2>
            <a href="inquiries.php" class="adm-btn adm-btn-secondary">View All</a>
        </div>
        <?php if (empty($recentInquiries)): ?>
            <div class="adm-empty" style="padding:2rem;">
                <p>No inquiries yet.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentInquiries as $inq): ?>
                            <tr>
                                <td class="cell-mono"><?= htmlspecialchars($inq['inquiry_id_code']) ?></td>
                                <td class="cell-main"><?= htmlspecialchars($inq['user_name'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($inq['subject']) ?></td>
                                <td>
                                    <?php
                                        $sClass = 'open'; $sLabel = 'Open';
                                        if ($inq['status'] === 'waiting_for_response') { $sClass = 'waiting'; $sLabel = 'Waiting'; }
                                        elseif ($inq['status'] === 'under_review') { $sClass = 'review'; $sLabel = 'Under Review'; }
                                        elseif ($inq['status'] === 'resolved') { $sClass = 'resolved'; $sLabel = 'Resolved'; }
                                    ?>
                                    <span class="adm-status-badge adm-status-<?= $sClass ?>"><?= $sLabel ?></span>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($inq['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Staff Overview -->
        <div class="adm-page-header">
            <h2>Staff Overview</h2>
            <a href="staff.php" class="adm-btn adm-btn-secondary">Manage Staff</a>
        </div>
        <?php
        $staffOverview = $db->query(
            "SELECT sp.*, u.full_name, u.email,
                    (SELECT COUNT(*) FROM staff_assignments sa WHERE sa.staff_id = sp.id) AS assignment_count
             FROM staff_profiles sp
             JOIN users u ON sp.user_id = u.id
             ORDER BY sp.is_available DESC, u.full_name ASC
             LIMIT 8"
        )->fetchAll();

        $deptLabels = [
            'operations' => 'Operations',
            'customer_service' => 'Customer Service',
            'sales' => 'Sales',
            'marketing' => 'Marketing',
        ];
        ?>
        <?php if (empty($staffOverview)): ?>
            <div class="adm-empty" style="padding:2rem;">
                <p>No staff members added yet. <a href="staff-edit.php">Add your first staff member</a>.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap" style="margin-bottom:2rem;">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Active Tasks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffOverview as $s): ?>
                            <tr>
                                <td class="cell-main"><?= htmlspecialchars($s['full_name']) ?></td>
                                <td>
                                    <span class="adm-status-badge adm-status-confirmed">
                                        <?= $deptLabels[$s['department']] ?? $s['department'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($s['position']) ?></td>
                                <td>
                                    <span class="adm-status-badge adm-status-<?= $s['is_available'] ? 'active' : 'inactive' ?>">
                                        <?= $s['is_available'] ? 'Available' : 'Unavailable' ?>
                                    </span>
                                </td>
                                <td class="cell-mono"><?= $s['assignment_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var revenueLabels = <?= json_encode(array_column($revenueData, 'month')) ?>;
    var revenueValues = <?= json_encode(array_map('floatval', array_column($revenueData, 'revenue'))) ?>;
    var revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Revenue (Rs.)',
                    data: revenueValues,
                    backgroundColor: '#264653',
                    borderColor: '#264653',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function(v) { return 'Rs. ' + v.toLocaleString(); } } }
                }
            }
        });
    }

    var bookingLabels = <?= json_encode(array_keys($bookingStatusMap)) ?>;
    var bookingValues = <?= json_encode(array_values($bookingStatusMap)) ?>;
    var bookingsCtx = document.getElementById('bookingsChart');
    if (bookingsCtx) {
        new Chart(bookingsCtx, {
            type: 'doughnut',
            data: {
                labels: bookingLabels.map(function(l) { return l.charAt(0).toUpperCase() + l.slice(1); }),
                datasets: [{
                    data: bookingValues,
                    backgroundColor: ['#f4a261', '#264653', '#ba1a1a'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    var userLabels = <?= json_encode(array_column($userGrowth, 'month')) ?>;
    var userValues = <?= json_encode(array_map('intval', array_column($userGrowth, 'cnt'))) ?>;
    var usersCtx = document.getElementById('usersChart');
    if (usersCtx) {
        new Chart(usersCtx, {
            type: 'line',
            data: {
                labels: userLabels,
                datasets: [{
                    label: 'New Users',
                    data: userValues,
                    borderColor: '#264653',
                    backgroundColor: 'rgba(38,70,83,0.05)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#264653'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
