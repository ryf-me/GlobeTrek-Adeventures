<?php
/**
 * File: admin/contacts.php
 * Purpose: Lists contact form messages with search, pagination, read/unread toggle, and delete functionality.
 * Dependencies: admin/includes/header.php (auth, DB, CSRF), admin/includes/sidebar.php, admin/includes/footer.php, config/helpers.php (csrf_field)
 * Used By: Admin staff managing contact form submissions
 * Parent Files: None (entry-point page)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Contact Messages';
require_once __DIR__ . '/includes/header.php';

// === GLOBAL CSRF VALIDATION ===
// Validate CSRF token once for all POST actions — invalid token cancels all processing.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRFToken($_POST['csrf_token'] ?? null)) {
    $error = 'Invalid security token. Please try again.';
}

// === DELETE HANDLER ===
// Process message deletion via POST to prevent accidental GET-based deletions.
if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['contact_id'] ?? 0);
    if ($delId > 0) {
        $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = :id");
        $stmt->execute([':id' => $delId]);
        // PRG pattern: redirect after POST to avoid duplicate submissions on refresh.
        header('Location: contacts.php?deleted=1');
        exit;
    }
}

// === MARK READ/UNREAD TOGGLE HANDLER ===
// Toggles the is_read status of a contact message (toggle pattern via NOT).
if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_read') {
    $mid = (int)($_POST['contact_id'] ?? 0);
    if ($mid > 0) {
        // Uses SQL NOT to toggle between read (1) and unread (0) without needing to know current state.
        $stmt = $db->prepare("UPDATE contact_messages SET is_read = NOT is_read WHERE id = :id");
        $stmt->execute([':id' => $mid]);
        header('Location: contacts.php?updated=1');
        exit;
    }
}

// === SIDEBAR ===
include __DIR__ . '/includes/sidebar.php';

// === SEARCH / FILTER ===
// Build dynamic WHERE clause for name, email, and subject search.
$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE name LIKE :q OR email LIKE :q2 OR subject LIKE :q3";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
}

// === PAGINATION ===
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total row count for pagination controls.
$countStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM contact_messages $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetch()['cnt'];
$totalPages = max(1, ceil($totalRows / $perPage));

// === FETCH CONTACT MESSAGES ===
$stmt = $db->prepare("SELECT * FROM contact_messages $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
// Bind all dynamic LIKE parameters.
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
// Bind limit/offset as integers to prevent PDO type issues.
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$contacts = $stmt->fetchAll();

// === UNREAD COUNT ===
// Fetch the total number of unread messages for the badge indicator in the filter bar.
$unreadStmt = $db->query("SELECT COUNT(*) AS cnt FROM contact_messages WHERE is_read = 0");
$unreadCount = (int)$unreadStmt->fetch()['cnt'];
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Contact Messages</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === FLASH MESSAGES === -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Message deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Message status updated.</div>
        <?php endif; ?>

        <!-- === PAGE HEADER === -->
        <div class="adm-page-header">
            <h1>Contact Messages (<?= $totalRows ?>)</h1>
        </div>

        <!-- === SEARCH BAR WITH UNREAD BADGE === -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search by name, email, subject..." value="<?= htmlspecialchars($search) ?>">
            </form>
            <!-- Show unread count badge if there are unread messages -->
            <?php if ($unreadCount > 0): ?>
                <span class="adm-status-badge adm-status-active" style="font-size:0.82rem;padding:0.35rem 0.75rem;"><?= $unreadCount ?> unread</span>
            <?php endif; ?>
        </div>

        <!-- === CONTACT MESSAGES TABLE / EMPTY STATE === -->
        <?php if (empty($contacts)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">mail</span>
                <h2>No contact messages found</h2>
                <p>There are no messages matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $c): ?>
                            <tr>
                                <td class="cell-mono">#<?= $c['id'] ?></td>
                                <td class="cell-main"><?= htmlspecialchars($c['name']) ?></td>
                                <td><?= htmlspecialchars($c['email']) ?></td>
                                <td><?= htmlspecialchars($c['subject']) ?></td>
                                <!-- Truncated message preview (80 chars) with ellipsis -->
                                <td class="cell-muted" style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars(mb_strimwidth($c['message'], 0, 80, '...')) ?></td>
                                <!-- === READ/UNREAD TOGGLE BUTTON === -->
                                <!-- The badge itself is a form submit button — clicking toggles the read state. -->
                                <td>
                                    <form method="post" style="display:inline;">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="contact_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="adm-status-badge <?= $c['is_read'] ? 'adm-status-inactive' : 'adm-status-active' ?>" style="cursor:pointer;border:none;font-family:inherit;">
                                            <?= $c['is_read'] ? 'Read' : 'Unread' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                <!-- === ACTION BUTTONS (Delete) === -->
                                <td>
                                    <div class="cell-actions">
                                        <!-- Delete form with CSRF protection and JS confirmation -->
                                        <form method="post" style="display:inline;" data-confirm="Delete this message?">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="contact_id" value="<?= $c['id'] ?>">
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
                    <!-- Previous page link (disabled if on first page) -->
                    <a href="?page=<?= max(1, $page - 1) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">&laquo;</a>
                    <!-- Page number links (window of 5 pages around current) -->
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <!-- Next page link (disabled if on last page) -->
                    <a href="?page=<?= min($totalPages, $page + 1) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>">&raquo;</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
