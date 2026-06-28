/**
 * File: pages/payment-policy.php
 * Purpose: Payment Policy page detailing payment terms, accepted methods, and refund procedures
 * Dependencies: css/style.css, css/navbar.css, css/support.css, css/footer.css, includes/navbar.php, includes/footer.php, js/script.js
 * Used By: Main website navigation, footer links, FAQ page, booking pages
 * Parent Files: index.php (via navigation), navbar.php (via link), faq.php (via link)
 * Child Files: None
 * @package GlobeTrek\Pages
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Policy - GlobeTrek Adventures</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/support.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="support-page">
    <!-- Navigation Bar -->
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="support-hero" aria-labelledby="payment-title">
            <img class="support-hero-bg" src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0" alt="">
            <div class="support-hero-content">
                <h1 id="payment-title">Payment Policy</h1>
                <p>Understand our payment terms, accepted methods, and refund procedures for all GlobeTrek Adventures bookings.</p>
            </div>
        </section>

        <!-- Payment Policy Content Section -->
        <section class="support-content" aria-labelledby="payment-content-title">
            <!-- Section 1: Accepted Payment Methods -->
            <h2 id="payment-content-title">1. Accepted Payment Methods</h2>
            <div class="policy-section">
                <p>GlobeTrek Adventures accepts the following payment methods for all bookings and services:</p>
                <ul>
                    <li><strong>Credit Cards:</strong> Visa, MasterCard, American Express</li>
                    <li><strong>Debit Cards:</strong> Visa Debit, MasterCard Debit</li>
                    <li><strong>Bank Transfers:</strong> Direct bank transfers to our designated accounts</li>
                    <li><strong>Digital Payments:</strong> Popular digital payment platforms (available in select regions)</li>
                </ul>
                <p>All payment transactions are securely processed using industry-standard SSL/TLS encryption and PCI-compliant payment gateways to ensure your financial information is protected.</p>
            </div>

            <!-- Section 2: Payment Schedule -->
            <h2>2. Payment Schedule</h2>
            <div class="policy-section">
                <h3>2.1 Deposit</h3>
                <p>A deposit of <strong>30% of the total trip cost</strong> is required at the time of booking to secure your reservation. Your booking is only confirmed once the deposit has been received and a confirmation email has been sent.</p>

                <h3>2.2 Final Payment</h3>
                <p>The remaining balance (70%) must be paid at least <strong>14 days before the departure date</strong>. Payment reminders will be sent to your registered email address.</p>

                <h3>2.3 Last-Minute Bookings</h3>
                <p>For bookings made within 14 days of the departure date, <strong>full payment (100%)</strong> is required at the time of booking.</p>
            </div>

            <!-- Section 3: Currency & Pricing -->
            <h2>3. Currency &amp; Pricing</h2>
            <div class="policy-section">
                <p>All prices displayed on the GlobeTrek Adventures website are in the local currency (Sri Lankan Rupee - LKR) unless otherwise specified. For international customers, your bank or payment provider may apply currency conversion fees at the time of transaction.</p>
                <p>Prices are subject to change without prior notice until a booking is confirmed. Once confirmed, the price stated in your confirmation email is final and will not be adjusted due to subsequent price changes.</p>
            </div>

            <!-- Section 4: Cancellation Refunds -->
            <h2>4. Cancellation Refunds</h2>
            <div class="policy-section">
                <p>If you need to cancel your booking, the following refund terms apply based on when the cancellation is made:</p>
                <ul>
                    <li><strong>30 or more days before departure:</strong> Full refund (100%) of all payments made</li>
                    <li><strong>15 to 29 days before departure:</strong> 50% refund of the total trip cost</li>
                    <li><strong>Less than 15 days before departure:</strong> No refund</li>
                </ul>
                <p>All cancellation requests must be submitted in writing via email to <a href="mailto:info@globetrek.lk">info@globetrek.lk</a>. The cancellation date is determined by the date we receive your written request.</p>
            </div>

            <!-- Section 5: Refund Processing -->
            <h2>5. Refund Processing</h2>
            <div class="policy-section">
                <p>Approved refunds will be processed within <strong>14 business days</strong> from the date of cancellation approval. Refunds will be issued to the original payment method used during booking.</p>
                <p>Please note that while we process refunds promptly, it may take an additional 5-10 business days for the refund to appear in your account, depending on your bank or payment provider.</p>
            </div>

            <!-- Section 6: Non-Refundable Items -->
            <h2>6. Non-Refundable Items</h2>
            <div class="policy-section">
                <p>The following are generally non-refundable:</p>
                <ul>
                    <li>Third-party service fees (visa application fees, travel insurance premiums)</li>
                    <li>Non-refundable airline tickets or hotel bookings</li>
                    <li>Special excursion or activity fees once confirmed</li>
                    <li>Administrative or processing fees</li>
                </ul>
                <p>Any non-refundable components will be clearly indicated at the time of booking.</p>
            </div>

            <!-- Section 7: Booking Modifications -->
            <h2>7. Booking Modifications</h2>
            <div class="policy-section">
                <p>If you wish to modify your booking (change dates, upgrade accommodations, add activities), please contact us as soon as possible. Modifications are subject to availability and may incur additional charges.</p>
                <p>Date changes made 30 or more days before departure are free of charge (subject to availability). Changes made within 29 days of departure may incur a modification fee of up to 20% of the trip cost.</p>
            </div>

            <!-- Section 8: Group Bookings -->
            <h2>8. Group Bookings</h2>
            <div class="policy-section">
                <p>For group bookings of 8 or more travelers, special payment terms may apply. A custom payment schedule will be provided with your group booking quotation. A non-refundable group deposit of 40% is required to secure group reservations.</p>
            </div>

            <!-- Section 9: Fraud Prevention -->
            <h2>9. Fraud Prevention</h2>
            <div class="policy-section">
                <p>GlobeTrek Adventures reserves the right to verify the identity of the cardholder and request additional documentation for payment verification. We may cancel bookings that appear fraudulent or suspicious to protect both our customers and our business.</p>
                <p>All payment data is handled securely by Insath Raif and the development team, following best practices in secure payment processing.</p>
            </div>

            <!-- Section 10: Contact Us -->
            <h2>10. Contact Us</h2>
            <div class="policy-section">
                <p>For any questions regarding payments, refunds, or billing, please contact us:</p>
                <ul>
                    <li>Email: <a href="mailto:info@globetrek.lk">info@globetrek.lk</a></li>
                    <li>Phone: +94 11 234 5678</li>
                    <li>Address: 123, Main Street, Negombo, Sri Lanka</li>
                </ul>
            </div>

            <!-- Developer Credit -->
            <div class="developer-credit">
                <p>
                    This Payment Policy was designed and implemented by <span class="dev-name">Insath Raif</span>, a software engineering student and knowledgeable developer. Insath Raif built the secure payment infrastructure of GlobeTrek Adventures, ensuring safe and transparent financial transactions for all customers.
                </p>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="../js/script.js"></script>
</body>
</html>