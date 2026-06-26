<?php
/**
 * OTP (One-Time Password) System
 *
 * Generates, stores, and verifies 6-digit OTPs for:
 * - Account registration verification
 * - Two-factor authentication
 * - Password reset confirmation
 *
 * OTPs are hashed with password_hash() before storage,
 * expire after 10 minutes, and are single-use.
 */

require_once __DIR__ . '/database.php';

/**
 * Generate a 6-digit OTP.
 *
 * @return string 6-digit OTP string
 */
function generateOTP(): string
{
    return (string) random_int(100000, 999999);
}

/**
 * Store a hashed OTP in the database.
 *
 * @param string $email   The email address for this OTP
 * @param string $purpose The purpose: 'registration', 'login', or 'password_reset'
 * @param int    $userId  Optional user ID (for login purpose)
 * @return string The raw OTP (to be sent to the user)
 */
function storeOTP(string $email, string $purpose, ?int $userId = null): string
{
    $db = getDB();
    $otp = generateOTP();
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    // Delete any existing OTPs for this email + purpose
    $del = $db->prepare("DELETE FROM otps WHERE email = :email AND purpose = :purpose");
    $del->execute([':email' => $email, ':purpose' => $purpose]);

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

    return $otp;
}

/**
 * Verify an OTP against the stored hash.
 *
 * @param string $email   The email address
 * @param string $otp     The raw OTP to verify
 * @param string $purpose The purpose to match
 * @return bool true if OTP is valid and not expired
 */
function verifyOTP(string $email, string $otp, string $purpose): bool
{
    $db = getDB();

    $stmt = $db->prepare(
        "SELECT id, otp_hash FROM otps
         WHERE email = :email AND purpose = :purpose AND used = 0 AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([':email' => $email, ':purpose' => $purpose]);
    $row = $stmt->fetch();

    if (!$row) return false;

    if (password_verify($otp, $row['otp_hash'])) {
        // Mark as used
        $upd = $db->prepare("UPDATE otps SET used = 1 WHERE id = :id");
        $upd->execute([':id' => $row['id']]);
        return true;
    }

    return false;
}

/**
 * Send an OTP email to the user.
 *
 * @param string $email The recipient email
 * @param string $otp   The raw OTP to include in the email
 * @param string $purpose The purpose (for the email subject/body)
 * @return bool true on success
 */
function sendOTPEmail(string $email, string $otp, string $purpose = 'verification'): bool
{
    require_once __DIR__ . '/../includes/mailer.php';

    $subject = 'GlobeTrek Adventures — Your Verification Code';
    $content = '
        <h2 style="margin:0 0 16px;color:#264653;">Email Verification</h2>
        <p>Thank you for registering with GlobeTrek Adventures. Please use the following code to verify your email address:</p>
        <div style="background:#f5f7fa;border-radius:8px;padding:20px;text-align:center;margin:20px 0;">
            <span style="font-size:32px;font-weight:bold;letter-spacing:8px;color:#264653;">' . htmlspecialchars($otp) . '</span>
        </div>
        <p style="color:#666;font-size:13px;">This code expires in <strong>10 minutes</strong>.</p>
        <p style="color:#666;font-size:13px;">If you did not request this code, please ignore this email.</p>
    ';

    $htmlBody = wrapEmailTemplate($content);
    $textBody = "Your verification code is: $otp\nThis code expires in 10 minutes.";

    return sendMail($email, $subject, $htmlBody, $textBody);
}
