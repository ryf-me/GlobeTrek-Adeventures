<?php
/**
 * File: admin/testimonials.php
 * Purpose: Lists and manages all customer testimonials with filtering, approve/reject/feature/delete actions.
 * Dependencies: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php, config/logger.php
 * Used By: Admin/staff users with manage_testimonials permission
 * Parent Files: admin/includes/sidebar.php (navigated from sidebar menu)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Manage Testimonials';

// === INITIALIZATION ===
// Load shared admin header (session, DB, CSRF, permissions)
require_once __DIR__ . '/includes/header.php';
// Load activity logging utility
require_once __DIR__ . '/../config/logger.php';

// === ACCESS CONTROL ===
// Only staff/admins with manage_testimonials permission may access this page
if (!hasPermission('manage_testimonials', $db)) {
    header('Location: index.php');
    exit;
}

// Store current admin ID for logging and assignment tracking
$adminId = $_SESSION['user_id'];

// === CSRF VALIDATION ===
// Validate CSRF token on POST requests to prevent cross-site request forgery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRFToken($_POST['csrf_token'] ?? null)) {
    $error = 'Invalid security token. Please try again.';
}

$action = $_POST['action'] ?? '';

// === SINGLE TESTIMONIAL ACTIONS ===

// Handle approve — sets testimonial status to 'approved'
if (empty($error) && $action === 'approve' && ($id = (int)($_POST['testimonial_id'] ?? 0)) > 0) {
    $stmt = $db->prepare("UPDATE testimonials SET status = 'approved' WHERE id = :id");
    $stmt->execute([':id' => $id]);
    logActivity('testimonial_approved', 'testimonial', $id, 'Testimonial approved');
    header('Location: testimonials.php?approved=1');
    exit;
}

// Handle reject — sets testimonial status to 'rejected'
if (empty($error) && $action === 'reject' && ($id = (int)($_POST['testimonial_id'] ?? 0)) > 0) {
    $stmt = $db->prepare("UPDATE testimonials SET status = 'rejected' WHERE id = :id");
    $stmt->execute([':id' => $id]);
    logActivity('testimonial_rejected', 'testimonial', $id, 'Testimonial rejected');
    header('Location: testimonials.php?rejected=1');
    exit;
}

// Handle toggle featured — flips the is_featured boolean flag
if (empty($error) && $action === 'toggle_feature' && ($id = (int)($_POST['testimonial_id'] ?? 0)) > 0) {
    $stmt = $db->prepare("UPDATE testimonials SET is_featured = NOT is_featured WHERE id = :id");
    $stmt->execute([':id' => $id]);
    logActivity('testimonial_featured', 'testimonial', $id, 'Testimonial featured toggled');
    header('Location: testimonials.php?featured=1');
    exit;
}

// Handle delete — permanently removes a testimonial from the database
if (empty($error) && $action === 'delete' && ($id = (int)($_POST['testimonial_id'] ?? 0)) > 0) {
    $stmt = $db->prepare("DELETE FROM testimonials WHERE id = :id");
    $stmt->execute([':id' => $id]);
    logActivity('testimonial_deleted', 'testimonial', $id, 'Testimonial deleted');
    header('Location: testimonials.php?deleted=1');
    exit;
}

// === BULK ACTIONS ===

// Handle bulk approve — approves multiple testimonials at once
if (empty($error) && $action === 'bulk_approve' && !empty($_POST['testimonial_ids'])) {
    // Sanitize all IDs to integers before using in query
    $ids = array_map('intval', $_POST['testimonial_ids']);
    if (!empty($ids)) {
        // Dynamically build placeholder list for IN clause
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("UPDATE testimonials SET status = 'approved' WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        logActivity('testimonials_bulk_approved', 'testimonial', 0, count($ids) . ' testimonials approved');
        header('Location: testimonials.php?approved=1');
        exit;
    }
}

// Handle bulk reject — rejects multiple testimonials at once
if (empty($error) && $action === 'bulk_reject' && !empty($_POST['testimonial_ids'])) {
    $ids = array_map('intval', $_POST['testimonial_ids']);
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("UPDATE testimonials SET status = 'rejected' WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        logActivity('testimonials_bulk_rejected', 'testimonial', 0, count($ids) . ' testimonials rejected');
        header('Location: testimonials.php?rejected=1');
        exit;
    }
}

// === SIDEBAR ===
// Include admin sidebar navigation
include __DIR__ . '/includes/sidebar.php';

// === STATISTICS ===
// Fetch counts for each testimonial status to display stat cards
$countTotal   = $db->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
$countPending = $db->query("SELECT COUNT(*) FROM testimonials WHERE status = 'pending'")->fetchColumn();
$countApproved = $db->query("SELECT COUNT(*) FROM testimonials WHERE status = 'approved'")->fetchColumn();
$countRejected = $db->query("SELECT COUNT(*) FROM testimonials WHERE status = 'rejected'")->fetchColumn();

// === FILTERS & SEARCH ===
// Read filter and search parameters from URL query string
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');

// Build dynamic WHERE clause based on selected filter
$where = '';
$params = [];

if ($filter === 'pending')   { $where = "WHERE t.status = 'pending'"; }
elseif ($filter === 'approved')  { $where = "WHERE t.status = 'approved'"; }
elseif ($filter === 'rejected')  { $where = "WHERE t.status = 'rejected'"; }

// Append search conditions — searches across reviewer name, title, and content
if ($search !== '') {
    $where = ($where === '') ? 'WHERE' : $where . ' AND';
    $where .= " (t.reviewer_name LIKE :q OR t.title LIKE :q2 OR t.content LIKE :q3)";
    $params[':q'] = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
}

// === FETCH TESTIMONIALS ===
// Join with packages and users tables to get related data
// Order by featured first, then by creation date descending
$stmt = $db->prepare(
    "SELECT t.*, p.title AS package_title, u.email AS user_email
     FROM testimonials t
     LEFT JOIN packages p ON t.package_id = p.id
     LEFT JOIN users u ON t.user_id = u.id
     $where
     ORDER BY t.is_featured DESC, t.created_at DESC"
);
$stmt->execute($params);
$testimonials = $stmt->fetchAll();
?>
<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Testimonials</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === SUCCESS / STATUS ALERTS === -->
        <!-- Display flash messages based on URL query parameters after actions -->
        <?php if (isset($_GET['approved'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Testimonial(s) approved successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['rejected'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Testimonial(s) rejected successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Testimonial deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['featured'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Featured status updated.</div>
        <?php endif; ?>

        <!-- === STAT CARDS === -->
        <!-- Summary statistics for total, pending, approved, and rejected testimonials -->
        <div class="adm-stat-grid" style="margin-bottom:1.5rem;">
            <div class="adm-stat-card">
                <span class="material-symbols-outlined" style="color:#264653;">star</span>
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

        <!-- === FILTER BAR === -->
        <!-- Search input and status filter tabs — preserve search term across filter changes -->
        <div class="adm-filter-bar">
            <form method="get" class="adm-search" style="display:flex;">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="q" id="adminSearch" placeholder="Search by name, title, or content..." value="<?= htmlspecialchars($search) ?>">
            </form>
            <a href="?filter=all<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'all' ? 'active' : '' ?>">All (<?= $countTotal ?>)</a>
            <a href="?filter=pending<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'pending' ? 'active' : '' ?>">Pending (<?= $countPending ?>)</a>
            <a href="?filter=approved<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'approved' ? 'active' : '' ?>">Approved (<?= $countApproved ?>)</a>
            <a href="?filter=rejected<?= $search ? '&q=' . urlencode($search) : '' ?>" class="adm-tab <?= $filter === 'rejected' ? 'active' : '' ?>">Rejected (<?= $countRejected ?>)</a>
        </div>

        <!-- === TESTIMONIALS TABLE === -->
        <?php if (empty($testimonials)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">star</span>
                <h2>No testimonials found</h2>
                <p>There are no testimonials matching your criteria.</p>
            </div>
        <?php else: ?>
            <form method="post" id="bulkForm">
                <?php csrf_field(); ?>
                <!-- Hidden field dynamically set by JS to indicate bulk action type -->
                <input type="hidden" name="action" id="bulkAction" value="">
                <!-- Bulk action buttons -->
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
                                <th>Package</th>
                                <th>Rating</th>
                                <th>Title</th>
                                <th>Content</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Date</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testimonials as $t): ?>
                                <?php
                                // Map status to CSS class and display label
                                $statusClass = 'active';
                                $statusLabel = 'Approved';
                                if ($t['status'] === 'pending') { $statusClass = 'inactive'; $statusLabel = 'Pending'; }
                                elseif ($t['status'] === 'rejected') { $statusClass = 'cancelled'; $statusLabel = 'Rejected'; }
                                ?>
                                <tr>
                                    <!-- Bulk selection checkbox -->
                                    <td><input type="checkbox" name="testimonial_ids[]" value="<?= $t['id'] ?>" class="row-select"></td>
                                    <td class="cell-mono">#<?= $t['id'] ?></td>
                                    <td class="cell-main">
                                        <?= htmlspecialchars($t['reviewer_name']) ?>
                                        <?php if (!empty($t['reviewer_country'])): ?>
                                            <br><span style="font-size:0.75rem;color:#888;"><?= htmlspecialchars($t['reviewer_country']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($t['user_email'])): ?>
                                            <br><span style="font-size:0.7rem;color:#aaa;"><?= htmlspecialchars($t['user_email']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($t['package_title'] ?? '—') ?></td>
                                    <!-- Star rating display — filled stars for rating, gray stars for remainder -->
                                    <td>
                                        <span style="color:#f4a261;white-space:nowrap;">
                                            <?php for ($s = 0; $s < (int)$t['rating']; $s++): ?>&#9733;<?php endfor; ?>
                                            <?php for ($s = (int)$t['rating']; $s < 5; $s++): ?><span style="color:#ddd;">&#9733;</span><?php endfor; ?>
                                        </span>
                                    </td>
                                    <!-- Truncate long titles to 50 chars -->
                                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?= htmlspecialchars(mb_strimwidth($t['title'] ?? '', 0, 50, '...')) ?>
                                    </td>
                                    <!-- Truncate long content to 80 chars with "View" link to open detail modal -->
                                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?= htmlspecialchars(mb_strimwidth($t['content'], 0, 80, '...')) ?>
                                        <button type="button" class="adm-btn-link" onclick="openDetailModal(<?= $t['id'] ?>)" style="font-size:0.75rem;">View</button>
                                    </td>
                                    <td>
                                        <span class="adm-status-badge adm-status-<?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <!-- Featured toggle — star icon changes color based on featured state -->
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_feature">
                                            <input type="hidden" name="testimonial_id" value="<?= $t['id'] ?>">
                                            <button type="submit" class="adm-btn-icon" title="<?= $t['is_featured'] ? 'Unfeature' : 'Feature' ?>">
                                                <span class="material-symbols-outlined" style="color:<?= $t['is_featured'] ? '#f4a261' : '#ccc' ?>"><?= $t['is_featured'] ? 'star' : 'star_border' ?></span>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="cell-muted"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                                    <!-- === ROW ACTION BUTTONS === -->
                                    <!-- Context-sensitive actions based on current status -->
                                    <td>
                                        <div class="cell-actions">
                                            <?php if ($t['status'] === 'pending'): ?>
                                                <!-- Pending: show both Approve and Reject buttons -->
                                                <form method="post" style="display:inline;">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="testimonial_id" value="<?= $t['id'] ?>">
                                                    <button type="submit" class="adm-btn-icon" title="Approve"><span class="material-symbols-outlined" style="color:#2a9d8f;">check_circle</span></button>
                                                </form>
                                                <form method="post" style="display:inline;">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="testimonial_id" value="<?= $t['id'] ?>">
                                                    <button type="submit" class="adm-btn-icon" title="Reject"><span class="material-symbols-outlined" style="color:#e76f51;">cancel</span></button>
                                                </form>
                                            <?php elseif ($t['status'] === 'approved'): ?>
                                                <!-- Approved: only show Reject button -->
                                                <form method="post" style="display:inline;">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="testimonial_id" value="<?= $t['id'] ?>">
                                                    <button type="submit" class="adm-btn-icon" title="Reject"><span class="material-symbols-outlined" style="color:#e76f51;">cancel</span></button>
                                                </form>
                                            <?php elseif ($t['status'] === 'rejected'): ?>
                                                <!-- Rejected: only show Approve button -->
                                                <form method="post" style="display:inline;">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="testimonial_id" value="<?= $t['id'] ?>">
                                                    <button type="submit" class="adm-btn-icon" title="Approve"><span class="material-symbols-outlined" style="color:#2a9d8f;">check_circle</span></button>
                                                </form>
                                            <?php endif; ?>
                                            <!-- View details in modal -->
                                            <button type="button" class="adm-btn-icon" title="View Details" onclick="openDetailModal(<?= $t['id'] ?>)"><span class="material-symbols-outlined">visibility</span></button>
                                            <!-- Delete with confirmation prompt -->
                                            <form method="post" style="display:inline;" data-confirm="Delete this testimonial permanently?">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="testimonial_id" value="<?= $t['id'] ?>">
                                                <button type="submit" class="adm-btn-icon adm-btn-icon-danger" title="Delete"><span class="material-symbols-outlined">delete</span></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Hidden JSON data for detail modal — used by JavaScript to populate modal without additional AJAX -->
                                <div id="detail-data-<?= $t['id'] ?>" style="display:none;" data-json='<?= htmlspecialchars(json_encode([
                                    'id' => $t['id'],
                                    'reviewer_name' => $t['reviewer_name'],
                                    'reviewer_country' => $t['reviewer_country'],
                                    'reviewer_avatar' => $t['reviewer_avatar'],
                                    'user_email' => $t['user_email'],
                                    'package_title' => $t['package_title'],
                                    'rating' => (int)$t['rating'],
                                    'title' => $t['title'],
                                    'content' => $t['content'],
                                    'status' => $t['status'],
                                    'is_featured' => (bool)$t['is_featured'],
                                    'created_at' => $t['created_at'],
                                ]), ENT_QUOTES, 'UTF-8') ?>'></div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<!-- === DETAIL MODAL === -->
<!-- Modal overlay for viewing full testimonial details -->
<div class="adm-modal-overlay" id="detailModal">
    <div class="adm-modal" style="max-width:600px;">
        <div class="adm-modal-header">
            <h2>Testimonial Details</h2>
            <button class="adm-modal-close" onclick="closeDetailModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="adm-modal-body" id="detailModalBody">
            <!-- Populated dynamically by JavaScript -->
        </div>
        <div class="adm-modal-footer" id="detailModalFooter">
            <!-- Populated dynamically by JavaScript -->
        </div>
    </div>
</div>

<script>
// === SELECT ALL CHECKBOX ===
// Toggle all row checkboxes when "select all" is checked/unchecked
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
        });
    }
});

// === DETAIL MODAL DATA STORE ===
// JavaScript object mapping testimonial IDs to their full data for modal display
const testimonialData = {};
<?php foreach ($testimonials as $t): ?>
testimonialData[<?= $t['id'] ?>] = <?= json_encode([
    'id' => $t['id'],
    'reviewer_name' => $t['reviewer_name'],
    'reviewer_country' => $t['reviewer_country'],
    'reviewer_avatar' => $t['reviewer_avatar'],
    'user_email' => $t['user_email'],
    'package_title' => $t['package_title'],
    'rating' => (int)$t['rating'],
    'title' => $t['title'],
    'content' => $t['content'],
    'status' => $t['status'],
    'is_featured' => (bool)$t['is_featured'],
    'created_at' => $t['created_at'],
]) ?>;
<?php endforeach; ?>

// === OPEN DETAIL MODAL ===
// Populates modal body with testimonial details and renders context-sensitive action buttons
function openDetailModal(id) {
    const d = testimonialData[id];
    if (!d) return;

    // Build star rating HTML
    const stars = Array(5).fill('').map((_, i) =>
        i < d.rating ? '<span style="color:#f4a261;">&#9733;</span>' : '<span style="color:#ddd;">&#9733;</span>'
    ).join('');

    const statusLabels = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected' };
    const statusColors = { pending: '#f4a261', approved: '#2a9d8f', rejected: '#e76f51' };

    // Render modal body content
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
            <tr><td style="padding:0.35rem 0.5rem 0.35rem 0;color:#888;width:100px;">Package:</td><td>${htmlEncode(d.package_title || 'General Review')}</td></tr>
            <tr><td style="padding:0.35rem 0.5rem 0.35rem 0;color:#888;">Status:</td><td><span style="display:inline-block;padding:0.15rem 0.5rem;border-radius:4px;font-size:0.8rem;font-weight:600;background:${statusColors[d.status]}22;color:${statusColors[d.status]};">${statusLabels[d.status]}</span></td></tr>
            <tr><td style="padding:0.35rem 0.5rem 0.35rem 0;color:#888;">Featured:</td><td>${d.is_featured ? 'Yes' : 'No'}</td></tr>
            <tr><td style="padding:0.35rem 0.5rem 0.35rem 0;color:#888;">Date:</td><td>${new Date(d.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</td></tr>
        </table>
        ${d.title ? `<h3 style="margin:1rem 0 0.5rem;font-size:1rem;">${htmlEncode(d.title)}</h3>` : ''}
        <div style="background:#f8f8f8;border-radius:8px;padding:1rem;line-height:1.65;color:#444;margin-top:0.5rem;">"${htmlEncode(d.content)}"</div>
    `;

    // Render modal footer with action buttons based on current status
    document.getElementById('detailModalFooter').innerHTML = `
        <div style="display:flex;gap:0.5rem;justify-content:flex-end;width:100%;">
            ${d.status === 'pending' ? `
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="testimonial_id" value="${d.id}">
                    <button type="submit" style="background:#2a9d8f;color:#fff;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;font-weight:600;">Approve</button>
                </form>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="testimonial_id" value="${d.id}">
                    <button type="submit" style="background:#e76f51;color:#fff;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;font-weight:600;">Reject</button>
                </form>
            ` : d.status === 'approved' ? `
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="testimonial_id" value="${d.id}">
                    <button type="submit" style="background:#e76f51;color:#fff;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;font-weight:600;">Reject</button>
                </form>
            ` : `
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="testimonial_id" value="${d.id}">
                    <button type="submit" style="background:#2a9d8f;color:#fff;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;font-weight:600;">Approve</button>
                </form>
            `}
            <button onclick="closeDetailModal()" style="background:#eee;color:#444;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;">Close</button>
        </div>
    `;

    document.getElementById('detailModal').classList.add('open');
}

// Close the detail modal by removing the 'open' class
function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('open');
}

// XSS-safe HTML encoding for dynamic content in modals
function htmlEncode(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// CSRF token injected from PHP for use in dynamically created modal forms
<?php $tok = getCSRFToken(); ?>
const csrfToken = '<?= $tok ?>';

// Close modal when clicking on the overlay background (not the modal content)
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
