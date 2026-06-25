<?php
/**
 * Staff Assignments
 *
 * View and manage staff assignments to bookings and inquiries.
 * Only accessible by admins.
 */
$pageTitle = 'Staff Assignments';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../config/logger.php';

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRFToken($_POST['csrf_token'] ?? null)) {
    $error = 'Invalid security token. Please try again.';
}

// Assign staff
if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign') {
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $entityType = $_POST['entity_type'] ?? '';
    $entityId = (int)($_POST['entity_id'] ?? 0);

    if ($staffId > 0 && in_array($entityType, ['booking', 'inquiry']) && $entityId > 0) {
        // Check if already assigned
        $checkStmt = $db->prepare("SELECT id FROM staff_assignments WHERE staff_id = :sid AND entity_type = :type AND entity_id = :eid LIMIT 1");
        $checkStmt->execute([':sid' => $staffId, ':type' => $entityType, ':eid' => $entityId]);

        if (!$checkStmt->fetch()) {
            $stmt = $db->prepare(
                "INSERT INTO staff_assignments (staff_id, entity_type, entity_id, assigned_by)
                 VALUES (:sid, :type, :eid, :assigned_by)"
            );
            $stmt->execute([
                ':sid' => $staffId,
                ':type' => $entityType,
                ':eid' => $entityId,
                ':assigned_by' => $_SESSION['user_id'],
            ]);
            logActivity('staff_assigned', 'assignment', $db->lastInsertId(), "Staff #$staffId assigned to $entityType #$entityId");
            header('Location: staff-assignments.php?assigned=1');
            exit;
        } else {
            $error = 'This staff member is already assigned to this item.';
        }
    }
}

// Unassign staff
if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unassign') {
    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    if ($assignmentId > 0) {
        $stmt = $db->prepare("DELETE FROM staff_assignments WHERE id = :id");
        $stmt->execute([':id' => $assignmentId]);
        logActivity('staff_unassigned', 'assignment', $assignmentId, 'Staff assignment removed');
        header('Location: staff-assignments.php?unassigned=1');
        exit;
    }
}

include __DIR__ . '/includes/sidebar.php';

// Filters
$filter = $_GET['filter'] ?? 'all';
$staffFilter = (int)($_GET['staff_id'] ?? 0);
$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if ($filter === 'booking') {
    $where[] = "sa.entity_type = 'booking'";
} elseif ($filter === 'inquiry') {
    $where[] = "sa.entity_type = 'inquiry'";
}

if ($staffFilter > 0) {
    $where[] = "sa.staff_id = :staff_id";
    $params[':staff_id'] = $staffFilter;
}

