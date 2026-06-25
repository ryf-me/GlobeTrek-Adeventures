<?php
/**
 * Staff Members List
 *
 * Displays all staff profiles with filtering by department, availability,
 * and search. Only accessible by admins.
 */
$pageTitle = 'Staff Members';
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

if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_availability') {
    $staffId = (int)($_POST['staff_id'] ?? 0);
    if ($staffId > 0) {
        $stmt = $db->prepare("UPDATE staff_profiles SET is_available = NOT is_available WHERE id = :id");
        $stmt->execute([':id' => $staffId]);
        logActivity('staff_availability_toggled', 'staff', $staffId, 'Staff availability toggled');
        header('Location: staff.php?updated=1');
        exit;
    }
}

if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $staffId = (int)($_POST['staff_id'] ?? 0);
    if ($staffId > 0) {
        // Get user_id before deleting
        $stmt = $db->prepare("SELECT user_id FROM staff_profiles WHERE id = :id");
        $stmt->execute([':id' => $staffId]);
        $staff = $stmt->fetch();
        if ($staff) {
            // Delete staff profile (cascades to permissions and assignments)
            $stmt = $db->prepare("DELETE FROM staff_profiles WHERE id = :id");
            $stmt->execute([':id' => $staffId]);
            // Demote user back to 'user' role
            $stmt = $db->prepare("UPDATE users SET role = 'user' WHERE id = :uid");
            $stmt->execute([':uid' => $staff['user_id']]);
            logActivity('staff_deleted', 'staff', $staffId, 'Staff profile deleted');
            header('Location: staff.php?deleted=1');
            exit;
        }
    }
}

include __DIR__ . '/includes/sidebar.php';

