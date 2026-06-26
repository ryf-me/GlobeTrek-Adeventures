<?php
/**
 * Session Configuration & Security
 *
 * - Configures secure session cookie parameters
 * - Enforces 30-minute idle timeout via last_activity tracking
 * - Call this on every page that needs session access
 */

if (session_status() === PHP_SESSION_NONE) {
    // Cookie security parameters
    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes

    session_start();
}

// Idle timeout: 30 minutes
$IDLE_TIMEOUT = 1800;

if (isset($_SESSION['user_id'])) {
    $now = time();

    // Check if session has expired
    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > $IDLE_TIMEOUT) {
        // Session expired — destroy it
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../pages/login.php' : 'login.php') . '?timeout=1');
        exit;
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = $now;
}
