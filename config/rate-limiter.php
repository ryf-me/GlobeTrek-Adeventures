<?php
/**
 * File: config/rate-limiter.php
 * Purpose: Dual-layer rate limiting system (file-based + session-based + DB-backed)
 *
 * This file provides three rate limiting mechanisms:
 *   1. File-based rate limiting (for IP-based limits, stored in temp files)
 *   2. Session-based rate limiting (for per-user limits, stored in session)
 *   3. Database-backed account lockout (for login attempts, stored in login_attempts table)
 *
 * Dependencies:
 *   - config/database.php (for DB-backed account lockout)
 *   - config/session.php (for session-based rate limiting)
 *
 * Used By:
 *   - pages/login.php (5 attempts/15 min per IP)
 *   - pages/signup.php (3 attempts/hour per IP)
 *   - pages/forgot-password.php (3 requests/hour)
 *   - pages/resend-verification.php (3 attempts/hour)
 *   - pages/submit-review.php (3 reviews/hour)
 *   - pages/inquiries.php (10 inquiries/hour)
 *
 * Parent Files: None (loaded via require/require_once)
 * Child Files: config/database.php (loaded via getDB())
 *
 * Architecture:
 *   - IP-based limits use temp files in /tmp/globetrek_rate/
 *   - User-based limits use PHP session storage
 *   - Account lockout uses the login_attempts database table
 *
 * @package GlobeTrek\Config
 */

// Ensure a session is active for session-based rate limiting
if (session_status() === PHP_SESSION_NONE) session_start();

// =============================================================================
// MAIN RATE LIMIT CHECK FUNCTION
// =============================================================================
/**
 * Check if an action has exceeded its rate limit.
 *
 * This is the main entry point for rate limiting. It routes to either
 * file-based (IP) or session-based (user) rate limiting based on the $useIP flag.
 *
 * @param string $key           Unique identifier for the action (e.g., 'login', 'signup')
 * @param int    $maxAttempts   Maximum allowed attempts within the time window
 * @param int    $windowSeconds Time window in seconds (e.g., 900 = 15 minutes)
 * @param bool   $useIP         If true, rate limit by IP address; if false, by session
 * @return bool  true if the attempt is allowed, false if rate limited
 *
 * Usage:
 *   // Allow 5 login attempts per 15 minutes per IP
 *   if (!checkRateLimit('login', 5, 900, true)) {
 *       die('Too many login attempts. Please try again later.');
 *   }
 *
 *   // Allow 3 form submissions per hour per user
 *   if (!checkRateLimit('signup', 3, 3600, false)) {
 *       die('Too many attempts. Please try again later.');
 *   }
 */
function checkRateLimit(string $key, int $maxAttempts, int $windowSeconds, bool $useIP = true): bool {
    // Build a composite key: "action:identifier"
    // For IP-based: "login:192.168.1.1"
    // For session-based: "signup:12345" (user_id) or "signup:abc123" (session_id)
    $identifier = $useIP ? ($_SERVER['REMOTE_ADDR'] ?? 'unknown') : ($_SESSION['user_id'] ?? session_id());
    $rateKey = $key . ':' . $identifier;

    // Route to the appropriate rate limiting backend
    if ($useIP) {
        return checkFileRateLimit($rateKey, $maxAttempts, $windowSeconds);
    }
    return checkSessionRateLimit($rateKey, $maxAttempts, $windowSeconds);
}

// =============================================================================
// FILE-BASED RATE LIMITING (IP-Based)
// =============================================================================
/**
 * File-based rate limiting using temp files.
 *
 * Stores attempt data as JSON files in the system temp directory.
 * Each unique rate key gets its own file (named by MD5 hash).
 *
 * File format:
 *   {
 *     "attempts": [1693000001, 1693000002, ...],  // Timestamps of attempts
 *     "blocked_until": 1693000901                    // Block expiry timestamp
 *   }
 *
 * Block duration: The window period starting from the oldest attempt
 * in the current window. This means blocking ends when the oldest
 * attempt would naturally expire from the window.
 *
 * @param string $rateKey        The composite rate limit key (e.g., "login:192.168.1.1")
 * @param int    $maxAttempts    Maximum allowed attempts
 * @param int    $windowSeconds  Time window in seconds
 * @return bool  true if allowed, false if rate limited
 */
function checkFileRateLimit(string $rateKey, int $maxAttempts, int $windowSeconds): bool {
    // Create the cache directory if it doesn't exist
    // sys_get_temp_dir() returns the system temp directory (e.g., /tmp on Linux, C:\Windows\Temp on Windows)
    $cacheDir = sys_get_temp_dir() . '/globetrek_rate';
    if (!is_dir($cacheDir)) {
        // 0733 permissions: owner read/write/execute, group read/execute, others read/execute
        mkdir($cacheDir, 0733, true);
    }

    // Use MD5 hash of the rate key as the filename
    // This ensures valid filenames and prevents path traversal
    $file = $cacheDir . '/' . md5($rateKey) . '.json';
    $now = time();

    // Default data structure for new or empty files
    $data = ['attempts' => [], 'blocked_until' => 0];

    // Load existing data from the file if it exists
    if (file_exists($file)) {
        $raw = file_get_contents($file);
        if ($raw !== false) {
            $data = json_decode($raw, true) ?? $data;
        }
    }

    // === CHECK IF CURRENTLY BLOCKED ===
    // If the block expiry time is in the future, deny the request
    if (($data['blocked_until'] ?? 0) > $now) {
        return false;
    }

    // === CLEAN UP EXPIRED ATTEMPTS ===
    // Remove timestamps that are older than the window period
    // This maintains a sliding window of recent attempts
    $data['attempts'] = array_filter($data['attempts'] ?? [], function ($ts) use ($now, $windowSeconds) {
        return ($now - $ts) < $windowSeconds;
    });

    // === CHECK IF MAX ATTEMPTS REACHED ===
    if (count($data['attempts']) >= $maxAttempts) {
        // Calculate block duration: oldest attempt + window period
        // This means blocking ends when the oldest attempt would naturally expire
        $oldest = min($data['attempts']);
        $data['blocked_until'] = $oldest + $windowSeconds;
        file_put_contents($file, json_encode($data));
        return false;
    }

    // === RECORD THIS ATTEMPT ===
    $data['attempts'][] = $now;
    file_put_contents($file, json_encode($data));
    return true;
}

