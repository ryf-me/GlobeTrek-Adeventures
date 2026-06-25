<?php
/**
 * Staff Dashboard
 *
 * Dedicated dashboard for staff members showing:
 * - Their assigned tasks (full access)
 * - Department items (view only, can claim unassigned)
 * - Quick availability toggle
 * - Department-specific KPIs
 * - Recent activity
 */
$pageTitle = 'Staff Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../config/logger.php';

if (($_SESSION['user_role'] ?? '') !== 'staff') {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get staff profile
$stmt = $db->prepare("SELECT sp.* FROM staff_profiles sp WHERE sp.user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $userId]);
$staffProfile = $stmt->fetch();

if (!$staffProfile) {
    session_destroy();
    header('Location: ../pages/login.php');
    exit;
}

$staffId = $staffProfile['id'];
$department = $staffProfile['department'];

$departmentLabels = [
    'operations' => 'Operations',
    'customer_service' => 'Customer Service',
    'sales' => 'Sales',
    'marketing' => 'Marketing',
];

$departmentIcons = [
    'operations' => 'settings',
    'customer_service' => 'support_agent',
    'sales' => 'trending_up',
    'marketing' => 'campaign',
];

// Handle availability toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_availability') {
    if (validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $stmt = $db->prepare("UPDATE staff_profiles SET is_available = NOT is_available WHERE id = :id");
        $stmt->execute([':id' => $staffId]);
        logActivity('availability_toggled', 'staff', $staffId, 'Availability toggled');
        header('Location: staff-dashboard.php?toggled=1');
        exit;
    }
}

// Handle claim task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'claim_task') {
    if (validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $entityType = $_POST['entity_type'] ?? '';
        $entityId = (int)($_POST['entity_id'] ?? 0);

        if (in_array($entityType, ['booking', 'inquiry']) && $entityId > 0) {
            // Check if already assigned to someone else
            $checkStmt = $db->prepare("SELECT id FROM staff_assignments WHERE entity_type = :type AND entity_id = :eid LIMIT 1");
            $checkStmt->execute([':type' => $entityType, ':eid' => $entityId]);

            if (!$checkStmt->fetch()) {
                $stmt = $db->prepare("INSERT INTO staff_assignments (staff_id, entity_type, entity_id, assigned_by) VALUES (:sid, :type, :eid, :assigned_by)");
                $stmt->execute([':sid' => $staffId, ':type' => $entityType, ':eid' => $entityId, ':assigned_by' => $userId]);
                logActivity('task_claimed', $entityType, $entityId, "Task claimed by staff");
                header('Location: staff-dashboard.php?claimed=1');
                exit;
            }
        }
    }
}

// Refresh staff profile for availability status
$stmt = $db->prepare("SELECT sp.* FROM staff_profiles sp WHERE sp.id = :id LIMIT 1");
$stmt->execute([':id' => $staffId]);
$staffProfile = $stmt->fetch();

// --- My Assigned Bookings ---
$stmt = $db->prepare(
    "SELECT b.*, p.title AS package_title, p.image AS package_image, sa.assigned_at
     FROM staff_assignments sa
     JOIN bookings b ON sa.entity_id = b.id AND sa.entity_type = 'booking'
     JOIN packages p ON b.package_id = p.id
     WHERE sa.staff_id = :sid
     ORDER BY b.created_at DESC"
);
$stmt->execute([':sid' => $staffId]);
$myBookings = $stmt->fetchAll();

// --- My Assigned Inquiries ---
$stmt = $db->prepare(
    "SELECT i.*, u.full_name AS user_name, u.email AS user_email, sa.assigned_at
     FROM staff_assignments sa
     JOIN inquiries i ON sa.entity_id = i.id AND sa.entity_type = 'inquiry'
     LEFT JOIN users u ON i.user_id = u.id
     WHERE sa.staff_id = :sid
     ORDER BY i.created_at DESC"
);
$stmt->execute([':sid' => $staffId]);
$myInquiries = $stmt->fetchAll();

