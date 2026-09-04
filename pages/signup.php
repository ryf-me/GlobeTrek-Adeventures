<?php
/**
 * File: pages/signup.php
 * Purpose: User registration page - creates new user accounts with bcrypt
 *          password hashing, email verification, CSRF protection, and
 *          rate limiting (3 attempts per hour per IP).
 * Dependencies: config/session.php, config/database.php, config/csrf.php,
 *               config/rate-limiter.php, config/otp.php, includes/mailer.php
 * Used By: login.php (sign up link), navbar.php (register link)
 * Parent Files: index.php (redirects here from signup link)
 * Child Files: None (leaf page, but includes mailer.php dynamically)
 * @package GlobeTrek\Pages
 */

// === CONFIGURATION & DEPENDENCIES ===
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/rate-limiter.php';
require_once __DIR__ . '/../config/otp.php';
$db = getDB();

// === STATE INITIALIZATION ===
$errors = [];
$successMessage = '';
// Preserved form values for repopulation on validation failure
$values = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
];

// === HANDLE FORM SUBMISSION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation — reject forged cross-site requests
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    }

    // Rate limiting — max 3 signup attempts per hour per IP
    if (empty($errors) && !checkRateLimit('signup', 3, 3600, true)) {
        $errors['general'] = 'Too many signup attempts. Please try again later.';
    }

    if (empty($errors)) {
        // Sanitize and collect form inputs
        $values['full_name'] = trim($_POST['full_name'] ?? '');
        $values['email'] = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $values['phone'] = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $acceptedTerms = isset($_POST['terms']);

        // === FIELD VALIDATION ===

        // Full name: required, non-empty
        if ($values['full_name'] === '') {
            $errors['full_name'] = 'Please enter your full name.';
        }

        // Email: must be valid format
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        // Phone: optional, but if provided must match pattern
        if ($values['phone'] !== '' && !preg_match('/^[0-9+\s\-()]{7,20}$/', $values['phone'])) {
            $errors['phone'] = 'Please enter a valid phone number.';
        }

        // === PASSWORD STRENGTH VALIDATION ===
        if ($password === '') {
            $errors['password'] = 'Please enter a password.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one number.';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one special character.';
        }

        // Confirm password: must match
        if ($confirmPassword === '') {
            $errors['confirm_password'] = 'Please confirm your password.';
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords must match.';
        }

        // Terms acceptance: required
        if (!$acceptedTerms) {
            $errors['terms'] = 'Please accept the terms before continuing.';
        }

        // === DUPLICATE EMAIL CHECK ===
        if (empty($errors)) {
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $checkStmt->execute([':email' => $values['email']]);
            if ($checkStmt->fetch()) {
                $errors['email'] = 'An account with this email already exists.';
            }
        }

        // === CREATE USER ACCOUNT ===
        if (empty($errors)) {
            // Hash password with bcrypt (PASSWORD_DEFAULT = bcrypt)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user record
            $insertStmt = $db->prepare(
                "INSERT INTO users (full_name, email, phone, password) VALUES (:full_name, :email, :phone, :password)"
            );
            $insertStmt->execute([
                ':full_name' => $values['full_name'],
                ':email' => $values['email'],
                ':phone' => $values['phone'] !== '' ? $values['phone'] : null,
                ':password' => $hashedPassword,
            ]);

            $newUserId = $db->lastInsertId();

            // === EMAIL VERIFICATION TOKEN ===
            // Generate a secure token for email verification (valid 24 hours)
            $verifyToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 86400);
            $stmt = $db->prepare(
                "INSERT INTO email_verifications (user_id, token, expires_at) VALUES (:uid, :token, :expires)"
            );
            $stmt->execute([
                ':uid'     => $newUserId,
                ':token'   => $verifyToken,
                ':expires' => $expiresAt,
            ]);

            // === SEND VERIFICATION EMAIL ===
            $verifyLink = BASE_URL . '/pages/verify-email.php?token=' . $verifyToken;
            require_once __DIR__ . '/../includes/mailer.php';
            $emailContent = '
                <h2 style="margin:0 0 16px;color:#264653;">Welcome to GlobeTrek Adventures!</h2>
                <p>Thank you for creating an account. Please verify your email address by clicking the button below:</p>
                <div style="text-align:center;margin:24px 0;">
                    <a href="' . htmlspecialchars($verifyLink) . '" style="display:inline-block;background:#e76f51;color:#ffffff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:600;">Verify Email Address</a>
                </div>
                <p style="color:#666;font-size:13px;">Or copy this link: ' . htmlspecialchars($verifyLink) . '</p>
                <p style="color:#666;font-size:13px;">This link expires in <strong>24 hours</strong>.</p>
                <p style="color:#666;font-size:13px;">If you did not create this account, please ignore this email.</p>
            ';
            $htmlBody = wrapEmailTemplate($emailContent);
            $textBody = "Welcome to GlobeTrek Adventures!\n\nPlease verify your email by visiting:\n$verifyLink\n\nThis link expires in 24 hours.";
            sendMail($values['email'], 'Verify Your Email — GlobeTrek Adventures', $htmlBody, $textBody);

            // Show success and clear form
            $safeEmail = htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8');
            $successMessage = "Account created for $safeEmail. Please check your email to verify your account.";
            $values = array_fill_keys(array_keys($values), '');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - GlobeTrek Adventures</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/signup.css">
</head>
<body class="signup-page">

    <!-- === BACKGROUND VIDEO === -->
    <div class="signup-bg">
        <video autoplay muted loop playsinline>
            <source src="https://videos.pexels.com/video-files/32504501/13860754_1280_720_30fps.mp4" type="video/mp4">
        </video>
        <div class="signup-bg-overlay"></div>
    </div>

    <!-- === TOP NAVIGATION BAR === -->
    <header class="signup-topbar">
        <a class="signup-topbar-logo" href="../index.php">
            <img src="../images/logo.png" alt="GlobeTrek Adventures logo" />
            <div class="signup-topbar-text">
                <span class="brand-name">GlobeTrek</span>
                <span class="brand-tagline">Explore. Experience. Remember.</span>
            </div>
        </a>
    </header>

    <!-- === SIGNUP FORM CARD === -->
    <main class="signup-shell">
        <div class="signup-card">
            <div class="signup-avatar">
                <span class="material-symbols-outlined">person_add</span>
            </div>

            <div class="signup-header">
                <h1>Create Your Account</h1>
                <p>Join GlobeTrek and start exploring the best of Sri Lanka.</p>
            </div>

            <!-- Display success or error messages -->
            <?php if ($successMessage !== ''): ?>
                <div class="signup-message success" role="status">
                    <?php echo $successMessage; ?>
                </div>
            <?php elseif ($errors !== []): ?>
                <div class="signup-message error" role="alert">
                    Please fix the highlighted fields and try again.
                </div>
            <?php endif; ?>

            <!-- === REGISTRATION FORM === -->
            <form class="signup-form" action="signup.php" method="post" novalidate>
                <?php csrf_field(); ?>

                <!-- Full Name & Email row -->
                <div class="form-row">
                    <div class="form-group<?php echo isset($errors['full_name']) ? ' has-error' : ''; ?>">
                        <label for="full-name">Full Name</label>
                        <div class="input-icon-wrapper">
                            <span class="material-symbols-outlined input-icon">person</span>
                            <input id="full-name" name="full_name" type="text" placeholder="Enter your name" autocomplete="name" value="<?php echo htmlspecialchars($values['full_name'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo isset($errors['full_name']) ? 'true' : 'false'; ?>" required>
                        </div>
                        <?php if (isset($errors['full_name'])): ?>
                            <p class="field-error"><?php echo $errors['full_name']; ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group<?php echo isset($errors['email']) ? ' has-error' : ''; ?>">
                        <label for="signup-email">Email Address</label>
                        <div class="input-icon-wrapper">
                            <span class="material-symbols-outlined input-icon">mail</span>
                            <input id="signup-email" name="email" type="email" placeholder="Enter your email" autocomplete="email" value="<?php echo htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo isset($errors['email']) ? 'true' : 'false'; ?>" required>
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <p class="field-error"><?php echo $errors['email']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Phone (optional) -->
                <div class="form-group<?php echo isset($errors['phone']) ? ' has-error' : ''; ?>">
                    <label for="signup-phone">Phone Number</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined input-icon">call</span>
                        <input id="signup-phone" name="phone" type="tel" placeholder="Enter your phone number" autocomplete="tel" value="<?php echo htmlspecialchars($values['phone'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo isset($errors['phone']) ? 'true' : 'false'; ?>">
                    </div>
                    <?php if (isset($errors['phone'])): ?>
                        <p class="field-error"><?php echo $errors['phone']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Password with strength meter -->
                <div class="form-group<?php echo isset($errors['password']) ? ' has-error' : ''; ?>">
                    <label for="signup-password">Password</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined input-icon">lock</span>
                        <input id="signup-password" name="password" type="password" placeholder="Create a password" autocomplete="new-password" aria-invalid="<?php echo isset($errors['password']) ? 'true' : 'false'; ?>" required>
                        <button type="button" class="password-toggle" data-target="signup-password" aria-label="Toggle password visibility">
                            <span class="material-symbols-outlined icon-visible">visibility</span>
                            <span class="material-symbols-outlined icon-hidden">visibility_off</span>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <p class="field-error"><?php echo $errors['password']; ?></p>
                    <?php endif; ?>
                    <div class="password-strength-meter"></div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group<?php echo isset($errors['confirm_password']) ? ' has-error' : ''; ?>">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined input-icon">lock</span>
                        <input id="confirm-password" name="confirm_password" type="password" placeholder="Confirm your password" autocomplete="new-password" aria-invalid="<?php echo isset($errors['confirm_password']) ? 'true' : 'false'; ?>" required>
                        <button type="button" class="password-toggle" data-target="confirm-password" aria-label="Toggle password visibility">
                            <span class="material-symbols-outlined icon-visible">visibility</span>
                            <span class="material-symbols-outlined icon-hidden">visibility_off</span>
                        </button>
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <p class="field-error"><?php echo $errors['confirm_password']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Terms & Conditions checkbox -->
                <div class="terms-group<?php echo isset($errors['terms']) ? ' has-error' : ''; ?>">
                    <input id="terms" name="terms" type="checkbox" value="1" aria-invalid="<?php echo isset($errors['terms']) ? 'true' : 'false'; ?>" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                    <label for="terms">
                        I agree to the <a href="terms.php">Terms &amp; Conditions</a> and <a href="privacy.php">Privacy Policy</a>.
                    </label>
                </div>
                <?php if (isset($errors['terms'])): ?>
                    <p class="field-error terms-error"><?php echo $errors['terms']; ?></p>
                <?php endif; ?>

                <button class="signup-submit" type="submit">
                    <span class="material-symbols-outlined">person_add</span>
                    Sign Up
                </button>
            </form>

            <p class="signup-prompt">Already have an account? <a href="login.php">Login</a></p>
        </div>
    </main>

    <!-- === FOOTER STATS BAR === -->
    <footer class="signup-stats">
        <div class="signup-stats-inner">
            <div class="signup-stat">
                <span class="material-symbols-outlined">verified_user</span>
                <div class="signup-stat-text">
                    <strong>Secure Booking</strong>
                    <span>Your data is protected</span>
                </div>
            </div>
            <div class="signup-stat">
                <span class="material-symbols-outlined">savings</span>
                <div class="signup-stat-text">
                    <strong>Best Price Guarantee</strong>
                    <span>Get the best deals</span>
                </div>
            </div>
            <div class="signup-stat">
                <span class="material-symbols-outlined">support_agent</span>
                <div class="signup-stat-text">
                    <strong>Expert Support</strong>
                    <span>We're here to help</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="../js/script.js"></script>
</body>
</html>
