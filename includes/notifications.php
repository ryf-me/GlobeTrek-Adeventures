<?php
/**
 * File: includes/notifications.php
 * Purpose: Automated email notification system for key business events
 *
 * This file provides 4 notification functions:
 *   1. sendBookingConfirmation() — Sent when a booking is confirmed
 *   2. sendPaymentReceipt() — Sent when a payment is received
 *   3. sendBookingStatusUpdate() — Sent when booking status changes
 *   4. sendInquiryReplyNotification() — Sent when staff replies to an inquiry
 *
 * Dependencies:
 *   - includes/mailer.php (for sendMail() and wrapEmailTemplate())
 *   - config/currency.php (for formatPrice())
 *   - config/database.php (for getDB() — loaded inside sendInquiryReplyNotification())
 *
 * Used By:
 *   - pages/payment.php (sends booking confirmation and payment receipt)
 *   - admin/bookings.php (sends status update notification)
 *   - admin/inquiries.php (sends inquiry reply notification)
 *
 * Parent Files: None (loaded via require_once)
 * Child Files:
 *   - includes/mailer.php (loaded immediately)
 *   - config/currency.php (loaded immediately)
 *   - config/database.php (loaded inside sendInquiryReplyNotification())
 *
 * Email Design:
 *   - All emails use inline styles for email client compatibility
 *   - Table-based layouts for maximum compatibility
 *   - Both HTML and plain-text versions are sent
 *   - Branded wrapper via wrapEmailTemplate()
 *
 * @package GlobeTrek\Includes
 */

// === DEPENDENCIES ===
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/../config/currency.php';

// =============================================================================
// BOOKING CONFIRMATION NOTIFICATION
// =============================================================================
/**
 * Send booking confirmation email to the customer.
 *
 * Displays booking details in a styled table format:
 *   - Booking reference number
 *   - Package name
 *   - Travel date
 *   - Number of travellers
 *   - Total price (formatted in LKR)
 *
 * @param array $booking Booking data from the database
 *   Required keys: first_name, last_name, email, booking_reference,
 *                  travel_date, total_price, num_travellers
 * @param array $package Package data from the database
 *   Required keys: title
 * @return bool true on success, false on failure
 *
 * Usage:
 *   sendBookingConfirmation($bookingData, $packageData);
 */
function sendBookingConfirmation(array $booking, array $package): bool
{
    // === PREPARE VARIABLES (escaped for XSS safety) ===
    $name = htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']);
    $ref = htmlspecialchars($booking['booking_reference']);
    $pkg = htmlspecialchars($package['title']);
    $date = date('d M Y', strtotime($booking['travel_date']));
    $price = formatPrice($booking['total_price'], 2);  // "Rs. 15,000.00"
    $travellers = (int)$booking['num_travellers'];

    // === BUILD HTML EMAIL CONTENT ===
    // Table-based layout with alternating row backgrounds
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

    // Wrap in branded template and create plain-text fallback
    $htmlBody = wrapEmailTemplate($content);
    $textBody = "Booking Confirmed!\n\nReference: {$ref}\nPackage: {$pkg}\nDate: {$date}\nTotal: {$price}\n\nThank you for booking with GlobeTrek Adventures!";

    // Send the email
    return sendMail($booking['email'], "Booking Confirmed — {$ref} | GlobeTrek Adventures", $htmlBody, $textBody);
}

// =============================================================================
// PAYMENT RECEIPT NOTIFICATION
// =============================================================================
/**
 * Send payment receipt email to the customer.
 *
 * Displays payment details including:
 *   - Booking reference
 *   - Amount paid (formatted in LKR)
 *   - Payment method (with masked card last-4 digits if available)
 *   - Transaction ID
 *   - Payment date
 *
 * @param array $payment Payment data from the database
 *   Required keys: amount, payment_method, transaction_id, created_at
 *   Optional keys: card_last_four
 * @param array $booking Booking data from the database
 *   Required keys: first_name, last_name, email, booking_reference
 * @return bool true on success, false on failure
 *
 * Usage:
 *   sendPaymentReceipt($paymentData, $bookingData);
 */
