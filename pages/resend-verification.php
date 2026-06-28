<?php
/**
 * File: pages/resend-verification.php
 * Purpose: Resend verification email page - allows users who haven't verified
 *          their email to request a new verification link. Rate-limited to
 *          3 attempts per hour. Prevents email enumeration by showing the
 *          same success message regardless of whether the email exists.
 * Dependencies: config/database.php, config/rate-limiter.php, config/otp.php,
 *               includes/mailer.php
 * Used By: login.php (resend verification link), signup.php (after registration)
 * Parent Files: login.php, signup.php
 * Child Files: None (leaf page)
 * @package GlobeTrek\Pages
 */

// === CONFIGURATION & DEPENDENCIES ===
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/rate-limiter.php';
require_once __DIR__ . '/../config/otp.php';
require_once __DIR__ . '/../includes/mailer.php';
$db = getDB();

// === STATE INITIALIZATION ===
$message = '';
$success = false;
// Pre-fill email from query string or previous POST submission
$email = $_GET['email'] ?? $_POST['email'] ?? '';

// === HANDLE FORM SUBMISSION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    // Rate limiting — max 3 resend attempts per hour per IP
    } elseif (!checkRateLimit('resend_verify', 3, 3600, true)) {
        $message = 'Too many resend attempts. Please try again later.';
    } else {
        // Look up user by email
        $stmt = $db->prepare("SELECT id, email_verified FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || $user['email_verified'] == 1) {
            // === PREVENT EMAIL ENUMERATION ===
            // Show the same success message whether user exists, is verified, or not found
            $success = true;
            $message = 'If an unverified account exists with that email, a new verification link has been sent.';
        } else {
            // === GENERATE NEW VERIFICATION TOKEN ===
            // Delete old verification tokens for this user first
            $del = $db->prepare("DELETE FROM email_verifications WHERE user_id = :uid");
            $del->execute([':uid' => $user['id']]);

            // Generate new 64-char hex token (valid 24 hours)
            $verifyToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 86400);
            $stmt = $db->prepare(
                "INSERT INTO email_verifications (user_id, token, expires_at) VALUES (:uid, :token, :expires)"
            );
            $stmt->execute([':uid' => $user['id'], ':token' => $verifyToken, ':expires' => $expiresAt]);

            // === SEND VERIFICATION EMAIL ===
            $verifyLink = BASE_URL . '/pages/verify-email.php?token=' . $verifyToken;
            $emailContent = '
                <h2 style="margin:0 0 16px;color:#264653;">Email Verification</h2>
                <p>You requested a new verification link. Please verify your email address by clicking below:</p>
                <div style="text-align:center;margin:24px 0;">
                    <a href="' . htmlspecialchars($verifyLink) . '" style="display:inline-block;background:#e76f51;color:#ffffff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:600;">Verify Email Address</a>
                </div>
                <p style="color:#666;font-size:13px;">This link expires in <strong>24 hours</strong>.</p>
            ';
            $htmlBody = wrapEmailTemplate($emailContent);
            $textBody = "Please verify your email by visiting:\n$verifyLink\n\nThis link expires in 24 hours.";
            sendMail($email, 'Verify Your Email — GlobeTrek Adventures', $htmlBody, $textBody);

            $success = true;
            $message = 'If an unverified account exists with that email, a new verification link has been sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification - GlobeTrek Adventures</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body class="login-page">

    <!-- === BACKGROUND IMAGE === -->
    <div class="login-bg">
        <img src="../images/login-bg.jpg" alt="" aria-hidden="true">
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

    <!-- === RESEND VERIFICATION FORM CARD === -->
    <main class="login-shell">
        <div class="login-card">
            <div class="login-lock">
                <span class="material-symbols-outlined">mail</span>
            </div>

            <div class="login-header">
                <h1>Resend Verification</h1>
                <p>Enter your email to receive a new verification link.</p>
            </div>

            <!-- Display success or error messages -->
            <?php if ($message !== ''): ?>
                <div class="signup-message <?php echo $success ? 'success' : 'error'; ?>" role="<?php echo $success ? 'status' : 'alert'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- === RESEND FORM === -->
            <form class="login-form forgot-form" action="resend-verification.php" method="post">
                <?php csrf_field(); ?>

                <!-- Email field (pre-filled from GET/POST if available) -->
                <div class="form-group">
                    <label for="forgot-email">Email Address</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined input-icon">mail</span>
                        <input id="forgot-email" name="email" type="email" placeholder="Enter your email" autocomplete="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>

                <button class="login-submit" type="submit">
                    <span class="material-symbols-outlined">send</span>
                    Send Verification Link
                </button>
            </form>

            <p class="signup-prompt" style="margin-top:1rem;">Remember your password? <a href="login.php">Login</a></p>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>
