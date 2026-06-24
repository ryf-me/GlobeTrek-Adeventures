<?php
/**
 * Global Error Handler
 *
 * Catches PHP errors, exceptions, and fatal errors,
 * then displays a user-friendly error page instead of raw stack traces.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Convert PHP errors into exceptions for unified handling
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Handle uncaught exceptions
set_exception_handler(function (Throwable $e) {
    logError($e->getMessage(), $e->getFile(), $e->getLine());
    show_error_page(500);
});

// Handle fatal errors at shutdown
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logError($error['message'], $error['file'], $error['line']);
        show_error_page(500);
    }
});

/**
 * Log error details to the activity_logs table if possible,
 * otherwise fall back to a temp file.
 */
function logError(string $message, string $file, int $line): void {
    $detail = "Error: $message in $file on line $line";

    // Try database logging if connection is available
    try {
        if (function_exists('getDB')) {
            $db = getDB();
            $userId = $_SESSION['user_id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt = $db->prepare(
                "INSERT INTO activity_logs (user_id, action, entity_type, details, ip_address)
                 VALUES (:uid, 'system_error', 'error', :details, :ip)"
            );
            $stmt->execute([':uid' => $userId, ':details' => $detail, ':ip' => $ip]);
            return;
        }
    } catch (Exception $e) {
        // DB unavailable, fall through to file logging
    }

    // Fallback: write to temp file
    $logFile = sys_get_temp_dir() . '/globetrek_errors.log';
    $entry = date('[Y-m-d H:i:s]') . " " . $detail . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Display a styled error page matching the GlobeTrek design.
 */
function show_error_page(int $code): void {
    $title = match ($code) {
        404 => 'Page Not Found',
        403 => 'Access Denied',
        500 => 'Server Error',
        default => 'Error',
    };
    $message = match ($code) {
        404 => 'The page you are looking for does not exist or has been moved.',
        403 => 'You do not have permission to access this page.',
        500 => 'Something went wrong on our end. Please try again later.',
        default => 'An unexpected error occurred.',
    };
    http_response_code($code);
    include __DIR__ . '/../pages/' . $code . '.php';
    exit;
}
