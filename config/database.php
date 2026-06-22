<?php
define('DB_HOST', 'localhost');      // Database server address
define('DB_NAME', 'globetrek');      // Database name
define('DB_USER', 'root');           // Database username (XAMPP default)
define('DB_PASS', '');               // Database password (empty for XAMPP default)

// Function to get database connection (singleton pattern)
function getDB() {
    static $pdo = null;              // Static variable persists between function calls
    if ($pdo === null) {             // Only create connection on first call
        try {
            // Build DSN (Data Source Name) with UTF-8 charset support
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            // Create PDO connection with security and performance settings
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Return associative arrays
                PDO::ATTR_EMULATE_PREPARES => false,                 // Use real prepared statements (SQL injection prevention)
            ]);
        } catch (PDOException $e) {    // Catch database connection errors
            die('Database connection failed: ' . $e->getMessage()); // Stop execution and show error
        }
    }
    return $pdo;                   // Return the database connection object
}
