<?php
/**
 * File: admin/index.php
 * Purpose: Main admin dashboard — displays KPIs, Chart.js visualizations, and recent activity tables.
 * Dependencies: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php, config/database.php
 * Used By: Admin users navigating to the dashboard
 * Parent Files: none (entry point)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

// === ROLE-BASED REDIRECT ===
// Staff members are redirected to their dedicated dashboard — they don't see the full admin dashboard.
if (($_SESSION['user_role'] ?? '') === 'staff') {
    header('Location: staff-dashboard.php');
    exit;
}

include __DIR__ . '/includes/sidebar.php';

// === KPI QUERIES ===
// Each query fetches a single aggregate count/value for the stat cards displayed at the top of the dashboard.
$stats = [];

// Total registered users
$res = $db->query("SELECT COUNT(*) AS cnt FROM users");
$stats['users'] = (int)$res->fetch()['cnt'];

// Active packages only
$res = $db->query("SELECT COUNT(*) AS cnt FROM packages WHERE is_active = 1");
$stats['packages'] = (int)$res->fetch()['cnt'];

// Total bookings (all statuses)
$res = $db->query("SELECT COUNT(*) AS cnt FROM bookings");
$stats['bookings'] = (int)$res->fetch()['cnt'];

// Sum of total_price from confirmed bookings — represents realized revenue
$res = $db->query("SELECT COALESCE(SUM(total_price), 0) AS total FROM bookings WHERE status = 'confirmed'");
$stats['revenue'] = (float)$res->fetch()['total'];

// Open inquiries awaiting action (open, waiting_for_response, under_review)
$res = $db->query("SELECT COUNT(*) AS cnt FROM inquiries WHERE status IN ('open','waiting_for_response','under_review')");
$stats['open_inquiries'] = (int)$res->fetch()['cnt'];

// Pending custom trip requests not yet fulfilled
$res = $db->query("SELECT COUNT(*) AS cnt FROM custom_trip_requests WHERE status = 'pending'");
$stats['pending_trips'] = (int)$res->fetch()['cnt'];

// Unread contact form messages
$res = $db->query("SELECT COUNT(*) AS cnt FROM contact_messages WHERE is_read = 0");
$stats['unread_contacts'] = (int)$res->fetch()['cnt'];

// Active newsletter subscribers
$res = $db->query("SELECT COUNT(*) AS cnt FROM newsletter_subscriptions WHERE is_active = 1");
$stats['subscribers'] = (int)$res->fetch()['cnt'];

// === STAFF KPIs ===
// Total staff members in the system
$res = $db->query("SELECT COUNT(*) AS cnt FROM staff_profiles");
$stats['total_staff'] = (int)$res->fetch()['cnt'];

// Currently available staff
$res = $db->query("SELECT COUNT(*) AS cnt FROM staff_profiles WHERE is_available = 1");
$stats['available_staff'] = (int)$res->fetch()['cnt'];

// Total active assignments (bookings + inquiries assigned to staff)
$res = $db->query("SELECT COUNT(*) AS cnt FROM staff_assignments");
$stats['total_assignments'] = (int)$res->fetch()['cnt'];

// Breakdown: booking assignments vs inquiry assignments
$res = $db->query("SELECT COUNT(*) AS cnt FROM staff_assignments WHERE entity_type = 'booking'");
$stats['booking_assignments'] = (int)$res->fetch()['cnt'];

$res = $db->query("SELECT COUNT(*) AS cnt FROM staff_assignments WHERE entity_type = 'inquiry'");
$stats['inquiry_assignments'] = (int)$res->fetch()['cnt'];

// === RECENT BOOKINGS ===
// Fetch the 5 most recent bookings with user and package details for the activity table.
$recentBookings = $db->query(
    "SELECT b.*, u.full_name AS user_name, p.title AS package_title
     FROM bookings b
     LEFT JOIN users u ON b.user_id = u.id
     LEFT JOIN packages p ON b.package_id = p.id
     ORDER BY b.created_at DESC LIMIT 5"
)->fetchAll();

// === RECENT INQUIRIES ===
// Fetch the 5 most recent inquiries with associated user name.
$recentInquiries = $db->query(
    "SELECT i.*, u.full_name AS user_name
     FROM inquiries i
     LEFT JOIN users u ON i.user_id = u.id
     ORDER BY i.created_at DESC LIMIT 5"
)->fetchAll();

// === REVENUE BY MONTH (LAST 6 MONTHS) ===
// Aggregates confirmed booking revenue per month for the bar chart.
// Results are reversed to show chronological order (oldest first).
$revenueData = $db->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(total_price) AS revenue, COUNT(*) AS cnt
     FROM bookings WHERE status = 'confirmed'
     GROUP BY month ORDER BY month DESC LIMIT 6"
)->fetchAll();
$revenueData = array_reverse($revenueData);

// === BOOKINGS BY STATUS ===
// Counts bookings per status for the doughnut chart.
// Converts to an associative map: [status => count].
$bookingStatus = $db->query(
    "SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status"
)->fetchAll();
$bookingStatusMap = [];
foreach ($bookingStatus as $row) { $bookingStatusMap[$row['status']] = (int)$row['cnt']; }

// === USER REGISTRATIONS OVER TIME (LAST 6 MONTHS) ===
// Aggregates new user registrations per month for the line chart.
// Reversed to chronological order for the chart axis.
$userGrowth = $db->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS cnt
     FROM users GROUP BY month ORDER BY month DESC LIMIT 6"
)->fetchAll();
$userGrowth = array_reverse($userGrowth);
?>

<!-- === SIDEBAR OVERLAY (mobile) === -->
<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <!-- Mobile menu toggle — toggles sidebar and overlay classes -->
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Dashboard</h1>
        </div>
        <div class="adm-topbar-right">
            <!-- Opens the public site in a new tab -->
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
        <!-- === STAT CARDS (KPI GRID) === -->
        <!-- Each card shows a key metric with an icon, formatted number, and label. -->
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
                    <div class="adm-stat-card-num"><?= formatPrice($stats['revenue'], 2) ?></div>
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

        <!-- === CHARTS SECTION === -->
        <!-- Chart.js canvases for revenue trends, booking status distribution, and user growth. -->
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

        <!-- === RECENT BOOKINGS TABLE === -->
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
                                <td class="cell-mono"><?= formatPrice($b['total_price'], 2) ?></td>
                                <td>
                                    <!-- Status badge with dynamic CSS class based on booking status -->
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

        <!-- === RECENT INQUIRIES TABLE === -->
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
                                        // Map inquiry status to CSS class and human-readable label
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

        <!-- === STAFF OVERVIEW TABLE === -->
        <!-- Shows up to 8 staff members with their department, position, availability, and active task count. -->
        <div class="adm-page-header">
            <h2>Staff Overview</h2>
            <a href="staff.php" class="adm-btn adm-btn-secondary">Manage Staff</a>
        </div>
        <?php
        // Fetch staff with their user info and count of active assignments (subquery)
        $staffOverview = $db->query(
            "SELECT sp.*, u.full_name, u.email,
                    (SELECT COUNT(*) FROM staff_assignments sa WHERE sa.staff_id = sp.id) AS assignment_count
             FROM staff_profiles sp
             JOIN users u ON sp.user_id = u.id
             ORDER BY sp.is_available DESC, u.full_name ASC
             LIMIT 8"
        )->fetchAll();

        // Department key-to-label mapping for display
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
                                    <!-- Availability badge: active (green) or inactive (grey) -->
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

<!-- === CHART.JS SCRIPTS === -->
<!-- Loads Chart.js from CDN and initializes three charts using data passed from PHP via json_encode. -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Revenue Bar Chart ---
    // Data injected via PHP json_encode for month labels and revenue values
    var revenueLabels = <?= json_encode(array_column($revenueData, 'month')) ?>;
    var revenueValues = <?= json_encode(array_map('floatval', array_column($revenueData, 'revenue'))) ?>;
    var revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Revenue (<?= CURRENCY_CODE ?>)',
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
                    // Y-axis starts at zero; tick labels prefixed with currency symbol
                    y: { beginAtZero: true, ticks: { callback: function(v) { return '<?= CURRENCY_SYMBOL ?> ' + v.toLocaleString(); } } }
                }
            }
        });
    }

    // --- Bookings Doughnut Chart ---
    // Shows distribution of bookings by status
    var bookingLabels = <?= json_encode(array_keys($bookingStatusMap)) ?>;
    var bookingValues = <?= json_encode(array_values($bookingStatusMap)) ?>;
    var bookingsCtx = document.getElementById('bookingsChart');
    if (bookingsCtx) {
        new Chart(bookingsCtx, {
            type: 'doughnut',
            data: {
                // Capitalize status labels for display
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

    // --- User Growth Line Chart ---
    // Shows new user registrations per month over the last 6 months
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