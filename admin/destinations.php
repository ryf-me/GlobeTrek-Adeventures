<?php
/**
 * File: admin/destinations.php
 * Purpose: Lists all travel destinations with search, delete functionality, and tag display.
 * Dependencies: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php, config/database.php, config/csrf.php
 * Used By: Admin staff managing destinations
 * Parent Files: none (entry point)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Manage Destinations';
require_once __DIR__ . '/includes/header.php';

// === DELETE HANDLER ===
// Processes destination deletion via POST form submission.
// Runs before sidebar include so the redirect happens before any HTML output.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $delId = (int)($_POST['dest_id'] ?? 0);
        if ($delId > 0) {
            $stmt = $db->prepare("DELETE FROM destinations WHERE id = :id");
            $stmt->execute([':id' => $delId]);
            header('Location: destinations.php?deleted=1');
            exit;
        }
    }
}

include __DIR__ . '/includes/sidebar.php';

// === SEARCH ===
// Simple name-based search — builds a dynamic WHERE clause.
$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') { $where = "WHERE name LIKE :q"; $params[':q'] = "%$search%"; }

// === FETCH DESTINATIONS ===
// Ordered by newest first.
$stmt = $db->prepare("SELECT * FROM destinations $where ORDER BY created_at DESC");
$stmt->execute($params);
$destinations = $stmt->fetchAll();

// === BATCH FETCH TAGS ===
// Efficiently fetch all tags for all displayed destinations in a single query.
// Builds associative array: [destination_id => [tag_name, ...]]
$destTags = [];
if (!empty($destinations)) {
    $destIds = array_column($destinations, 'id');
    $placeholders = implode(',', array_fill(0, count($destIds), '?'));
    $tagStmt = $db->prepare("SELECT dt.destination_id, t.name FROM destination_tags dt JOIN tags t ON dt.tag_id = t.id WHERE dt.destination_id IN ($placeholders) ORDER BY t.name");
    $tagStmt->execute($destIds);
    while ($row = $tagStmt->fetch()) {
        $destTags[$row['destination_id']][] = $row['name'];
    }
}
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
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
        <!-- === FLASH MESSAGES === -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Destination deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['saved'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Destination saved successfully.</div>
        <?php endif; ?>

        <!-- === PAGE HEADER === -->
        <div class="adm-page-header">
            <h1>Destinations (<?= count($destinations) ?>)</h1>
            <a href="destination-edit.php" class="adm-btn adm-btn-primary"><span class="material-symbols-outlined">add</span> Add Destination</a>
        </div>

        <!-- === SEARCH BAR === -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search destinations..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <!-- === DESTINATIONS TABLE === -->
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
                            <th>Tags</th>
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
                                    <?php
                                    // Render destination tags as styled badges
                                    $dTags = $destTags[$d['id']] ?? [];
                                    if (!empty($dTags)):
                                        foreach ($dTags as $dTag):
                                    ?>
                                        <span class="pkg-tag"><?= htmlspecialchars($dTag) ?></span>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                        <span class="cell-muted">—</span>
                                    <?php endif; ?>
                                </td>
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
                                        <!-- Delete form with CSRF protection and JS confirmation -->
                                        <form method="post" style="display:inline;" data-confirm="Delete this destination?">
                                            <?php csrf_field(); ?>
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
