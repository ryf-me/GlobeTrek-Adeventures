<?php
$pageTitle = 'Manage Destinations';
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['dest_id'] ?? 0);
    if ($delId > 0) {
        $stmt = $db->prepare("DELETE FROM destinations WHERE id = :id");
        $stmt->execute([':id' => $delId]);
        header('Location: destinations.php?deleted=1');
        exit;
    }
}

$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') { $where = "WHERE name LIKE :q"; $params[':q'] = "%$search%"; }

$stmt = $db->prepare("SELECT * FROM destinations $where ORDER BY created_at DESC");
$stmt->execute($params);
$destinations = $stmt->fetchAll();
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Destinations</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Destination deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['saved'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Destination saved successfully.</div>
        <?php endif; ?>

        <div class="adm-page-header">
            <h1>Destinations (<?= count($destinations) ?>)</h1>
            <a href="destination-edit.php" class="adm-btn adm-btn-primary"><span class="material-symbols-outlined">add</span> Add Destination</a>
        </div>

        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search destinations..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <?php if (empty($destinations)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">location_on</span>
                <h2>No destinations found</h2>
                <p>Get started by adding a new destination.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Featured</th>
                            <th>Active</th>
                            <th>Created</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($destinations as $d): ?>
                            <tr>
                                <td class="cell-mono">#<?= $d['id'] ?></td>
                                <td class="cell-main"><?= htmlspecialchars($d['name']) ?></td>
                                <td class="cell-muted"><?= htmlspecialchars($d['slug']) ?></td>
                                <td>
                                    <span class="adm-status-badge <?= $d['is_featured'] ? 'adm-status-active' : 'adm-status-inactive' ?>">
                                        <?= $d['is_featured'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="adm-status-badge <?= $d['is_active'] ? 'adm-status-active' : 'adm-status-inactive' ?>">
                                        <?= $d['is_active'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($d['created_at'])) ?></td>
                                <td>
                                    <div class="cell-actions">
                                        <a href="destination-edit.php?id=<?= $d['id'] ?>" class="adm-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                                        <form method="post" style="display:inline;" data-confirm="Delete this destination?">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="dest_id" value="<?= $d['id'] ?>">
                                            <button type="submit" class="adm-btn-icon adm-btn-icon-danger" title="Delete"><span class="material-symbols-outlined">delete</span></button>
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