// --- Department Unassigned Bookings (for claiming) ---
$deptBookings = [];
if (in_array($department, ['operations', 'sales'])) {
    $stmt = $db->query(
        "SELECT b.*, p.title AS package_title,
                (SELECT COUNT(*) FROM staff_assignments sa WHERE sa.entity_type = 'booking' AND sa.entity_id = b.id) AS is_assigned
         FROM bookings b
         JOIN packages p ON b.package_id = p.id
         WHERE b.status != 'cancelled'
         ORDER BY b.created_at DESC
         LIMIT 20"
    );
    $deptBookings = $stmt->fetchAll();
}

// --- Department Unassigned Inquiries (for claiming) ---
$deptInquiries = [];
if (in_array($department, ['customer_service'])) {
    $stmt = $db->query(
        "SELECT i.*, u.full_name AS user_name,
                (SELECT COUNT(*) FROM staff_assignments sa WHERE sa.entity_type = 'inquiry' AND sa.entity_id = i.id) AS is_assigned
         FROM inquiries i
         LEFT JOIN users u ON i.user_id = u.id
         WHERE i.status != 'resolved'
         ORDER BY i.created_at DESC
         LIMIT 20"
    );
    $deptInquiries = $stmt->fetchAll();
}

// --- Department KPIs ---
$deptKPIs = [];

