<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors['password'] = 'Please enter your password.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ../index.php');
            exit;
        } else {
            $errors['general'] = 'Invalid email or password.';
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
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body class="login-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main class="login-shell">
        <div class="login-card">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Please enter your details to sign in.</p>
            </div>

            <?php if (isset($errors['general'])): ?>
                <div class="signup-message error" role="alert">
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>

            <form class="login-form" action="login.php" method="post">
                <div class="form-group<?php echo isset($errors['email']) ? ' has-error' : ''; ?>">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" placeholder="Enter your email" autocomplete="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <p class="field-error"><?php echo $errors['email']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group<?php echo isset($errors['password']) ? ' has-error' : ''; ?>">
                    <div class="password-row">
                        <label for="password">Password</label>
                        <a href="#">Forgot Password?</a>
                    </div>
                    <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                    <?php if (isset($errors['password'])): ?>
                        <p class="field-error"><?php echo $errors['password']; ?></p>
                    <?php endif; ?>
                </div>

                <button class="login-submit" type="submit">Login</button>
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

            <p class="signup-prompt">Don't have an account? <a href="signup.php">Sign Up</a></p>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>
