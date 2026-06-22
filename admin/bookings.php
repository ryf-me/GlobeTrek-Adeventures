<?php
$pageTitle = 'Manage Bookings';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../config/logger.php';
include __DIR__ . '/includes/sidebar.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $bid = (int)($_POST['booking_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    if ($bid > 0 && in_array($newStatus, ['pending', 'confirmed', 'cancelled'])) {
        $stmt = $db->prepare("UPDATE bookings SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $newStatus, ':id' => $bid]);
        logActivity('booking_status_updated', 'booking', $bid, 'Status changed to ' . $newStatus);
        header('Location: bookings.php?updated=1');
        exit;
    }
}

$filter = $_GET['filter'] ?? 'all';
$where = '';
$params = [];
if ($filter === 'pending') { $where = "WHERE b.status = 'pending'"; }
elseif ($filter === 'confirmed') { $where = "WHERE b.status = 'confirmed'"; }
elseif ($filter === 'cancelled') { $where = "WHERE b.status = 'cancelled'"; }

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $where = ($where === '') ? "WHERE" : $where . " AND";
    $where .= " (b.booking_reference LIKE :q OR b.first_name LIKE :q2 OR b.last_name LIKE :q3 OR b.email LIKE :q4)";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
    $params[':q4'] = "%$search%";
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM bookings b $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetch()['cnt'];
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = $db->prepare(
    "SELECT b.*, u.full_name AS user_name, p.title AS package_title
     FROM bookings b
     LEFT JOIN users u ON b.user_id = u.id
     LEFT JOIN packages p ON b.package_id = p.id
     $where
     ORDER BY b.created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$bookings = $stmt->fetchAll();

// Stats
$stats = [];
$res = $db->query("SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status");
foreach ($res->fetchAll() as $r) $stats[$r['status']] = (int)$r['cnt'];
$totalBookings = array_sum($stats);
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Bookings</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <?php if (isset($_GET['updated'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Booking status updated.</div>
        <?php endif; ?>

        <div class="adm-page-header">
            <h1>Bookings (<?= $totalBookings ?>)</h1>
        </div>

        <div class="adm-tabs">
            <a href="?filter=all" class="adm-tab <?= $filter === 'all' ? 'active' : '' ?>">All (<?= $totalBookings ?>)</a>
            <a href="?filter=pending" class="adm-tab <?= $filter === 'pending' ? 'active' : '' ?>">Pending (<?= $stats['pending'] ?? 0 ?>)</a>
            <a href="?filter=confirmed" class="adm-tab <?= $filter === 'confirmed' ? 'active' : '' ?>">Confirmed (<?= $stats['confirmed'] ?? 0 ?>)</a>
            <a href="?filter=cancelled" class="adm-tab <?= $filter === 'cancelled' ? 'active' : '' ?>">Cancelled (<?= $stats['cancelled'] ?? 0 ?>)</a>
        </div>

        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search by reference, name, email..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <?php if (empty($bookings)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">flight_takeoff</span>
                <h2>No bookings found</h2>
                <p>There are no bookings matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table" id="adminTable">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>User</th>
                            <th>Package</th>
                            <th>Travellers</th>
                            <th>Amount</th>
                            <th>Travel Date</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td class="cell-mono"><?= htmlspecialchars($b['booking_reference']) ?></td>
                                <td>
                                    <div class="cell-main"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></div>
                                    <div class="cell-sub"><?= htmlspecialchars($b['email']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($b['package_title'] ?? 'N/A') ?></td>
                                <td><?= $b['num_travellers'] ?></td>
                                <td class="cell-mono">Rs.<?= number_format($b['total_price'], 2) ?></td>
                                <td class="cell-muted"><?= $b['travel_date'] ? date('M d, Y', strtotime($b['travel_date'])) : '—' ?></td>
                                <td>
                                    <span class="adm-status-badge adm-status-<?= $b['status'] ?>">
                                        <span class="adm-badge-dot"></span>
                                        <?= ucfirst(htmlspecialchars($b['status'])) ?>
                                    </span>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($b['created_at'])) ?></td>
                                <td>
                                    <div class="cell-actions">
                                        <form method="post" style="display:flex;gap:0.35rem;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <select name="status" onchange="if(confirm('Update status?')) this.form.submit();" style="padding:0.3rem 0.5rem;border:1px solid var(--adm-outline-variant);font-size:0.78rem;font-family:inherit;background:transparent;">
                                                <option value="pending" <?= $b['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="confirmed" <?= $b['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                <option value="cancelled" <?= $b['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
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
                    <a href="?filter=<?= urlencode($filter) ?>&page=<?= max(1, $page - 1) ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">&laquo;</a>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?filter=<?= urlencode($filter) ?>&page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?filter=<?= urlencode($filter) ?>&page=<?= min($totalPages, $page + 1) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>">&raquo;</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
