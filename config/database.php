<?php
/**
 * File: config/database.php
 * Purpose: Core database connection layer and base URL detection
 *
 * This file provides:
 *   1. MySQL connection constants (DB_HOST, DB_NAME, DB_USER, DB_PASS)
 *   2. Automatic BASE_URL detection for subdirectory installations
 *   3. A singleton PDO database connection via getDB()
 *
 * Dependencies: None (this is a root-level config file)
 *
 * Used By:
 *   - ~70 files across the entire application
 *   - Every file that calls getDB() or uses BASE_URL
 *
 * Parent Files: None (loaded via require/require_once)
 * Child Files: None (no includes)
 *
 * Security Notes:
 *   - PDO uses real prepared statements (EMULATE_PREPARES = false) to prevent SQL injection
 *   - Database credentials should be in environment variables in production
 *
 * @package GlobeTrek\Config
 */

// =============================================================================
// DATABASE CONNECTION CONSTANTS
// =============================================================================
// These constants define the MySQL connection parameters.
// In production, these should be loaded from environment variables or a .env file.

define('DB_HOST', 'localhost');      // Database server address (XAMPP default: localhost)
define('DB_NAME', 'globetrek');      // Database name (must match your MySQL database)
define('DB_USER', 'root');           // Database username (XAMPP default: root)
define('DB_PASS', '');               // Database password (empty for XAMPP default)

// =============================================================================
// BASE URL AUTO-DETECTION
// =============================================================================
// Automatically detects the application's base URL from the server environment.
// This handles subdirectory installations like /GlobeTrek-Adeventures/
// and works across different hosting configurations.
//
// Result example: "http://192.168.1.10/GlobeTrek-Adeventures"
// or in production: "https://www.globetrek.lk"

if (!defined('BASE_URL')) {
    // Step 1: Detect protocol (HTTP or HTTPS)
    // Checks if HTTPS is enabled and not set to 'off'
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    // Step 2: Get the hostname from the server environment
    // Falls back to 'localhost' if HTTP_HOST is not available
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Step 3: If running on localhost, try to resolve the machine's LAN IP
    // This allows other devices on the network to access the site
    // gethostbyname(gethostname()) resolves the hostname to its IP address
    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        $ip = gethostbyname(gethostname());
        // Only use the resolved IP if it's not the loopback address
        if ($ip && $ip !== '127.0.0.1') {
            $host = $ip;
        }
    }

    // Step 4: Extract the base path from the current script location
    // dirname($_SERVER['SCRIPT_NAME']) gives the directory of the current script
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

    // Step 5: Strip trailing "/pages" from the path if present
    // This ensures BASE_URL points to the project root, not the pages subdirectory
    // For example: "/GlobeTrek-Adeventures/pages" becomes "/GlobeTrek-Adeventures"
    $basePath = preg_replace('#/pages$#', '', $scriptDir);

    // Step 6: Construct and define the final BASE_URL constant
    // Format: protocol://hostname/basePath
    define('BASE_URL', $protocol . '://' . $host . $basePath);
}

// =============================================================================
// DATABASE CONNECTION FUNCTION (SINGLETON PATTERN)
// =============================================================================
/**
 * Get a singleton PDO database connection.
 *
 * Uses a static variable to ensure only one connection is created per request.
 * The connection is reused on subsequent calls, avoiding duplicate connections.
 *
 * PDO Configuration:
 *   - ERRMODE_EXCEPTION: Throws PDOException on any database error
 *   - FETCH_ASSOC: Returns result sets as associative arrays by default
 *   - EMULATE_PREPARES = false: Uses native MySQL prepared statements
 *     (critical for SQL injection prevention)
 *
 * @return PDO A configured PDO connection object
 * @throws RuntimeException If the database connection fails
 *
 * Usage:
 *   $db = getDB();
 *   $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
 *   $stmt->execute([':id' => $userId]);
 *   $user = $stmt->fetch();
 */
function getDB() {
    // Static variable persists between function calls within the same request
    // This is the core of the singleton pattern
    static $pdo = null;

    // Only create a new connection on the first call
    if ($pdo === null) {
        try {
            // Build the DSN (Data Source Name) with UTF-8mb4 charset support
            // utf8mb4 supports full Unicode including emojis
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

            // Create the PDO connection with security and performance settings
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                // Throw exceptions on any database error (instead of returning false)
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                // Return associative arrays by default (e.g., $row['column_name'])
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                // Disable emulated prepared statements — use real MySQL prepared statements
                // This is critical for preventing SQL injection attacks
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Re-throw as a RuntimeException so the error handler can display a friendly page
            // Never expose raw database errors to users in production
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // Return the existing or newly created connection
    return $pdo;
}
