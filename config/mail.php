<?php
/**
 * File: config/mail.php
 * Purpose: Gmail SMTP configuration constants for email delivery
 *
 * This file defines the SMTP credentials and settings used by PHPMailer
 * to send emails through Gmail's SMTP server.
 *
 * Dependencies: None (constants-only file)
 *
 * Used By:
 *   - includes/mailer.php (loads these constants for PHPMailer configuration)
 *
 * Parent Files: None (loaded via require_once)
 * Child Files: None (no includes)
 *
 * SECURITY WARNING:
 *   This file contains hardcoded credentials (email and app password).
 *   In production, these should be stored in environment variables or a .env file
 *   that is NOT committed to version control.
 *
 * Setup Instructions:
 *   1. Enable 2-Step Verification on your Google account
 *   2. Go to https://myaccount.google.com/apppasswords
 *   3. Generate an app password for "Mail"
 *   4. Use that 16-character password below (NOT your regular Gmail password)
 *
 * @package GlobeTrek\Config
 */

// =============================================================================
// SMTP SERVER CONFIGURATION
// =============================================================================

// Gmail SMTP server address
define('MAIL_HOST', 'smtp.gmail.com');

// Port 587 for TLS-encrypted SMTP (STARTTLS)
// Alternative: Port 465 for SSL encryption
define('MAIL_PORT', 587);

// Gmail address used for SMTP authentication
define('MAIL_USERNAME', 'insathraifyk3@gmail.com');

// Google App Password (16-character code, NOT the regular Gmail password)
// Generated from: https://myaccount.google.com/apppasswords
define('MAIL_PASSWORD', 'yuxdxdanxuwwkkjk');

// Encryption type: TLS (STARTTLS on port 587)
define('MAIL_ENCRYPTION', 'tls');

// Sender address displayed in email headers
define('MAIL_FROM_ADDRESS', 'insathraifyk3@gmail.com');

// Sender name displayed in email headers
define('MAIL_FROM_NAME', 'GlobeTrek Adventures');
