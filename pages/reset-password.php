<?php
/**
 * File: pages/reset-password.php
 * Purpose: Reset password page - validates the reset token from the email
 *          link and allows the user to set a new password. Token must exist
 *          and not be expired. Uses the same password strength rules as signup.
 * Dependencies: config/database.php, config/csrf.php
 * Used By: forgot-password.php (reset link in email)
 * Parent Files: forgot-password.php
 * Child Files: None (leaf page)
 * @package GlobeTrek\Pages
 */

// === CONFIGURATION & DEPENDENCIES ===
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
$db = getDB();

// === STATE INITIALIZATION ===
$token = $_GET['token'] ?? '';
$message = '';
$success = false;
$validToken = false;

// === VALIDATE RESET TOKEN ON PAGE LOAD ===
// Check if the token exists in the database and has not expired
if ($token !== '') {
    $stmt = $db->prepare(
        "SELECT id, email, expires_at FROM password_resets WHERE token = :token LIMIT 1"
    );
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch();

    if ($row && strtotime($row['expires_at']) > time()) {
        // Token is valid and not expired
        $validToken = true;
    } elseif ($row) {
        // Token found but expired
        $message = 'This reset link has expired. Please request a new one.';
    } else {
        // Token not found in database
        $message = 'Invalid reset link. Please request a new one.';
    }
} else {
    $message = 'No reset token provided.';
}

// === HANDLE FORM SUBMISSION ===
// Only process if token was valid on page load
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid security token. Please try again.';
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // === PASSWORD STRENGTH VALIDATION ===
        // Same rules as signup page for consistency
        if (strlen($newPassword) < 8) {
            $message = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $newPassword)) {
            $message = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[0-9]/', $newPassword)) {
            $message = 'Password must contain at least one number.';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            $message = 'Password must contain at least one special character.';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Passwords do not match.';
        } else {
            // === UPDATE PASSWORD ===
            // Hash the new password with bcrypt
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $upd = $db->prepare("UPDATE users SET password = :pw WHERE email = :email");
            $upd->execute([':pw' => $hashed, ':email' => $row['email']]);

            // === DELETE USED TOKEN ===
            // One-time use: delete the token after successful password reset
            $del = $db->prepare("DELETE FROM password_resets WHERE id = :id");
            $del->execute([':id' => $row['id']]);

            $success = true;
            $message = 'Your password has been reset successfully! You can now log in with your new password.';
            // Hide the form after successful reset
            $validToken = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - GlobeTrek Adventures</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body class="login-page">

    <!-- === BACKGROUND VIDEO === -->
    <div class="login-bg">
        <video autoplay muted loop playsinline>
            <source src="https://videos.pexels.com/video-files/32504501/13860754_1280_720_30fps.mp4" type="video/mp4">
        </video>
        <div class="login-bg-overlay"></div>
    </div>

    <!-- === TOP NAVIGATION BAR === -->
    <header class="login-topbar">
        <a class="login-topbar-logo" href="../index.php">
            <img src="../images/logo.png" alt="GlobeTrek Adventures logo" />
            <div class="login-topbar-text">
                <span class="brand-name">GlobeTrek</span>
                <span class="brand-tagline">Explore. Experience. Remember.</span>
            </div>
        </a>
    </header>

    <!-- === RESET PASSWORD FORM CARD === -->
    <main class="login-shell">
        <div class="login-card">
            <!-- Dynamic icon: lock_open on success, lock otherwise -->
            <div class="login-lock">
                <span class="material-symbols-outlined"><?php echo $success ? 'lock_open' : 'lock'; ?></span>
            </div>

            <div class="login-header">
                <h1><?php echo $success ? 'Password Reset!' : 'Reset Password'; ?></h1>
                <?php if (!$success && $validToken): ?>
                    <p>Enter your new password below.</p>
                <?php endif; ?>
            </div>

            <!-- Display success or error messages -->
            <?php if ($message !== ''): ?>
                <div class="signup-message <?php echo $success ? 'success' : 'error'; ?>" role="<?php echo $success ? 'status' : 'alert'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- === NEW PASSWORD FORM === -->
            <!-- Only shown when token is valid and password not yet reset -->
            <?php if ($validToken): ?>
                <form class="login-form reset-form" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" method="post">
                    <?php csrf_field(); ?>

                    <!-- New password field with strength meter -->
                    <div class="form-group">
                        <label for="new-password">New Password</label>
                        <div class="input-icon-wrapper">
                            <span class="material-symbols-outlined input-icon">lock</span>
                            <input id="new-password" name="new_password" type="password" placeholder="Create a new password" autocomplete="new-password" required minlength="8">
                            <button type="button" class="password-toggle" data-target="new-password" aria-label="Toggle password visibility">
                                <span class="material-symbols-outlined icon-visible">visibility</span>
                                <span class="material-symbols-outlined icon-hidden">visibility_off</span>
                            </button>
                        </div>
                        <div class="password-strength-meter"></div>
                    </div>

                    <!-- Confirm password field -->
                    <div class="form-group">
                        <label for="confirm-password">Confirm New Password</label>
                        <div class="input-icon-wrapper">
                            <span class="material-symbols-outlined input-icon">lock</span>
                            <input id="confirm-password" name="confirm_password" type="password" placeholder="Confirm your password" autocomplete="new-password" required minlength="8">
                            <button type="button" class="password-toggle" data-target="confirm-password" aria-label="Toggle password visibility">
                                <span class="material-symbols-outlined icon-visible">visibility</span>
                                <span class="material-symbols-outlined icon-hidden">visibility_off</span>
                            </button>
                        </div>
                    </div>

                    <button class="login-submit" type="submit">
                        <span class="material-symbols-outlined">save</span>
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>

            <!-- === NAVIGATION LINKS === -->
            <p class="signup-prompt" style="margin-top:1rem;">
                <?php if ($success): ?>
                    <a href="login.php">Go to Login</a>
                <?php else: ?>
                    <a href="forgot-password.php">Request a new reset link</a> &middot; <a href="login.php">Back to Login</a>
                <?php endif; ?>
            </p>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>
