<?php
/**
 * File: config/mail.php
 * Purpose: Gmail SMTP configuration constants for email delivery
 *
 * This file defines the SMTP credentials and settings used by PHPMailer
 * to send emails through Gmail's SMTP server.
 *
 * Dependencies: vlucas/phpdotenv (loaded via config/database.php)
 *
 * Used By:
 *   - includes/mailer.php (loads these constants for PHPMailer configuration)
 *
 * Parent Files: None (loaded via require_once)
 * Child Files: None (no includes)
 *
 * SECURITY: Credentials are loaded from .env file (never committed to git).
 *   Copy .env.example to .env and fill in your Gmail app password.
 *
 * Setup Instructions:
 *   1. Enable 2-Step Verification on your Google account
 *   2. Go to https://myaccount.google.com/apppasswords
 *   3. Generate an app password for "Mail"
 *   4. Use that 16-character password in your .env file
 *
 * @package GlobeTrek\Config
 */

// =============================================================================
// SMTP SERVER CONFIGURATION
// =============================================================================
// All values loaded from .env file.

define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
define('MAIL_PORT', (int)($_ENV['MAIL_PORT'] ?? 587));
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? '');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'GlobeTrek Adventures');
