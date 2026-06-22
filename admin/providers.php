<?php
$pageTitle = 'Service Providers';
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$search = trim($_GET['q'] ?? '');
$typeFilter = $_GET['type'] ?? 'all';

$where = '';
$params = [];

if ($typeFilter === 'accommodation') {
    $where = "WHERE a.provider_name IS NOT NULL AND a.provider_name != ''";
} elseif ($typeFilter === 'transport') {
    $where = "WHERE t.provider_name IS NOT NULL AND t.provider_name != ''";
} else {
    $where = "WHERE (a.provider_name IS NOT NULL AND a.provider_name != '') OR (t.provider_name IS NOT NULL AND t.provider_name != '')";
}

$providers = [];

$accomStmt = $db->prepare(
    "SELECT 'accommodation' AS type, a.id, a.name, a.location, a.property_type AS service_type,
            a.provider_name, a.provider_email, a.provider_phone, a.updated_at, a.created_at
     FROM accommodations a
     WHERE a.provider_name IS NOT NULL AND a.provider_name != ''
     " . ($search ? "AND (a.provider_name LIKE :q OR a.provider_email LIKE :q2)" : "") .
     " ORDER BY a.provider_name ASC"
);
if ($search) {
    $accomStmt->execute([':q' => "%$search%", ':q2' => "%$search%"]);
} else {
    $accomStmt->execute();
}
$accomProviders = $accomStmt->fetchAll();

$transStmt = $db->prepare(
    "SELECT 'transport' AS type, t.id, t.name, t.location, t.vehicle_type AS service_type,
            t.provider_name, t.provider_email, t.provider_phone, t.updated_at, t.created_at
     FROM transportations t
     WHERE t.provider_name IS NOT NULL AND t.provider_name != ''
     " . ($search ? "AND (t.provider_name LIKE :q OR t.provider_email LIKE :q2)" : "") .
     " ORDER BY t.provider_name ASC"
);
if ($search) {
    $transStmt->execute([':q' => "%$search%", ':q2' => "%$search%"]);
} else {
    $transStmt->execute();
}
$transProviders = $transStmt->fetchAll();

$providers = array_merge($accomProviders, $transProviders);
usort($providers, function ($a, $b) {
    return strcmp($a['provider_name'], $b['provider_name']);
});
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Service Providers</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <h1>Providers (<?= count($providers) ?>)</h1>
        </div>

        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex; gap:0.5rem; align-items:center;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" placeholder="Search providers..." value="<?= htmlspecialchars($search) ?>">
                <select name="type" style="padding:0.5rem; border:1px solid var(--adm-outline-variant); border-radius:6px;">
                    <option value="all" <?= $typeFilter === 'all' ? 'selected' : '' ?>>All Types</option>
                    <option value="accommodation" <?= $typeFilter === 'accommodation' ? 'selected' : '' ?>>Accommodations</option>
                    <option value="transport" <?= $typeFilter === 'transport' ? 'selected' : '' ?>>Transportation</option>
                </select>
                <button type="submit" class="adm-btn adm-btn-primary" style="padding:0.5rem 1rem;">Filter</button>
            </form>
        </div>

        <?php if (empty($providers)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">handshake</span>
                <h2>No providers found</h2>
                <p>Add provider contact info to accommodations or transportation to see them here.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Provider Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Service</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($providers as $p): ?>
                            <tr>
                                <td class="cell-main"><?= htmlspecialchars($p['provider_name']) ?></td>
                                <td><?= htmlspecialchars($p['provider_email'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($p['provider_phone'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= htmlspecialchars($p['location']) ?></td>
                                <td>
                                    <span class="adm-status-badge <?= $p['type'] === 'accommodation' ? 'adm-status-confirmed' : 'adm-status-active' ?>">
                                        <?= $p['type'] === 'accommodation' ? 'Accommodation' : 'Transport' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="cell-actions">
                                        <a href="<?= $p['type'] === 'accommodation' ? 'accommodation-edit.php' : 'transport-edit.php' ?>?id=<?= $p['id'] ?>" class="adm-btn-icon" title="Edit">
                                            <span class="material-symbols-outlined">edit</span>
                                        </a>
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
