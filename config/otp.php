<?php
/**
 * File: config/otp.php
 * Purpose: One-Time Password (OTP) system for verification and authentication
 *
 * This file provides:
 *   1. Cryptographically secure 6-digit OTP generation
 *   2. Bcrypt-hashed storage in the database
 *   3. Timing-safe verification with single-use enforcement
 *   4. HTML email delivery via PHPMailer
 *
 * Dependencies:
 *   - config/database.php (for OTP storage and verification)
 *   - includes/mailer.php (for sending OTP emails, loaded lazily)
 *
 * Used By:
 *   - pages/signup.php (email verification after registration)
 *   - pages/resend-verification.php (resend verification email)
 *   - pages/forgot-password.php (password reset verification)
 *
 * Parent Files: None (loaded via require_once)
 * Child Files:
 *   - config/database.php (loaded immediately)
 *   - includes/mailer.php (loaded inside sendOTPEmail() function)
 *
 * Security Notes:
 *   - OTPs are hashed with bcrypt before storage (password_hash())
 *   - Single-use: OTPs are marked as used after verification
 *   - Time-limited: OTPs expire after 10 minutes
 *   - Previous OTPs are deleted when a new one is generated (prevents accumulation)
 *
 * @package GlobeTrek\Config
 */

// Load database connection for OTP storage
require_once __DIR__ . '/database.php';

// =============================================================================
// OTP GENERATION
// =============================================================================
/**
 * Generate a cryptographically secure 6-digit OTP.
 *
 * Uses random_int() which provides cryptographically secure random integers.
 * The result is cast to a string for consistent handling.
 *
 * @return string A 6-digit OTP string (e.g., "482917")
 *
 * Security:
 *   - random_int() uses the OS's cryptographic random number generator
 *   - Range 100000-999999 ensures exactly 6 digits
 */
function generateOTP(): string
{
    return (string) random_int(100000, 999999);
}

// =============================================================================
// OTP STORAGE
// =============================================================================
/**
 * Store a hashed OTP in the database.
 *
 * This function:
 *   1. Generates a new 6-digit OTP
 *   2. Hashes it with bcrypt (password_hash with PASSWORD_DEFAULT)
 *   3. Deletes any existing OTPs for the same email+purpose (prevents spam)
 *   4. Inserts the new OTP with a 10-minute expiry
 *   5. Returns the raw OTP (to be sent to the user via email)
 *
 * @param string $email    The email address for this OTP
 * @param string $purpose  The purpose: 'registration', 'login', or 'password_reset'
 * @param int|null $userId Optional user ID (for login purpose, not used for registration)
 * @return string The raw OTP (to be sent to the user — never stored in plaintext)
 *
 * Usage:
 *   $otp = storeOTP('user@example.com', 'registration');
 *   sendOTPEmail('user@example.com', $otp, 'registration');
 */
function storeOTP(string $email, string $purpose, ?int $userId = null): string
{
    $db = getDB();

    // Generate a new 6-digit OTP
    $otp = generateOTP();

    // Hash the OTP with bcrypt before storage
    // bcrypt is intentionally slow (by design) to resist brute-force attacks
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);

    // Set expiry to 10 minutes from now
    $expiresAt = date('Y-m-d H:i:s', time() + 600);

    // DELETE-BEFORE-INSERT pattern:
    // Remove any existing OTPs for this email + purpose combination
    // This prevents OTP spam/accumulation and ensures only the latest OTP is valid
    $del = $db->prepare("DELETE FROM otps WHERE email = :email AND purpose = :purpose");
    $del->execute([':email' => $email, ':purpose' => $purpose]);

    // Insert the new OTP record
    $stmt = $db->prepare(
        "INSERT INTO otps (user_id, email, otp_hash, purpose, expires_at)
         VALUES (:uid, :email, :hash, :purpose, :expires)"
    );
    $stmt->execute([
        ':uid'     => $userId,
        ':email'   => $email,
        ':hash'    => $otpHash,
        ':purpose' => $purpose,
        ':expires' => $expiresAt,
    ]);

    // Return the raw OTP — this is sent to the user via email
    // The hashed version is stored in the database
    return $otp;
}

