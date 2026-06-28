<?php
/**
 * File: admin/guides.php
 * Purpose: Lists all tour guides with search, tag display, and delete functionality.
 * Dependencies: admin/includes/header.php (auth, DB, CSRF), admin/includes/sidebar.php, admin/includes/footer.php, config/helpers.php (csrf_field)
 * Used By: Admin staff accessing the guides management page
 * Parent Files: None (entry-point page)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Manage Guides';
require_once __DIR__ . '/includes/header.php';

// === DELETE HANDLER ===
// Process guide deletion via POST to prevent accidental GET-based deletions.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    // CSRF token validation — rejects the request if the token is missing or invalid.
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Cast to int to prevent SQL injection via type juggling.
        $delId = (int)($_POST['guide_id'] ?? 0);
        if ($delId > 0) {
            $stmt = $db->prepare("DELETE FROM guides WHERE id = :id");
            $stmt->execute([':id' => $delId]);
            // PRG pattern: redirect after POST to avoid duplicate submissions on refresh.
            header('Location: guides.php?deleted=1');
            exit;
        }
    }
}

// === SIDEBAR ===
include __DIR__ . '/includes/sidebar.php';

// === SEARCH / FILTER ===
// Build dynamic WHERE clause for name and specialty search.
$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') { $where = "WHERE name LIKE :q OR specialty LIKE :q2"; $params[':q'] = "%$search%"; $params[':q2'] = "%$search%"; }

// === FETCH GUIDES ===
$stmt = $db->prepare("SELECT * FROM guides $where ORDER BY created_at DESC");
$stmt->execute($params);
$guides = $stmt->fetchAll();

// === FETCH GUIDE TAGS ===
// Eagerly load all tags for the displayed guides in a single query
// to avoid N+1 query problems on the tag display column.
$guideTags = [];
if (!empty($guides)) {
    $guideIds = array_column($guides, 'id');
    // Build dynamic placeholder list for the IN clause.
    $placeholders = implode(',', array_fill(0, count($guideIds), '?'));
    $tagStmt = $db->prepare("SELECT gt.guide_id, t.name FROM guide_tags gt JOIN tags t ON gt.tag_id = t.id WHERE gt.guide_id IN ($placeholders) ORDER BY t.name");
    $tagStmt->execute($guideIds);
    while ($row = $tagStmt->fetch()) {
        $guideTags[$row['guide_id']][] = $row['name'];
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
            <h1 class="adm-topbar-title">Guides</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === FLASH MESSAGES === -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Guide deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['saved'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Guide saved successfully.</div>
        <?php endif; ?>

        <!-- === PAGE HEADER === -->
        <div class="adm-page-header">
            <h1>Guides (<?= count($guides) ?>)</h1>
            <a href="guide-edit.php" class="adm-btn adm-btn-primary"><span class="material-symbols-outlined">add</span> Add Guide</a>
        </div>

        <!-- === SEARCH BAR === -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search guides..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <!-- === GUIDES TABLE / EMPTY STATE === -->
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
                            <th>Tags</th>
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
                                <!-- Tag badges: display each tag or a dash if none exist -->
                                <td>
                                    <?php
                                    $gTags = $guideTags[$g['id']] ?? [];
                                    if (!empty($gTags)):
                                        foreach ($gTags as $gTag):
                                    ?>
                                        <span class="pkg-tag"><?= htmlspecialchars($gTag) ?></span>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                        <span class="cell-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Featured / Active status badges -->
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
                                <!-- === ACTION BUTTONS (Edit / Delete) === -->
                                <td>
                                    <div class="cell-actions">
                                        <a href="guide-edit.php?id=<?= $g['id'] ?>" class="adm-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                                        <!-- Delete form with CSRF protection and JS confirmation -->
                                        <form method="post" style="display:inline;" data-confirm="Delete this guide?">
                                            <?php csrf_field(); ?>
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
