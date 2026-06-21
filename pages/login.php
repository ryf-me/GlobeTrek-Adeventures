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

    <main class="login-shell" aria-labelledby="login-title">
        <section class="login-card">
            <div class="login-panel" aria-hidden="true">
                <div class="login-panel-overlay">
                    <img src="../images/logo.png" alt="">
                    <p>Globe Trek</p>
                </div>
                <div class="login-trip-note">
                    <span>Next departure</span>
                    <strong>Ella sunrise trail</strong>
                </div>
            </div>

            <section class="login-form-panel" aria-label="Account login">
                <a class="login-brand" href="../index.php#home" aria-label="Go to GlobeTrek home">
                    <img src="../images/logo.png" alt="">
                    <span>GlobeTrek</span>
                </a>

                <div class="login-copy">
                    <p class="login-eyebrow">Traveler access</p>
                    <h1 id="login-title">Welcome back</h1>
                    <p>Sign in to review saved routes, bookings, and upcoming travel details.</p>
                </div>

                <form class="login-form" action="#" method="post">
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email" placeholder="you@example.com" autocomplete="email" required>
                    </div>

                    <div class="form-group">
                        <div class="password-row">
                            <label for="password">Password</label>
                            <a href="#">Forgot password?</a>
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

                <button class="google-login" type="button">
                    <span class="google-mark" aria-hidden="true">G</span>
                    <span>Google</span>
                </button>

                <p class="signup-prompt">Do not have an account? <a href="signup.php">Sign Up</a></p>
            </section>
        </section>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>
