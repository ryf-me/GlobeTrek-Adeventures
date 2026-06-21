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

            <form class="login-form" action="#" method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" placeholder="Enter your email" autocomplete="email" required>
                </div>

                <div class="form-group">
                    <div class="password-row">
                        <label for="password">Password</label>
                        <a href="#">Forgot Password?</a>
                    </div>
                    <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
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
