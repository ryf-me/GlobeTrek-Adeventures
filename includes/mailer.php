<?php
/**
 * File: includes/mailer.php
 * Purpose: PHPMailer wrapper for sending HTML emails via Gmail SMTP
 *
 * This file provides:
 *   1. sendMail() — Reusable email sending function using PHPMailer
 *   2. wrapEmailTemplate() — Branded HTML email template builder
 *
 * Dependencies:
 *   - vendor/autoload.php (Composer autoloader for PHPMailer)
 *   - config/mail.php (Gmail SMTP credentials)
 *
 * Used By:
 *   - includes/notifications.php (booking/payment/status emails)
 *   - config/otp.php (OTP verification emails)
 *   - pages/forgot-password.php (password reset emails)
 *   - pages/resend-verification.php (verification emails)
 *
 * Parent Files: None (loaded via require_once)
 * Child Files: None (no includes)
 *
 * Email Architecture:
 *   1. Caller builds inner HTML content
 *   2. wrapEmailTemplate() wraps it in a branded layout
 *   3. sendMail() sends via PHPMailer/Gmail SMTP
 *   4. Both HTML and plain-text versions are sent
 *
 * @package GlobeTrek\Includes
 */

// === COMPOSER AUTOLOADER ===
// Loads PHPMailer and all other Composer-managed dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// === SMTP CONFIGURATION ===
// Load Gmail SMTP credentials from config/mail.php
require_once __DIR__ . '/../config/mail.php';

// === PHPMAILER NAMESPACES ===
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// =============================================================================
// EMAIL SENDING FUNCTION
// =============================================================================
/**
 * Send an HTML email via Gmail SMTP.
 *
 * Configures PHPMailer with SMTP settings from config/mail.php,
 * sends the email, and handles any exceptions gracefully.
 *
 * @param string $to        Recipient email address
 * @param string $subject   Email subject line
 * @param string $htmlBody  HTML body content (usually wrapped via wrapEmailTemplate())
 * @param string $textBody  Optional plain-text fallback for non-HTML email clients
 * @return bool true on success, false on failure
 *
 * Usage:
 *   $html = wrapEmailTemplate('<h1>Hello!</h1><p>This is a test email.</p>');
 *   $text = "Hello! This is a test email.";
 *   sendMail('user@example.com', 'Test Email', $html, $text);
 */
function sendMail(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
{
    // Create a new PHPMailer instance
    // The true parameter enables exception handling (throws on errors)
    $mail = new PHPMailer(true);

    try {
        // === SMTP TRANSPORT CONFIGURATION ===
        $mail->isSMTP();                                            // Use SMTP transport
        $mail->Host       = MAIL_HOST;                              // Gmail SMTP server
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = MAIL_USERNAME;                          // Gmail address
        $mail->Password   = MAIL_PASSWORD;                          // Google App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;        // TLS encryption (port 587)
        $mail->Port       = MAIL_PORT;                              // Port 587
        $mail->CharSet    = 'UTF-8';                                // UTF-8 for full Unicode support

        // === SENDER & RECIPIENT ===
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);          // From: GlobeTrek Adventures
        $mail->addAddress($to);                                     // To: recipient

        // === EMAIL CONTENT ===
        $mail->isHTML(true);                                        // Enable HTML email
        $mail->Subject = $subject;                                  // Subject line
        $mail->Body    = $htmlBody;                                 // HTML body

        // Set plain-text fallback for non-HTML email clients
        if ($textBody !== '') {
            $mail->AltBody = $textBody;
        }

        // === SEND THE EMAIL ===
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error but don't crash the application
        // Mail failures should be silent from the user's perspective
        error_log('Mail error: ' . $e->getMessage());
        return false;
    }
}

// =============================================================================
// EMAIL TEMPLATE BUILDER
// =============================================================================
/**
 * Build a styled HTML email template wrapper.
 *
 * Creates a complete HTML document with:
 *   - Branded header bar (dark teal #264653 with "GlobeTrek Adventures")
 *   - White content card with border-radius and shadow
 *   - Footer with dynamic copyright year
 *
 * Uses table-based layout for maximum email client compatibility
 * (most email clients don't support modern CSS like flexbox/grid).
 *
 * All styles are inline (email clients strip <style> tags).
 *
 * @param string $content Inner HTML content to embed in the email body
 * @return string Complete HTML email document ready for sending
 *
 * Usage:
 *   $content = '<h2>Hello!</h2><p>This is the email body.</p>';
 *   $htmlEmail = wrapEmailTemplate($content);
 *   sendMail('user@example.com', 'Hello', $htmlEmail);
 */
function wrapEmailTemplate(string $content): string
{
    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:\'Segoe UI\',Tahoma,Geneva,Verdana,sans-serif;">
    <!-- Full-width background table -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fa;padding:40px 0;">
        <tr>
            <td align="center">
                <!-- 600px content card (standard email width) -->
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
                    <!-- === BRANDED HEADER === -->
                    <tr>
                        <td style="background-color:#264653;padding:24px 32px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:22px;">GlobeTrek Adventures</h1>
                        </td>
                    </tr>
                    <!-- === EMAIL BODY CONTENT === -->
                    <tr>
                        <td style="padding:32px;color:#264653;font-size:15px;line-height:1.6;">
                            ' . $content . '
                        </td>
                    </tr>
                    <!-- === FOOTER === -->
                    <tr>
                        <td style="background-color:#f5f7fa;padding:16px 32px;text-align:center;font-size:12px;color:#888888;">
                            &copy; ' . date('Y') . ' GlobeTrek Adventures. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}
