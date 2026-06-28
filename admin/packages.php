<?php
/**
 * File: admin/packages.php
 * Purpose: Lists all travel packages with search, filtering (active/inactive/featured), delete functionality, and tag display.
 * Dependencies: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php, config/database.php, config/csrf.php
 * Used By: Admin staff managing travel packages
 * Parent Files: none (entry point)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Manage Packages';
require_once __DIR__ . '/includes/header.php';

// === DELETE HANDLER ===
// Processes package deletion via POST form submission.
// Runs before sidebar include so redirects happen before any output.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    // CSRF validation — reject if token is invalid
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $delId = (int)($_POST['package_id'] ?? 0);
        if ($delId > 0) {
            $stmt = $db->prepare("DELETE FROM packages WHERE id = :id");
            $stmt->execute([':id' => $delId]);
            header('Location: packages.php?deleted=1');
            exit;
        }
    }
}

include __DIR__ . '/includes/sidebar.php';

// === FILTER & SEARCH ===
// Build dynamic WHERE clause based on filter tabs and search query.
$filter = $_GET['filter'] ?? 'all';
$where = '';
$params = [];

// Filter by active/inactive/featured status
if ($filter === 'active') { $where = "WHERE is_active = 1"; }
elseif ($filter === 'inactive') { $where = "WHERE is_active = 0"; }
elseif ($filter === 'featured') { $where = "WHERE is_featured = 1"; }

// Search by title or destination category — appends to existing WHERE clause
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $where = ($where === '') ? "WHERE" : $where . " AND";
    $where .= " (title LIKE :q OR destination_category LIKE :q2)";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
}

// === FETCH PACKAGES ===
// Ordered by newest first (created_at DESC).
$stmt = $db->prepare("SELECT * FROM packages $where ORDER BY created_at DESC");
$stmt->execute($params);
$packages = $stmt->fetchAll();

// === BATCH FETCH TAGS ===
// Instead of N+1 queries per package, fetch all tags in one query using IN clause.
// Builds an associative array: [package_id => [tag_name, ...]]
$packageTags = [];
if (!empty($packages)) {
    $pkgIds = array_column($packages, 'id');
    $placeholders = implode(',', array_fill(0, count($pkgIds), '?'));
    $tagStmt = $db->prepare("SELECT pt.package_id, t.name FROM package_tags pt JOIN tags t ON pt.tag_id = t.id WHERE pt.package_id IN ($placeholders) ORDER BY t.name");
    $tagStmt->execute($pkgIds);
    while ($row = $tagStmt->fetch()) {
        $packageTags[$row['package_id']][] = $row['name'];
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
            <h1 class="adm-topbar-title">Packages</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === FLASH MESSAGES === -->
        <!-- Display success notifications after redirect from delete/save actions. -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Package deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['saved'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Package saved successfully.</div>
        <?php endif; ?>

        <!-- === PAGE HEADER === -->
        <div class="adm-page-header">
            <h1>Packages (<?= count($packages) ?>)</h1>
            <div class="adm-page-header-actions">
                <a href="package-edit.php" class="adm-btn adm-btn-primary"><span class="material-symbols-outlined">add</span> Add Package</a>
            </div>
        </div>

        <!-- === FILTER BAR === -->
        <!-- Search input + filter tabs (All / Active / Inactive / Featured) -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search packages..." value="<?= htmlspecialchars($search) ?>">
            </form>
            <!-- Preserve search query when switching filters via tabs -->
            <a href="?filter=all<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="?filter=active<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'active' ? 'active' : '' ?>">Active</a>
            <a href="?filter=inactive<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'inactive' ? 'active' : '' ?>">Inactive</a>
            <a href="?filter=featured<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'featured' ? 'active' : '' ?>">Featured</a>
        </div>

        <!-- === PACKAGES TABLE === -->
        <?php if (empty($packages)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">luggage</span>
                <h2>No packages found</h2>
                <p>Get started by adding a new package.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Duration</th>
                            <th>Price</th>
                            <th>Tags</th>
                            <th>Featured</th>
                            <th>Active</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $p): ?>
                            <tr>
                                <td class="cell-mono">#<?= $p['id'] ?></td>
                                <td class="cell-main"><?= htmlspecialchars($p['title']) ?></td>
                                <td><?= htmlspecialchars($p['destination_category'] ?? '—') ?></td>
                                <!-- Duration displayed as "XD/YN" format -->
                                <td><?= $p['duration_days'] ?>D/<?= $p['duration_nights'] ?>N</td>
                                <td class="cell-mono"><?= formatPrice($p['price'], 2) ?></td>
                                <td>
                                    <?php
                                    // Render tags as styled badge elements
                                    $pTags = $packageTags[$p['id']] ?? [];
                                    if (!empty($pTags)):
                                        foreach ($pTags as $pTag):
                                    ?>
                                        <span class="pkg-tag"><?= htmlspecialchars($pTag) ?></span>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                        <span class="cell-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="adm-status-badge <?= $p['is_featured'] ? 'adm-status-active' : 'adm-status-inactive' ?>">
                                        <?= $p['is_featured'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="adm-status-badge <?= $p['is_active'] ? 'adm-status-active' : 'adm-status-inactive' ?>">
                                        <?= $p['is_active'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="cell-actions">
                                        <a href="package-edit.php?id=<?= $p['id'] ?>" class="adm-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                                        <!-- Delete form with CSRF token and JS confirmation dialog -->
                                        <form method="post" style="display:inline;" data-confirm="Delete this package permanently?">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="package_id" value="<?= $p['id'] ?>">
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
