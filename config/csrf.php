<?php
/**
 * CSRF (Cross-Site Request Forgery) Protection
 *
 * Generates and validates anti-forgery tokens to prevent
 * unauthorized commands from being transmitted from trusted users.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Generate a CSRF token and store it in the session.
 * Returns the token string for use in forms or AJAX.
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a submitted CSRF token against the session token.
 * Returns true if valid, false otherwise.
 */
function validateCSRFToken(?string $token): bool {
    if ($token === null || $token === '') return false;
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Output a hidden input field containing the CSRF token.
 * Call this inside <form> tags.
 */
function csrf_field(): void {
    $token = generateCSRFToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Get the current CSRF token value (for meta tags or JS).
 */
function getCSRFToken(): string {
    return generateCSRFToken();
}
