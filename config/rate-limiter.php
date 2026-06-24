<?php
/**
 * Rate Limiter — File-based request throttling
 *
 * Limits the number of attempts for a given action within a time window.
 * Uses PHP session storage for per-user limits and a temp directory for IP-based limits.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Check if an action has exceeded its rate limit.
 *
 * @param string $key          Unique identifier for the action (e.g., 'login', 'signup')
 * @param int    $maxAttempts  Maximum allowed attempts in the window
 * @param int    $windowSeconds Time window in seconds
 * @param bool   $useIP        If true, rate limit by IP address instead of session
 * @return bool  true if the attempt is allowed, false if rate limited
 */
function checkRateLimit(string $key, int $maxAttempts, int $windowSeconds, bool $useIP = true): bool {
    $identifier = $useIP ? ($_SERVER['REMOTE_ADDR'] ?? 'unknown') : ($_SESSION['user_id'] ?? session_id());
    $rateKey = $key . ':' . $identifier;

    if ($useIP) {
        return checkFileRateLimit($rateKey, $maxAttempts, $windowSeconds);
    }
    return checkSessionRateLimit($rateKey, $maxAttempts, $windowSeconds);
}

/**
 * File-based rate limiting (for IP-based limits).
 * Stores attempt data in temp files.
 */
function checkFileRateLimit(string $rateKey, int $maxAttempts, int $windowSeconds): bool {
    $cacheDir = sys_get_temp_dir() . '/globetrek_rate';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0733, true);
    }

    $file = $cacheDir . '/' . md5($rateKey) . '.json';
    $now = time();

    $data = ['attempts' => [], 'blocked_until' => 0];

    if (file_exists($file)) {
        $raw = file_get_contents($file);
        if ($raw !== false) {
            $data = json_decode($raw, true) ?? $data;
        }
    }

    // Check if currently blocked
    if (($data['blocked_until'] ?? 0) > $now) {
        return false;
    }

    // Remove expired attempts
    $data['attempts'] = array_filter($data['attempts'] ?? [], function ($ts) use ($now, $windowSeconds) {
        return ($now - $ts) < $windowSeconds;
    });

    if (count($data['attempts']) >= $maxAttempts) {
        // Block for the remaining window period
        $oldest = min($data['attempts']);
        $data['blocked_until'] = $oldest + $windowSeconds;
        file_put_contents($file, json_encode($data));
        return false;
    }

    // Record this attempt
    $data['attempts'][] = $now;
    file_put_contents($file, json_encode($data));
    return true;
}

/**
 * Session-based rate limiting (for per-user limits).
 */
function checkSessionRateLimit(string $rateKey, int $maxAttempts, int $windowSeconds): bool {
    $now = time();
    $_SESSION['rate_limits'] = $_SESSION['rate_limits'] ?? [];

    if (!isset($_SESSION['rate_limits'][$rateKey])) {
        $_SESSION['rate_limits'][$rateKey] = [];
    }

    // Remove expired attempts
    $_SESSION['rate_limits'][$rateKey] = array_filter(
        $_SESSION['rate_limits'][$rateKey],
        function ($ts) use ($now, $windowSeconds) {
            return ($now - $ts) < $windowSeconds;
        }
    );

    if (count($_SESSION['rate_limits'][$rateKey]) >= $maxAttempts) {
        return false;
    }

    $_SESSION['rate_limits'][$rateKey][] = $now;
    return true;
}