if ($department === 'operations') {
    $r = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $deptKPIs[] = ['label' => 'Pending Bookings', 'value' => $r->fetchColumn(), 'icon' => 'schedule', 'color' => '#f4a261'];
    $r = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'");
    $deptKPIs[] = ['label' => 'Confirmed Bookings', 'value' => $r->fetchColumn(), 'icon' => 'check_circle', 'color' => '#264653'];
    $r = $db->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()");
    $deptKPIs[] = ['label' => 'Bookings Today', 'value' => $r->fetchColumn(), 'icon' => 'today', 'color' => '#e76f51'];
    $r = $db->query("SELECT COUNT(*) FROM packages WHERE is_active = 1");
    $deptKPIs[] = ['label' => 'Active Packages', 'value' => $r->fetchColumn(), 'icon' => 'luggage', 'color' => '#2a9d8f'];
} elseif ($department === 'customer_service') {
    $r = $db->query("SELECT COUNT(*) FROM inquiries WHERE status = 'open'");
    $deptKPIs[] = ['label' => 'Open Inquiries', 'value' => $r->fetchColumn(), 'icon' => 'chat', 'color' => '#f4a261'];
    $r = $db->query("SELECT COUNT(*) FROM inquiries WHERE status = 'waiting_for_response'");
    $deptKPIs[] = ['label' => 'Waiting for Response', 'value' => $r->fetchColumn(), 'icon' => 'hourglass_empty', 'color' => '#e76f51'];
    $r = $db->query("SELECT COUNT(*) FROM inquiries WHERE status = 'under_review'");
    $deptKPIs[] = ['label' => 'Under Review', 'value' => $r->fetchColumn(), 'icon' => 'rate_review', 'color' => '#264653'];
    $r = $db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
    $deptKPIs[] = ['label' => 'Unread Messages', 'value' => $r->fetchColumn(), 'icon' => 'mail', 'color' => '#2a9d8f'];
} elseif ($department === 'sales') {
    $r = $db->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status = 'confirmed' AND MONTH(created_at) = MONTH(CURDATE())");
    $deptKPIs[] = ['label' => 'Revenue This Month', 'value' => 'Rs.' . number_format($r->fetchColumn(), 0), 'icon' => 'payments', 'color' => '#264653'];
    $r = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND MONTH(created_at) = MONTH(CURDATE())");
    $deptKPIs[] = ['label' => 'Confirmed This Month', 'value' => $r->fetchColumn(), 'icon' => 'trending_up', 'color' => '#2a9d8f'];
    $r = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $deptKPIs[] = ['label' => 'Pending Bookings', 'value' => $r->fetchColumn(), 'icon' => 'schedule', 'color' => '#f4a261'];
    $r = $db->query("SELECT COUNT(*) FROM payments WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURDATE())");
    $deptKPIs[] = ['label' => 'Payments This Month', 'value' => $r->fetchColumn(), 'icon' => 'receipt', 'color' => '#e76f51'];
} elseif ($department === 'marketing') {
    $r = $db->query("SELECT COUNT(*) FROM newsletter_subscriptions WHERE is_active = 1");
    $deptKPIs[] = ['label' => 'Newsletter Subscribers', 'value' => $r->fetchColumn(), 'icon' => 'campaign', 'color' => '#264653'];
    $r = $db->query("SELECT COUNT(*) FROM destinations WHERE is_active = 1");
    $deptKPIs[] = ['label' => 'Active Destinations', 'value' => $r->fetchColumn(), 'icon' => 'location_on', 'color' => '#2a9d8f'];
    $r = $db->query("SELECT COUNT(*) FROM testimonials WHERE status = 'approved'");
    $deptKPIs[] = ['label' => 'Approved Testimonials', 'value' => $r->fetchColumn(), 'icon' => 'star', 'color' => '#f4a261'];
    $r = $db->query("SELECT COUNT(*) FROM contact_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $deptKPIs[] = ['label' => 'Messages This Week', 'value' => $r->fetchColumn(), 'icon' => 'mail', 'color' => '#e76f51'];
}

// --- Recent Activity ---
$stmt = $db->prepare(
    "SELECT action, entity_type, details, created_at
     FROM activity_logs
     WHERE user_id = :uid
     ORDER BY created_at DESC
     LIMIT 10"
);
$stmt->execute([':uid' => $userId]);
$recentActivity = $stmt->fetchAll();

// Count stats
$myBookingCount = count($myBookings);
$myInquiryCount = count($myInquiries);
$pendingBookings = 0;
$openInquiries = 0;
foreach ($myBookings as $b) { if ($b['status'] === 'pending') $pendingBookings++; }
foreach ($myInquiries as $i) { if ($i['status'] !== 'resolved') $openInquiries++; }

include __DIR__ . '/includes/sidebar.php';
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">My Dashboard</h1>
        </div>
        <div class="adm-topbar-right">
            <!-- Availability Toggle -->
            <form method="post" style="display:inline;">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="toggle_availability">
                <button type="submit" class="adm-btn <?= $staffProfile['is_available'] ? 'adm-btn-primary' : 'adm-btn-secondary' ?>" style="display:flex;align-items:center;gap:0.5rem;">
                    <span class="material-symbols-outlined"><?= $staffProfile['is_available'] ? 'toggle_on' : 'toggle_off' ?></span>
                    <?= $staffProfile['is_available'] ? 'Available' : 'Unavailable' ?>
                </button>
            </form>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <?php if (isset($_GET['toggled'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Availability status updated.</div>
        <?php endif; ?>
        <?php if (isset($_GET['claimed'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Task claimed successfully!</div>
        <?php endif; ?>

        <!-- Welcome Banner -->
        <div style="background:linear-gradient(135deg,#264653,#1a3a4a);color:#ffffff;padding:1.5rem 2rem;border-radius:12px;margin-bottom:1.5rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 style="margin:0 0 0.25rem;color:#ffffff;">Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Staff') ?>!</h2>
                    <p style="margin:0;opacity:0.85;color:#ffffff;">
                        <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;"><?= $departmentIcons[$department] ?? 'business' ?></span>
                        <?= $departmentLabels[$department] ?? $department ?> Department &bull; <?= htmlspecialchars($staffProfile['position']) ?>
                    </p>
                </div>
                <div style="display:flex;gap:1.5rem;text-align:center;">
                    <div>
                        <div style="font-size:1.5rem;font-weight:700;color:#ffffff;"><?= $myBookingCount ?></div>
                        <div style="font-size:0.8rem;opacity:0.8;color:#ffffff;">My Bookings</div>
                    </div>
                    <div>
                        <div style="font-size:1.5rem;font-weight:700;color:#ffffff;"><?= $myInquiryCount ?></div>
                        <div style="font-size:0.8rem;opacity:0.8;color:#ffffff;">My Inquiries</div>
                    </div>
                    <div>
                        <div style="font-size:1.5rem;font-weight:700;color:#ffffff;"><?= $pendingBookings + $openInquiries ?></div>
                        <div style="font-size:0.8rem;opacity:0.8;color:#ffffff;">Pending Tasks</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department KPIs -->
        <?php if (!empty($deptKPIs)): ?>
            <div class="adm-stat-grid" style="margin-bottom:1.5rem;">
                <?php foreach ($deptKPIs as $kpi): ?>
                    <div class="adm-stat-card">
                        <div class="adm-stat-card-icon" style="background:<?= $kpi['color'] ?>15;color:<?= $kpi['color'] ?>;">
                            <span class="material-symbols-outlined"><?= $kpi['icon'] ?></span>
                        </div>
                        <div class="adm-stat-card-info">
                            <div class="adm-stat-card-num"><?= $kpi['value'] ?></div>
                            <div class="adm-stat-card-label"><?= $kpi['label'] ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- My Assigned Bookings -->
        <div class="adm-page-header">
            <h2>My Assigned Bookings (<?= $myBookingCount ?>)</h2>
        </div>
        <?php if (empty($myBookings)): ?>
            <div class="adm-empty" style="padding:1.5rem;">
                <span class="material-symbols-outlined adm-empty-icon" style="font-size:2rem;">flight_takeoff</span>
                <p>No bookings assigned to you yet.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap" style="margin-bottom:2rem;">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Package</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myBookings as $b): ?>
                            <tr>
                                <td class="cell-mono"><?= htmlspecialchars($b['booking_reference']) ?></td>
                                <td>
                                    <div class="cell-main"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></div>
                                    <div class="cell-sub"><?= htmlspecialchars($b['email']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($b['package_title']) ?></td>
                                <td class="cell-mono">Rs.<?= number_format($b['total_price'], 2) ?></td>
                                <td>
                                    <span class="adm-status-badge adm-status-<?= $b['status'] ?>">
                                        <?= ucfirst($b['status']) ?>
                                    </span>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($b['assigned_at'])) ?></td>
                                <td>
                                    <div class="cell-actions">
                                        <a href="booking-detail.php?ref=<?= urlencode($b['booking_reference']) ?>" class="adm-btn-icon" title="View Details">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </a>
                                        <form method="post" style="display:inline;" data-confirm="Update status to Confirmed?">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="update_booking_status">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" class="adm-btn-icon" title="Confirm Booking" <?= $b['status'] === 'confirmed' ? 'disabled' : '' ?>>
                                                <span class="material-symbols-outlined">check_circle</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- My Assigned Inquiries -->
        <div class="adm-page-header">
            <h2>My Assigned Inquiries (<?= $myInquiryCount ?>)</h2>
        </div>
        <?php if (empty($myInquiries)): ?>
            <div class="adm-empty" style="padding:1.5rem;">
                <span class="material-symbols-outlined adm-empty-icon" style="font-size:2rem;">chat</span>
                <p>No inquiries assigned to you yet.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap" style="margin-bottom:2rem;">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myInquiries as $i): ?>
                            <?php
                                $statusClass = 'open';
                                if ($i['status'] === 'waiting_for_response') $statusClass = 'waiting';
                                elseif ($i['status'] === 'under_review') $statusClass = 'review';
                                elseif ($i['status'] === 'resolved') $statusClass = 'resolved';
                            ?>
                            <tr>
                                <td class="cell-mono"><?= htmlspecialchars($i['inquiry_id_code']) ?></td>
                                <td>
                                    <div class="cell-main"><?= htmlspecialchars($i['user_name'] ?? 'Unknown') ?></div>
                                    <div class="cell-sub"><?= htmlspecialchars($i['user_email'] ?? '') ?></div>
                                </td>
                                <td><?= htmlspecialchars($i['subject']) ?></td>
                                <td>
                                    <span class="adm-status-badge adm-status-<?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $i['status'])) ?>
                                    </span>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($i['assigned_at'])) ?></td>
                                <td>
                                    <div class="cell-actions">
                                        <a href="inquiries.php?thread=<?= $i['id'] ?>" class="adm-btn-icon" title="View & Reply">
                                            <span class="material-symbols-outlined">reply</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Department Items (Claimable) -->
        <?php if (!empty($deptBookings) || !empty($deptInquiries)): ?>
            <div class="adm-page-header">
                <h2>Department Tasks (Claimable)</h2>
                <p style="color:var(--adm-secondary);margin:0;font-size:0.9rem;">Unassigned tasks from your department that you can claim.</p>
            </div>

            <?php if (!empty($deptBookings) && in_array($department, ['operations', 'sales'])): ?>
                <h3 style="font-size:1rem;margin-bottom:0.75rem;">Unassigned Bookings</h3>
                <div class="adm-table-wrap" style="margin-bottom:2rem;">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $unassignedBookings = array_filter($deptBookings, fn($b) => $b['is_assigned'] == 0);
                            $shown = 0;
                            foreach ($unassignedBookings as $b):
                                if ($shown >= 5) break;
                                $shown++;
                            ?>
                                <tr>
                                    <td class="cell-mono"><?= htmlspecialchars($b['booking_reference']) ?></td>
                                    <td class="cell-main"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
                                    <td><?= htmlspecialchars($b['package_title']) ?></td>
                                    <td>
                                        <span class="adm-status-badge adm-status-<?= $b['status'] ?>">
                                            <?= ucfirst($b['status']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <form method="post" style="display:inline;" data-confirm="Claim this booking?">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="claim_task">
                                            <input type="hidden" name="entity_type" value="booking">
                                            <input type="hidden" name="entity_id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="adm-btn adm-btn-sm adm-btn-primary">
                                                <span class="material-symbols-outlined" style="font-size:1rem;">add_task</span>
                                                Claim
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($shown === 0): ?>
                                <tr><td colspan="5" class="cell-muted" style="text-align:center;">All bookings are assigned.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($deptInquiries) && $department === 'customer_service'): ?>
                <h3 style="font-size:1rem;margin-bottom:0.75rem;">Unassigned Inquiries</h3>
                <div class="adm-table-wrap" style="margin-bottom:2rem;">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $unassignedInquiries = array_filter($deptInquiries, fn($i) => $i['is_assigned'] == 0);
                            $shown = 0;
                            foreach ($unassignedInquiries as $i):
                                if ($shown >= 5) break;
                                $shown++;
                            ?>
                                <tr>
                                    <td class="cell-mono"><?= htmlspecialchars($i['inquiry_id_code']) ?></td>
                                    <td class="cell-main"><?= htmlspecialchars($i['user_name'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($i['subject']) ?></td>
                                    <td>
                                        <?php
                                            $statusClass = 'open';
                                            if ($i['status'] === 'waiting_for_response') $statusClass = 'waiting';
                                            elseif ($i['status'] === 'under_review') $statusClass = 'review';
                                        ?>
                                        <span class="adm-status-badge adm-status-<?= $statusClass ?>">
                                            <?= ucfirst(str_replace('_', ' ', $i['status'])) ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <form method="post" style="display:inline;" data-confirm="Claim this inquiry?">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="claim_task">
                                            <input type="hidden" name="entity_type" value="inquiry">
                                            <input type="hidden" name="entity_id" value="<?= $i['id'] ?>">
                                            <button type="submit" class="adm-btn adm-btn-sm adm-btn-primary">
                                                <span class="material-symbols-outlined" style="font-size:1rem;">add_task</span>
                                                Claim
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($shown === 0): ?>
                                <tr><td colspan="5" class="cell-muted" style="text-align:center;">All inquiries are assigned.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Recent Activity -->
        <div class="adm-page-header">
            <h2>My Recent Activity</h2>
        </div>
        <?php if (empty($recentActivity)): ?>
            <div class="adm-empty" style="padding:1.5rem;">
                <span class="material-symbols-outlined adm-empty-icon" style="font-size:2rem;">history</span>
                <p>No recent activity.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Type</th>
                            <th>Details</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivity as $activity): ?>
                            <tr>
                                <td class="cell-main"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $activity['action']))) ?></td>
                                <td>
                                    <span class="adm-status-badge adm-status-confirmed">
                                        <?= htmlspecialchars(ucfirst($activity['entity_type'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($activity['details'] ?? '—') ?></td>
                                <td class="cell-muted"><?= date('M d, Y g:i A', strtotime($activity['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
