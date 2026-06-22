<?php
$pageTitle = 'Contact Messages';
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['contact_id'] ?? 0);
    if ($delId > 0) {
        $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = :id");
        $stmt->execute([':id' => $delId]);
        header('Location: contacts.php?deleted=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_read') {
    $mid = (int)($_POST['contact_id'] ?? 0);
    if ($mid > 0) {
        $stmt = $db->prepare("UPDATE contact_messages SET is_read = NOT is_read WHERE id = :id");
        $stmt->execute([':id' => $mid]);
        header('Location: contacts.php?updated=1');
        exit;
    }
}

$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE name LIKE :q OR email LIKE :q2 OR subject LIKE :q3";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM contact_messages $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetch()['cnt'];
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = $db->prepare("SELECT * FROM contact_messages $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$contacts = $stmt->fetchAll();

$unreadStmt = $db->query("SELECT COUNT(*) AS cnt FROM contact_messages WHERE is_read = 0");
$unreadCount = (int)$unreadStmt->fetch()['cnt'];
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
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
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Message deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Message status updated.</div>
        <?php endif; ?>

        <div class="adm-page-header">
            <h1>Contact Messages (<?= $totalRows ?>)</h1>
        </div>

        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search by name, email, subject..." value="<?= htmlspecialchars($search) ?>">
            </form>
            <?php if ($unreadCount > 0): ?>
                <span class="adm-status-badge adm-status-active" style="font-size:0.82rem;padding:0.35rem 0.75rem;"><?= $unreadCount ?> unread</span>
            <?php endif; ?>
        </div>

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
                                <td class="cell-muted" style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars(mb_strimwidth($c['message'], 0, 80, '...')) ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="contact_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="adm-status-badge <?= $c['is_read'] ? 'adm-status-inactive' : 'adm-status-active' ?>" style="cursor:pointer;border:none;font-family:inherit;">
                                            <?= $c['is_read'] ? 'Read' : 'Unread' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                <td>
                                    <div class="cell-actions">
                                        <form method="post" style="display:inline;" data-confirm="Delete this message?">
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

            <?php if ($totalPages > 1): ?>
                <div class="adm-pagination">
                    <a href="?page=<?= max(1, $page - 1) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">&laquo;</a>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?page=<?= min($totalPages, $page + 1) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>">&raquo;</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>