// =============================================================================
// DATABASE-BACKED ACCOUNT LOCKOUT
// =============================================================================
/**
 * Per-account lockout — checks if a specific email has too many failed login attempts.
 *
 * Uses the login_attempts database table for tracking. This is separate from
 * the file-based rate limiter and provides account-specific protection.
 *
 * Security Design:
 *   - Default: 5 failed attempts per 15 minutes
 *   - Fails open: If the database is down, login is allowed (availability > security)
 *   - Attempts are cleared on successful login
 *
 * @param string $email          The email address to check
 * @param int    $maxAttempts    Maximum failed attempts allowed (default: 5)
 * @param int    $windowSeconds  Time window in seconds (default: 900 = 15 minutes)
 * @return bool  true if login is allowed, false if account is locked
 *
 * Usage:
 *   if (!checkAccountLockout($email)) {
 *       die('Account temporarily locked due to too many failed attempts.');
 *   }
 */
function checkAccountLockout(string $email, int $maxAttempts = 5, int $windowSeconds = 900): bool
{
    try {
        $db = getDB();
        // Count failed attempts for this email within the time window
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS cnt FROM login_attempts
             WHERE email = :email AND attempted_at > DATE_SUB(NOW(), INTERVAL :window SECOND)"
        );
        $stmt->execute([':email' => $email, ':window' => $windowSeconds]);
        $count = (int)$stmt->fetch()['cnt'];

        // Allow login if count is below the maximum
        return $count < $maxAttempts;
    } catch (Exception $e) {
        // FAIL OPEN POLICY: If the database is unavailable, allow login
        // This prioritizes availability over security — the user can still access their account
        // The trade-off is that brute-force protection is temporarily disabled
        return true;
    }
}

// =============================================================================
// RECORD FAILED LOGIN ATTEMPT
// =============================================================================
/**
 * Record a failed login attempt in the database.
 *
 * Called after a failed login attempt to track failed authentication.
 * This data is used by checkAccountLockout() to enforce account lockout.
 *
 * @param string $email  The email address that failed authentication
 * @param string $ip     The IP address of the failed attempt
 * @return void
 *
 * Usage:
 *   // After failed password verification:
 *   recordLoginAttempt($email, $_SERVER['REMOTE_ADDR']);
 */
function recordLoginAttempt(string $email, string $ip): void
{
    try {
        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)"
        );
        $stmt->execute([':email' => $email, ':ip' => $ip]);
    } catch (Exception$e) {
        // Log the error but don't break the application
        error_log('Failed to record login attempt: ' . $e->getMessage());
    }
}

// =============================================================================
// CLEAR FAILED LOGIN ATTEMPTS
// =============================================================================
/**
 * Clear all failed login attempts for an email address.
 *
 * Called after a successful login to reset the lockout counter.
 * This prevents the account from remaining locked after a successful authentication.
 *
 * @param string $email  The email address to clear
 * @return void
 *
 * Usage:
 *   // After successful login:
 *   clearLoginAttempts($email);
 */
function clearLoginAttempts(string $email): void
{
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM login_attempts WHERE email = :email");
        $stmt->execute([':email' => $email]);
    } catch (Exception $e) {
        error_log('Failed to clear login attempts: ' . $e->getMessage());
    }
}

// =============================================================================
// SESSION-BASED RATE LIMITING (User-Based)
// =============================================================================
/**
 * Session-based rate limiting for per-user limits.
 *
 * Stores attempt timestamps in the PHP session. This is used for
 * authenticated users where IP-based limiting is not appropriate
 * (e.g., multiple users behind the same NAT/proxy).
 *
 * Session structure:
 *   $_SESSION['rate_limits']['action:user_id'] = [timestamp1, timestamp2, ...]
 *
 * @param string $rateKey        The composite rate limit key
 * @param int    $maxAttempts    Maximum allowed attempts
 * @param int    $windowSeconds  Time window in seconds
 * @return bool  true if allowed, false if rate limited
 */
function checkSessionRateLimit(string $rateKey, int $maxAttempts, int $windowSeconds): bool {
    $now = time();

    // Initialize the rate_limits array if it doesn't exist
    $_SESSION['rate_limits'] = $_SESSION['rate_limits'] ?? [];

    // Initialize the specific rate key if it doesn't exist
    if (!isset($_SESSION['rate_limits'][$rateKey])) {
        $_SESSION['rate_limits'][$rateKey] = [];
    }

    // Remove expired attempts from the sliding window
    $_SESSION['rate_limits'][$rateKey] = array_filter(
        $_SESSION['rate_limits'][$rateKey],
        function ($ts) use ($now, $windowSeconds) {
            return ($now - $ts) < $windowSeconds;
        }
    );

    // Check if max attempts reached
    if (count($_SESSION['rate_limits'][$rateKey]) >= $maxAttempts) {
        return false;
    }

    // Record this attempt
    $_SESSION['rate_limits'][$rateKey][] = $now;
    return true;
}
