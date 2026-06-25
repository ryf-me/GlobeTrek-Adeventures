<?php
/**
 * Admin Guide Reviews Management
 *
 * Lists all guide reviews with filtering, approve/reject/delete actions.
 * Accessible by admin and staff with manage_testimonials permission.
 */
$pageTitle = 'Manage Guide Reviews';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../config/logger.php';

if (!hasPermission('manage_testimonials', $db)) {
    header('Location: index.php');
    exit;
}

$adminId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRFToken($_POST['csrf_token'] ?? null)) {
    $error = 'Invalid security token. Please try again.';
}

$action = $_POST['action'] ?? '';

// Handle approve
if (empty($error) && $action === 'approve' && ($id = (int)($_POST['review_id'] ?? 0)) > 0) {
    $stmt = $db->prepare("UPDATE guide_reviews SET status = 'approved' WHERE id = :id");
    $stmt->execute([':id' => $id]);
    logActivity('guide_review_approved', 'guide_review', $id, 'Guide review approved');
    header('Location: guide-reviews.php?approved=1');
    exit;
}

// Handle reject
if (empty($error) && $action === 'reject' && ($id = (int)($_POST['review_id'] ?? 0)) > 0) {
    $stmt = $db->prepare("UPDATE guide_reviews SET status = 'rejected' WHERE id = :id");
    $stmt->execute([':id' => $id]);
    logActivity('guide_review_rejected', 'guide_review', $id, 'Guide review rejected');
    header('Location: guide-reviews.php?rejected=1');
    exit;
}

// Handle delete
if (empty($error) && $action === 'delete' && ($id = (int)($_POST['review_id'] ?? 0)) > 0) {
    $stmt = $db->prepare("DELETE FROM guide_reviews WHERE id = :id");
    $stmt->execute([':id' => $id]);
    logActivity('guide_review_deleted', 'guide_review', $id, 'Guide review deleted');
    header('Location: guide-reviews.php?deleted=1');
    exit;
}

// Handle bulk approve
if (empty($error) && $action === 'bulk_approve' && !empty($_POST['review_ids'])) {
    $ids = array_map('intval', $_POST['review_ids']);
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("UPDATE guide_reviews SET status = 'approved' WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        logActivity('guide_reviews_bulk_approved', 'guide_review', 0, count($ids) . ' guide reviews approved');
        header('Location: guide-reviews.php?approved=1');
        exit;
    }
}

// Handle bulk reject
if (empty($error) && $action === 'bulk_reject' && !empty($_POST['review_ids'])) {
    $ids = array_map('intval', $_POST['review_ids']);
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("UPDATE guide_reviews SET status = 'rejected' WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        logActivity('guide_reviews_bulk_rejected', 'guide_review', 0, count($ids) . ' guide reviews rejected');
        header('Location: guide-reviews.php?rejected=1');
        exit;
    }
}

include __DIR__ . '/includes/sidebar.php';

// --- Stats ---
$countTotal   = $db->query("SELECT COUNT(*) FROM guide_reviews")->fetchColumn();
$countPending = $db->query("SELECT COUNT(*) FROM guide_reviews WHERE status = 'pending'")->fetchColumn();
$countApproved = $db->query("SELECT COUNT(*) FROM guide_reviews WHERE status = 'approved'")->fetchColumn();
$countRejected = $db->query("SELECT COUNT(*) FROM guide_reviews WHERE status = 'rejected'")->fetchColumn();

// --- Filters ---
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$where = '';
$params = [];

if ($filter === 'pending')   { $where = "WHERE gr.status = 'pending'"; }
elseif ($filter === 'approved')  { $where = "WHERE gr.status = 'approved'"; }
elseif ($filter === 'rejected')  { $where = "WHERE gr.status = 'rejected'"; }

if ($search !== '') {
    $where = ($where === '') ? 'WHERE' : $where . ' AND';
    $where .= " (gr.reviewer_name LIKE :q OR gr.title LIKE :q2 OR gr.content LIKE :q3 OR g.name LIKE :q4)";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
    $params[':q4'] = "%$search%";
}

