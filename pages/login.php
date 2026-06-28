<?php
/**
 * File: pages/login.php
 * Purpose: User login page - authenticates users via email/password with
 *          CSRF protection, rate limiting, session fixation prevention,
 *          and persistent "Remember Me" token support.
 * Dependencies: config/session.php, config/database.php, config/csrf.php,
 *               config/rate-limiter.php
 * Used By: navbar.php (login link), signup.php (redirect), forgot-password.php (redirect),
 *          resend-verification.php (redirect)
 * Parent Files: index.php (redirects here on auth failure)
 * Child Files: None (leaf page, no includes beyond config)
 * @package GlobeTrek\Pages
 */

// === CONFIGURATION & DEPENDENCIES ===

// Start session and load core config files
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/rate-limiter.php';
$db = getDB();

// === REMEMBER-ME HELPER FUNCTIONS ===

/**
 * Create a persistent login token for the given user.
 * Stores a SHA-256 hash in the DB and sets a plain-text cookie.
 * Security: Only the hash is stored; the raw token lives solely in the cookie.
 */
function setRememberToken(PDO $db, int $userId): void
{
    // Generate a cryptographically secure 64-char hex token
    $rawToken  = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    // Token expires after 30 days
    $expiresAt = date('Y-m-d H:i:s', time() + 30 * 24 * 3600);

    // Store hash in database — raw token never touches the DB
    $stmt = $db->prepare(
        "INSERT INTO remember_tokens (user_id, token_hash, expires_at)
         VALUES (:uid, :th, :exp)"
    );
    $stmt->execute([
        ':uid' => $userId,
        ':th'  => $tokenHash,
        ':exp' => $expiresAt,
    ]);

    // Set cookie with security flags
    setcookie('remember_me', $rawToken, [
        'expires'  => time() + 30 * 24 * 3600,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),   // HTTPS-only in production
        'httponly'  => true,                        // Not accessible via JavaScript
        'samesite' => 'Lax',                       // CSRF mitigation
    ]);
}

/**
 * Attempt to authenticate the visitor from a remember_me cookie.
 * Returns true if a valid session was created.
 * Security: Rotates token on each use to limit replay window.
 */
function tryAutoLogin(PDO $db): bool
{
    // Skip if already authenticated
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user_id'])) {
        return false;
    }

    // No cookie = no auto-login attempt
    if (empty($_COOKIE['remember_me'])) {
        return false;
    }

    // Hash the cookie value to match against the stored hash
    $tokenHash = hash('sha256', $_COOKIE['remember_me']);

    // Look up the token and join user data in one query
    $stmt = $db->prepare(
        "SELECT rt.*, u.id AS uid, u.full_name, u.email, u.role, u.profile_photo
         FROM remember_tokens rt
         JOIN users u ON u.id = rt.user_id
         WHERE rt.token_hash = :th AND rt.expires_at > NOW()
         LIMIT 1"
    );
    $stmt->execute([':th' => $tokenHash]);
    $row = $stmt->fetch();

    if (!$row) {
        // Invalid or expired token — clear the cookie
        clearRememberCookie();
        return false;
    }

    // Valid token — establish session with fresh ID
    session_regenerate_id(true);
    $_SESSION['user_id']            = $row['uid'];
    $_SESSION['user_name']          = $row['full_name'];
    $_SESSION['user_email']         = $row['email'];
    $_SESSION['user_role']          = $row['role'];
    $_SESSION['user_profile_photo'] = $row['profile_photo'] ?? '';

    // Token rotation: delete old token and issue new one
    $del = $db->prepare("DELETE FROM remember_tokens WHERE id = :id");
    $del->execute([':id' => $row['id']]);
    setRememberToken($db, $row['uid']);

    return true;
}

/**
 * Remove the remember_me cookie by expiring it.
 */
function clearRememberCookie(): void
{
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);
    }
}

/**
 * Purge expired tokens (runs once per request, lightweight).
 * Prevents unbounded growth of the remember_tokens table.
 */
function purgeExpiredTokens(PDO $db): void
{
    $db->exec("DELETE FROM remember_tokens WHERE expires_at < NOW()");
}

// === AUTO-LOGIN FROM COOKIE ===
// Attempt auto-login before any POST handling so remembered users
// are silently redirected to the homepage.
purgeExpiredTokens($db);

if (tryAutoLogin($db)) {
    header('Location: ../index.php');
    exit;
}

