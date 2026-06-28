<?php
/**
 * File: pages/logout.php
 * Purpose: Destroys the user session, clears session variables, expires
 *          the session cookie, removes any persistent "Remember Me" tokens,
 *          and redirects to the homepage.
 * Dependencies: config/database.php (conditionally loaded for token deletion)
 * Used By: navbar.php (logout link), all pages that check session
 * Parent Files: All pages with logout link in navbar
 * Child Files: None
 * @package GlobeTrek\Pages
 */

// === SESSION INITIALIZATION ===
session_start();

// === DELETE REMEMBER-ME TOKENS ===
// Remove all persistent login tokens for this user to prevent future auto-login
if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
}

// === DESTROY SESSION ===
// Remove all session variables and destroy the session
session_unset();
session_destroy();

// === EXPIRE SESSION COOKIE ===
// Force-expire the PHPSESSID cookie so the browser discards it
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// === EXPIRE REMEMBER-ME COOKIE ===
// Clear the persistent login cookie if it exists
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
}

// === REDIRECT TO HOMEPAGE ===
header('Location: ../index.php');
exit;
