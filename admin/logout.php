<?php
/**
 * File: admin/logout.php
 * Purpose: Destroys the admin/staff session, clears remember-me tokens and cookies, redirects to login.
 * Dependencies: config/database.php
 * Used By: admin/includes/header.php, admin/includes/sidebar.php (logout links)
 * Parent Files: none (standalone endpoint)
 * Child Files: config/database.php
 * @package GlobeTrek\Admin
 */

// === SESSION START ===
// Ensure a session is active before accessing session data.
if (session_status() === PHP_SESSION_NONE) session_start();

// === CLEAR REMEMBER-ME TOKEN FROM DATABASE ===
// Removes the persistent login token so the user cannot stay logged in on other devices.
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
}

// === CLEAR REMEMBER-ME COOKIE ===
// Expires the cookie by setting it to a past timestamp. Uses secure flags matching session config.
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
}

// === DESTROY SESSION ===
// Clear the session superglobal and destroy it server-side.
$_SESSION = [];
session_destroy();

// === CLEAR SESSION COOKIE ===
// Removes the session cookie from the browser if cookies are used for sessions.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// === REDIRECT TO LOGIN ===
// After full logout, redirect to the public login page.
header('Location: ../pages/login.php');
exit;
