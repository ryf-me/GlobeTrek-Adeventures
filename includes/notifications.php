<?php
/**
 * Notification System
 *
 * Sends automated email notifications for key business events:
 * - Booking confirmed
 * - Payment received
 * - Booking status update
 * - Staff reply to inquiry
 *
 * SMS notifications are not yet implemented (email only).
 */

require_once __DIR__ . '/mailer.php';

/**
 * Send booking confirmation email.
 *
 * @param array $booking  Booking data from database
 * @param array $package  Package data from database
 * @return bool
 */
function sendBookingConfirmation(array $booking, array $package): bool
{
    $name = htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']);
    $ref = htmlspecialchars($booking['booking_reference']);
    $pkg = htmlspecialchars($package['title']);
    $date = date('d M Y', strtotime($booking['travel_date']));
    $price = 'Rs.' . number_format($booking['total_price'], 2);
    $travellers = (int)$booking['num_travellers'];

    $content = "
        <h2 style='margin:0 0 16px;color:#264653;'>Booking Confirmed!</h2>
        <p>Dear {$name},</p>
        <p>Your booking has been confirmed. Here are your booking details:</p>
        <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
            <tr style='background:#f5f7fa;'>
                <td style='padding:10px;font-weight:600;border:1px solid #e2e8f0;'>Booking Reference</td>
                <td style='padding:10px;border:1px solid #e2e8f0;'>{$ref}</td>
            </tr>
            <tr>
                <td style='padding:10px;font-weight:600;border:1px solid #e2e8f0;'>Package</td>
                <td style='padding:10px;border:1px solid #e2e8f0;'>{$pkg}</td>
            </tr>
            <tr style='background:#f5f7fa;'>
                <td style='padding:10px;font-weight:600;border:1px solid #e2e8f0;'>Travel Date</td>
                <td style='padding:10px;border:1px solid #e2e8f0;'>{$date}</td>
            </tr>
            <tr>
                <td style='padding:10px;font-weight:600;border:1px solid #e2e8f0;'>Travellers</td>
                <td style='padding:10px;border:1px solid #e2e8f0;'>{$travellers}</td>
            </tr>
            <tr style='background:#f5f7fa;'>
                <td style='padding:10px;font-weight:600;border:1px solid #e2e8f0;'>Total</td>
                <td style='padding:10px;border:1px solid #e2e8f0;font-weight:700;color:#e76f51;'>{$price}</td>
            </tr>
        </table>
        <p>You can view your booking details in your account dashboard.</p>
        <p>If you have any questions, please don't hesitate to contact us.</p>
    ";

    $htmlBody = wrapEmailTemplate($content);
    $textBody = "Booking Confirmed!\n\nReference: {$ref}\nPackage: {$pkg}\nDate: {$date}\nTotal: {$price}\n\nThank you for booking with GlobeTrek Adventures!";

    return sendMail($booking['email'], "Booking Confirmed — {$ref} | GlobeTrek Adventures", $htmlBody, $textBody);
}

/**
 * Send payment receipt email.
 *
 * @param array $payment  Payment data from database
 * @param array $booking  Booking data from database
 * @return bool
 */
function sendPaymentReceipt(array $payment, array $booking): bool
{
    $name = htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']);
    $ref = htmlspecialchars($booking['booking_reference']);
    $amount = 'Rs.' . number_format($payment['amount'], 2);
    $method = ucfirst(str_replace('_', ' ', $payment['payment_method'] ?? ''));
    $txnId = htmlspecialchars($payment['transaction_id']);
    $date = date('d M Y', strtotime($payment['created_at']));
    $lastFour = htmlspecialchars($payment['card_last_four'] ?? '');

    $content = "
        <h2 style='margin:0 0 16px;color:#264653;'>Payment Receipt</h2>
        <p>Dear {$name},</p>
        <p>We have received your payment. Here are the details:</p>
        <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
            <tr style='background:#f5f7fa;'>
                <td style='padding:10px;font-weight:600;border:1px solid #e2e8f0;'>Booking Reference</td>
                <td style='padding:10px;border:1px solid #e2e8f0;'>{$ref}</td>
            </tr>
            <tr>
                <td style='padding:10px;font-weight:600;border:1px solid #e2e8f0;'>Amount Paid</td>
                <td style='padding:10px;border:1px solid #e2e8f0;font-weight:700;color:#286f45;'>{$amount}</td>
            </tr>
            <tr style='background:#f5f7fa;'>
                <td style='padding:10px;font-weight:600;border:1px solid #e2e8f0;'>Payment Method</td>
                <td style='padding:10px;border:1px solid #e2e8f0;'>{$method}" . ($lastFour ? " (****{$lastFour})" : '') . "</td>
            </tr>
            <tr>
                <td style='padding:10px;font-weight:600;border:1px solid #e2e8f0;'>Transaction ID</td>
                <td style='padding:10px;border:1px solid #e2e8f0;'>{$txnId}</td>
            </tr>
            <tr style='background:#f5f7fa;'>
                <td style='padding:10px;font-weight:600;border:1px solid #e2e8f0;'>Date</td>
                <td style='padding:10px;border:1px solid #e2e8f0;'>{$date}</td>
            </tr>
        </table>
        <p>Thank you for your payment. Your booking is now confirmed.</p>
    ";

    $htmlBody = wrapEmailTemplate($content);
    $textBody = "Payment Receipt\n\nBooking: {$ref}\nAmount: {$amount}\nMethod: {$method}\nDate: {$date}\n\nThank you for your payment!";

    return sendMail($booking['email'], "Payment Receipt — {$ref} | GlobeTrek Adventures", $htmlBody, $textBody);
}

