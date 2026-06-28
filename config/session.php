<?php
/**
 * File: config/session.php
 * Purpose: Secure session configuration and idle timeout enforcement
 *
 * This file provides:
 *   1. Secure session cookie parameters (HttpOnly, SameSite, Strict Mode)
 *   2. 30-minute idle timeout via last_activity tracking
 *   3. Smart redirect for both public and admin pages on timeout
 *
 * Dependencies: None (standalone config file)
 *
 * Used By:
 *   - pages/login.php (starts session on login)
 *   - pages/signup.php (starts session on registration)
 *   - All files that need session access
 *
 * Parent Files: None (loaded via require/require_once)
 * Child Files: None (no includes)
 *
 * Security Notes:
 *   - HttpOnly cookies prevent JavaScript access (XSS protection)
 *   - SameSite=Lax provides CSRF mitigation
 *   - Strict Mode rejects uninitialized session IDs (prevents session fixation)
 *   - Session timeout prevents abandoned sessions from being exploited
 *
 * @package GlobeTrek\Config
 */

// =============================================================================
// SESSION INITIALIZATION
// =============================================================================
// Only start a session if one is not already active.
// This prevents "session already started" warnings on multiple includes.

if (session_status() === PHP_SESSION_NONE) {
    // Detect if the connection is HTTPS (for secure cookie flag)
    // Note: $isSecure is computed but not currently used in cookie settings
    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    // Configure session security settings via ini_set()
    // These must be set BEFORE session_start()

    // Prevent JavaScript from accessing the session cookie (XSS protection)
    ini_set('session.cookie_httponly', 1);

    // Send cookie only over HTTPS connections when available
    ini_set('session.cookie_secure', $isSecure ? 1 : 0);

    // Prevent session ID from being passed via URL query string
    ini_set('session.use_only_cookies', 1);

    // Mitigate CSRF attacks by only sending the cookie with same-site requests
    // 'Lax' allows the cookie on top-level navigations (e.g., clicking a link)
    ini_set('session.cookie_samesite', 'Lax');

    // Reject uninitialized session IDs to prevent session fixation attacks
    // If a client sends an invalid session ID, a new one is generated
    ini_set('session.use_strict_mode', 1);

    // Set garbage collection lifetime to 30 minutes (1800 seconds)
    // PHP will clean up expired sessions after this period
    ini_set('session.gc_maxlifetime', 1800);

    // Start the session — this either resumes an existing session or creates a new one
    session_start();
}

// =============================================================================
// IDLE TIMEOUT ENFORCEMENT
// =============================================================================
// If a logged-in user is inactive for more than 30 minutes, their session is
// automatically destroyed and they are redirected to the login page.
// This prevents abandoned sessions from being exploited by others.

$IDLE_TIMEOUT = 1800; // 30 minutes in seconds

// Only enforce timeout for logged-in users (those with a user_id in the session)
if (isset($_SESSION['user_id'])) {
    $now = time();

    // Check if the session has expired due to inactivity
    // Compares the current time against the last recorded activity timestamp
    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > $IDLE_TIMEOUT) {
        // === SESSION EXPIRED — DESTROY IT ===

        // Step 1: Clear all session data
        $_SESSION = [];

        // Step 2: Delete the session cookie from the browser
        // Uses time() - 42000 to set the cookie expiry far in the past
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Step 3: Destroy the session on the server
        session_destroy();

        // Step 4: Redirect to the appropriate login page
        // Detects if the user was in the admin area (/admin/) and adjusts the path
        // Admin pages use '../pages/login.php', public pages use 'login.php'
        header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../pages/login.php' : 'login.php') . '?timeout=1');
        exit;
    }

    // Update the last activity timestamp to the current time
    // This is checked on the next page load to determine if the session has timed out
    $_SESSION['last_activity'] = $now;
}
