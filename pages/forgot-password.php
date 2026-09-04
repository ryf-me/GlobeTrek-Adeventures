<?php
/**
 * File: pages/forgot-password.php
 * Purpose: Forgot password page - allows users to request a password reset
 *          link. Generates a secure token stored in password_resets table
 *          with 30-minute expiry. Prevents email enumeration by always
 *          showing the same success message.
 * Dependencies: config/database.php, config/csrf.php, config/rate-limiter.php,
 *               includes/mailer.php
 * Used By: login.php (forgot password link)
 * Parent Files: login.php, navbar.php
 * Child Files: None (leaf page)
 * @package GlobeTrek\Pages
 */

// === CONFIGURATION & DEPENDENCIES ===
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/rate-limiter.php';
require_once __DIR__ . '/../includes/mailer.php';
$db = getDB();

// === STATE INITIALIZATION ===
$message = '';
$success = false;

// === HANDLE FORM SUBMISSION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid security token. Please try again.';
    // Rate limiting — max 3 requests per hour per IP
    } elseif (!checkRateLimit('forgot_password', 3, 3600, true)) {
        $message = 'Too many requests. Please try again later.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
        } else {
            // === PREVENT EMAIL ENUMERATION ===
            // Always show the same success message regardless of whether
            // the email exists in the database. This prevents attackers
            // from discovering which emails are registered.
            $success = true;
            $message = 'If an account exists with that email, a password reset link has been sent.';

            // Check if user exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                // === GENERATE RESET TOKEN ===
                // Delete any existing reset tokens for this email first
                $del = $db->prepare("DELETE FROM password_resets WHERE email = :email");
                $del->execute([':email' => $email]);

                // Generate cryptographically secure token (64-char hex)
                $token = bin2hex(random_bytes(32));
                // Token expires in 30 minutes
                $expiresAt = date('Y-m-d H:i:s', time() + 1800);

                $ins = $db->prepare(
                    "INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (:user_id, :email, :token, :expires)"
                );
                $ins->execute([':user_id' => $user['id'], ':email' => $email, ':token' => $token, ':expires' => $expiresAt]);

                // === SEND RESET EMAIL ===
                $resetLink = BASE_URL . '/pages/reset-password.php?token=' . $token;

                $emailContent = '
                    <h2 style="margin:0 0 16px;color:#264653;">Password Reset Request</h2>
                    <p>We received a request to reset your password. Click the button below to create a new password:</p>
                    <div style="text-align:center;margin:24px 0;">
                        <a href="' . htmlspecialchars($resetLink) . '" style="display:inline-block;background:#e76f51;color:#ffffff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:600;">Reset Password</a>
                    </div>
                    <p style="color:#666;font-size:13px;">Or copy this link: ' . htmlspecialchars($resetLink) . '</p>
                    <p style="color:#666;font-size:13px;">This link expires in <strong>30 minutes</strong>.</p>
                    <p style="color:#666;font-size:13px;">If you did not request this reset, please ignore this email. Your password will remain unchanged.</p>
                ';
                $htmlBody = wrapEmailTemplate($emailContent);
                $textBody = "Password Reset Request\n\nTo reset your password, visit:\n$resetLink\n\nThis link expires in 30 minutes.\nIf you did not request this, ignore this email.";
                sendMail($email, 'Reset Your Password — GlobeTrek Adventures', $htmlBody, $textBody);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - GlobeTrek Adventures</title>
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

    <!-- === FORGOT PASSWORD FORM CARD === -->
    <main class="login-shell">
        <div class="login-card">
            <div class="login-lock">
                <span class="material-symbols-outlined">lock_reset</span>
            </div>

            <div class="login-header">
                <h1>Forgot Password?</h1>
                <p>Enter your email and we'll send you a link to reset your password.</p>
            </div>

            <!-- Display success or error messages -->
            <?php if ($message !== ''): ?>
                <div class="signup-message <?php echo $success ? 'success' : 'error'; ?>" role="<?php echo $success ? 'status' : 'alert'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- === PASSWORD RESET REQUEST FORM === -->
            <form class="login-form forgot-form" action="forgot-password.php" method="post">
                <?php csrf_field(); ?>

                <!-- Email field (pre-filled from GET/POST if available) -->
                <div class="form-group">
                    <label for="forgot-email">Email Address</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined input-icon">mail</span>
                        <input id="forgot-email" name="email" type="email" placeholder="Enter your email" autocomplete="email" value="<?php echo htmlspecialchars($_GET['email'] ?? $_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>

                <button class="login-submit" type="submit">
                    <span class="material-symbols-outlined">send</span>
                    Send Reset Link
                </button>
            </form>

            <p class="signup-prompt" style="margin-top:1rem;">Remember your password? <a href="login.php">Login</a></p>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>
