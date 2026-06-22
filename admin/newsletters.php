<?php
$pageTitle = 'Newsletter Subscribers';
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['sub_id'] ?? 0);
    if ($delId > 0) {
        $stmt = $db->prepare("DELETE FROM newsletter_subscriptions WHERE id = :id");
        $stmt->execute([':id' => $delId]);
        header('Location: newsletters.php?deleted=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_active') {
    $sid = (int)($_POST['sub_id'] ?? 0);
    if ($sid > 0) {
        $stmt = $db->prepare("UPDATE newsletter_subscriptions SET is_active = NOT is_active WHERE id = :id");
        $stmt->execute([':id' => $sid]);
        header('Location: newsletters.php?updated=1');
        exit;
    }
}

$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') { $where = "WHERE email LIKE :q"; $params[':q'] = "%$search%"; }

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM newsletter_subscriptions $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetch()['cnt'];
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = $db->prepare("SELECT * FROM newsletter_subscriptions $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$subscriptions = $stmt->fetchAll();

$activeStmt = $db->query("SELECT COUNT(*) AS cnt FROM newsletter_subscriptions WHERE is_active = 1");
$activeCount = (int)$activeStmt->fetch()['cnt'];
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
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
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Subscriber removed successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Subscriber status updated.</div>
        <?php endif; ?>

        <div class="adm-page-header">
            <h1>Newsletter Subscribers (<?= $totalRows ?>)</h1>
        </div>

        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search by email..." value="<?= htmlspecialchars($search) ?>">
            </form>
            <span class="adm-status-badge adm-status-active" style="font-size:0.82rem;padding:0.35rem 0.75rem;"><?= $activeCount ?> active</span>
        </div>

        <?php if (empty($subscriptions)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">campaign</span>
                <h2>No subscribers found</h2>
                <p>There are no newsletter subscribers matching your criteria.</p>
            </div>
        <?php else: ?>
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
                        <?php foreach ($subscriptions as $s): ?>
                            <tr>
                                <td class="cell-mono">#<?= $s['id'] ?></td>
                                <td class="cell-main"><?= htmlspecialchars($s['email']) ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="adm-status-badge <?= $s['is_active'] ? 'adm-status-active' : 'adm-status-inactive' ?>" style="cursor:pointer;border:none;font-family:inherit;">
                                            <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                                <td>
                                    <div class="cell-actions">
                                        <form method="post" style="display:inline;" data-confirm="Remove this subscriber?">
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