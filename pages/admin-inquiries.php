<?php
require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = getDB();
$adminId = $_SESSION['user_id'];

// --- Handle POST actions ---
$action = $_POST['action'] ?? '';

// Admin reply
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

            if ($newStatus !== '') {
                $stmt = $db->prepare("UPDATE inquiries SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $newStatus, ':id' => $inquiryId]);
            }
            header('Location: admin-inquiries.php?thread=' . $inquiryId . '&replied=1');
            exit;
        }
    }
}

// Update status only
if ($action === 'update_status') {
    $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
    $newStatus = $_POST['inquiry_status'] ?? '';

    if ($newStatus !== '' && $inquiryId > 0) {
        $stmt = $db->prepare("UPDATE inquiries SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $newStatus, ':id' => $inquiryId]);
        header('Location: admin-inquiries.php?thread=' . $inquiryId . '&updated=1');
        exit;
    }
}

// --- Stats ---
$stmt = $db->query("SELECT status, COUNT(*) AS cnt FROM inquiries GROUP BY status");
$statusCounts = ['open' => 0, 'waiting_for_response' => 0, 'under_review' => 0, 'resolved' => 0];
foreach ($stmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int)$row['cnt'];
}
$totalCount = array_sum($statusCounts);

// --- Filter ---
$filter = $_GET['filter'] ?? 'all';
$where = '';
$params = [];
if ($filter === 'open') { $where = "WHERE i.status = 'open'"; }
elseif ($filter === 'waiting') { $where = "WHERE i.status = 'waiting_for_response'"; }
elseif ($filter === 'review') { $where = "WHERE i.status = 'under_review'"; }
elseif ($filter === 'resolved') { $where = "WHERE i.status = 'resolved'"; }

// --- Fetch inquiries ---
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

// --- Fetch thread if viewing ---
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inquiries - GlobeTrek Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/admin-inquiries.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="adm-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <?php if (isset($_GET['replied'])): ?>
            <div class="form-alert success" role="status" style="max-width:1280px;margin:0 auto;padding:0.85rem clamp(1.25rem,4vw,3rem);">
                Reply sent successfully.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="form-alert success" role="status" style="max-width:1280px;margin:0 auto;padding:0.85rem clamp(1.25rem,4vw,3rem);">
                Status updated successfully.
            </div>
        <?php endif; ?>

        <div class="adm-layout">
            <!-- Header -->
            <div class="adm-header">
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

            <!-- Filter Tabs -->
            <div class="adm-tabs">
                <a href="?filter=all" class="adm-tab <?= $filter === 'all' ? 'active' : '' ?>">All (<?= $totalCount ?>)</a>
                <a href="?filter=open" class="adm-tab <?= $filter === 'open' ? 'active' : '' ?>">Open (<?= $statusCounts['open'] ?>)</a>
                <a href="?filter=waiting" class="adm-tab <?= $filter === 'waiting' ? 'active' : '' ?>">Waiting (<?= $statusCounts['waiting_for_response'] ?>)</a>
                <a href="?filter=review" class="adm-tab <?= $filter === 'review' ? 'active' : '' ?>">Under Review (<?= $statusCounts['under_review'] ?>)</a>
                <a href="?filter=resolved" class="adm-tab <?= $filter === 'resolved' ? 'active' : '' ?>">Resolved (<?= $statusCounts['resolved'] ?>)</a>
            </div>

            <!-- Inquiries Table -->
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
                                <tr onclick="openAdminThread(<?= $inq['id'] ?>)">
                                    <td class="inq-id"><?= htmlspecialchars($inq['inquiry_id_code']) ?></td>
                                    <td class="inq-user"><?= htmlspecialchars($inq['user_name'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($inq['package_title'] ?? 'General') ?></td>
                                    <td class="inq-subject"><?= htmlspecialchars($inq['subject']) ?></td>
                                    <td>
                                        <span class="adm-status-badge adm-status-<?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td class="inq-date"><?= date('M d, Y', strtotime($inq['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Thread Modal -->
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

                    <!-- Status Update -->
                    <div class="adm-status-row">
                        <label for="status-select">Status:</label>
                        <form method="post" action="admin-inquiries.php" style="display:flex;gap:0.5rem;flex:1;align-items:center;" novalidate>
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

                    <!-- Messages -->
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

                    <!-- Admin Reply Form -->
                    <?php if ($viewThread['status'] !== 'resolved'): ?>
                        <form method="post" action="admin-inquiries.php" class="adm-reply-form" novalidate>
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

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script>
    function openAdminThread(id) {
        window.location.href = 'admin-inquiries.php?thread=' + id + '<?= $filter !== 'all' ? '&filter=' . $filter : '' ?>';
    }

    function closeAdminThread() {
        window.location.href = 'admin-inquiries.php<?= $filter !== 'all' ? '?filter=' . $filter : '' ?>';
    }

    // Close modal on overlay click
    document.getElementById('threadModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAdminThread();
        }
    });
    </script>
</body>
</html>
