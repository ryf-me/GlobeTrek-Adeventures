<?php
/**
 * File: admin/bookings.php
 * Purpose: Lists all bookings with status filtering, search, staff assignment, and status update functionality. Includes pagination and notification emails.
 * Dependencies: admin/includes/header.php (auth, DB, CSRF), admin/includes/sidebar.php, admin/includes/footer.php, config/logger.php (logActivity), config/helpers.php (csrf_field, formatPrice), includes/notifications.php (sendBookingStatusUpdate)
 * Used By: Admin/staff managing bookings
 * Parent Files: None (entry-point page)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php, config/logger.php, includes/notifications.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Manage Bookings';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../config/logger.php';

// === STATUS UPDATE HANDLER ===
// Allows admin to change a booking's status (pending/confirmed/cancelled).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    // CSRF token validation — rejects the request if the token is missing or invalid.
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $bid = (int)($_POST['booking_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        // Validate status against allowed values to prevent arbitrary status injection.
        if ($bid > 0 && in_array($newStatus, ['pending', 'confirmed', 'cancelled'])) {
            // Fetch old status before updating for notification comparison.
            $oldStmt = $db->prepare("SELECT status FROM bookings WHERE id = :id");
            $oldStmt->execute([':id' => $bid]);
            $oldStatus = $oldStmt->fetch()['status'] ?? '';

            $stmt = $db->prepare("UPDATE bookings SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $bid]);
            logActivity('booking_status_updated', 'booking', $bid, 'Status changed to ' . $newStatus);

            // === SEND STATUS UPDATE NOTIFICATION ===
            // Only send email if the status actually changed and the old status was valid.
            if ($oldStatus !== $newStatus && $oldStatus !== '') {
                require_once __DIR__ . '/../includes/notifications.php';
                $bStmt = $db->prepare("SELECT * FROM bookings WHERE id = :id");
                $bStmt->execute([':id' => $bid]);
                $bookingData = $bStmt->fetch();
                if ($bookingData && !empty($bookingData['email'])) {
                    $bookingData['status'] = $newStatus;
                    sendBookingStatusUpdate($bookingData, $oldStatus);
                }
            }

            // PRG pattern: redirect after POST to avoid duplicate submissions on refresh.
            header('Location: bookings.php?updated=1');
            exit;
        }
    }
}

// === STAFF ASSIGNMENT HANDLER ===
// Assigns a staff member to a booking for operational management.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign_staff') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $staffId = (int)($_POST['staff_id'] ?? 0);
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        if ($staffId > 0 && $bookingId > 0) {
            // Prevent duplicate assignments by checking if the staff member is already assigned.
            $checkStmt = $db->prepare("SELECT id FROM staff_assignments WHERE staff_id = :sid AND entity_type = 'booking' AND entity_id = :eid LIMIT 1");
            $checkStmt->execute([':sid' => $staffId, ':eid' => $bookingId]);
            if (!$checkStmt->fetch()) {
                $stmt = $db->prepare("INSERT INTO staff_assignments (staff_id, entity_type, entity_id, assigned_by) VALUES (:sid, 'booking', :eid, :assigned_by)");
                $stmt->execute([':sid' => $staffId, ':eid' => $bookingId, ':assigned_by' => $_SESSION['user_id']]);
                logActivity('staff_assigned', 'booking', $bookingId, "Staff #$staffId assigned to booking");
            }
            header('Location: bookings.php?assigned=1');
            exit;
        }
    }
}

// === STAFF UNASSIGNMENT HANDLER ===
// Removes a staff assignment from a booking.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unassign_staff') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $assignmentId = (int)($_POST['assignment_id'] ?? 0);
        if ($assignmentId > 0) {
            $stmt = $db->prepare("DELETE FROM staff_assignments WHERE id = :id");
            $stmt->execute([':id' => $assignmentId]);
            logActivity('staff_unassigned', 'booking', 0, "Assignment #$assignmentId removed");
            header('Location: bookings.php?unassigned=1');
            exit;
        }
    }
}

// === SIDEBAR ===
include __DIR__ . '/includes/sidebar.php';

// === FILTER / SEARCH / PAGINATION ===
$filter = $_GET['filter'] ?? 'all';
$where = '';
$params = [];
// Build WHERE clause based on the active status filter tab.
if ($filter === 'pending') { $where = "WHERE b.status = 'pending'"; }
elseif ($filter === 'confirmed') { $where = "WHERE b.status = 'confirmed'"; }
elseif ($filter === 'cancelled') { $where = "WHERE b.status = 'cancelled'"; }

// Append search conditions for reference, name, and email fields.
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $where = ($where === '') ? "WHERE" : $where . " AND";
    $where .= " (b.booking_reference LIKE :q OR b.first_name LIKE :q2 OR b.last_name LIKE :q3 OR b.email LIKE :q4)";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
    $params[':q4'] = "%$search%";
}

// Calculate pagination offset.
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total row count for pagination controls.
$countStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings b $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetch()['cnt'];
$totalPages = max(1, ceil($totalRows / $perPage));

// === FETCH BOOKINGS (with JOINs for user and package info) ===
$stmt = $db->prepare(
    "SELECT b.*, u.full_name AS user_name, p.title AS package_title
     FROM bookings b
     LEFT JOIN users u ON b.user_id = u.id
     LEFT JOIN packages p ON b.package_id = p.id
     $where
     ORDER BY b.created_at DESC
     LIMIT :limit OFFSET :offset"
);
// Bind all dynamic LIKE parameters.
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
// Bind limit/offset as integers to prevent PDO type issues.
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$bookings = $stmt->fetchAll();

// === LOAD STAFF ASSIGNMENTS ===
// Eagerly load all staff assignments for the displayed bookings in a single query.
$bookingIds = array_column($bookings, 'id');
$staffAssignments = [];
if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    $assignStmt = $db->prepare(
        "SELECT sa.entity_id, sa.id AS assignment_id, u.full_name AS staff_name, sp.department
         FROM staff_assignments sa
         JOIN staff_profiles sp ON sa.staff_id = sp.id
         JOIN users u ON sp.user_id = u.id
         WHERE sa.entity_type = 'booking' AND sa.entity_id IN ($placeholders)
         ORDER BY u.full_name ASC"
    );
    $assignStmt->execute($bookingIds);
    foreach ($assignStmt->fetchAll() as $a) {
        $staffAssignments[$a['entity_id']][] = $a;
    }
}

// === LOAD AVAILABLE STAFF ===
// Fetch all available staff members for the assignment dropdown.
$availableStaff = $db->query(
    "SELECT sp.id, u.full_name, sp.department
     FROM staff_profiles sp
     JOIN users u ON sp.user_id = u.id
     WHERE sp.is_available = 1
     ORDER BY u.full_name ASC"
)->fetchAll();

// Short department labels for compact display in the staff dropdown.
$deptLabels = [
    'operations' => 'Ops',
    'customer_service' => 'CS',
    'sales' => 'Sales',
    'marketing' => 'Mkt',
];

// === STATUS STATS ===
// Aggregate booking counts by status for the tab filter badges.
$stats = [];
$res = $db->query("SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status");
foreach ($res->fetchAll() as $r) $stats[$r['status']] = (int)$r['cnt'];
$totalBookings = array_sum($stats);
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Bookings</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === FLASH MESSAGES === -->
        <?php if (isset($_GET['updated'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Booking status updated.</div>
        <?php endif; ?>
        <?php if (isset($_GET['assigned'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Staff member assigned to booking.</div>
        <?php endif; ?>
        <?php if (isset($_GET['unassigned'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Staff assignment removed.</div>
        <?php endif; ?>

        <!-- === PAGE HEADER === -->
        <div class="adm-page-header">
            <h1>Bookings (<?= $totalBookings ?>)</h1>
        </div>

        <!-- === STATUS FILTER TABS === -->
        <div class="adm-tabs">
            <a href="?filter=all" class="adm-tab <?= $filter === 'all' ? 'active' : '' ?>">All (<?= $totalBookings ?>)</a>
            <a href="?filter=pending" class="adm-tab <?= $filter === 'pending' ? 'active' : '' ?>">Pending (<?= $stats['pending'] ?? 0 ?>)</a>
            <a href="?filter=confirmed" class="adm-tab <?= $filter === 'confirmed' ? 'active' : '' ?>">Confirmed (<?= $stats['confirmed'] ?? 0 ?>)</a>
            <a href="?filter=cancelled" class="adm-tab <?= $filter === 'cancelled' ? 'active' : '' ?>">Cancelled (<?= $stats['cancelled'] ?? 0 ?>)</a>
        </div>

        <!-- === SEARCH BAR === -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <!-- Preserve the active filter when searching -->
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search by reference, name, email..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <!-- === BOOKINGS TABLE / EMPTY STATE === -->
        <?php if (empty($bookings)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">flight_takeoff</span>
                <h2>No bookings found</h2>
                <p>There are no bookings matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>User</th>
                            <th>Package</th>
                            <th>Assigned Staff</th>
                            <th>Travellers</th>
                            <th>Amount</th>
                            <th>Travel Date</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td class="cell-mono"><?= htmlspecialchars($b['booking_reference']) ?></td>
                                <td>
                                    <div class="cell-main"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></div>
                                    <div class="cell-sub"><?= htmlspecialchars($b['email']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($b['package_title'] ?? 'N/A') ?></td>
                                <!-- === ASSIGNED STAFF COLUMN === -->
                                <td>
                                    <?php if (!empty($staffAssignments[$b['id']])): ?>
                                        <?php foreach ($staffAssignments[$b['id']] as $sa): ?>
                                            <div style="display:flex;align-items:center;gap:0.35rem;margin-bottom:0.25rem;">
                                                <span class="adm-status-badge adm-status-active" style="font-size:0.7rem;padding:0.15rem 0.4rem;">
                                                    <?= htmlspecialchars($sa['staff_name']) ?>
                                                </span>
                                                <!-- Unassign button with CSRF and confirmation -->
                                                <form method="post" style="display:inline;" data-confirm="Remove assignment?">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="action" value="unassign_staff">
                                                    <input type="hidden" name="assignment_id" value="<?= $sa['assignment_id'] ?>">
                                                    <button type="submit" class="adm-btn-icon adm-btn-icon-danger" style="width:20px;height:20px;" title="Remove">
                                                        <span class="material-symbols-outlined" style="font-size:14px;">close</span>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="cell-muted">Unassigned</span>
                                    <?php endif; ?>
                                    <!-- Staff assignment dropdown -->
                                    <div style="margin-top:0.35rem;">
                                        <select onchange="assignStaffToBooking(this, <?= $b['id'] ?>)" style="padding:0.2rem 0.4rem;border:1px solid var(--adm-outline-variant);font-size:0.75rem;font-family:inherit;background:transparent;border-radius:4px;">
                                            <option value="">+ Assign Staff</option>
                                            <?php foreach ($availableStaff as $s): ?>
                                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= $deptLabels[$s['department']] ?? $s['department'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </td>
                                <td><?= $b['num_travellers'] ?></td>
                                <td class="cell-mono"><?= formatPrice($b['total_price'], 2) ?></td>
                                <td class="cell-muted"><?= $b['travel_date'] ? date('M d, Y', strtotime($b['travel_date'])) : '—' ?></td>
                                <!-- === STATUS BADGE === -->
                                <td>
                                    <span class="adm-status-badge adm-status-<?= $b['status'] ?>">
                                        <span class="adm-badge-dot"></span>
                                        <?= ucfirst(htmlspecialchars($b['status'])) ?>
                                    </span>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($b['created_at'])) ?></td>
                                <!-- === STATUS UPDATE FORM === -->
                                <td>
                                    <div class="cell-actions">
                                        <form method="post" style="display:flex;gap:0.35rem;">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <!-- Status select with JS confirmation on change -->
                                            <select name="status" onchange="if(confirm('Update status?')) this.form.submit();" style="padding:0.3rem 0.5rem;border:1px solid var(--adm-outline-variant);font-size:0.78rem;font-family:inherit;background:transparent;">
                                                <option value="pending" <?= $b['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="confirmed" <?= $b['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                <option value="cancelled" <?= $b['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- === PAGINATION CONTROLS === -->
            <?php if ($totalPages > 1): ?>
                <div class="adm-pagination">
                    <a href="?filter=<?= urlencode($filter) ?>&page=<?= max(1, $page - 1) ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">&laquo;</a>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?filter=<?= urlencode($filter) ?>&page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?filter=<?= urlencode($filter) ?>&page=<?= min($totalPages, $page + 1) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>">&raquo;</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<!-- === JAVASCRIPT: STAFF ASSIGNMENT (DYNAMIC FORM SUBMISSION) === -->
<!-- Builds and submits a hidden form to POST staff assignment data via AJAX-like behavior. -->
<script>
function assignStaffToBooking(select, bookingId) {
    var staffId = select.value;
    if (!staffId) return;

    // Dynamically create a form element to POST the assignment.
    var form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    // Retrieve CSRF token from an existing form on the page.
    var csrfToken = document.querySelector('input[name="csrf_token"]').value;

    var csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);

    var actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'assign_staff';
    form.appendChild(actionInput);

    var staffInput = document.createElement('input');
    staffInput.type = 'hidden';
    staffInput.name = 'staff_id';
    staffInput.value = staffId;
    form.appendChild(staffInput);

    var bookingInput = document.createElement('input');
    bookingInput.type = 'hidden';
    bookingInput.name = 'booking_id';
    bookingInput.value = bookingId;
    form.appendChild(bookingInput);

    document.body.appendChild(form);
    form.submit();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