if ($search !== '') {
    $where[] = "(u.full_name LIKE :q OR b.booking_reference LIKE :q2 OR i.inquiry_id_code LIKE :q3)";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Query assignments
$stmt = $db->prepare(
    "SELECT sa.*,
            u.full_name AS staff_name, u.email AS staff_email,
            sp.department, sp.position,
            b.booking_reference, b.first_name AS booking_first_name, b.last_name AS booking_last_name, b.total_price, b.status AS booking_status,
            i.inquiry_id_code, i.subject AS inquiry_subject, i.status AS inquiry_status,
            assigned_by_user.full_name AS assigned_by_name
     FROM staff_assignments sa
     JOIN staff_profiles sp ON sa.staff_id = sp.id
     JOIN users u ON sp.user_id = u.id
     LEFT JOIN bookings b ON sa.entity_type = 'booking' AND sa.entity_id = b.id
     LEFT JOIN inquiries i ON sa.entity_type = 'inquiry' AND sa.entity_id = i.id
     LEFT JOIN users assigned_by_user ON sa.assigned_by = assigned_by_user.id
     $whereSQL
     ORDER BY sa.assigned_at DESC"
);
$stmt->execute($params);
$assignments = $stmt->fetchAll();

// Get all staff for filter/assign dropdown
$allStaff = $db->query(
    "SELECT sp.id, u.full_name, sp.department, sp.position, sp.is_available
     FROM staff_profiles sp
     JOIN users u ON sp.user_id = u.id
     ORDER BY u.full_name ASC"
)->fetchAll();

// Get unassigned bookings (for assign form)
$unassignedBookings = $db->query(
    "SELECT b.id, b.booking_reference, b.first_name, b.last_name, p.title AS package_title
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     WHERE b.status != 'cancelled'
     ORDER BY b.created_at DESC
     LIMIT 50"
)->fetchAll();

// Get unassigned inquiries (for assign form)
$unassignedInquiries = $db->query(
    "SELECT i.id, i.inquiry_id_code, i.subject, u.full_name AS user_name
     FROM inquiries i
     LEFT JOIN users u ON i.user_id = u.id
     WHERE i.status != 'resolved'
     ORDER BY i.created_at DESC
     LIMIT 50"
)->fetchAll();

$departmentLabels = [
    'operations' => 'Operations',
    'customer_service' => 'Customer Service',
    'sales' => 'Sales',
    'marketing' => 'Marketing',
];

// Stats
$stats = ['total' => count($assignments), 'bookings' => 0, 'inquiries' => 0];
foreach ($assignments as $a) {
    if ($a['entity_type'] === 'booking') $stats['bookings']++;
    else $stats['inquiries']++;
}
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Staff Assignments</h1>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-btn adm-btn-primary" onclick="document.getElementById('assignModal').classList.add('open')">
                <span class="material-symbols-outlined">add</span>
                <span>New Assignment</span>
            </button>
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <?php if (isset($_GET['assigned'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Staff member assigned successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['unassigned'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Assignment removed successfully.</div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="adm-alert adm-alert-error"><span class="material-symbols-outlined">error</span> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="adm-stat-grid" style="margin-bottom:1.5rem;">
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">assignment_ind</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= $stats['total'] ?></div>
                    <div class="adm-stat-card-label">Total Assignments</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">flight_takeoff</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= $stats['bookings'] ?></div>
                    <div class="adm-stat-card-label">Booking Assignments</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">chat</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= $stats['inquiries'] ?></div>
                    <div class="adm-stat-card-label">Inquiry Assignments</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search by staff name, reference..." value="<?= htmlspecialchars($search) ?>">
                <?php if ($filter !== 'all'): ?>
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <?php endif; ?>
                <?php if ($staffFilter > 0): ?>
                    <input type="hidden" name="staff_id" value="<?= $staffFilter ?>">
                <?php endif; ?>
            </form>
            <a href="?filter=all<?= $staffFilter > 0 ? '&staff_id=' . $staffFilter : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="?filter=booking<?= $staffFilter > 0 ? '&staff_id=' . $staffFilter : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'booking' ? 'active' : '' ?>">Bookings</a>
            <a href="?filter=inquiry<?= $staffFilter > 0 ? '&staff_id=' . $staffFilter : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'inquiry' ? 'active' : '' ?>">Inquiries</a>
        </div>

        <!-- Staff Filter -->
        <div class="adm-tabs" style="margin-bottom:1rem;">
            <a href="?filter=<?= urlencode($filter) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $staffFilter === 0 ? 'active' : '' ?>">All Staff</a>
            <?php foreach ($allStaff as $s): ?>
                <a href="?staff_id=<?= $s['id'] ?><?= $filter !== 'all' ? '&filter=' . urlencode($filter) : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $staffFilter === $s['id'] ? 'active' : '' ?>"><?= htmlspecialchars($s['full_name']) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($assignments)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">assignment_ind</span>
                <h2>No assignments found</h2>
                <p><?= $filter !== 'all' || $staffFilter > 0 || $search ? 'Try adjusting your filters.' : 'No staff assignments have been made yet.' ?></p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Department</th>
                            <th>Assigned To</th>
                            <th>Details</th>
                            <th>Assigned Date</th>
                            <th>Assigned By</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): ?>
                            <tr>
                                <td>
                                    <div class="cell-main"><?= htmlspecialchars($a['staff_name']) ?></div>
                                    <div class="cell-sub"><?= htmlspecialchars($a['position']) ?></div>
                                </td>
                                <td>
                                    <span class="adm-status-badge adm-status-confirmed">
                                        <?= $departmentLabels[$a['department']] ?? $a['department'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($a['entity_type'] === 'booking'): ?>
                                        <span class="adm-status-badge adm-status-pending">Booking</span>
                                    <?php else: ?>
                                        <span class="adm-status-badge adm-status-review">Inquiry</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($a['entity_type'] === 'booking'): ?>
                                        <div class="cell-main"><?= htmlspecialchars($a['booking_reference']) ?></div>
                                        <div class="cell-sub"><?= htmlspecialchars(($a['booking_first_name'] ?? '') . ' ' . ($a['booking_last_name'] ?? '')) ?></div>
                                    <?php else: ?>
                                        <div class="cell-main"><?= htmlspecialchars($a['inquiry_id_code'] ?? '') ?></div>
                                        <div class="cell-sub"><?= htmlspecialchars($a['inquiry_subject'] ?? '') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y g:i A', strtotime($a['assigned_at'])) ?></td>
                                <td class="cell-muted"><?= htmlspecialchars($a['assigned_by_name'] ?? 'System') ?></td>
                                <td>
                                    <div class="cell-actions">
                                        <?php if ($a['entity_type'] === 'booking'): ?>
                                            <a href="bookings.php?ref=<?= urlencode($a['booking_reference'] ?? '') ?>" class="adm-btn-icon" title="View Booking">
                                                <span class="material-symbols-outlined">open_in_new</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="inquiries.php?thread=<?= $a['entity_id'] ?>" class="adm-btn-icon" title="View Inquiry">
                                                <span class="material-symbols-outlined">open_in_new</span>
                                            </a>
                                        <?php endif; ?>
                                        <form method="post" style="display:inline;" data-confirm="Remove this assignment?">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="unassign">
                                            <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                            <button type="submit" class="adm-btn-icon adm-btn-icon-danger" title="Remove Assignment">
                                                <span class="material-symbols-outlined">link_off</span>
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
    </div>
</main>

<!-- Assign Modal -->
<div class="adm-modal-overlay" id="assignModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h2>Assign Staff</h2>
            <button class="adm-modal-close" onclick="document.getElementById('assignModal').classList.remove('open')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="post" novalidate>
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="assign">
            <div class="adm-modal-body">
                <div class="adm-form-field" style="margin-bottom:1rem;">
                    <label for="assign-staff">Staff Member *</label>
                    <select id="assign-staff" name="staff_id" required>
                        <option value="">Select Staff</option>
                        <?php foreach ($allStaff as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= !$s['is_available'] ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($s['full_name']) ?> — <?= $departmentLabels[$s['department']] ?? $s['department'] ?>
                                <?= !$s['is_available'] ? '(Unavailable)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="adm-form-field" style="margin-bottom:1rem;">
                    <label for="assign-type">Entity Type *</label>
                    <select id="assign-type" name="entity_type" required onchange="updateEntityDropdown()">
                        <option value="">Select Type</option>
                        <option value="booking">Booking</option>
                        <option value="inquiry">Inquiry</option>
                    </select>
                </div>
                <div class="adm-form-field" style="margin-bottom:1rem;">
                    <label for="assign-entity">Select Item *</label>
                    <select id="assign-entity" name="entity_id" required>
                        <option value="">Select type first</option>
                    </select>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-secondary" onclick="document.getElementById('assignModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="adm-btn adm-btn-primary">Assign</button>
            </div>
        </form>
    </div>
</div>

<script>
var bookings = <?= json_encode($unassignedBookings) ?>;
var inquiries = <?= json_encode($unassignedInquiries) ?>;

function updateEntityDropdown() {
    var type = document.getElementById('assign-type').value;
    var entitySelect = document.getElementById('assign-entity');
    entitySelect.innerHTML = '<option value="">Select item</option>';

    if (type === 'booking') {
        bookings.forEach(function(b) {
            var opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = b.booking_reference + ' - ' + b.first_name + ' ' + b.last_name + ' (' + b.package_title + ')';
            entitySelect.appendChild(opt);
        });
    } else if (type === 'inquiry') {
        inquiries.forEach(function(i) {
            var opt = document.createElement('option');
            opt.value = i.id;
            opt.textContent = i.inquiry_id_code + ' - ' + i.subject + ' (' + i.user_name + ')';
            entitySelect.appendChild(opt);
        });
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
