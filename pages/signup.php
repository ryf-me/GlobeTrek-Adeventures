<?php
/**
 * User Registration Page
 *
 * Creates new user accounts with bcrypt password hashing.
 * Includes CSRF protection and rate limiting (3 attempts per hour per IP).
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/rate-limiter.php';
$db = getDB();

$errors = [];
$successMessage = '';
$values = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
];

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    }

    // Rate limiting — max 3 signup attempts per hour per IP
    if (empty($errors) && !checkRateLimit('signup', 3, 3600, true)) {
        $errors['general'] = 'Too many signup attempts. Please try again later.';
    }

    if (empty($errors)) {
        $values['full_name'] = trim($_POST['full_name'] ?? '');
        $values['email'] = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $values['phone'] = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $acceptedTerms = isset($_POST['terms']);

        if ($values['full_name'] === '') {
            $errors['full_name'] = 'Please enter your full name.';
        }
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if ($values['phone'] !== '' && !preg_match('/^[0-9+\s\-()]{7,20}$/', $values['phone'])) {
            $errors['phone'] = 'Please enter a valid phone number.';
        }
        if ($password === '') {
            $errors['password'] = 'Please enter a password.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }
        if ($confirmPassword === '') {
            $errors['confirm_password'] = 'Please confirm your password.';
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords must match.';
        }
        if (!$acceptedTerms) {
            $errors['terms'] = 'Please accept the terms before continuing.';
        }

        if (empty($errors)) {
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $checkStmt->execute([':email' => $values['email']]);
            if ($checkStmt->fetch()) {
                $errors['email'] = 'An account with this email already exists.';
            }
        }

        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $db->prepare(
                "INSERT INTO users (full_name, email, phone, password) VALUES (:full_name, :email, :phone, :password)"
            );
            $insertStmt->execute([
                ':full_name' => $values['full_name'],
                ':email' => $values['email'],
                ':phone' => $values['phone'] !== '' ? $values['phone'] : null,
                ':password' => $hashedPassword,
            ]);

            $safeEmail = htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8');
            $successMessage = "Account created for $safeEmail. You can now log in.";
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
    <div class="signup-bg">
        <img src="../images/login-bg.jpg" alt="" aria-hidden="true">
        <div class="signup-bg-overlay"></div>
    </div>

    <header class="signup-topbar">
        <a class="signup-topbar-logo" href="../index.php">
            <img src="../images/logo.png" alt="GlobeTrek Adventures logo" />
            <div class="signup-topbar-text">
                <span class="brand-name">GlobeTrek</span>
                <span class="brand-tagline">Explore. Experience. Remember.</span>
            </div>
        </a>
    </header>

    <main class="signup-shell">
        <div class="signup-card">
            <div class="signup-avatar">
                <span class="material-symbols-outlined">person_add</span>
            </div>

            <div class="signup-header">
                <h1>Create Your Account</h1>
                <p>Join GlobeTrek and start exploring the best of Sri Lanka.</p>
            </div>

            <?php if ($successMessage !== ''): ?>
                <div class="signup-message success" role="status">
                    <?php echo $successMessage; ?>
                </div>
            <?php elseif ($errors !== []): ?>
                <div class="signup-message error" role="alert">
                    Please fix the highlighted fields and try again.
                </div>
            <?php endif; ?>

            <form class="signup-form" action="signup.php" method="post" novalidate>
                <?php csrf_field(); ?>

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
                </div>

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

            <div class="login-divider" aria-hidden="true">
                <span></span>
                <p>or sign up with</p>
                <span></span>
            </div>

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
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.537-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
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

            <p class="signup-prompt">Already have an account? <a href="login.php">Login</a></p>
        </div>
    </main>

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