// Filters
$filter = $_GET['filter'] ?? 'all';
$department = $_GET['department'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if ($filter === 'available') {
    $where[] = "sp.is_available = 1";
} elseif ($filter === 'unavailable') {
    $where[] = "sp.is_available = 0";
}

if ($department !== 'all' && in_array($department, ['operations', 'customer_service', 'sales', 'marketing'])) {
    $where[] = "sp.department = :dept";
    $params[':dept'] = $department;
}

if ($search !== '') {
    $where[] = "(u.full_name LIKE :q OR u.email LIKE :q2 OR sp.position LIKE :q3)";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Query staff
$stmt = $db->prepare(
    "SELECT sp.*, u.full_name, u.email, u.profile_photo, u.created_at AS user_created_at,
            (SELECT COUNT(*) FROM staff_assignments sa WHERE sa.staff_id = sp.id) AS assignment_count
     FROM staff_profiles sp
     JOIN users u ON sp.user_id = u.id
     $whereSQL
     ORDER BY u.full_name ASC"
);
$stmt->execute($params);
$staffMembers = $stmt->fetchAll();

// Stats
$stats = [];
$stats['total'] = count($staffMembers);
$stats['available'] = 0;
$stats['unavailable'] = 0;
$stats['by_department'] = ['operations' => 0, 'customer_service' => 0, 'sales' => 0, 'marketing' => 0];

foreach ($staffMembers as $s) {
    if ($s['is_available']) $stats['available']++;
    else $stats['unavailable']++;
    $stats['by_department'][$s['department']] = ($stats['by_department'][$s['department']] ?? 0) + 1;
}

$departmentLabels = [
    'operations' => 'Operations',
    'customer_service' => 'Customer Service',
    'sales' => 'Sales',
    'marketing' => 'Marketing',
];
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Staff Members</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="staff-edit.php" class="adm-btn adm-btn-primary">
                <span class="material-symbols-outlined">add</span>
                <span>Add Staff</span>
            </a>
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Staff member removed successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Staff member updated successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['saved'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Staff member saved successfully.</div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="adm-alert adm-alert-error"><span class="material-symbols-outlined">error</span> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="adm-stat-grid" style="margin-bottom:1.5rem;">
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">badge</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= $stats['total'] ?></div>
                    <div class="adm-stat-card-label">Total Staff</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">check_circle</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= $stats['available'] ?></div>
                    <div class="adm-stat-card-label">Available</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-card-icon"><span class="material-symbols-outlined">do_not_disturb</span></div>
                <div class="adm-stat-card-info">
                    <div class="adm-stat-card-num"><?= $stats['unavailable'] ?></div>
                    <div class="adm-stat-card-label">Unavailable</div>
                </div>
            </div>
            <?php foreach ($departmentLabels as $key => $label): ?>
                <div class="adm-stat-card">
                    <div class="adm-stat-card-icon"><span class="material-symbols-outlined">business</span></div>
                    <div class="adm-stat-card-info">
                        <div class="adm-stat-card-num"><?= $stats['by_department'][$key] ?? 0 ?></div>
                        <div class="adm-stat-card-label"><?= $label ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Page Header -->
        <div class="adm-page-header">
            <h1>Staff Members (<?= $stats['total'] ?>)</h1>
        </div>

        <!-- Filter Bar -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search by name, email, position..." value="<?= htmlspecialchars($search) ?>">
                <?php if ($department !== 'all'): ?>
                    <input type="hidden" name="department" value="<?= htmlspecialchars($department) ?>">
                <?php endif; ?>
                <?php if ($filter !== 'all'): ?>
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <?php endif; ?>
            </form>
            <a href="?filter=all<?= $department !== 'all' ? '&department=' . urlencode($department) : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="?filter=available<?= $department !== 'all' ? '&department=' . urlencode($department) : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'available' ? 'active' : '' ?>">Available</a>
            <a href="?filter=unavailable<?= $department !== 'all' ? '&department=' . urlencode($department) : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'unavailable' ? 'active' : '' ?>">Unavailable</a>
        </div>

        <!-- Department Filter -->
        <div class="adm-tabs" style="margin-bottom:1rem;">
            <a href="?filter=<?= urlencode($filter) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $department === 'all' ? 'active' : '' ?>">All Departments</a>
            <?php foreach ($departmentLabels as $key => $label): ?>
                <a href="?department=<?= urlencode($key) ?><?= $filter !== 'all' ? '&filter=' . urlencode($filter) : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $department === $key ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($staffMembers)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">badge</span>
                <h2>No staff members found</h2>
                <p><?= $search || $filter !== 'all' || $department !== 'all' ? 'Try adjusting your filters.' : 'Add your first staff member to get started.' ?></p>
                <?php if (!$search && $filter === 'all' && $department === 'all'): ?>
                    <a href="staff-edit.php" class="adm-btn adm-btn-primary" style="margin-top:1rem;">
                        <span class="material-symbols-outlined">add</span>
                        Add Staff Member
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Active Tasks</th>
                            <th>Hire Date</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffMembers as $s): ?>
                            <tr>
                                <td>
                                    <div class="cell-main"><?= htmlspecialchars($s['full_name']) ?></div>
                                    <div class="cell-sub"><?= htmlspecialchars($s['email']) ?></div>
                                </td>
                                <td>
                                    <span class="adm-status-badge adm-status-confirmed">
                                        <?= $departmentLabels[$s['department']] ?? $s['department'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($s['position']) ?></td>
                                <td>
                                    <span class="adm-status-badge adm-status-<?= $s['is_available'] ? 'active' : 'inactive' ?>">
                                        <?= $s['is_available'] ? 'Available' : 'Unavailable' ?>
                                    </span>
                                </td>
                                <td class="cell-mono"><?= $s['assignment_count'] ?> / <?= $s['max_concurrent_tasks'] ?></td>
                                <td class="cell-muted"><?= $s['hire_date'] ? date('M d, Y', strtotime($s['hire_date'])) : '—' ?></td>
                                <td>
                                    <div class="cell-actions">
                                        <a href="staff-edit.php?id=<?= $s['id'] ?>" class="adm-btn-icon" title="Edit">
                                            <span class="material-symbols-outlined">edit</span>
                                        </a>
                                        <form method="post" style="display:inline;" data-confirm="<?= $s['is_available'] ? 'Mark as unavailable?' : 'Mark as available?' ?>">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_availability">
                                            <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="adm-btn-icon" title="<?= $s['is_available'] ? 'Mark Unavailable' : 'Mark Available' ?>">
                                                <span class="material-symbols-outlined"><?= $s['is_available'] ? 'do_not_disturb' : 'check_circle' ?></span>
                                            </button>
                                        </form>
                                        <form method="post" style="display:inline;" data-confirm="Delete this staff member permanently?">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="adm-btn-icon adm-btn-icon-danger" title="Delete">
                                                <span class="material-symbols-outlined">delete</span>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
