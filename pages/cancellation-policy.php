/**
 * File: pages/cancellation-policy.php
 * Purpose: Cancellation Policy page detailing terms for cancelling bookings and refund eligibility
 * Dependencies: css/style.css, css/navbar.css, css/support.css, css/footer.css, includes/navbar.php, includes/footer.php, js/script.js
 * Used By: Main website navigation, footer links, FAQ page, booking pages
 * Parent Files: index.php (via navigation), navbar.php (via link), faq.php (via link), contact.php (via FAQ section)
 * Child Files: None
 * @package GlobeTrek\Pages
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancellation Policy - GlobeTrek Adventures</title>
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
        <section class="support-hero" aria-labelledby="cancellation-title">
            <img class="support-hero-bg" src="https://images.unsplash.com/photo-1436491865332-7a61a109db05?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0" alt="">
            <div class="support-hero-content">
                <h1 id="cancellation-title">Cancellation Policy</h1>
                <p>Learn about our cancellation terms, refund eligibility, and how to cancel a booking with GlobeTrek Adventures.</p>
            </div>
        </section>

        <!-- Cancellation Policy Content Section -->
        <section class="support-content" aria-labelledby="cancellation-content-title">
            <!-- Section 1: Overview -->
            <h2 id="cancellation-content-title">1. Overview</h2>
            <div class="policy-section">
                <p>This Cancellation Policy applies to all bookings made through GlobeTrek Adventures, including tour packages, accommodation reservations, and transportation rentals. We understand that plans can change, and we aim to be fair and transparent with our cancellation terms.</p>
                <p>The cancellation date is determined by the date we receive your written cancellation request via email. Refund eligibility is calculated based on the number of days between your cancellation date and the scheduled departure or check-in date.</p>
            </div>

            <!-- Section 2: Cancellation by Customer -->
            <h2>2. Cancellation by Customer</h2>
            <div class="policy-section">
                <h3>2.1 Standard Cancellation Tiers</h3>
                <p>If you need to cancel your booking, the following refund terms apply:</p>
                <ul>
                    <li><strong>30 or more days before departure/check-in:</strong> Full refund (100%) of all payments made</li>
                    <li><strong>15 to 29 days before departure/check-in:</strong> 50% refund of the total booking cost</li>
                    <li><strong>Less than 15 days before departure/check-in:</strong> No refund</li>
                </ul>

                <h3>2.2 Group Bookings</h3>
                <p>For group bookings of 8 or more travelers, a non-refundable deposit of 40% is required. Cancellation of group bookings follows the same tiered structure, but the 40% deposit is non-refundable regardless of when the cancellation is made.</p>

                <h3>2.3 Last-Minute Bookings</h3>
                <p>For bookings made within 14 days of the departure or check-in date, the full payment is non-refundable if cancellation occurs within 7 days of the booking date. Cancellations made more than 7 days before departure/check-in will receive a 50% refund.</p>
            </div>

            <!-- Section 3: How to Cancel -->
            <h2>3. How to Cancel</h2>
            <div class="policy-section">
                <p>To cancel a booking, you must submit a written cancellation request via email to <a href="mailto:info@globetrek.lk">info@globetrek.lk</a>. Please include the following information in your request:</p>
                <ul>
                    <li>Booking reference number (e.g., GT-XXXXXX)</li>
                    <li>Full name of the primary traveler</li>
                    <li>Reason for cancellation (optional, but helps us improve our services)</li>
                    <li>Preferred refund method (if different from original payment method)</li>
                </ul>
                <p>Cancellation requests received by phone or through other channels will not be accepted. Only written requests via email will be processed.</p>
                <p>Once we receive your request, you will receive an acknowledgment email within 24 hours, followed by a confirmation of the cancellation and refund details within 3 business days.</p>
            </div>

            <!-- Section 4: Cancellation by GlobeTrek Adventures -->
            <h2>4. Cancellation by GlobeTrek Adventures</h2>
            <div class="policy-section">
                <p>We reserve the right to cancel any booking due to unforeseen circumstances, including but not limited to:</p>
                <ul>
                    <li>Natural disasters or extreme weather conditions</li>
                    <li>Political instability or government advisories</li>
                    <li>Insufficient group size to operate the tour</li>
                    <li>Safety concerns or health emergencies</li>
                    <li>Force majeure events beyond our control</li>
                </ul>
                <p>In the event of a cancellation by GlobeTrek Adventures, you will be offered one of the following options:</p>
                <ul>
                    <li>A full refund of all payments made</li>
                    <li>Rebooking to an alternative date at no additional cost</li>
                    <li>Transfer to an alternative tour or package of equivalent value</li>
                </ul>
            </div>

            <!-- Section 5: Package Cancellations -->
            <h2>5. Package Cancellations</h2>
            <div class="policy-section">
                <p>Cancellation of tour packages (e.g., Island Escape, Wild Safari, Mountain Explorer) follows the standard cancellation tiers outlined in Section 2.1. The following additional terms apply to packages:</p>
                <ul>
                    <li>Pre-booked excursions or activities included in the package are subject to the same refund terms as the overall package</li>
                    <li>Special permits or entry fees that have been pre-purchased on your behalf may be non-refundable, and this will be clearly indicated at the time of booking</li>
                    <li>If a portion of the package has already been consumed, the refund will be calculated based on the remaining unused portion</li>
                </ul>
            </div>

            <!-- Section 6: Accommodation Cancellations -->
            <h2>6. Accommodation Cancellations</h2>
            <div class="policy-section">
                <p>Cancellation of accommodation bookings (hotels, villas, resorts, boutique properties) follows the standard cancellation tiers. Additional terms for accommodations include:</p>
                <ul>
                    <li>Peak season bookings (December to April) may have stricter cancellation terms, which will be communicated at the time of booking</li>
                    <li>Special rates or promotional offers may be non-refundable or subject to different cancellation terms</li>
                    <li>Early check-out requests are treated as cancellations for the remaining nights and are subject to the standard refund tiers</li>
                </ul>
            </div>

            <!-- Section 7: Transportation Cancellations -->
            <h2>7. Transportation Cancellations</h2>
            <div class="policy-section">
                <p>Cancellation of transportation rentals (tuk-tuks, cars, bikes, minivans) follows the standard cancellation tiers. Additional terms for transportation include:</p>
                <ul>
                    <li>Cancellations made on the scheduled pick-up date or later are non-refundable</li>
                    <li>Driver-inclusive rentals may have additional cancellation fees if the driver has already been dispatched</li>
                    <li>Insurance add-ons selected at the time of booking are non-refundable once the rental period has begun</li>
                </ul>
            </div>

            <!-- Section 8: Non-Refundable Items -->
            <h2>8. Non-Refundable Items</h2>
            <div class="policy-section">
                <p>The following items and fees are generally non-refundable under any circumstances:</p>
                <ul>
                    <li>Third-party service fees (visa application fees, travel insurance premiums)</li>
                    <li>Non-refundable airline tickets or third-party hotel bookings</li>
                    <li>Special excursion or activity fees once confirmed</li>
                    <li>Administrative or processing fees</li>
                    <li>Custom trip request processing fees</li>
                </ul>
                <p>Any non-refundable components will be clearly indicated at the time of booking. If you are unsure about any item, please contact us before confirming your booking.</p>
            </div>

            <!-- Section 9: Refund Processing -->
            <h2>9. Refund Processing</h2>
            <div class="policy-section">
                <p>Approved refunds will be processed within <strong>14 business days</strong> from the date of cancellation approval. Refunds will be issued to the original payment method used during booking.</p>
                <p>Please note that while we process refunds promptly, it may take an additional 5-10 business days for the refund to appear in your account, depending on your bank or payment provider.</p>
                <p>If you have not received your refund within 25 business days of the cancellation approval date, please contact us at <a href="mailto:info@globetrek.lk">info@globetrek.lk</a> so we can investigate.</p>
            </div>

            <!-- Section 10: Contact Us -->
            <h2>10. Contact Us</h2>
            <div class="policy-section">
                <p>For any questions regarding cancellations, refunds, or this policy, please contact us:</p>
                <ul>
                    <li>Email: <a href="mailto:info@globetrek.lk">info@globetrek.lk</a></li>
                    <li>Phone: +94 11 234 5678</li>
                    <li>Address: 123, Main Street, Negombo, Sri Lanka</li>
                </ul>
            </div>

            <!-- Developer Credit -->
            <div class="developer-credit">
                <p>
                    This Cancellation Policy was designed and implemented by <span class="dev-name">Insath Raif</span>, a software engineering student and knowledgeable developer. Insath Raif built the booking management system of GlobeTrek Adventures, ensuring fair and transparent cancellation terms for all customers.
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