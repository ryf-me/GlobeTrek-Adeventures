<?php
/**
 * File: config/csrf.php
 * Purpose: CSRF (Cross-Site Request Forgery) protection system
 *
 * This file provides:
 *   1. Token generation using cryptographically secure random_bytes()
 *   2. Timing-safe token validation using hash_equals()
 *   3. HTML hidden field output for forms
 *   4. Token retrieval for JavaScript/AJAX use
 *
 * Dependencies: None (standalone config file)
 *
 * Used By:
 *   - All form-handling pages (login, signup, booking, payment, etc.)
 *   - includes/navbar.php (injects token for AJAX)
 *   - All admin pages (via includes/header.php)
 *
 * Parent Files: None (loaded via require/require_once)
 * Child Files: None (no includes)
 *
 * Security Notes:
 *   - Tokens are 256 bits of entropy (32 bytes = 64 hex characters)
 *   - hash_equals() prevents timing attacks
 *   - htmlspecialchars() prevents XSS injection in HTML attributes
 *   - One token per session (not regenerated on each request)
 *
 * @package GlobeTrek\Config
 */

// Ensure a session is active before accessing session data
if (session_status() === PHP_SESSION_NONE) session_start();

// =============================================================================
// CSRF TOKEN GENERATION
// =============================================================================
/**
 * Generate a CSRF token and store it in the session.
 *
 * Uses lazy generation — only creates a new token if one doesn't already exist
 * in the current session. This means the same token is used for all forms on
 * a single page (which is acceptable for most use cases).
 *
 * Token format: 64-character hexadecimal string (256 bits of entropy)
 * Generated using: random_bytes(32) → bin2hex()
 *
 * @return string The CSRF token (64-char hex string)
 *
 * Usage:
 *   $token = generateCSRFToken();
 *   // Use $token in a hidden form field or meta tag
 */
function generateCSRFToken(): string {
    // Only generate a new token if one doesn't exist in the session
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes(32) generates 32 cryptographically secure random bytes
        // bin2hex() converts to a 64-character hexadecimal string
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// =============================================================================
// CSRF TOKEN VALIDATION
// =============================================================================
/**
 * Validate a submitted CSRF token against the session token.
 *
 * Uses hash_equals() for timing-safe comparison to prevent timing attacks.
 * An attacker cannot determine the correct token by measuring response times.
 *
 * @param string|null $token The token submitted by the client (from form or AJAX)
 * @return bool true if the token is valid, false otherwise
 *
 * Usage:
 *   if (!validateCSRFToken($_POST['csrf_token'])) {
 *       die('Invalid CSRF token');
 *   }
 */
function validateCSRFToken(?string $token): bool {
    // Reject null or empty tokens immediately
    if ($token === null || $token === '') return false;

    // Use hash_equals() for timing-safe comparison
    // This prevents attackers from guessing the token by measuring response times
    // The null coalescing operator handles the case where no token exists in the session
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// =============================================================================
// CSRF HIDDEN FIELD OUTPUT
// =============================================================================
/**
 * Output a hidden input field containing the CSRF token.
 *
 * Call this inside <form> tags to include the CSRF token in form submissions.
 * The token is HTML-escaped to prevent XSS injection in the attribute.
 *
 * @return void (outputs HTML directly)
 *
 * Usage:
 *   <form method="post" action="handler.php">
 *       <?php csrf_field(); ?>
 *       <!-- other form fields -->
 *       <button type="submit">Submit</button>
 *   </form>
 */
function csrf_field(): void {
    $token = generateCSRFToken();
    // htmlspecialchars() with ENT_QUOTES and UTF-8 prevents XSS injection
    // The token is already safe (hex string), but this is defense-in-depth
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

// =============================================================================
// CSRF TOKEN RETRIEVAL
// =============================================================================
/**
 * Get the current CSRF token value (for meta tags or JavaScript).
 *
 * This is used when CSRF tokens need to be included in:
 *   - <meta> tags for JavaScript to read
 *   - AJAX request headers
 *   - JavaScript variables
 *
 * @return string The current CSRF token
 *
 * Usage:
 *   // In a <meta> tag:
 *   <meta name="csrf-token" content="<?= getCSRFToken() ?>">
 *
 *   // In JavaScript:
 *   fetch('/api/endpoint', {
 *       headers: { 'X-CSRF-Token': '<?= getCSRFToken() ?>' }
 *   });
 */
function getCSRFToken(): string {
    return generateCSRFToken();
}
