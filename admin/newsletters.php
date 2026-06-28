<?php
/**
 * File: admin/newsletters.php
 * Purpose: Admin page to view, search, delete, and toggle active status of newsletter subscribers.
 * Dependencies: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php, config/database.php
 * Used By: Admin users navigating via admin sidebar
 * Parent Files: None (entry point for newsletter management)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

// === PAGE SETUP ===
$pageTitle = 'Newsletter Subscribers';
require_once __DIR__ . '/includes/header.php';

// === CSRF VALIDATION ===
// Validate CSRF token on POST requests to prevent cross-site request forgery attacks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRFToken($_POST['csrf_token'] ?? null)) {
    $error = 'Invalid security token. Please try again.';
}

// === DELETE SUBSCRIBER ===
// Handle subscriber deletion - only processes if no prior error and action is 'delete'
if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    // Cast to integer to prevent SQL injection via parameter type
    $delId = (int)($_POST['sub_id'] ?? 0);
    if ($delId > 0) {
        $stmt = $db->prepare("DELETE FROM newsletter_subscriptions WHERE id = :id");
        $stmt->execute([':id' => $delId]);
        // Redirect after successful deletion to prevent form resubmission (PRG pattern)
        header('Location: newsletters.php?deleted=1');
        exit;
    }
}

// === TOGGLE ACTIVE STATUS ===
// Toggle subscriber's active/inactive status using SQL NOT operator for boolean flip
if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_active') {
    $sid = (int)($_POST['sub_id'] ?? 0);
    if ($sid > 0) {
        // NOT is_active flips the boolean value (1->0 or 0->1) in a single query
        $stmt = $db->prepare("UPDATE newsletter_subscriptions SET is_active = NOT is_active WHERE id = :id");
        $stmt->execute([':id' => $sid]);
        header('Location: newsletters.php?updated=1');
        exit;
    }
}

// === SIDEBAR INCLUDE ===
include __DIR__ . '/includes/sidebar.php';

// === SEARCH FILTER ===
// Build dynamic WHERE clause for email search
$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    // Sanitize search input by using prepared statement with LIKE operator
    $where = "WHERE email LIKE :q";
    $params[':q'] = "%$search%";
}

// === PAGINATION ===
// Calculate pagination parameters with sensible defaults
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20; // Fixed page size for consistent UX
$offset = ($page - 1) * $perPage;

// === COUNT TOTAL ROWS ===
// Get total matching rows for pagination calculation
$countStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM newsletter_subscriptions $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetch()['cnt'];
$totalPages = max(1, ceil($totalRows / $perPage));

// === FETCH PAGINATED RESULTS ===
// Retrieve subscribers for current page with LIMIT/OFFSET pagination
$stmt = $db->prepare("SELECT * FROM newsletter_subscriptions $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
// Bind dynamic search parameters first
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
// Bind integer parameters with explicit type for PDO safety
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$subscriptions = $stmt->fetchAll();

// === ACTIVE SUBSCRIBER COUNT ===
// Get count of active subscribers for status badge display
$activeStmt = $db->query("SELECT COUNT(*) AS cnt FROM newsletter_subscriptions WHERE is_active = 1");
$activeCount = (int)$activeStmt->fetch()['cnt'];
?>

<!-- === ADMIN LAYOUT === -->
<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <!-- Mobile menu toggle button - controls sidebar visibility on small screens -->
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Newsletter Subscribers</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === FLASH MESSAGES === -->
        <!-- Display success messages after redirect from delete/toggle actions -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Subscriber removed successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Subscriber status updated.</div>
        <?php endif; ?>

        <!-- === PAGE HEADER === -->
        <div class="adm-page-header">
            <h1>Newsletter Subscribers (<?= $totalRows ?>)</h1>
        </div>

        <!-- === SEARCH & STATUS BAR === -->
        <div class="adm-filter-bar">
            <!-- Search form - uses GET method for bookmarkable URLs -->
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <!-- htmlspecialchars prevents XSS in search input display -->
                <input type="text" name="q" placeholder="Search by email..." value="<?= htmlspecialchars($search) ?>">
            </form>
            <!-- Active subscribers count badge -->
            <span class="adm-status-badge adm-status-active" style="font-size:0.82rem;padding:0.35rem 0.75rem;"><?= $activeCount ?> active</span>
        </div>

        <!-- === EMPTY STATE === -->
        <?php if (empty($subscriptions)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">campaign</span>
                <h2>No subscribers found</h2>
                <p>There are no newsletter subscribers matching your criteria.</p>
            </div>
        <?php else: ?>
            <!-- === SUBSCRIBERS TABLE === -->
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Subscribed Date</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- === ROW LOOP === -->
                        <?php foreach ($subscriptions as $s): ?>
                            <tr>
                                <!-- Subscriber ID displayed in monospace font -->
                                <td class="cell-mono">#<?= $s['id'] ?></td>
                                <!-- Email with htmlspecialchars to prevent XSS -->
                                <td class="cell-main"><?= htmlspecialchars($s['email']) ?></td>
                                <td>
                                    <!-- Inline form for toggling active status - clicking badge triggers toggle -->
                                    <form method="post" style="display:inline;">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                                        <!-- Badge doubles as a button - color changes based on status -->
                                        <button type="submit" class="adm-status-badge <?= $s['is_active'] ? 'adm-status-active' : 'adm-status-inactive' ?>" style="cursor:pointer;border:none;font-family:inherit;">
                                            <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                                        </button>
                                    </form>
                                </td>
                                <!-- Format date for display -->
                                <td class="cell-muted"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                                <td>
                                    <div class="cell-actions">
                                        <!-- Delete button with JavaScript confirmation dialog -->
                                        <form method="post" style="display:inline;" data-confirm="Remove this subscriber?">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="adm-btn-icon adm-btn-icon-danger" title="Delete"><span class="material-symbols-outlined">delete</span></button>
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
                    <!-- Previous page link - disabled if on first page -->
                    <a href="?page=<?= max(1, $page - 1) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">&laquo;</a>
                    <!-- Page number links - shows window of 5 pages around current page -->
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <!-- Next page link - disabled if on last page -->
                    <a href="?page=<?= min($totalPages, $page + 1) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>">&raquo;</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