$stmt = $db->prepare(
    "SELECT gr.*, g.name AS guide_name, g.specialty AS guide_specialty, u.email AS user_email
     FROM guide_reviews gr
     LEFT JOIN guides g ON gr.guide_id = g.id
     LEFT JOIN users u ON gr.user_id = u.id
     $where
     ORDER BY gr.created_at DESC"
);
$stmt->execute($params);
$reviews = $stmt->fetchAll();
?>
<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Guide Reviews</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <?php if (isset($_GET['approved'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Guide review(s) approved successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['rejected'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Guide review(s) rejected successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Guide review deleted successfully.</div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="adm-stat-grid" style="margin-bottom:1.5rem;">
            <div class="adm-stat-card">
                <span class="material-symbols-outlined" style="color:#264653;">reviews</span>
                <div>
                    <div class="adm-stat-num"><?= $countTotal ?></div>
                    <div class="adm-stat-label">Total Reviews</div>
                </div>
            </div>
            <div class="adm-stat-card" style="border-left:3px solid #f4a261;">
                <span class="material-symbols-outlined" style="color:#f4a261;">schedule</span>
                <div>
                    <div class="adm-stat-num"><?= $countPending ?></div>
                    <div class="adm-stat-label">Pending</div>
                </div>
            </div>
            <div class="adm-stat-card" style="border-left:3px solid #2a9d8f;">
                <span class="material-symbols-outlined" style="color:#2a9d8f;">check_circle</span>
                <div>
                    <div class="adm-stat-num"><?= $countApproved ?></div>
                    <div class="adm-stat-label">Approved</div>
                </div>
            </div>
            <div class="adm-stat-card" style="border-left:3px solid #e76f51;">
                <span class="material-symbols-outlined" style="color:#e76f51;">cancel</span>
                <div>
                    <div class="adm-stat-num"><?= $countRejected ?></div>
                    <div class="adm-stat-label">Rejected</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" id="adminSearch" placeholder="Search by name, guide, title, or content..." value="<?= htmlspecialchars($search) ?>">
            </form>
            <a href="?filter=all<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'all' ? 'active' : '' ?>">All (<?= $countTotal ?>)</a>
            <a href="?filter=pending<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'pending' ? 'active' : '' ?>">Pending (<?= $countPending ?>)</a>
            <a href="?filter=approved<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'approved' ? 'active' : '' ?>">Approved (<?= $countApproved ?>)</a>
            <a href="?filter=rejected<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'rejected' ? 'active' : '' ?>">Rejected (<?= $countRejected ?>)</a>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">reviews</span>
                <h2>No guide reviews found</h2>
                <p>There are no guide reviews matching your criteria.</p>
            </div>
        <?php else: ?>
            <form method="post" id="bulkForm">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" id="bulkAction" value="">
                <div style="display:flex;gap:0.5rem;margin-bottom:0.75rem;">
                    <button type="button" class="adm-btn adm-btn-sm" style="background:#2a9d8f;color:#fff;" onclick="document.getElementById('bulkAction').value='bulk_approve';document.getElementById('bulkForm').submit();">Approve Selected</button>
                    <button type="button" class="adm-btn adm-btn-sm" style="background:#e76f51;color:#fff;" onclick="document.getElementById('bulkAction').value='bulk_reject';document.getElementById('bulkForm').submit();">Reject Selected</button>
                </div>

                <div class="adm-table-wrap">
                    <table class="adm-table" id="adminTable">
                        <thead>
                            <tr>
                                <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Reviewer</th>
                                <th>Guide</th>
                                <th>Rating</th>
                                <th>Title</th>
                                <th>Content</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $r): ?>
                                <?php
                                $statusClass = 'active';
                                $statusLabel = 'Approved';
                                if ($r['status'] === 'pending') { $statusClass = 'inactive'; $statusLabel = 'Pending'; }
                                elseif ($r['status'] === 'rejected') { $statusClass = 'cancelled'; $statusLabel = 'Rejected'; }
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="review_ids[]" value="<?= $r['id'] ?>" class="row-select"></td>
                                    <td class="cell-mono">#<?= $r['id'] ?></td>
                                    <td class="cell-main">
                                        <?= htmlspecialchars($r['reviewer_name']) ?>
                                        <?php if (!empty($r['reviewer_country'])): ?>
                                            <br><span style="font-size:0.75rem;color:#888;"><?= htmlspecialchars($r['reviewer_country']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($r['user_email'])): ?>
                                            <br><span style="font-size:0.7rem;color:#aaa;"><?= htmlspecialchars($r['user_email']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['guide_name'] ?? '—') ?>
                                        <?php if (!empty($r['guide_specialty'])): ?>
                                            <br><span style="font-size:0.75rem;color:#888;"><?= htmlspecialchars($r['guide_specialty']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="color:#f4a261;white-space:nowrap;">
                                            <?php for ($s = 0; $s < (int)$r['rating']; $s++): ?>&#9733;<?php endfor; ?>
                                            <?php for ($s = (int)$r['rating']; $s < 5; $s++): ?><span style="color:#ddd;">&#9733;</span><?php endfor; ?>
                                        </span>
                                    </td>
                                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?= htmlspecialchars(mb_strimwidth($r['title'] ?? '', 0, 50, '...')) ?>
                                    </td>
                                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?= htmlspecialchars(mb_strimwidth($r['content'], 0, 80, '...')) ?>
                                        <button type="button" class="adm-btn-link" onclick="openDetailModal(<?= $r['id'] ?>)" style="font-size:0.75rem;">View</button>
                                    </td>
                                    <td>
                                        <span class="adm-status-badge adm-status-<?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td class="cell-muted"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                                    <td>
                                        <div class="cell-actions">
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <form method="post" style="display:inline;">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                                    <button type="submit" class="adm-btn-icon" title="Approve"><span class="material-symbols-outlined" style="color:#2a9d8f;">check_circle</span></button>
                                                </form>
                                                <form method="post" style="display:inline;">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                                    <button type="submit" class="adm-btn-icon" title="Reject"><span class="material-symbols-outlined" style="color:#e76f51;">cancel</span></button>
                                                </form>
                                            <?php elseif ($r['status'] === 'approved'): ?>
                                                <form method="post" style="display:inline;">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                                    <button type="submit" class="adm-btn-icon" title="Reject"><span class="material-symbols-outlined" style="color:#e76f51;">cancel</span></button>
                                                </form>
                                            <?php elseif ($r['status'] === 'rejected'): ?>
                                                <form method="post" style="display:inline;">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                                    <button type="submit" class="adm-btn-icon" title="Approve"><span class="material-symbols-outlined" style="color:#2a9d8f;">check_circle</span></button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" class="adm-btn-icon" title="View Details" onclick="openDetailModal(<?= $r['id'] ?>)"><span class="material-symbols-outlined">visibility</span></button>
                                            <form method="post" style="display:inline;" data-confirm="Delete this guide review permanently?">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="adm-btn-icon adm-btn-icon-danger" title="Delete"><span class="material-symbols-outlined">delete</span></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Detail Modal Data (hidden JSON) -->
                                <div id="detail-data-<?= $r['id'] ?>" style="display:none;" data-json='<?= htmlspecialchars(json_encode([
                                    'id' => $r['id'],
                                    'reviewer_name' => $r['reviewer_name'],
                                    'reviewer_country' => $r['reviewer_country'],
                                    'reviewer_avatar' => $r['reviewer_avatar'],
                                    'user_email' => $r['user_email'],
                                    'guide_name' => $r['guide_name'],
                                    'guide_specialty' => $r['guide_specialty'],
                                    'rating' => (int)$r['rating'],
                                    'title' => $r['title'],
                                    'content' => $r['content'],
                                    'status' => $r['status'],
                                    'created_at' => $r['created_at'],
                                ]), ENT_QUOTES, 'UTF-8') ?>'></div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<!-- Detail Modal -->
<div class="adm-modal-overlay" id="detailModal">
    <div class="adm-modal" style="max-width:600px;">
        <div class="adm-modal-header">
            <h2>Guide Review Details</h2>
            <button class="adm-modal-close" onclick="closeDetailModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="adm-modal-body" id="detailModalBody">
            <!-- Populated by JS -->
        </div>
        <div class="adm-modal-footer" id="detailModalFooter">
            <!-- Populated by JS -->
        </div>
    </div>
</div>

<script>
// Select all checkbox
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
        });
    }
});

