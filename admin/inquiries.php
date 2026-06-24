<?php
$pageTitle = 'Manage Inquiries';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../config/logger.php';

$adminId = $_SESSION['user_id'];

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRFToken($_POST['csrf_token'] ?? null)) {
    $error = 'Invalid security token. Please try again.';
    $action = '';
}

if ($action === 'admin_reply') {
    $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
    $replyMsg = trim($_POST['reply_message'] ?? '');
    $newStatus = $_POST['inquiry_status'] ?? '';

    if ($replyMsg !== '' && $inquiryId > 0) {
        $stmt = $db->prepare("SELECT id FROM inquiries WHERE id = :id");
        $stmt->execute([':id' => $inquiryId]);
        if ($stmt->fetch()) {
            $stmt = $db->prepare(
                "INSERT INTO inquiry_replies (inquiry_id, sender_id, sender_role, message) VALUES (:iid, :sid, 'admin', :msg)"
            );
            $stmt->execute([':iid' => $inquiryId, ':sid' => $adminId, ':msg' => $replyMsg]);
            logActivity('inquiry_reply', 'inquiry', $inquiryId, 'Admin reply sent');

            if ($newStatus !== '') {
                $stmt = $db->prepare("UPDATE inquiries SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $newStatus, ':id' => $inquiryId]);
                logActivity('inquiry_status_updated', 'inquiry', $inquiryId, 'Status changed to ' . $newStatus);
            }
            header('Location: inquiries.php?thread=' . $inquiryId . '&replied=1');
            exit;
        }
    }
}

if ($action === 'update_status') {
    $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
    $newStatus = $_POST['inquiry_status'] ?? '';

    if ($newStatus !== '' && $inquiryId > 0) {
        $stmt = $db->prepare("UPDATE inquiries SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $newStatus, ':id' => $inquiryId]);
        header('Location: inquiries.php?thread=' . $inquiryId . '&updated=1');
        exit;
    }
}

if ($action === 'assign_staff') {
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
    if ($staffId > 0 && $inquiryId > 0) {
        $checkStmt = $db->prepare("SELECT id FROM staff_assignments WHERE staff_id = :sid AND entity_type = 'inquiry' AND entity_id = :eid LIMIT 1");
        $checkStmt->execute([':sid' => $staffId, ':eid' => $inquiryId]);
        if (!$checkStmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO staff_assignments (staff_id, entity_type, entity_id, assigned_by) VALUES (:sid, 'inquiry', :eid, :assigned_by)");
            $stmt->execute([':sid' => $staffId, ':eid' => $inquiryId, ':assigned_by' => $_SESSION['user_id']]);
            logActivity('staff_assigned', 'inquiry', $inquiryId, "Staff #$staffId assigned to inquiry");
        }
        header('Location: inquiries.php?assigned=1');
        exit;
    }
}

if ($action === 'unassign_staff') {
    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    if ($assignmentId > 0) {
        $stmt = $db->prepare("DELETE FROM staff_assignments WHERE id = :id");
        $stmt->execute([':id' => $assignmentId]);
        logActivity('staff_unassigned', 'inquiry', 0, "Assignment #$assignmentId removed");
        header('Location: inquiries.php?unassigned=1');
        exit;
    }
}

include __DIR__ . '/includes/sidebar.php';

$stmt = $db->query("SELECT status, COUNT(*) AS cnt FROM inquiries GROUP BY status");
$statusCounts = ['open' => 0, 'waiting_for_response' => 0, 'under_review' => 0, 'resolved' => 0];
foreach ($stmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int)$row['cnt'];
}
$totalCount = array_sum($statusCounts);

$filter = $_GET['filter'] ?? 'all';
$where = '';
$params = [];
if ($filter === 'open') { $where = "WHERE i.status = 'open'"; }
elseif ($filter === 'waiting') { $where = "WHERE i.status = 'waiting_for_response'"; }
elseif ($filter === 'review') { $where = "WHERE i.status = 'under_review'"; }
elseif ($filter === 'resolved') { $where = "WHERE i.status = 'resolved'"; }

$stmt = $db->prepare(
    "SELECT i.*, u.full_name AS user_name, u.email AS user_email, p.title AS package_title,
            (SELECT COUNT(*) FROM inquiry_replies ir WHERE ir.inquiry_id = i.id) AS reply_count
     FROM inquiries i
     LEFT JOIN users u ON i.user_id = u.id
     LEFT JOIN packages p ON i.package_id = p.id
     $where
     ORDER BY i.created_at DESC"
);
$stmt->execute($params);
$inquiries = $stmt->fetchAll();

// Load staff assignments for displayed inquiries
$inquiryIds = array_column($inquiries, 'id');
$staffAssignments = [];
if (!empty($inquiryIds)) {
    $placeholders = implode(',', array_fill(0, count($inquiryIds), '?'));
    $assignStmt = $db->prepare(
        "SELECT sa.entity_id, sa.id AS assignment_id, u.full_name AS staff_name, sp.department
         FROM staff_assignments sa
         JOIN staff_profiles sp ON sa.staff_id = sp.id
         JOIN users u ON sp.user_id = u.id
         WHERE sa.entity_type = 'inquiry' AND sa.entity_id IN ($placeholders)
         ORDER BY u.full_name ASC"
    );
    $assignStmt->execute($inquiryIds);
    foreach ($assignStmt->fetchAll() as $a) {
        $staffAssignments[$a['entity_id']][] = $a;
    }
}

// Load available staff for assignment dropdown
$availableStaff = $db->query(
    "SELECT sp.id, u.full_name, sp.department
     FROM staff_profiles sp
     JOIN users u ON sp.user_id = u.id
     WHERE sp.is_available = 1
     ORDER BY u.full_name ASC"
)->fetchAll();

$deptLabels = [
    'operations' => 'Ops',
    'customer_service' => 'CS',
    'sales' => 'Sales',
    'marketing' => 'Mkt',
];

$viewThread = null;
$threadReplies = [];
$threadId = isset($_GET['thread']) ? (int)$_GET['thread'] : 0;

if ($threadId > 0) {
    $stmt = $db->prepare(
        "SELECT i.*, u.full_name AS user_name, u.email AS user_email, p.title AS package_title, b.booking_reference
         FROM inquiries i
         LEFT JOIN users u ON i.user_id = u.id
         LEFT JOIN packages p ON i.package_id = p.id
         LEFT JOIN bookings b ON i.booking_reference = b.booking_reference
         WHERE i.id = :id"
    );
    $stmt->execute([':id' => $threadId]);
    $viewThread = $stmt->fetch();

    if ($viewThread) {
        $stmt = $db->prepare(
            "SELECT ir.*, u.full_name AS sender_name
             FROM inquiry_replies ir
             LEFT JOIN users u ON ir.sender_id = u.id
             WHERE ir.inquiry_id = :iid
             ORDER BY ir.created_at ASC"
        );
        $stmt->execute([':iid' => $threadId]);
        $threadReplies = $stmt->fetchAll();
    }
}
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Inquiries</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <?php if (isset($_GET['replied'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Reply sent successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Status updated successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['assigned'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Staff member assigned to inquiry.</div>
        <?php endif; ?>
        <?php if (isset($_GET['unassigned'])): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Staff assignment removed.</div>
        <?php endif; ?>

        <div class="adm-page-header">
            <h1>Manage Inquiries</h1>
            <div class="adm-stats">
                <div class="adm-stat">
                    <div class="adm-stat-num"><?= $totalCount ?></div>
                    <div class="adm-stat-label">Total</div>
                </div>
                <div class="adm-stat">
                    <div class="adm-stat-num"><?= $statusCounts['open'] + $statusCounts['waiting_for_response'] + $statusCounts['under_review'] ?></div>
                    <div class="adm-stat-label">Active</div>
                </div>
                <div class="adm-stat">
                    <div class="adm-stat-num"><?= $statusCounts['resolved'] ?></div>
                    <div class="adm-stat-label">Resolved</div>
                </div>
            </div>
        </div>

        <div class="adm-tabs">
            <a href="?filter=all" class="adm-tab <?= $filter === 'all' ? 'active' : '' ?>">All (<?= $totalCount ?>)</a>
            <a href="?filter=open" class="adm-tab <?= $filter === 'open' ? 'active' : '' ?>">Open (<?= $statusCounts['open'] ?>)</a>
            <a href="?filter=waiting" class="adm-tab <?= $filter === 'waiting' ? 'active' : '' ?>">Waiting (<?= $statusCounts['waiting_for_response'] ?>)</a>
            <a href="?filter=review" class="adm-tab <?= $filter === 'review' ? 'active' : '' ?>">Under Review (<?= $statusCounts['under_review'] ?>)</a>
            <a href="?filter=resolved" class="adm-tab <?= $filter === 'resolved' ? 'active' : '' ?>">Resolved (<?= $statusCounts['resolved'] ?>)</a>
        </div>

        <?php if (empty($inquiries)): ?>
            <div class="adm-empty">
                <span class="material-symbols-outlined adm-empty-icon">chat_bubble</span>
                <h2>No inquiries found</h2>
                <p>There are no inquiries matching the selected filter.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Package</th>
                            <th>Subject</th>
                            <th>Assigned Staff</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $inq): ?>
                            <?php
                                $statusClass = 'open';
                                $statusLabel = 'Open';
                                if ($inq['status'] === 'waiting_for_response') { $statusClass = 'waiting'; $statusLabel = 'Waiting'; }
                                elseif ($inq['status'] === 'under_review') { $statusClass = 'review'; $statusLabel = 'Under Review'; }
                                elseif ($inq['status'] === 'resolved') { $statusClass = 'resolved'; $statusLabel = 'Resolved'; }
                            ?>
                            <tr>
                                <td class="cell-mono" onclick="openAdminThread(<?= $inq['id'] ?>)"><?= htmlspecialchars($inq['inquiry_id_code']) ?></td>
                                <td class="cell-main" onclick="openAdminThread(<?= $inq['id'] ?>)"><?= htmlspecialchars($inq['user_name'] ?? 'Unknown') ?></td>
                                <td onclick="openAdminThread(<?= $inq['id'] ?>)"><?= htmlspecialchars($inq['package_title'] ?? 'General') ?></td>
                                <td onclick="openAdminThread(<?= $inq['id'] ?>)"><?= htmlspecialchars($inq['subject']) ?></td>
                                <td onclick="event.stopPropagation()">
                                    <?php if (!empty($staffAssignments[$inq['id']])): ?>
                                        <?php foreach ($staffAssignments[$inq['id']] as $sa): ?>
                                            <div style="display:flex;align-items:center;gap:0.35rem;margin-bottom:0.25rem;">
                                                <span class="adm-status-badge adm-status-active" style="font-size:0.7rem;padding:0.15rem 0.4rem;">
                                                    <?= htmlspecialchars($sa['staff_name']) ?>
                                                </span>
                                                <form method="post" style="display:inline;" data-confirm="Remove assignment?">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="action" value="unassign_staff">
                                                    <input type="hidden" name="assignment_id" value="<?= $sa['assignment_id'] ?>">
                                                    <button type="submit" class="adm-btn-icon adm-btn-icon-danger" style="width:20px;height:20px;" title="Remove">
                                                        <span class="material-symbols-outlined" style="font-size:14px;">close</span>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="cell-muted">Unassigned</span>
                                    <?php endif; ?>
                                    <div style="margin-top:0.35rem;">
                                        <select onchange="assignStaffToInquiry(this, <?= $inq['id'] ?>)" style="padding:0.2rem 0.4rem;border:1px solid var(--adm-outline-variant);font-size:0.75rem;font-family:inherit;background:transparent;border-radius:4px;">
                                            <option value="">+ Assign Staff</option>
                                            <?php foreach ($availableStaff as $s): ?>
                                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= $deptLabels[$s['department']] ?? $s['department'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <span class="adm-status-badge adm-status-<?= $statusClass ?>"><?= $statusLabel ?></span>
                                </td>
                                <td class="cell-muted"><?= date('M d, Y', strtotime($inq['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<div class="adm-modal-overlay <?= $viewThread ? 'open' : '' ?>" id="threadModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h2>Inquiry Details</h2>
            <button class="adm-modal-close" onclick="closeAdminThread()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="adm-modal-body">
            <?php if ($viewThread): ?>
                <div class="adm-inquiry-details">
                    <div class="adm-detail-item">
                        <span class="adm-detail-label">Inquiry ID</span>
                        <span class="adm-detail-value"><?= htmlspecialchars($viewThread['inquiry_id_code']) ?></span>
                    </div>
                    <div class="adm-detail-item">
                        <span class="adm-detail-label">User</span>
                        <span class="adm-detail-value"><?= htmlspecialchars($viewThread['user_name'] ?? 'Unknown') ?></span>
                    </div>
                    <div class="adm-detail-item">
                        <span class="adm-detail-label">Email</span>
                        <span class="adm-detail-value"><?= htmlspecialchars($viewThread['user_email'] ?? '') ?></span>
                    </div>
                    <div class="adm-detail-item">
                        <span class="adm-detail-label">Package</span>
                        <span class="adm-detail-value"><?= htmlspecialchars($viewThread['package_title'] ?? 'General') ?></span>
                    </div>
                    <div class="adm-detail-item">
                        <span class="adm-detail-label">Date</span>
                        <span class="adm-detail-value"><?= date('M d, Y \a\t g:i A', strtotime($viewThread['created_at'])) ?></span>
                    </div>
                    <div class="adm-detail-item">
                        <span class="adm-detail-label">Replies</span>
                        <span class="adm-detail-value"><?= count($threadReplies) ?></span>
                    </div>
                </div>

                <div class="adm-status-row">
                    <label for="status-select">Status:</label>
                    <form method="post" action="inquiries.php" style="display:flex;gap:0.5rem;flex:1;align-items:center;" novalidate>
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="inquiry_id" value="<?= $viewThread['id'] ?>">
                        <select id="status-select" name="inquiry_status" onchange="this.form.submit()">
                            <option value="open" <?= $viewThread['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="waiting_for_response" <?= $viewThread['status'] === 'waiting_for_response' ? 'selected' : '' ?>>Waiting for Response</option>
                            <option value="under_review" <?= $viewThread['status'] === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                            <option value="resolved" <?= $viewThread['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        </select>
                    </form>
                </div>

                <div class="adm-thread-messages">
                    <?php foreach ($threadReplies as $reply): ?>
                        <div class="adm-message <?= $reply['sender_role'] === 'admin' ? 'admin-message' : 'user-message' ?>">
                            <div class="adm-message-header">
                                <span class="adm-message-sender">
                                    <?= htmlspecialchars($reply['sender_name'] ?? 'Unknown') ?>
                                    <?php if ($reply['sender_role'] === 'admin'): ?>
                                        <span class="user-label">Staff</span>
                                    <?php else: ?>
                                        <span class="user-label">User</span>
                                    <?php endif; ?>
                                </span>
                                <span class="adm-message-date"><?= date('M d, Y \a\t g:i A', strtotime($reply['created_at'])) ?></span>
                            </div>
                            <p class="adm-message-text"><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($threadReplies)): ?>
                        <p style="color:var(--adm-secondary);text-align:center;padding:1rem;">No replies yet.</p>
                    <?php endif; ?>
                </div>

                <?php if ($viewThread['status'] !== 'resolved'): ?>
                    <form method="post" action="inquiries.php" class="adm-reply-form" novalidate>
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="admin_reply">
                        <input type="hidden" name="inquiry_id" value="<?= $viewThread['id'] ?>">
                        <div class="form-field">
                            <label for="admin-reply-msg">Reply as Admin</label>
                            <textarea id="admin-reply-msg" name="reply_message" rows="3"
                                      placeholder="Type your reply..."></textarea>
                        </div>
                        <div class="adm-reply-actions">
                            <button type="button" class="adm-cancel-btn" onclick="closeAdminThread()">Cancel</button>
                            <button type="submit" class="adm-reply-btn">Send Reply</button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p>Inquiry not found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function openAdminThread(id) {
    window.location.href = 'inquiries.php?thread=' + id + '<?= $filter !== 'all' ? '&filter=' . $filter : '' ?>';
}

function closeAdminThread() {
    window.location.href = 'inquiries.php<?= $filter !== 'all' ? '?filter=' . $filter : '' ?>';
}

document.getElementById('threadModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAdminThread();
    }
});

function assignStaffToInquiry(select, inquiryId) {
    var staffId = select.value;
    if (!staffId) return;

    var form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    var csrfToken = document.querySelector('input[name="csrf_token"]').value;

    var csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);

    var actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'assign_staff';
    form.appendChild(actionInput);

    var staffInput = document.createElement('input');
    staffInput.type = 'hidden';
    staffInput.name = 'staff_id';
    staffInput.value = staffId;
    form.appendChild(staffInput);

    var inquiryInput = document.createElement('input');
    inquiryInput.type = 'hidden';
    inquiryInput.name = 'inquiry_id';
    inquiryInput.value = inquiryId;
    form.appendChild(inquiryInput);

    document.body.appendChild(form);
    form.submit();
}
</script>
