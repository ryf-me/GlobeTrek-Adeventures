<?php
$errors = [];
$successMessage = '';
$values = [
    'full_name' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['full_name'] = trim($_POST['full_name'] ?? '');
    $values['email'] = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $acceptedTerms = isset($_POST['terms']);

    if ($values['full_name'] === '') {
        $errors['full_name'] = 'Please enter your full name.';
    }

    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors['password'] = 'Please enter a password.';
    }

    if ($confirmPassword === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords must match.';
    }

    if (!$acceptedTerms) {
        $errors['terms'] = 'Please accept the terms before continuing.';
    }

    if ($errors === []) {
        $safeEmail = htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8');
        $successMessage = "Account demo created for $safeEmail.";
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
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/signup.css">
</head>
<body class="signup-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main class="login-shell">
        <div class="login-card">
            <div class="login-header">
                <h1>Create Account</h1>
                <p>Join the adventure with GlobeTrek.</p>
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

            <form class="login-form" action="signup.php" method="post" novalidate>
                <div class="form-group<?php echo isset($errors['full_name']) ? ' has-error' : ''; ?>">
                    <label for="full-name">Full Name</label>
                    <input
                        id="full-name"
                        name="full_name"
                        type="text"
                        placeholder="Enter your full name"
                        autocomplete="name"
                        value="<?php echo htmlspecialchars($values['full_name'], ENT_QUOTES, 'UTF-8'); ?>"
                        aria-invalid="<?php echo isset($errors['full_name']) ? 'true' : 'false'; ?>"
                        <?php echo isset($errors['full_name']) ? 'aria-describedby="full-name-error"' : ''; ?>
                        required
                    >
                    <?php if (isset($errors['full_name'])): ?>
                        <p class="field-error" id="full-name-error"><?php echo $errors['full_name']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group<?php echo isset($errors['email']) ? ' has-error' : ''; ?>">
                    <label for="signup-email">Email Address</label>
                    <input
                        id="signup-email"
                        name="email"
                        type="email"
                        placeholder="Enter your email"
                        autocomplete="email"
                        value="<?php echo htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8'); ?>"
                        aria-invalid="<?php echo isset($errors['email']) ? 'true' : 'false'; ?>"
                        <?php echo isset($errors['email']) ? 'aria-describedby="signup-email-error"' : ''; ?>
                        required
                    >
                    <?php if (isset($errors['email'])): ?>
                        <p class="field-error" id="signup-email-error"><?php echo $errors['email']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group<?php echo isset($errors['password']) ? ' has-error' : ''; ?>">
                    <label for="signup-password">Password</label>
                    <div class="password-wrapper">
                        <input
                            id="signup-password"
                            name="password"
                            type="password"
                            placeholder="Create a password"
                            autocomplete="new-password"
                            aria-invalid="<?php echo isset($errors['password']) ? 'true' : 'false'; ?>"
                            <?php echo isset($errors['password']) ? 'aria-describedby="signup-password-error"' : ''; ?>
                            required
                        >
                        <button type="button" class="password-toggle" data-target="signup-password" aria-label="Toggle password visibility">
                            <span class="material-symbols-outlined icon-visible">visibility</span>
                            <span class="material-symbols-outlined icon-hidden">visibility_off</span>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <p class="field-error" id="signup-password-error"><?php echo $errors['password']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group<?php echo isset($errors['confirm_password']) ? ' has-error' : ''; ?>">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input
                            id="confirm-password"
                            name="confirm_password"
                            type="password"
                            placeholder="Repeat your password"
                            autocomplete="new-password"
                            aria-invalid="<?php echo isset($errors['confirm_password']) ? 'true' : 'false'; ?>"
                            <?php echo isset($errors['confirm_password']) ? 'aria-describedby="confirm-password-error"' : ''; ?>
                            required
                        >
                        <button type="button" class="password-toggle" data-target="confirm-password" aria-label="Toggle password visibility">
                            <span class="material-symbols-outlined icon-visible">visibility</span>
                            <span class="material-symbols-outlined icon-hidden">visibility_off</span>
                        </button>
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <p class="field-error" id="confirm-password-error"><?php echo $errors['confirm_password']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="terms-group<?php echo isset($errors['terms']) ? ' has-error' : ''; ?>">
                    <input
                        id="terms"
                        name="terms"
                        type="checkbox"
                        value="1"
                        aria-invalid="<?php echo isset($errors['terms']) ? 'true' : 'false'; ?>"
                        <?php echo isset($errors['terms']) ? 'aria-describedby="terms-error"' : ''; ?>
                        <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>
                    >
                    <label for="terms">
                        I agree to the <a href="#">Terms and Conditions</a> and <a href="#">Privacy Policy</a>.
                    </label>
                </div>
                <?php if (isset($errors['terms'])): ?>
                    <p class="field-error terms-error" id="terms-error"><?php echo $errors['terms']; ?></p>
                <?php endif; ?>

                <button class="login-submit" type="submit">Sign Up</button>
            </form>

            <div class="login-divider" aria-hidden="true">
                <span></span>
                <p>Or continue with</p>
                <span></span>
            </div>

            <button class="google-btn" type="button">
                <span class="google-mark" aria-hidden="true">G</span>
                <span>Google</span>
            </button>

            <p class="signup-prompt">Already have an account? <a href="login.php">Login</a></p>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>