// === HANDLE FORM SUBMISSION ===
$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation — reject forged requests
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    }

    // Rate limiting — max 5 login attempts per 15 minutes per IP
    if (empty($errors) && !checkRateLimit('login', 5, 900, true)) {
        $errors['general'] = 'Too many login attempts. Please try again in 15 minutes.';
    }

    if (empty($errors)) {
        // Sanitize and validate email input
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if ($password === '') {
            $errors['password'] = 'Please enter your password.';
        }
    }

    if (empty($errors)) {
        // Per-account lockout check — prevents brute-force on specific accounts
        if (!checkAccountLockout($email, 5, 900)) {
            $errors['general'] = 'Too many failed login attempts for this account. Please try again in 15 minutes.';
        }
    }

    if (empty($errors)) {
        // Fetch user record by email
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Generic message prevents email enumeration
            $errors['general'] = 'Invalid email or password.';
            recordLoginAttempt($email, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        } elseif ($user['is_active'] == 0) {
            // Account deactivated by admin
            $errors['general'] = 'Your account has been deactivated. Please contact support.';
        } elseif (isset($user['email_verified']) && $user['email_verified'] == 0) {
            // Email not yet verified — show resend link
            $errors['general'] = 'Please verify your email first. <a href="resend-verification.php?email=' . urlencode($email) . '" style="color:#e76f51;text-decoration:underline;">Resend verification email</a>';
        } elseif ($user && password_verify($password, $user['password'])) {
            // === SUCCESSFUL LOGIN ===
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Populate session with user data
            $_SESSION['user_id']            = $user['id'];
            $_SESSION['user_name']          = $user['full_name'];
            $_SESSION['user_email']         = $user['email'];
            $_SESSION['user_role']          = $user['role'];
            $_SESSION['user_profile_photo'] = $user['profile_photo'] ?? '';

            // Clear failed login attempts on success
            clearLoginAttempts($email);

            // Remember Me — issue persistent token if requested
            if (!empty($_POST['remember'])) {
                setRememberToken($db, $user['id']);
            } else {
                // Explicit opt-out: clear any existing token
                $del = $db->prepare("DELETE FROM remember_tokens WHERE user_id = :uid");
                $del->execute([':uid' => $user['id']]);
                clearRememberCookie();
            }

            header('Location: ../index.php');
            exit;
        } else {
            // Wrong password — log the attempt
            $errors['general'] = 'Invalid email or password.';
            recordLoginAttempt($email, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GlobeTrek Adventures</title>
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

    <!-- === LOGIN FORM CARD === -->
    <main class="login-shell">
        <div class="login-card">
            <div class="login-lock">
                <span class="material-symbols-outlined">lock</span>
            </div>

            <div class="login-header">
                <h1>Welcome Back!</h1>
                <p>Login to continue your travel journey with GlobeTrek.</p>
            </div>

            <!-- Display general errors (CSRF, rate limit, auth failures) -->
            <?php if (isset($errors['general'])): ?>
                <div class="signup-message error" role="alert">
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>

            <!-- === LOGIN FORM === -->
            <form class="login-form" action="login.php" method="post">
                <?php csrf_field(); ?>

                <!-- Email field -->
                <div class="form-group<?php echo isset($errors['email']) ? ' has-error' : ''; ?>">
                    <label for="email">Email Address</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined input-icon">mail</span>
                        <input id="email" name="email" type="email" placeholder="Enter your email" autocomplete="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <p class="field-error"><?php echo $errors['email']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Password field with visibility toggle -->
                <div class="form-group<?php echo isset($errors['password']) ? ' has-error' : ''; ?>">
                    <label for="password">Password</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined input-icon">lock</span>
                        <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" data-target="password" aria-label="Toggle password visibility">
                            <span class="material-symbols-outlined icon-visible">visibility</span>
                            <span class="material-symbols-outlined icon-hidden">visibility_off</span>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <p class="field-error"><?php echo $errors['password']; ?></p>
                    <?php endif; ?>
                </div>

                <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>

                <!-- Remember Me checkbox (checked by default) -->
                <div class="remember-group">
                    <input id="remember" name="remember" type="checkbox" checked>
                    <label for="remember">Remember Me</label>
                </div>

                <button class="login-submit" type="submit">
                    <span class="material-symbols-outlined">arrow_forward</span>
                    Login
                </button>
            </form>

            <!-- === SOCIAL LOGIN DIVIDER === -->
            <div class="login-divider" aria-hidden="true">
                <span></span>
                <p>or continue with</p>
                <span></span>
            </div>

            <!-- Social login buttons (not yet functional) -->
            <div class="social-buttons">
                <button class="social-btn" type="button">
                    <svg class="social-icon" viewBox="0 0 24 24" width="20" height="20">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Google
                </button>
                <button class="social-btn" type="button">
                    <svg class="social-icon" viewBox="0 0 24 24" width="20" height="20" fill="#1877F2">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Facebook
                </button>
                <button class="social-btn" type="button">
                    <svg class="social-icon" viewBox="0 0 24 24" width="20" height="20" fill="#000000">
                        <path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                    </svg>
                    Apple
                </button>
            </div>

            <p class="signup-prompt">Don't have an account? <a href="signup.php">Sign Up</a></p>
        </div>
    </main>

    <!-- === FOOTER STATS BAR === -->
    <footer class="login-stats">
        <div class="login-stats-inner">
            <div class="login-stat">
                <span class="material-symbols-outlined">groups</span>
                <div class="login-stat-text">
                    <strong>10,000+</strong>
                    <span>Happy Travelers</span>
                </div>
            </div>
            <div class="login-stat">
                <span class="material-symbols-outlined">luggage</span>
                <div class="login-stat-text">
                    <strong>150+</strong>
                    <span>Tour Packages</span>
                </div>
            </div>
            <div class="login-stat">
                <span class="material-symbols-outlined">support_agent</span>
                <div class="login-stat-text">
                    <strong>24/7</strong>
                    <span>Customer Support</span>
                </div>
            </div>
            <div class="login-stat">
                <span class="material-symbols-outlined">verified</span>
                <div class="login-stat-text">
                    <strong>Best Price</strong>
                    <span>Guarantee</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="../js/script.js"></script>
</body>
</html>
