<?php
/**
 * Email Verification Page
 *
 * Verifies a user's email address using a token link sent after registration.
 * The token is validated against the email_verifications table and must not be expired.
 */

session_start();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$message = '';
$success = false;

$token = $_GET['token'] ?? '';

if ($token === '') {
    $message = 'No verification token provided.';
} else {
    // Look up the token
    $stmt = $db->prepare(
        "SELECT ev.id, ev.user_id, ev.expires_at
         FROM email_verifications ev
         WHERE ev.token = :token
         LIMIT 1"
    );
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch();

    if (!$row) {
        $message = 'Invalid or already used verification link.';
    } elseif (strtotime($row['expires_at']) < time()) {
        $message = 'This verification link has expired. Please register again or request a new link.';
    } else {
        // Mark email as verified
        $upd = $db->prepare("UPDATE users SET email_verified = 1 WHERE id = :uid");
        $upd->execute([':uid' => $row['user_id']]);

        // Delete the used token
        $del = $db->prepare("DELETE FROM email_verifications WHERE id = :id");
        $del->execute([':id' => $row['id']]);

        $success = true;
        $message = 'Your email has been verified successfully! You can now log in.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - GlobeTrek Adventures</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body class="login-page">
    <div class="login-bg">
        <img src="../images/login-bg.jpg" alt="" aria-hidden="true">
        <div class="login-bg-overlay"></div>
    </div>

    <header class="login-topbar">
        <a class="login-topbar-logo" href="../index.php">
            <img src="../images/logo.png" alt="GlobeTrek Adventures logo" />
            <div class="login-topbar-text">
                <span class="brand-name">GlobeTrek</span>
                <span class="brand-tagline">Explore. Experience. Remember.</span>
            </div>
        </a>
    </header>

    <main class="login-shell">
        <div class="login-card">
            <div class="login-lock">
                <span class="material-symbols-outlined"><?php echo $success ? 'mark_email_read' : 'error'; ?></span>
            </div>

            <div class="login-header">
                <h1><?php echo $success ? 'Email Verified!' : 'Verification Issue'; ?></h1>
            </div>

            <div class="signup-message <?php echo $success ? 'success' : 'error'; ?>" role="<?php echo $success ? 'status' : 'alert'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

            <div style="text-align:center; margin-top:1.5rem;">
                <a href="login.php" class="login-submit" style="display:inline-flex; text-decoration:none;">
                    <span class="material-symbols-outlined">login</span>
                    Go to Login
                </a>
            </div>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>