// Detail modal data store
const reviewData = {};
<?php foreach ($reviews as $r): ?>
reviewData[<?= $r['id'] ?>] = <?= json_encode([
    'id' => $r['id'],
    'reviewer_name' => $r['reviewer_name'],
    'reviewer_country' => $r['reviewer_country'],
    'reviewer_avatar' => $r['reviewer_avatar'],
    'user_email' => $r['user_email'],
    'guide_name' => $r['guide_name'],
    'guide_specialty' => $r['guide_specialty'],
    'rating' => (int)$r['rating'],
    'title' => $r['title'],
    'content' => $r['content'],
    'status' => $r['status'],
    'created_at' => $r['created_at'],
]) ?>;
<?php endforeach; ?>

function openDetailModal(id) {
    const d = reviewData[id];
    if (!d) return;

    const stars = Array(5).fill('').map((_, i) =>
        i < d.rating ? '<span style="color:#f4a261;">&#9733;</span>' : '<span style="color:#ddd;">&#9733;</span>'
    ).join('');

    const statusLabels = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected' };
    const statusColors = { pending: '#f4a261', approved: '#2a9d8f', rejected: '#e76f51' };

    document.getElementById('detailModalBody').innerHTML = `
        <div style="display:flex;gap:1rem;margin-bottom:1.5rem;">
            ${d.reviewer_avatar ? `<img src="${htmlEncode(d.reviewer_avatar)}" alt="" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid #e9e9e9;">` : ''}
            <div>
                <div style="font-weight:700;font-size:1.05rem;">${htmlEncode(d.reviewer_name)}</div>
                ${d.reviewer_country ? `<div style="color:#888;font-size:0.85rem;">${htmlEncode(d.reviewer_country)}</div>` : ''}
                ${d.user_email ? `<div style="color:#aaa;font-size:0.8rem;">${htmlEncode(d.user_email)}</div>` : ''}
            </div>
        </div>
        <div style="margin-bottom:1rem;">${stars}</div>
        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <tr><td style="padding:0.35rem 0.5rem 0.35rem 0;color:#888;width:100px;">Guide:</td><td>${htmlEncode(d.guide_name || '—')} ${d.guide_specialty ? '<span style="color:#888;font-size:0.8rem;">(' + htmlEncode(d.guide_specialty) + ')</span>' : ''}</td></tr>
            <tr><td style="padding:0.35rem 0.5rem 0.35rem 0;color:#888;">Status:</td><td><span style="display:inline-block;padding:0.15rem 0.5rem;border-radius:4px;font-size:0.8rem;font-weight:600;background:${statusColors[d.status]}22;color:${statusColors[d.status]};">${statusLabels[d.status]}</span></td></tr>
            <tr><td style="padding:0.35rem 0.5rem 0.35rem 0;color:#888;">Date:</td><td>${new Date(d.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</td></tr>
        </table>
        ${d.title ? `<h3 style="margin:1rem 0 0.5rem;font-size:1rem;">${htmlEncode(d.title)}</h3>` : ''}
        <div style="background:#f8f8f8;border-radius:8px;padding:1rem;line-height:1.65;color:#444;margin-top:0.5rem;">"${htmlEncode(d.content)}"</div>
    `;

    document.getElementById('detailModalFooter').innerHTML = `
        <div style="display:flex;gap:0.5rem;justify-content:flex-end;width:100%;">
            ${d.status === 'pending' ? `
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="review_id" value="${d.id}">
                    <button type="submit" style="background:#2a9d8f;color:#fff;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;font-weight:600;">Approve</button>
                </form>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="review_id" value="${d.id}">
                    <button type="submit" style="background:#e76f51;color:#fff;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;font-weight:600;">Reject</button>
                </form>
            ` : d.status === 'approved' ? `
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="review_id" value="${d.id}">
                    <button type="submit" style="background:#e76f51;color:#fff;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;font-weight:600;">Reject</button>
                </form>
            ` : `
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="review_id" value="${d.id}">
                    <button type="submit" style="background:#2a9d8f;color:#fff;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;font-weight:600;">Approve</button>
                </form>
            `}
            <button onclick="closeDetailModal()" style="background:#eee;color:#444;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;">Close</button>
        </div>
    `;

    document.getElementById('detailModal').classList.add('open');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('open');
}

function htmlEncode(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// CSRF token for modal forms
<?php $tok = getCSRFToken(); ?>
const csrfToken = '<?= $tok ?>';

// Close modal on overlay click
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('detailModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeDetailModal();
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