// =============================================================================
// OTP VERIFICATION
// =============================================================================
/**
 * Verify an OTP against the stored hash.
 *
 * This function:
 *   1. Queries for the latest unused, non-expired OTP matching email+purpose
 *   2. Uses password_verify() for timing-safe comparison against the bcrypt hash
 *   3. Marks the OTP as used (single-use enforcement)
 *   4. Returns true if valid, false otherwise
 *
 * @param string $email    The email address
 * @param string $otp      The raw OTP to verify (from user input)
 * @param string $purpose  The purpose to match (e.g., 'registration')
 * @return bool true if OTP is valid and not expired, false otherwise
 *
 * Usage:
 *   if (verifyOTP($_POST['email'], $_POST['otp'], 'registration')) {
 *       // Email verified successfully
 *   } else {
 *       die('Invalid or expired verification code.');
 *   }
 */
function verifyOTP(string $email, string $otp, string $purpose): bool
{
    $db = getDB();

    // Query for the latest OTP that matches the criteria:
    // - Same email and purpose
    // - Not yet used (used = 0)
    // - Not expired (expires_at > NOW())
    // ORDER BY id DESC LIMIT 1 ensures we get the most recent OTP
    $stmt = $db->prepare(
        "SELECT id, otp_hash FROM otps
         WHERE email = :email AND purpose = :purpose AND used = 0 AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([':email' => $email, ':purpose' => $purpose]);
    $row = $stmt->fetch();

    // No matching OTP found (either doesn't exist, already used, or expired)
    if (!$row) return false;

    // Verify the OTP against the stored bcrypt hash
    // password_verify() does timing-safe comparison internally
    if (password_verify($otp, $row['otp_hash'])) {
        // Mark the OTP as used (single-use enforcement)
        // This prevents the same OTP from being used multiple times
        $upd = $db->prepare("UPDATE otps SET used = 1 WHERE id = :id");
        $upd->execute([':id' => $row['id']]);
        return true;
    }

    // OTP does not match the stored hash
    return false;
}

// =============================================================================
// OTP EMAIL DELIVERY
// =============================================================================
/**
 * Send an OTP email to the user.
 *
 * Builds an HTML email with the OTP displayed prominently in a styled box.
 * Includes both HTML and plain-text versions for email client compatibility.
 *
 * @param string $email    The recipient email address
 * @param string $otp      The raw OTP to include in the email
 * @param string $purpose  The purpose (for email subject/body customization)
 * @return bool true on success, false on failure
 *
 * Usage:
 *   $otp = storeOTP('user@example.com', 'registration');
 *   sendOTPEmail('user@example.com', $otp, 'registration');
 */
function sendOTPEmail(string $email, string $otp, string $purpose = 'verification'): bool
{
    // Lazily load the mailer (only when needed)
    require_once __DIR__ . '/../includes/mailer.php';

    // Build the email subject
    $subject = 'GlobeTrek Adventures — Your Verification Code';

    // Build the HTML email content
    // Uses inline styles for email client compatibility
    $content = '
        <h2 style="margin:0 0 16px;color:#264653;">Email Verification</h2>
        <p>Thank you for registering with GlobeTrek Adventures. Please use the following code to verify your email address:</p>
        <div style="background:#f5f7fa;border-radius:8px;padding:20px;text-align:center;margin:20px 0;">
            <!-- htmlspecialchars() is defense-in-depth — OTP is already numeric -->
            <span style="font-size:32px;font-weight:bold;letter-spacing:8px;color:#264653;">' . htmlspecialchars($otp) . '</span>
        </div>
        <p style="color:#666;font-size:13px;">This code expires in <strong>10 minutes</strong>.</p>
        <p style="color:#666;font-size:13px;">If you did not request this code, please ignore this email.</p>
    ';

    // Wrap the content in the branded email template
    $htmlBody = wrapEmailTemplate($content);

    // Create a plain-text fallback for non-HTML email clients
    $textBody = "Your verification code is: $otp\nThis code expires in 10 minutes.";

    // Send the email via PHPMailer/Gmail SMTP
    return sendMail($email, $subject, $htmlBody, $textBody);
}
