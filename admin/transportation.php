<?php
$pageTitle = 'Manage Transportation';
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['transport_id'] ?? 0);
    if ($delId > 0) {
        $stmt = $db->prepare("DELETE FROM transportations WHERE id = :id");
        $stmt->execute([':id' => $delId]);
        header('Location: transportation.php?deleted=1');
        exit;
    }
}

$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') { $where = "WHERE name LIKE :q OR location LIKE :q2 OR vehicle_type LIKE :q3"; $params[':q'] = "%$search%"; $params[':q2'] = "%$search%"; $params[':q3'] = "%$search%"; }

$stmt = $db->prepare("SELECT * FROM transportations $where ORDER BY created_at DESC");
$stmt->execute($params);
$transports = $stmt->fetchAll();
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Transportation</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Transport deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['saved'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Transport saved successfully.</div>
        <?php endif; ?>

        <div class="adm-page-header">
            <h1>Transportation (<?= count($transports) ?>)</h1>
            <a href="transport-edit.php" class="adm-btn adm-btn-primary"><span class="material-symbols-outlined">add</span> Add Transport</a>
        </div>

        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search transportation..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <?php if (empty($transports)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">directions_car</span>
                <h2>No transportation found</h2>
                <p>Get started by adding a new transport option.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Price/Day</th>
                            <th>Rating</th>
                            <th>Available</th>
                            <th>Active</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transports as $t): ?>
                            <tr>
                                <td class="cell-mono">#<?= $t['id'] ?></td>
                                <td class="cell-main"><?= htmlspecialchars($t['name']) ?></td>
                                <td><?= htmlspecialchars($t['vehicle_type']) ?></td>
                                <td><?= htmlspecialchars($t['location']) ?></td>
                                <td class="cell-mono">Rs.<?= number_format($t['price_per_day'], 2) ?></td>
                                <td><?= number_format($t['rating'], 1) ?></td>
                                <td>
                                    <span class="adm-status-badge <?= $t['is_available'] ? 'adm-status-active' : 'adm-status-inactive' ?>">
                                        <?= $t['is_available'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="adm-status-badge <?= $t['is_active'] ? 'adm-status-active' : 'adm-status-inactive' ?>">
                                        <?= $t['is_active'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="cell-actions">
                                        <a href="transport-edit.php?id=<?= $t['id'] ?>" class="adm-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                                        <form method="post" style="display:inline;" data-confirm="Delete this transport?">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="transport_id" value="<?= $t['id'] ?>">
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