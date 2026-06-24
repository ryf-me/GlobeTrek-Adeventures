<?php
$pageTitle = 'Manage Accommodations';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $delId = (int)($_POST['accom_id'] ?? 0);
        if ($delId > 0) {
            $stmt = $db->prepare("DELETE FROM accommodations WHERE id = :id");
            $stmt->execute([':id' => $delId]);
            header('Location: accommodations.php?deleted=1');
            exit;
        }
    }
}

include __DIR__ . '/includes/sidebar.php';

$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') { $where = "WHERE name LIKE :q OR location LIKE :q2"; $params[':q'] = "%$search%"; $params[':q2'] = "%$search%"; }

$stmt = $db->prepare("SELECT * FROM accommodations $where ORDER BY created_at DESC");
$stmt->execute($params);
$accommodations = $stmt->fetchAll();
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Accommodations</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Accommodation deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['saved'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Accommodation saved successfully.</div>
        <?php endif; ?>

        <div class="adm-page-header">
            <h1>Accommodations (<?= count($accommodations) ?>)</h1>
            <a href="accommodation-edit.php" class="adm-btn adm-btn-primary"><span class="material-symbols-outlined">add</span> Add Accommodation</a>
        </div>

        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search accommodations..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <?php if (empty($accommodations)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">hotel</span>
                <h2>No accommodations found</h2>
                <p>Get started by adding a new accommodation.</p>
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
                            <th>Price/Night</th>
                            <th>Rating</th>
                            <th>Active</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accommodations as $a): ?>
                            <tr>
                                <td class="cell-mono">#<?= $a['id'] ?></td>
                                <td class="cell-main"><?= htmlspecialchars($a['name']) ?></td>
                                <td><?= htmlspecialchars($a['property_type']) ?></td>
                                <td><?= htmlspecialchars($a['location']) ?></td>
                                <td class="cell-mono">Rs.<?= number_format($a['price_per_night'], 2) ?></td>
                                <td><?= number_format($a['rating'], 1) ?></td>
                                <td>
                                    <span class="adm-status-badge <?= $a['is_active'] ? 'adm-status-active' : 'adm-status-inactive' ?>">
                                        <?= $a['is_active'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="cell-actions">
                                        <a href="accommodation-edit.php?id=<?= $a['id'] ?>" class="adm-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                                        <form method="post" style="display:inline;" data-confirm="Delete this accommodation?">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="accom_id" value="<?= $a['id'] ?>">
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
