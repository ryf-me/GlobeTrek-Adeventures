<?php
/**
 * File: admin/system-logs.php
 * Purpose: Admin page to view, search, and filter system activity logs with pagination.
 * Dependencies: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php, config/database.php
 * Used By: Admin users navigating via admin sidebar
 * Parent Files: None (entry point for system logs viewer)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

// === PAGE SETUP ===
$pageTitle = 'System Logs';
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

// === FILTER PARAMETERS ===
$actionFilter = $_GET['action_filter'] ?? '';
$search = trim($_GET['q'] ?? '');

// === PAGINATION ===
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20; // Fixed page size
$offset = ($page - 1) * $perPage;

// === BUILD DYNAMIC WHERE CLAUSE ===
// Constructs WHERE conditions based on active filters
$where = '';
$params = [];

// Action type filter - exact match on action column
if ($actionFilter !== '') {
    $where = "WHERE al.action = :action";
    $params[':action'] = $actionFilter;
}

// Text search - searches across action, entity_type, and details columns
// Appends AND condition if action filter is already present
if ($search !== '') {
    $where = ($where === '') ? "WHERE" : $where . " AND";
    $where .= " (al.action LIKE :q OR al.entity_type LIKE :q2 OR al.details LIKE :q3)";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
}

// === COUNT TOTAL MATCHING ROWS ===
$countStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM activity_logs al $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetch()['cnt'];
$totalPages = max(1, ceil($totalRows / $perPage));

// === FETCH PAGINATED LOGS ===
// LEFT JOIN to users table to resolve user_id to human-readable name
$stmt = $db->prepare(
    "SELECT al.*, u.full_name AS user_name
     FROM activity_logs al
     LEFT JOIN users u ON al.user_id = u.id
     $where
     ORDER BY al.created_at DESC
     LIMIT :limit OFFSET :offset"
);
// Bind dynamic search parameters
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
// Bind integer parameters with explicit type for PDO safety
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// === GET DISTINCT ACTIONS FOR FILTER DROPDOWN ===
// Populates the action filter dropdown with all unique action types
$r = $db->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");
$actions = $r->fetchAll(PDO::FETCH_COLUMN);
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
            <h1 class="adm-topbar-title">System Logs</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === PAGE HEADER === -->
        <div class="adm-page-header">
            <h1>Activity Logs (<?= $totalRows ?>)</h1>
        </div>

        <!-- === FILTER BAR === -->
        <!-- Combined search and action type filter -->
        <div class="adm-filter-bar">
            <form method="get" style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap; flex:1;">
                <div class="adm-search" style="flex:1; min-width:200px;">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" name="q" placeholder="Search logs..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <!-- Action type dropdown - populated from distinct actions in database -->
                <select name="action_filter" style="padding:0.5rem; border:1px solid var(--adm-outline-variant); border-radius:6px;">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $act): ?>
                        <option value="<?= htmlspecialchars($act) ?>" <?= $actionFilter === $act ? 'selected' : '' ?>><?= htmlspecialchars($act) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="adm-btn adm-btn-primary" style="padding:0.5rem 1rem;">Filter</button>
            </form>
        </div>

        <!-- === EMPTY STATE === -->
        <?php if (empty($logs)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">history</span>
                <h2>No logs found</h2>
                <p>No activity logs match your criteria.</p>
            </div>
        <?php else: ?>
            <!-- === LOGS TABLE === -->
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- === LOG ENTRY LOOP === -->
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <!-- Log ID in monospace for readability -->
                                <td class="cell-mono">#<?= $log['id'] ?></td>
                                <!-- Formatted timestamp -->
                                <td class="cell-muted"><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></td>
                                <!-- User name from JOIN; fallback to 'System' for system-generated actions -->
                                <td><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                                <td>
                                    <!-- Action type displayed as status badge -->
                                    <span class="adm-status-badge adm-status-active"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <!-- Entity type and ID displayed together -->
                                <td><?= htmlspecialchars($log['entity_type']) ?> #<?= $log['entity_id'] ?? '—' ?></td>
                                <!-- Details truncated with CSS ellipsis for long text -->
                                <td class="cell-muted" style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= htmlspecialchars($log['details'] ?? '—') ?>
                                </td>
                                <!-- IP address in monospace for technical readability -->
                                <td class="cell-mono"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- === PAGINATION === -->
            <?php if ($totalPages > 1): ?>
                <div class="adm-pagination">
                    <!-- Previous page - preserves filter state across page navigation -->
                    <a href="?page=<?= max(1, $page - 1) ?>&action_filter=<?= urlencode($actionFilter) ?>&q=<?= urlencode($search) ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">&laquo;</a>
                    <!-- Page number window (5 pages centered on current) -->
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?>&action_filter=<?= urlencode($actionFilter) ?>&q=<?= urlencode($search) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <!-- Next page -->
                    <a href="?page=<?= min($totalPages, $page + 1) ?>&action_filter=<?= urlencode($actionFilter) ?>&q=<?= urlencode($search) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>">&raquo;</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
