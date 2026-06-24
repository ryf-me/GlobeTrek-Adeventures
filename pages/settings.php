<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
$db = getDB();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$notificationPrefs = json_decode($user['notification_preferences'] ?? '{}', true) ?: [
    'email_notifications' => true,
    'sms_updates' => false,
    'promotional_offers' => true,
    'public_profile' => false
];

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errorMsg = 'Invalid security token. Please try again.';
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $errorMsg = 'All password fields are required.';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errorMsg = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 6) {
            $errorMsg = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = 'New passwords do not match.';
        } else {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $updStmt = $db->prepare("UPDATE users SET password = :pw WHERE id = :id");
            $updStmt->execute([':pw' => $hashed, ':id' => $userId]);
            $successMsg = 'Password updated successfully.';
        }
    } elseif ($action === 'update_notifications') {
        $notifPrefs = [
            'email_notifications' => isset($_POST['email_notifications']),
            'sms_updates' => isset($_POST['sms_updates']),
            'promotional_offers' => isset($_POST['promotional_offers']),
        ];
        $updStmt = $db->prepare("UPDATE users SET notification_preferences = :prefs WHERE id = :id");
        $updStmt->execute([':prefs' => json_encode($notifPrefs), ':id' => $userId]);
        $notificationPrefs = $notifPrefs;
        $successMsg = 'Notification preferences saved.';
    } elseif ($action === 'update_privacy') {
        $privacy = ['public_profile' => isset($_POST['public_profile'])];
        $notifPrefs['public_profile'] = $privacy['public_profile'];
        $updStmt = $db->prepare("UPDATE users SET notification_preferences = :prefs WHERE id = :id");
        $updStmt->execute([':prefs' => json_encode($notifPrefs), ':id' => $userId]);
        $notificationPrefs = $notifPrefs;
        $successMsg = 'Privacy settings saved.';
    } elseif ($action === 'delete_account') {
        $deletePassword = $_POST['delete_password'] ?? '';
        if (!password_verify($deletePassword, $user['password'])) {
            $errorMsg = 'Incorrect password. Account not deleted.';
        } else {
            $db->prepare("DELETE FROM wishlist WHERE user_id = :id")->execute([':id' => $userId]);
            $db->prepare("UPDATE bookings SET user_id = NULL WHERE user_id = :id")->execute([':id' => $userId]);
            $db->prepare("UPDATE inquiries SET user_id = NULL WHERE user_id = :id")->execute([':id' => $userId]);
            $db->prepare("DELETE FROM inquiry_replies WHERE sender_id = :id")->execute([':id' => $userId]);
            $db->prepare("DELETE FROM activity_logs WHERE user_id = :id")->execute([':id' => $userId]);
            $db->prepare("DELETE FROM payments WHERE user_id = :id")->execute([':id' => $userId]);
            $db->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $userId]);
            session_destroy();
            header('Location: ../index.php?account_deleted=1');
            exit;
        }
    }
}

