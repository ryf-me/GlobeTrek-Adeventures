<?php
/**
 * User Logout
 *
 * Destroys the session, clears session variables, and expires
 * the session cookie before redirecting to the homepage.
 */
session_start();
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

header('Location: ../index.php');
exit;