/**
 * Send booking status update email.
 *
 * @param array  $booking   Booking data from database
 * @param string $oldStatus Previous status
 * @return bool
 */
function sendBookingStatusUpdate(array $booking, string $oldStatus): bool
{
    $name = htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']);
    $ref = htmlspecialchars($booking['booking_reference']);
    $newStatus = ucfirst(htmlspecialchars($booking['status']));
    $oldStatusDisplay = ucfirst(htmlspecialchars($oldStatus));

    $statusColors = [
        'confirmed' => '#286f45',
        'cancelled' => '#ba1a1a',
        'pending'   => '#e6a817',
    ];
    $color = $statusColors[$booking['status']] ?? '#264653';

    $content = "
        <h2 style='margin:0 0 16px;color:#264653;'>Booking Status Updated</h2>
        <p>Dear {$name},</p>
        <p>Your booking <strong>{$ref}</strong> status has been updated.</p>
        <div style='text-align:center;margin:20px 0;'>
            <span style='display:inline-block;background:#f5f7fa;border-radius:8px;padding:12px 24px;font-size:14px;color:#666;'>{$oldStatusDisplay}</span>
            <span style='margin:0 12px;font-size:18px;color:#999;'>&rarr;</span>
            <span style='display:inline-block;background:{$color};color:#fff;border-radius:8px;padding:12px 24px;font-size:14px;font-weight:600;'>{$newStatus}</span>
        </div>
        <p>You can view your booking details in your account dashboard.</p>
    ";

    $htmlBody = wrapEmailTemplate($content);
    $textBody = "Booking Status Updated\n\nBooking: {$ref}\nStatus: {$oldStatusDisplay} -> {$newStatus}\n\nView your booking in your account dashboard.";

    return sendMail($booking['email'], "Booking Status Update — {$ref} | GlobeTrek Adventures", $htmlBody, $textBody);
}

/**
 * Send inquiry reply notification email.
 *
 * @param array $inquiry  Inquiry data from database
 * @param array $reply    Reply data from database
 * @return bool
 */
function sendInquiryReplyNotification(array $inquiry, array $reply): bool
{
    // Get user email
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $stmt = $db->prepare("SELECT email, full_name FROM users WHERE id = :uid LIMIT 1");
    $stmt->execute([':uid' => $inquiry['user_id']]);
    $user = $stmt->fetch();

    if (!$user) return false;

    $name = htmlspecialchars($user['full_name']);
    $code = htmlspecialchars($inquiry['inquiry_id_code']);
    $subject = htmlspecialchars($inquiry['subject']);
    $message = nl2br(htmlspecialchars($reply['message']));

    $content = "
        <h2 style='margin:0 0 16px;color:#264653;'>New Reply to Your Inquiry</h2>
        <p>Dear {$name},</p>
        <p>There is a new reply to your inquiry <strong>{$code}</strong> regarding \"{$subject}\".</p>
        <div style='background:#f5f7fa;border-radius:8px;padding:16px;margin:16px 0;border-left:4px solid #e76f51;'>
            {$message}
        </div>
        <p>Log in to your account to view the full conversation and reply.</p>
    ";

    $htmlBody = wrapEmailTemplate($content);
    $textBody = "New Reply to Inquiry {$code}\n\nSubject: {$subject}\n\n{$reply['message']}\n\nLog in to view and reply.";

    return sendMail($user['email'], "New Reply — {$code} | GlobeTrek Adventures", $htmlBody, $textBody);
}
