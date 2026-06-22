<?php
$pageTitle = 'Manage Guides';
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['guide_id'] ?? 0);
    if ($delId > 0) {
        $stmt = $db->prepare("DELETE FROM guides WHERE id = :id");
        $stmt->execute([':id' => $delId]);
        header('Location: guides.php?deleted=1');
        exit;
    }
}

$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') { $where = "WHERE name LIKE :q OR specialty LIKE :q2"; $params[':q'] = "%$search%"; $params[':q2'] = "%$search%"; }

$stmt = $db->prepare("SELECT * FROM guides $where ORDER BY created_at DESC");
$stmt->execute($params);
$guides = $stmt->fetchAll();
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Guides</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Guide deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['saved'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Guide saved successfully.</div>
        <?php endif; ?>

        <div class="adm-page-header">
            <h1>Guides (<?= count($guides) ?>)</h1>
            <a href="guide-edit.php" class="adm-btn adm-btn-primary"><span class="material-symbols-outlined">add</span> Add Guide</a>
        </div>

        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search guides..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <?php if (empty($guides)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">person_raised_hand</span>
                <h2>No guides found</h2>
                <p>Get started by adding a new guide.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Specialty</th>
                            <th>Region</th>
                            <th>Featured</th>
                            <th>Active</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($guides as $g): ?>
                            <tr>
                                <td class="cell-mono">#<?= $g['id'] ?></td>
                                <td class="cell-main"><?= htmlspecialchars($g['name']) ?></td>
                                <td><?= htmlspecialchars($g['specialty'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($g['region'] ?? '—') ?></td>
                                <td>
                                    <span class="adm-status-badge <?= $g['is_featured'] ? 'adm-status-active' : 'adm-status-inactive' ?>">
                                        <?= $g['is_featured'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="adm-status-badge <?= $g['is_active'] ? 'adm-status-active' : 'adm-status-inactive' ?>">
                                        <?= $g['is_active'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="cell-actions">
                                        <a href="guide-edit.php?id=<?= $g['id'] ?>" class="adm-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                                        <form method="post" style="display:inline;" data-confirm="Delete this guide?">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="guide_id" value="<?= $g['id'] ?>">
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
