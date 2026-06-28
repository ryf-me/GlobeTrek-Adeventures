<?php
/**
 * File: admin/tags.php
 * Purpose: Lists and manages all tags used for packages, destinations, and guides.
 * Dependencies: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * Used By: Admin/staff users with appropriate permissions
 * Parent Files: admin/includes/sidebar.php (navigated from sidebar menu)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Manage Tags';

// === INITIALIZATION ===
require_once __DIR__ . '/includes/header.php';

// === TAG DELETION ===
// Handle delete action via POST request with CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Sanitize tag ID to integer to prevent SQL injection
        $delId = (int)($_POST['tag_id'] ?? 0);
        if ($delId > 0) {
            $stmt = $db->prepare("DELETE FROM tags WHERE id = :id");
            $stmt->execute([':id' => $delId]);
            header('Location: tags.php?deleted=1');
            exit;
        }
    }
}

// === SIDEBAR ===
include __DIR__ . '/includes/sidebar.php';

// === SEARCH & FILTER ===
// Read search query from URL, build dynamic WHERE clause
$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') { $where = "WHERE t.name LIKE :q"; $params[':q'] = "%$search%"; }

// === FETCH TAGS ===
// Include subquery counts for packages, destinations, and guides associated with each tag
$stmt = $db->prepare("
    SELECT t.*,
        (SELECT COUNT(*) FROM package_tags WHERE tag_id = t.id) AS pkg_count,
        (SELECT COUNT(*) FROM destination_tags WHERE tag_id = t.id) AS dest_count,
        (SELECT COUNT(*) FROM guide_tags WHERE tag_id = t.id) AS guide_count
    FROM tags t
    $where
    ORDER BY t.name ASC
");
$stmt->execute($params);
$tags = $stmt->fetchAll();
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Tags</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === SUCCESS / STATUS ALERTS === -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Tag deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['saved'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Tag saved successfully.</div>
        <?php endif; ?>

        <!-- === PAGE HEADER === -->
        <div class="adm-page-header">
            <h1>Tags (<?= count($tags) ?>)</h1>
            <a href="tag-edit.php" class="adm-btn adm-btn-primary"><span class="material-symbols-outlined">add</span> Add Tag</a>
        </div>

        <!-- === SEARCH BAR === -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search tags..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <!-- === TAGS TABLE === -->
        <?php if (empty($tags)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">label</span>
                <h2>No tags found</h2>
                <p>Get started by adding a new tag.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Packages</th>
                            <th>Destinations</th>
                            <th>Guides</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tags as $t): ?>
                            <tr>
                                <td class="cell-mono">#<?= $t['id'] ?></td>
                                <td class="cell-main"><?= htmlspecialchars($t['name']) ?></td>
                                <!-- Display usage counts from subqueries -->
                                <td><?= (int)$t['pkg_count'] ?></td>
                                <td><?= (int)$t['dest_count'] ?></td>
                                <td><?= (int)$t['guide_count'] ?></td>
                                <td>
                                    <div class="cell-actions">
                                        <!-- Edit link to tag-edit.php with tag ID -->
                                        <a href="tag-edit.php?id=<?= $t['id'] ?>" class="adm-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                                        <!-- Delete with confirmation — warns about removal from all associated items -->
                                        <form method="post" style="display:inline;" data-confirm="Delete this tag? It will be removed from all associated items.">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="tag_id" value="<?= $t['id'] ?>">
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
