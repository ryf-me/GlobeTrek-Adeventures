<?php
/**
 * Mailer Helper
 *
 * Reusable email sending function using PHPMailer with Gmail SMTP.
 * All email features in the application call this function.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an HTML email via Gmail SMTP.
 *
 * @param string $to        Recipient email address
 * @param string $subject   Email subject
 * @param string $htmlBody  HTML body content
 * @param string $textBody  Optional plain text fallback
 * @return bool  true on success, false on failure
 */
function sendMail(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
{
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // Sender
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        if ($textBody !== '') {
            $mail->AltBody = $textBody;
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Build a styled email HTML template wrapper.
 *
 * @param string $content  Inner HTML content
 * @return string  Full HTML email document
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
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fa;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background-color:#264653;padding:24px 32px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:22px;">GlobeTrek Adventures</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;color:#264653;font-size:15px;line-height:1.6;">
                            ' . $content . '
                        </td>
                    </tr>
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
