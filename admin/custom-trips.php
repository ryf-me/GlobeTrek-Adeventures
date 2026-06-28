<?php
/**
 * File: admin/custom-trips.php
 * Purpose: Lists custom trip requests with status filtering, search, pagination, and expandable detail rows.
 * Dependencies: admin/includes/header.php (auth, DB, CSRF), admin/includes/sidebar.php, admin/includes/footer.php, config/helpers.php (csrf_field)
 * Used By: Admin staff managing custom trip requests
 * Parent Files: None (entry-point page)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Custom Trip Requests';
require_once __DIR__ . '/includes/header.php';

// === STATUS UPDATE HANDLER ===
// Allows admin to change a custom trip request's status (pending/reviewed/completed).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    // CSRF token validation — rejects the request if the token is missing or invalid.
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $tid = (int)($_POST['trip_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        // Validate status against allowed values to prevent arbitrary status injection.
        if ($tid > 0 && in_array($newStatus, ['pending', 'reviewed', 'completed'])) {
            $stmt = $db->prepare("UPDATE custom_trip_requests SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $tid]);
            // PRG pattern: redirect after POST to avoid duplicate submissions on refresh.
            header('Location: custom-trips.php?updated=1');
            exit;
        }
    }
}

// === SIDEBAR ===
include __DIR__ . '/includes/sidebar.php';

// === FILTER / SEARCH / PAGINATION ===
$filter = $_GET['filter'] ?? 'all';
$where = '';
$params = [];
// Build WHERE clause based on the active status filter tab.
if ($filter === 'pending') { $where = "WHERE status = 'pending'"; }
elseif ($filter === 'reviewed') { $where = "WHERE status = 'reviewed'"; }
elseif ($filter === 'completed') { $where = "WHERE status = 'completed'"; }

// Append search conditions for name, email, and destination fields.
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $where = ($where === '') ? "WHERE" : $where . " AND";
    $where .= " (full_name LIKE :q OR email LIKE :q2 OR destination LIKE :q3)";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
}

// Calculate pagination offset.
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total row count for pagination controls.
$countStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM custom_trip_requests $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetch()['cnt'];
$totalPages = max(1, ceil($totalRows / $perPage));

// === FETCH CUSTOM TRIP REQUESTS ===
$stmt = $db->prepare("SELECT * FROM custom_trip_requests $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
// Bind all dynamic LIKE parameters.
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
// Bind limit/offset as integers to prevent PDO type issues.
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$trips = $stmt->fetchAll();

// === STATUS STATS ===
// Aggregate trip request counts by status for the tab filter badges.
$stats = [];
$res = $db->query("SELECT status, COUNT(*) AS cnt FROM custom_trip_requests GROUP BY status");
foreach ($res->fetchAll() as $r) $stats[$r['status']] = (int)$r['cnt'];
$totalTrips = array_sum($stats);
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Custom Trip Requests</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === FLASH MESSAGE === -->
        <?php if (isset($_GET['updated'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Trip request status updated.</div>
        <?php endif; ?>

        <!-- === PAGE HEADER === -->
        <div class="adm-page-header">
            <h1>Custom Trip Requests (<?= $totalTrips ?>)</h1>
        </div>

        <!-- === STATUS FILTER TABS === -->
        <div class="adm-tabs">
            <a href="?filter=all<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'all' ? 'active' : '' ?>">All (<?= $totalTrips ?>)</a>
            <a href="?filter=pending<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'pending' ? 'active' : '' ?>">Pending (<?= $stats['pending'] ?? 0 ?>)</a>
            <a href="?filter=reviewed<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'reviewed' ? 'active' : '' ?>">Reviewed (<?= $stats['reviewed'] ?? 0 ?>)</a>
            <a href="?filter=completed<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'completed' ? 'active' : '' ?>">Completed (<?= $stats['completed'] ?? 0 ?>)</a>
        </div>

        <!-- === SEARCH BAR === -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <!-- Preserve the active filter when searching -->
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search by name, email, destination..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <!-- === CUSTOM TRIPS TABLE / EMPTY STATE === -->
        <?php if (empty($trips)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">route</span>
                <h2>No custom trip requests found</h2>
                <p>There are no requests matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Destination</th>
                            <th>Duration</th>
                            <th>Travelers</th>
                            <th>Style</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trips as $t): ?>
                            <!-- === MAIN ROW === -->
                            <tr>
                                <td class="cell-mono">#<?= $t['id'] ?></td>
                                <td class="cell-main"><?= htmlspecialchars($t['full_name']) ?></td>
                                <td><?= htmlspecialchars($t['email']) ?></td>
                                <td><?= htmlspecialchars($t['destination'] ?? '—') ?></td>
                                <td><?= $t['duration_days'] ? $t['duration_days'] . ' days' : '—' ?></td>
                                <td><?= $t['num_travelers'] ?? '—' ?></td>
                                <td><?= ucfirst(htmlspecialchars($t['travel_style'] ?? '—')) ?></td>
                                <!-- === STATUS BADGE === -->
                                <td>
                                    <span class="adm-status-badge adm-status-<?= $t['status'] ?>">
                                        <span class="adm-badge-dot"></span>
                                        <?= ucfirst(htmlspecialchars($t['status'])) ?>
                                    </span>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                                <!-- === ACTION BUTTONS (View Details / Status Update) === -->
                                <td>
                                    <div class="cell-actions">
                                        <!-- Toggle detail row visibility via inline JS -->
                                        <button type="button" class="adm-btn-icon" title="View Details" onclick="var el=document.getElementById('detail-<?= $t['id'] ?>');el.style.display=el.style.display==='none'||el.style.display===''?'table-row':'none';">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                        <!-- Status update form with JS confirmation on change -->
                                        <form method="post" style="display:flex;gap:0.35rem;">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="trip_id" value="<?= $t['id'] ?>">
                                            <select name="status" onchange="if(confirm('Update status?')) this.form.submit();" style="padding:0.3rem 0.5rem;border:1px solid var(--adm-outline-variant);font-size:0.78rem;font-family:inherit;background:transparent;">
                                                <option value="pending" <?= $t['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="reviewed" <?= $t['status'] === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                                                <option value="completed" <?= $t['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <!-- === EXPANDABLE DETROW ROW === -->
                            <!-- Hidden by default; toggled via the visibility button JS above. -->
                            <tr id="detail-<?= $t['id'] ?>" style="display:none;">
                                <td colspan="10" style="padding:1rem 1.5rem;background:var(--adm-surface-alt, #f8f9fa);border-top:none;">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                                        <div>
                                            <strong style="font-size:0.78rem;text-transform:uppercase;color:var(--adm-text-muted, #666);">Estimated Dates</strong>
                                            <div style="margin-top:0.25rem;"><?= htmlspecialchars($t['estimated_dates'] ?? '—') ?></div>
                                        </div>
                                        <div>
                                            <strong style="font-size:0.78rem;text-transform:uppercase;color:var(--adm-text-muted, #666);">Interests</strong>
                                            <!-- Handle both array and string formats for the interests field (JSON vs plain text) -->
                                            <div style="margin-top:0.25rem;"><?= htmlspecialchars(is_array($t['interests']) ? implode(', ', $t['interests']) : ($t['interests'] ?? '—')) ?></div>
                                        </div>
                                    </div>
                                    <?php if (!empty($t['additional_details'])): ?>
                                        <div style="margin-top:1rem;">
                                            <strong style="font-size:0.78rem;text-transform:uppercase;color:var(--adm-text-muted, #666);">Additional Details</strong>
                                            <div style="margin-top:0.25rem;white-space:pre-wrap;"><?= htmlspecialchars($t['additional_details']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- === PAGINATION CONTROLS === -->
            <?php if ($totalPages > 1): ?>
                <div class="adm-pagination">
                    <!-- Previous page link (disabled if on first page) -->
                    <a href="?filter=<?= urlencode($filter) ?>&page=<?= max(1, $page - 1) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">&laquo;</a>
                    <!-- Page number links (window of 5 pages around current) -->
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?filter=<?= urlencode($filter) ?>&page=<?= $i ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <!-- Next page link (disabled if on last page) -->
                    <a href="?filter=<?= urlencode($filter) ?>&page=<?= min($totalPages, $page + 1) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>">&raquo;</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
