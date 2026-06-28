<?php
/**
 * File: config/error-handler.php
 * Purpose: Global error, exception, and shutdown handler for user-friendly error pages
 *
 * This file provides:
 *   1. Error-to-exception conversion (set_error_handler)
 *   2. Uncaught exception handling (set_exception_handler)
 *   3. Fatal error handling at shutdown (register_shutdown_function)
 *   4. Two-tier error logging (database preferred, temp file fallback)
 *   5. Styled error page display (404, 403, 500)
 *
 * Dependencies:
 *   - config/database.php (for error logging — accessed via function_exists guard)
 *   - config/session.php (for $_SESSION access in error logging)
 *
 * Used By:
 *   - All PHP files (loaded early in the request lifecycle)
 *   - config/error-handler.php is typically the first config file loaded
 *
 * Parent Files: None (loaded via require_once at the start of each page)
 * Child Files: pages/404.php, pages/500.php (included for error display)
 *
 * Error Types Handled:
 *   - set_error_handler: Converts PHP warnings/notices/errors into ErrorException
 *   - set_exception_handler: Catches uncaught exceptions
 *   - register_shutdown_function: Catches fatal errors (E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR)
 *
 * Note: The @ error suppression operator will still throw exceptions with this handler.
 * This is by design — it ensures all errors are properly handled.
 *
 * @package GlobeTrek\Config
 */

// Ensure a session is active for logging user context
if (session_status() === PHP_SESSION_NONE) session_start();

// =============================================================================
// ERROR-TO-EXCEPTION CONVERSION
// =============================================================================
// Converts PHP warnings, notices, and errors into ErrorException objects.
// This unifies error handling — all errors can be caught by the exception handler.
// Note: This means @ error suppression will still throw exceptions.
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// =============================================================================
// UNCAUGHT EXCEPTION HANDLER
// =============================================================================
// Catches any exception that is not caught by a try/catch block.
// Logs the error and displays a friendly 500 error page.
set_exception_handler(function (Throwable $e) {
    logError($e->getMessage(), $e->getFile(), $e->getLine());
    show_error_page(500);
});

// =============================================================================
// SHUTDOWN FUNCTION (FATAL ERROR HANDLER)
// =============================================================================
// Registered to run when the script terminates.
// Checks error_get_last() for fatal errors that cannot be caught by try/catch.
// These 4 error types are the only ones that cannot be caught by set_error_handler:
//   - E_ERROR: Fatal runtime errors
//   - E_PARSE: Compile-time parse errors
//   - E_CORE_ERROR: Fatal errors during PHP initialization
//   - E_COMPILE_ERROR: Fatal compile-time errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logError($error['message'], $error['file'], $error['line']);
        show_error_page(500);
    }
});

// =============================================================================
// ERROR LOGGING FUNCTION
// =============================================================================
/**
 * Log error details to the activity_logs table or a temp file.
 *
 * Uses a two-tier logging approach:
 *   1. Database logging (preferred) — inserts into activity_logs table
 *   2. File logging (fallback) — appends to /tmp/globetrek_errors.log
 *
 * The function_exists('getDB') guard prevents a fatal error if database.php
 * hasn't been included yet (e.g., if the error occurs during early bootstrapping).
 *
 * @param string $message The error message
 * @param string $file    The file where the error occurred
 * @param int    $line    The line number where the error occurred
 * @return void
 *
 * Usage:
 *   logError('Division by zero', '/var/www/app/math.php', 42);
 */
function logError(string $message, string $file, int $line): void {
    // Format the error detail string
    $detail = "Error: $message in $file on line $line";

    // === TIER 1: DATABASE LOGGING (preferred) ===
    try {
        // Guard: Only attempt DB logging if getDB() is available
        // This prevents a fatal error if database.php hasn't been loaded yet
        if (function_exists('getDB')) {
            $db = getDB();
            $userId = $_SESSION['user_id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            // Insert the error as a system_error action in the activity_logs table
            $stmt = $db->prepare(
                "INSERT INTO activity_logs (user_id, action, entity_type, details, ip_address)
                 VALUES (:uid, 'system_error', 'error', :details, :ip)"
            );
            $stmt->execute([':uid' => $userId, ':details' => $detail, ':ip' => $ip]);
            return; // Success — no need to fall through to file logging
        }
    } catch (Exception $e) {
        // Database unavailable — fall through to file logging
    }

    // === TIER 2: FILE LOGGING (fallback) ===
    // Write to a temp file when the database is unavailable
    $logFile = sys_get_temp_dir() . '/globetrek_errors.log';
    $entry = date('[Y-m-d H:i:s]') . " " . $detail . PHP_EOL;

    // FILE_APPEND: Add to the end of the file (don't overwrite)
    // LOCK_EX: Acquire an exclusive lock to prevent race conditions in concurrent writes
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

// =============================================================================
// ERROR PAGE DISPLAY FUNCTION
// =============================================================================
/**
 * Display a styled error page matching the GlobeTrek design.
 *
 * Uses PHP 8.0+ match expressions to map HTTP status codes to titles and messages.
 * Sets the HTTP response code via http_response_code() for proper browser behavior.
 * Includes the corresponding error page template from pages/{code}.php.
 *
 * @param int $code HTTP status code (404, 403, or 500)
 * @return void (outputs HTML and exits)
 *
 * Usage:
 *   show_error_page(404); // Displays the 404 error page
 *   show_error_page(500); // Displays the 500 error page
 */
function show_error_page(int $code): void {
    // Map HTTP codes to page titles using PHP 8.0+ match expressions
    $title = match ($code) {
        404 => 'Page Not Found',
        403 => 'Access Denied',
        500 => 'Server Error',
        default => 'Error',
    };

    // Map HTTP codes to user-friendly error messages
    $message = match ($code) {
        404 => 'The page you are looking for does not exist or has been moved.',
        403 => 'You do not have permission to access this page.',
        500 => 'Something went wrong on our end. Please try again later.',
        default => 'An unexpected error occurred.',
    };

    // Set the HTTP response code for proper browser behavior
    // This ensures the browser shows the correct error page
    http_response_code($code);

    // Include the error page template
    // Assumes pages/404.php, pages/403.php, pages/500.php exist
    include __DIR__ . '/../pages/' . $code . '.php';

    // Halt script execution after displaying the error page
    exit;
}
