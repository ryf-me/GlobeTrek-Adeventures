<?php
/**
 * File: pages/inquiries.php
 * Purpose: Manages the authenticated user's support inquiries — create new inquiries,
 *          view inquiry threads, add replies, and filter by active/resolved status.
 * Dependencies: config/database.php, config/csrf.php, config/rate-limiter.php, js/script.js
 * Used By: User sidebar navigation (user-sidebar.php)
 * Parent Files: None (standalone page rendered in browser)
 * Child Files: Includes navbar.php, user-sidebar.php, footer.php
 * @package GlobeTrek\Pages
 */

session_start();

// === AUTH GUARD ===
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// === DATABASE & CONFIG ===
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/rate-limiter.php';
$db = getDB();
$userId = $_SESSION['user_id'];

// === HANDLE POST ACTIONS ===
$action = $_POST['action'] ?? '';

// === ACTION: CREATE NEW INQUIRY ===
if ($action === 'create_inquiry') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    }

    // Rate limiting — max 10 inquiries per hour to prevent abuse
    if (empty($errors) && !checkRateLimit('inquiries', 10, 3600, false)) {
        $errors['general'] = 'Too many inquiries. Please try again later.';
    }

    $packageId = $_POST['package_id'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // === VALIDATION ===
    $errors = [];
    if ($subject === '') $errors['subject'] = 'Please enter a subject.';
    if ($message === '') $errors['message'] = 'Please enter your message.';

    if (empty($errors)) {
        // Generate a unique inquiry ID code for reference (e.g., "INQ-48291")
        $code = 'INQ-' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
        $stmt = $db->prepare(
            "INSERT INTO inquiries (user_id, package_id, inquiry_id_code, subject, message) VALUES (:uid, :pid, :code, :subject, :message)"
        );
        $stmt->execute([
            ':uid' => $userId,
            ':pid' => $packageId ?: null,
            ':code' => $code,
            ':subject' => $subject,
            ':message' => $message,
        ]);
        header('Location: inquiries.php?created=1');
        exit;
    }
}

// === ACTION: ADD REPLY TO EXISTING INQUIRY ===
if ($action === 'add_reply') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        header('Location: inquiries.php?error=token');
        exit;
    }

    $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
    $replyMsg = trim($_POST['reply_message'] ?? '');

    if ($replyMsg !== '' && $inquiryId > 0) {
        // Verify the inquiry belongs to the current user before allowing a reply
        $stmt = $db->prepare("SELECT id FROM inquiries WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $inquiryId, ':uid' => $userId]);
        if ($stmt->fetch()) {
            // Insert reply with sender_role = 'user' to distinguish from admin replies
            $stmt = $db->prepare(
                "INSERT INTO inquiry_replies (inquiry_id, sender_id, sender_role, message) VALUES (:iid, :sid, 'user', :msg)"
            );
            $stmt->execute([':iid' => $inquiryId, ':sid' => $userId, ':msg' => $replyMsg]);
            header('Location: inquiries.php?thread=' . $inquiryId . '&replied=1');
            exit;
        }
    }
}

// === FETCH USER'S BOOKINGS FOR INQUIRY FORM DROPDOWN ===
// Allows user to optionally link an inquiry to a specific booking
$stmt = $db->prepare(
    "SELECT b.id, b.booking_reference, p.title
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     WHERE b.user_id = :uid
     ORDER BY b.created_at DESC"
);
$stmt->execute([':uid' => $userId]);
$userBookings = $stmt->fetchAll();

// === FETCH INQUIRIES LIST ===
// Filter by active/resolved status based on tab selection
$filter = $_GET['filter'] ?? 'active';
$statusWhere = $filter === 'resolved'
    ? "WHERE i.status = 'resolved'"
    : "WHERE i.status != 'resolved'";

// Subqueries compute reply_count and last_reply_at for each inquiry
$stmt = $db->prepare(
    "SELECT i.*, p.title AS package_title,
            (SELECT COUNT(*) FROM inquiry_replies ir WHERE ir.inquiry_id = i.id) AS reply_count,
            (SELECT ir.created_at FROM inquiry_replies ir WHERE ir.inquiry_id = i.id ORDER BY ir.created_at DESC LIMIT 1) AS last_reply_at
     FROM inquiries i
     LEFT JOIN packages p ON i.package_id = p.id
     $statusWhere
     AND i.user_id = :uid
     ORDER BY i.created_at DESC"
);
$stmt->execute([':uid' => $userId]);
$inquiries = $stmt->fetchAll();

