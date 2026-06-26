<?php
/**
 * Admin/Staff Logout
 *
 * Destroys session, clears remember-me tokens and cookies.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Clear remember-me token from database
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
}

// Clear remember-me cookie
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
}

// Destroy session
$_SESSION = [];
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header('Location: ../pages/login.php');
exit;
