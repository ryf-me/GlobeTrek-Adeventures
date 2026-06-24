<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../config/logger.php';

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRFToken($_POST['csrf_token'] ?? null)) {
    $error = 'Invalid security token. Please try again.';
}

// Handle delete
if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['user_id'] ?? 0);
    if ($delId > 0 && $delId !== $_SESSION['user_id']) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $delId]);
        logActivity('user_deleted', 'user', $delId, 'User account deleted');
        header('Location: users.php?deleted=1');
        exit;
    }
}

// Handle role toggle
if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_role') {
    $rid = (int)($_POST['user_id'] ?? 0);
    $newRole = $_POST['new_role'] ?? '';
    if ($rid > 0 && in_array($newRole, ['user', 'staff', 'admin'])) {
        $stmt = $db->prepare("UPDATE users SET role = :role WHERE id = :id");
        $stmt->execute([':role' => $newRole, ':id' => $rid]);
        logActivity('user_role_changed', 'user', $rid, 'Role changed to ' . $newRole);
        header('Location: users.php?updated=1');
        exit;
    }
}

include __DIR__ . '/includes/sidebar.php';

$filter = $_GET['filter'] ?? 'all';
$where = '';
$params = [];
if ($filter === 'admin') { $where = "WHERE role = 'admin'"; }
elseif ($filter === 'user') { $where = "WHERE role = 'user'"; }
elseif ($filter === 'staff') { $where = "WHERE role = 'staff'"; }

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $where = ($where === '') ? "WHERE" : $where . " AND";
    $where .= " (full_name LIKE :q OR email LIKE :q2)";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
}

$stmt = $db->prepare("SELECT * FROM users $where ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Users</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> User deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> User updated successfully.</div>
        <?php endif; ?>

        <div class="adm-page-header">
            <h1>Users (<?= count($users) ?>)</h1>
        </div>

        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" id="adminSearch" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
            </form>
            <a href="?filter=all<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="?filter=admin<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'admin' ? 'active' : '' ?>">Admins</a>
            <a href="?filter=staff<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'staff' ? 'active' : '' ?>">Staff</a>
            <a href="?filter=user<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'user' ? 'active' : '' ?>">Users</a>
        </div>

        <?php if (empty($users)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">group</span>
                <h2>No users found</h2>
                <p>There are no users matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="cell-mono">#<?= $u['id'] ?></td>
                                <td class="cell-main"><?= htmlspecialchars($u['full_name']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                                <td>
                                    <span class="adm-status-badge adm-status-<?= $u['role'] === 'admin' ? 'confirmed' : ($u['role'] === 'staff' ? 'active' : 'inactive') ?>">
                                        <?= ucfirst(htmlspecialchars($u['role'])) ?>
                                    </span>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <div class="cell-actions">
                                        <a href="user-edit.php?id=<?= $u['id'] ?>" class="adm-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                                        <form method="post" style="display:inline;" data-confirm="Change this user's role?">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_role">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <?php
                                            $nextRole = 'user';
                                            $nextIcon = 'admin_panel_settings';
                                            $nextTitle = 'Promote to Admin';
                                            if ($u['role'] === 'user') {
                                                $nextRole = 'staff';
                                                $nextIcon = 'badge';
                                                $nextTitle = 'Promote to Staff';
                                            } elseif ($u['role'] === 'staff') {
                                                $nextRole = 'admin';
                                                $nextIcon = 'admin_panel_settings';
                                                $nextTitle = 'Promote to Admin';
                                            } else {
                                                $nextRole = 'user';
                                                $nextIcon = 'shield';
                                                $nextTitle = 'Demote to User';
                                            }
                                            ?>
                                            <input type="hidden" name="new_role" value="<?= $nextRole ?>">
                                            <button type="submit" class="adm-btn-icon" title="<?= $nextTitle ?>">
                                                <span class="material-symbols-outlined"><?= $nextIcon ?></span>
                                            </button>
                                        </form>
                                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                            <form method="post" style="display:inline;" data-confirm="Delete this user permanently?">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="adm-btn-icon adm-btn-icon-danger" title="Delete"><span class="material-symbols-outlined">delete</span></button>
                                            </form>
                                        <?php endif; ?>
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