// === COUNT ACTIVE AND RESOLVED INQUIRIES ===
// Used for tab badges
$stmt = $db->prepare("SELECT COUNT(*) FROM inquiries WHERE user_id = :uid AND status != 'resolved'");
$stmt->execute([':uid' => $userId]);
$activeCount = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM inquiries WHERE user_id = :uid AND status = 'resolved'");
$stmt->execute([':uid' => $userId]);
$resolvedCount = (int)$stmt->fetchColumn();

// === FETCH THREAD VIEW ===
// If the user is viewing a specific inquiry thread, load it with all replies
$viewThread = null;
$threadReplies = [];
$threadId = isset($_GET['thread']) ? (int)$_GET['thread'] : 0;

if ($threadId > 0) {
    // Fetch inquiry details with related package and booking reference
    $stmt = $db->prepare(
        "SELECT i.*, p.title AS package_title, b.booking_reference
         FROM inquiries i
         LEFT JOIN packages p ON i.package_id = p.id
         LEFT JOIN bookings b ON i.booking_reference = b.booking_reference
         WHERE i.id = :id AND i.user_id = :uid"
    );
    $stmt->execute([':id' => $threadId, ':uid' => $userId]);
    $viewThread = $stmt->fetch();

    if ($viewThread) {
        // Fetch all replies in chronological order, joined with sender name
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

// === TEMPLATE HELPER FUNCTIONS ===

/**
 * Render a field value for re-populating form inputs.
 */
function inq_old(string $field, array $fields): string
{
    return htmlspecialchars($fields[$field] ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Render a validation error message for a given field.
 */
function inq_error(string $field, array $errors): string
{
    return htmlspecialchars($errors[$field] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Inquiries - GlobeTrek</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Winky+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/user-sidebar.css">
    <link rel="stylesheet" href="../css/inquiries.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="inq-page">
    <!-- === NAVBAR === -->
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <!-- === FLASH MESSAGES === -->
        <?php if (isset($_GET['created'])): ?>
            <div class="form-alert success" role="status" style="max-width:1280px;margin:0 auto;padding:0.85rem clamp(1.25rem,4vw,3rem);">
                Your inquiry has been submitted successfully.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['replied'])): ?>
            <div class="form-alert success" role="status" style="max-width:1280px;margin:0 auto;padding:0.85rem clamp(1.25rem,4vw,3rem);">
                Your reply has been sent.
            </div>
        <?php endif; ?>

        <div class="usr-layout">
            <!-- === SIDEBAR === -->
            <?php $activePage = 'inquiries'; include '../includes/user-sidebar.php'; ?>

            <!-- === MAIN CONTENT === -->
            <div class="usr-canvas">
                <!-- === HEADER WITH NEW INQUIRY BUTTON === -->
                <div class="inq-header">
                    <h1>My Inquiries</h1>
                    <button class="inq-new-btn" onclick="document.getElementById('newInquiryModal').classList.add('open')">
                        <span class="material-symbols-outlined">add</span>
                        New Inquiry
                    </button>
                </div>

                <!-- === STATUS TABS === -->
                <div class="inq-tabs">
                    <a href="?filter=active" class="inq-tab <?= $filter !== 'resolved' ? 'active' : '' ?>">
                        Active (<?= $activeCount ?>)
                    </a>
                    <a href="?filter=resolved" class="inq-tab <?= $filter === 'resolved' ? 'active' : '' ?>">
                        Resolved (<?= $resolvedCount ?>)
                    </a>
                </div>

                <!-- === INQUIRIES LIST === -->
                <?php if (empty($inquiries)): ?>
                    <div class="inq-empty">
                        <span class="material-symbols-outlined inq-empty-icon">chat_bubble</span>
                        <h2>No <?= $filter === 'resolved' ? 'resolved' : 'active' ?> inquiries</h2>
                        <p><?= $filter === 'resolved'
                            ? 'You have no resolved inquiries yet.'
                            : 'Start a conversation with our team about any booking or travel question.'
                        ?></p>
                        <?php if ($filter !== 'resolved'): ?>
                            <button class="inq-new-btn" onclick="document.getElementById('newInquiryModal').classList.add('open')">
                                <span class="material-symbols-outlined">add</span>
                                New Inquiry
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="inq-list">
                        <?php foreach ($inquiries as $inq): ?>
                            <?php
                                // Map DB status values to display classes and labels
                                $statusClass = 'open';
                                $statusLabel = 'Open';
                                if ($inq['status'] === 'waiting_for_response') { $statusClass = 'waiting'; $statusLabel = 'Waiting for Response'; }
                                elseif ($inq['status'] === 'under_review') { $statusClass = 'review'; $statusLabel = 'Under Review'; }
                                elseif ($inq['status'] === 'resolved') { $statusClass = 'resolved'; $statusLabel = 'Resolved'; }

                                $date = date('M d, Y', strtotime($inq['created_at']));
                                // Show unread indicator if there are replies and inquiry is not resolved
                                $hasUnread = ($inq['reply_count'] > 0 && $inq['status'] !== 'resolved');
                            ?>
                            <div class="inq-card">
                                <div class="inq-card-body">
                                    <div class="inq-card-meta">
                                        <span class="inq-badge inq-badge-id"><?= htmlspecialchars($inq['inquiry_id_code']) ?></span>
                                        <span class="inq-badge inq-badge-date">
                                            <span class="material-symbols-outlined">calendar_today</span>
                                            <?= $date ?>
                                        </span>
                                        <span class="inq-badge inq-badge-status inq-status-<?= $statusClass ?>">
                                            <span class="status-dot"></span>
                                            <?= $statusLabel ?>
                                        </span>
                                    </div>
                                    <h3 class="inq-card-title <?= $statusClass === 'resolved' ? 'resolved' : '' ?>">
                                        <?= htmlspecialchars($inq['subject']) ?>
                                    </h3>
                                    <p class="inq-card-preview <?= $statusClass === 'resolved' ? 'italic' : '' ?>">
                                        <?= $statusClass === 'resolved'
                                            ? 'This inquiry has been closed. If you have further questions, please reference the inquiry ID in a new message.'
                                            : htmlspecialchars($inq['message'])
                                        ?>
                                    </p>
                                </div>
                                <div class="inq-card-actions">
                                    <button class="inq-action-btn <?= $hasUnread ? 'primary' : '' ?>"
                                            onclick="openThread(<?= $inq['id'] ?>)">
                                        View Thread
                                        <?php if ($hasUnread): ?>
                                            <span class="unread-dot"></span>
                                        <?php endif; ?>
                                    </button>
                                    <?php if ($statusClass !== 'resolved'): ?>
                                        <button class="inq-action-btn"
                                                onclick="openThread(<?= $inq['id'] ?>)">
                                            Add Note
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- === NEW INQUIRY MODAL === -->
    <div class="inq-modal-overlay" id="newInquiryModal">
        <div class="inq-modal">
            <div class="inq-modal-header">
                <h2>New Inquiry</h2>
                <button class="inq-modal-close" onclick="closeNewInquiryModal()">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form method="post" action="inquiries.php" novalidate>
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="create_inquiry">
                <div class="inq-modal-body">
                    <?php if (!empty($errors)): ?>
                        <div class="form-alert error" role="alert">
                            Please review the highlighted fields and try again.
                        </div>
                    <?php endif; ?>

                    <!-- Optional: link inquiry to an existing booking -->
                    <div class="form-field" style="margin-bottom:1rem;">
                        <label for="new-package">Related Booking (Optional)</label>
                        <select id="new-package" name="package_id">
                            <option value="">-- No specific booking --</option>
                            <?php foreach ($userBookings as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= inq_old('package_id', $_POST ?? []) == $b['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['booking_reference'] . ' - ' . $b['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field" style="margin-bottom:1rem;">
                        <label for="new-subject">Subject</label>
                        <input id="new-subject" name="subject" type="text"
                               value="<?= inq_old('subject', $_POST ?? []) ?>"
                               placeholder="Brief summary of your question"
                               aria-invalid="<?= isset($errors['subject']) ? 'true' : 'false' ?>">
                        <?php if (isset($errors['subject'])): ?>
                            <p class="field-error"><?= inq_error('subject', $errors) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="new-message">Message</label>
                        <textarea id="new-message" name="message" rows="5"
                                  placeholder="Describe your question or concern in detail..."
                                  aria-invalid="<?= isset($errors['message']) ? 'true' : 'false' ?>"><?= inq_old('message', $_POST ?? []) ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <p class="field-error"><?= inq_error('message', $errors) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="inq-modal-footer">
                    <button type="button" class="inq-cancel-btn" onclick="closeNewInquiryModal()">Cancel</button>
                    <button type="submit" class="inq-submit-btn">Submit Inquiry</button>
                </div>
            </form>
        </div>
    </div>

    <!-- === THREAD VIEW MODAL === -->
    <!-- Displays the full conversation for a selected inquiry -->
    <div class="inq-modal-overlay inq-thread-modal" id="threadModal">
        <div class="inq-modal">
            <div class="inq-modal-header">
                <h2>Inquiry Thread</h2>
                <button class="inq-modal-close" onclick="closeThreadModal()">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="inq-modal-body">
                <?php if ($viewThread): ?>
                    <!-- Thread header with inquiry metadata -->
                    <div class="inq-thread-subject"><?= htmlspecialchars($viewThread['subject']) ?></div>
                    <div class="inq-thread-info">
                        <span class="inq-badge inq-badge-id"><?= htmlspecialchars($viewThread['inquiry_id_code']) ?></span>
                        <?php if ($viewThread['package_title']): ?>
                            <span class="inq-badge">
                                <?= htmlspecialchars($viewThread['package_title']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($viewThread['booking_reference']): ?>
                            <span class="inq-badge">
                                <?= htmlspecialchars($viewThread['booking_reference']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- === MESSAGES LIST === -->
                    <div class="inq-thread-messages">
                        <!-- Original inquiry message (always from the user) -->
                        <div class="inq-message user-message">
                            <div class="inq-message-header">
                                <span class="inq-message-sender">You</span>
                                <span class="inq-message-date"><?= date('M d, Y \a\t g:i A', strtotime($viewThread['created_at'])) ?></span>
                            </div>
                            <p class="inq-message-text"><?= nl2br(htmlspecialchars($viewThread['message'])) ?></p>
                        </div>

                        <!-- All replies (user and admin) in chronological order -->
                        <?php foreach ($threadReplies as $reply): ?>
                            <div class="inq-message <?= $reply['sender_role'] === 'admin' ? 'admin-message' : 'user-message' ?>">
                                <div class="inq-message-header">
                                    <span class="inq-message-sender">
                                        <?= htmlspecialchars($reply['sender_name'] ?? 'Unknown') ?>
                                        <?php if ($reply['sender_role'] === 'admin'): ?>
                                            <span class="admin-badge">Staff</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="inq-message-date"><?= date('M d, Y \a\t g:i A', strtotime($reply['created_at'])) ?></span>
                                </div>
                                <p class="inq-message-text"><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- === REPLY FORM === -->
                    <!-- Only shown if the inquiry is not resolved -->
                    <?php if ($viewThread['status'] !== 'resolved'): ?>
                        <form method="post" action="inquiries.php" class="inq-reply-form" novalidate>
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="add_reply">
                            <input type="hidden" name="inquiry_id" value="<?= $viewThread['id'] ?>">
                            <div class="form-field">
                                <label for="reply-msg">Your Reply</label>
                                <textarea id="reply-msg" name="reply_message" rows="3"
                                          placeholder="Type your reply..."></textarea>
                            </div>
                            <button type="submit" class="inq-submit-btn" style="margin-top:0.5rem;">Send Reply</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Inquiry not found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- === FOOTER === -->
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script>
    // === THREAD NAVIGATION ===
    // Navigates to the inquiry thread via URL parameter (server-side rendering)
    function openThread(id) {
        window.location.href = 'inquiries.php?thread=' + id + '<?= $filter === 'resolved' ? '&filter=resolved' : '' ?>';
    }

    // === MODAL CLOSE FUNCTIONS ===
    function closeNewInquiryModal() {
        document.getElementById('newInquiryModal').classList.remove('open');
    }

    function closeThreadModal() {
        // Navigate back to the list view (clears thread parameter)
        window.location.href = 'inquiries.php<?= $filter === 'resolved' ? '?filter=resolved' : '' ?>';
    }

    // === CLOSE MODALS ON OVERLAY CLICK ===
    // Clicking the backdrop (not the modal content) closes the modal
    document.querySelectorAll('.inq-modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                if (overlay.id === 'newInquiryModal') {
                    closeNewInquiryModal();
                } else {
                    closeThreadModal();
                }
            }
        });
    });

    // === AUTO-OPEN THREAD MODAL ===
    // If the URL contains a thread parameter, open the thread modal on page load
    <?php if ($viewThread): ?>
    document.getElementById('threadModal').classList.add('open');
    <?php endif; ?>
    </script>
</body>
</html>