function sendPaymentReceipt(array $payment, array $booking): bool
{
    // === PREPARE VARIABLES ===
    $name = htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']);
    $ref = htmlspecialchars($booking['booking_reference']);
    $amount = formatPrice($payment['amount'], 2);

    // Format payment method: "credit_card" → "Credit card"
    // str_replace converts underscores to spaces, ucfirst capitalizes first letter
    $method = ucfirst(str_replace('_', ' ', $payment['payment_method'] ?? ''));
    $txnId = htmlspecialchars($payment['transaction_id']);
    $date = date('d M Y', strtotime($payment['created_at']));
    $lastFour = htmlspecialchars($payment['card_last_four'] ?? '');

    // === BUILD HTML EMAIL CONTENT ===
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

// =============================================================================
// BOOKING STATUS UPDATE NOTIFICATION
// =============================================================================
/**
 * Send booking status update email to the customer.
 *
 * Displays a visual "old status → new status" transition with
 * color-coded status badges:
 *   - confirmed: green (#286f45)
 *   - cancelled: red (#ba1a1a)
 *   - pending: yellow (#e6a817)
 *   - default: teal (#264653)
 *
 * @param array  $booking   Booking data from the database
 *   Required keys: first_name, last_name, email, booking_reference, status
 * @param string $oldStatus The previous booking status (before the change)
 * @return bool true on success, false on failure
 *
 * Usage:
 *   sendBookingStatusUpdate($bookingData, 'pending');
 */
function sendBookingStatusUpdate(array $booking, string $oldStatus): bool
{
    // === PREPARE VARIABLES ===
    $name = htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']);
    $ref = htmlspecialchars($booking['booking_reference']);
    $newStatus = ucfirst(htmlspecialchars($booking['status']));
    $oldStatusDisplay = ucfirst(htmlspecialchars($oldStatus));

    // === STATUS-TO-COLOR MAPPING ===
    // Maps booking status to email badge color
    $statusColors = [
        'confirmed' => '#286f45',  // Green
        'cancelled' => '#ba1a1a',  // Red
        'pending'   => '#e6a817',  // Yellow
    ];
    // Default to teal for unknown statuses
    $color = $statusColors[$booking['status']] ?? '#264653';

    // === BUILD HTML EMAIL CONTENT ===
    // Visual status transition display: "Pending → Confirmed"
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

// =============================================================================
// INQUIRY REPLY NOTIFICATION
// =============================================================================
/**
 * Send inquiry reply notification email to the customer.
 *
 * Looks up the user's email from the database, then sends an email
 * notification about a new reply to their support inquiry.
 *
 * The reply message is displayed in a left-bordered blockquote style
 * to visually distinguish it from the rest of the email.
 *
 * @param array $inquiry Inquiry data from the database
 *   Required keys: user_id, inquiry_id_code, subject
 * @param array $reply Reply data from the database
 *   Required keys: message
 * @return bool true on success, false if user not found
 *
 * Usage:
 *   sendInquiryReplyNotification($inquiryData, $replyData);
 */
function sendInquiryReplyNotification(array $inquiry, array $reply): bool
{
    // === LOOK UP USER EMAIL ===
    // The inquiry only contains user_id, so we need to fetch the email
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $stmt = $db->prepare("SELECT email, full_name FROM users WHERE id = :uid LIMIT 1");
    $stmt->execute([':uid' => $inquiry['user_id']]);
    $user = $stmt->fetch();

    // Guard clause: if user not found, we can't send the email
    if (!$user) return false;

    // === PREPARE VARIABLES ===
    $name = htmlspecialchars($user['full_name']);
    $code = htmlspecialchars($inquiry['inquiry_id_code']);
    $subject = htmlspecialchars($inquiry['subject']);

    // nl2br() preserves newlines in the HTML output
    // htmlspecialchars() prevents XSS injection
    $message = nl2br(htmlspecialchars($reply['message']));

    // === BUILD HTML EMAIL CONTENT ===
    // The reply message is displayed in a styled blockquote
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
