<?php
/**
 * User Logout
 *
 * Destroys the session, clears session variables, expires
 * the session cookie, and removes any persistent "Remember Me"
 * token before redirecting to the homepage.
 */
session_start();

// Delete remember-me tokens for this user (if any)
if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
}

session_unset();
session_destroy();

// Expire the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Expire the remember_me cookie
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
}

header('Location: ../index.php');
exit;