$activePage = 'settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - GlobeTrek</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Winky+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/user-sidebar.css">
    <link rel="stylesheet" href="../css/settings.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="usr-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <div class="usr-layout">
            <?php include '../includes/user-sidebar.php'; ?>

            <div class="usr-canvas">
                <div class="usr-page-header">
                    <h1>Settings</h1>
                    <p>Manage your account settings and preferences.</p>
                </div>

                <?php if ($successMsg): ?>
                    <div class="settings-alert settings-alert-success">
                        <span class="material-symbols-outlined">check_circle</span>
                        <?= htmlspecialchars($successMsg) ?>
                    </div>
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    <div class="settings-alert settings-alert-error">
                        <span class="material-symbols-outlined">error</span>
                        <?= htmlspecialchars($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <div class="settings-sections">
                    <!-- Security -->
                    <section class="settings-section">
                        <div class="settings-section-header">
                            <span class="material-symbols-outlined">security</span>
                            <h3>Security</h3>
                        </div>
                        <form method="post" class="settings-password-stack">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="update_password">
                            <p class="settings-subheading">Change Password</p>
                            <div class="settings-form-group">
                                <label for="current-password">Current Password</label>
                                <input type="password" id="current-password" name="current_password" placeholder="••••••••" required>
                            </div>
                            <div class="settings-form-row">
                                <div class="settings-form-group">
                                    <label for="new-password">New Password</label>
                                    <input type="password" id="new-password" name="new_password" placeholder="••••••••" minlength="6" required>
                                </div>
                                <div class="settings-form-group">
                                    <label for="confirm-password">Confirm New Password</label>
                                    <input type="password" id="confirm-password" name="confirm_password" placeholder="••••••••" minlength="6" required>
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="settings-btn settings-btn-primary">Update Password</button>
                            </div>
                        </form>
                    </section>

                    <!-- Notifications -->
                    <section class="settings-section">
                        <div class="settings-section-header">
                            <span class="material-symbols-outlined">notifications</span>
                            <h3>Notifications</h3>
                        </div>
                        <form method="post">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="update_notifications">
                            <div>
                                <div class="settings-toggle-row">
                                    <div class="settings-toggle-info">
                                        <p>Email Notifications</p>
                                        <p>Receive booking updates via email</p>
                                    </div>
                                    <label class="settings-toggle">
                                        <input type="checkbox" name="email_notifications" <?= !empty($notificationPrefs['email_notifications']) ? 'checked' : '' ?>>
                                        <span class="settings-toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="settings-toggle-row">
                                    <div class="settings-toggle-info">
                                        <p>SMS Updates</p>
                                        <p>Get real-time trip alerts on your phone</p>
                                    </div>
                                    <label class="settings-toggle">
                                        <input type="checkbox" name="sms_updates" <?= !empty($notificationPrefs['sms_updates']) ? 'checked' : '' ?>>
                                        <span class="settings-toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="settings-toggle-row">
                                    <div class="settings-toggle-info">
                                        <p>Promotional Offers</p>
                                        <p>Occasional discounts and travel inspiration</p>
                                    </div>
                                    <label class="settings-toggle">
                                        <input type="checkbox" name="promotional_offers" <?= !empty($notificationPrefs['promotional_offers']) ? 'checked' : '' ?>>
                                        <span class="settings-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div style="margin-top:1rem;">
                                <button type="submit" class="settings-btn settings-btn-primary">Save Preferences</button>
                            </div>
                        </form>
                    </section>

                    <!-- Account Privacy -->
                    <section class="settings-section">
                        <div class="settings-section-header">
                            <span class="material-symbols-outlined">privacy_tip</span>
                            <h3>Account Privacy</h3>
                        </div>
                        <form method="post">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="update_privacy">
                            <div>
                                <label class="settings-checkbox-row">
                                    <input type="checkbox" name="public_profile" <?= !empty($notificationPrefs['public_profile']) ? 'checked' : '' ?>>
                                    <div class="settings-checkbox-info">
                                        <span>Make Profile Public</span>
                                        <p>Allow others to see your travel wishlist and reviews.</p>
                                    </div>
                                </label>
                            </div>
                            <div style="margin-top:1rem;">
                                <button type="submit" class="settings-btn settings-btn-primary">Save Privacy Settings</button>
                            </div>
                        </form>
                    </section>

                    <!-- Danger Zone -->
                    <section class="settings-section danger">
                        <div class="settings-section-header">
                            <span class="material-symbols-outlined">warning</span>
                            <h3>Danger Zone</h3>
                        </div>
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="delete_account">
                            <div class="settings-danger-row">
                                <div class="settings-danger-info">
                                    <p>Delete Account</p>
                                    <p>This action is permanent and cannot be undone. All your bookings and data will be lost.</p>
                                    <div class="settings-form-group" style="margin-top:1rem; max-width:300px;">
                                        <label for="delete-password">Confirm your password</label>
                                        <input type="password" id="delete-password" name="delete_password" placeholder="Enter password to confirm" required>
                                    </div>
                                </div>
                                <button type="submit" class="settings-btn settings-btn-danger">Delete Account</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
</body>
</html>
