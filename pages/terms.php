<?php
/**
 * File: pages/terms.php
 * Purpose: Terms and Conditions page outlining legal agreements for using GlobeTrek services
 * Dependencies: css/style.css, css/navbar.css, css/support.css, css/footer.css, includes/navbar.php, includes/footer.php, js/script.js
 * Used By: Main website navigation, footer links, signup page
 * Parent Files: index.php (via navigation), navbar.php (via link), signup.php (via link)
 * Child Files: None
 * @package GlobeTrek\Pages
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms &amp; Conditions - GlobeTrek Adventures</title>
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
        <section class="support-hero" aria-labelledby="terms-title">
            <img class="support-hero-bg" src="https://images.unsplash.com/photo-1450101499163-c8848c66ca85?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0" alt="">
            <div class="support-hero-content">
                <h1 id="terms-title">Terms &amp; Conditions</h1>
                <p>Please read these terms carefully before using our services or booking a trip with GlobeTrek Adventures.</p>
            </div>
        </section>

        <!-- Terms Content Section -->
        <section class="support-content" aria-labelledby="terms-content-title">
            <!-- Section 1: Acceptance of Terms -->
            <h2 id="terms-content-title">1. Acceptance of Terms</h2>
            <div class="policy-section">
                <p>By accessing and using the GlobeTrek Adventures website and services, you agree to be bound by these Terms and Conditions. If you do not agree with any part of these terms, please do not use our platform. These terms apply to all visitors, users, and customers of GlobeTrek Adventures.</p>
                <p>GlobeTrek Adventures is operated by Insath Raif, a software engineering student and knowledgeable developer committed to providing a trustworthy and transparent travel platform for all users.</p>
            </div>

            <!-- Section 2: Booking Terms -->
            <h2>2. Booking Terms</h2>
            <div class="policy-section">
                <h3>2.1 Reservation Process</h3>
                <p>All bookings are subject to availability. A booking is only confirmed once you receive a confirmation email from GlobeTrek Adventures. The person making the booking must be at least 18 years of age and accepts responsibility for all persons included in the booking.</p>

                <h3>2.2 Deposit &amp; Payment</h3>
                <p>A deposit of 30% of the total trip cost is required to confirm your booking. The remaining balance must be paid at least 14 days before the departure date. Failure to make the final payment by the due date may result in automatic cancellation of your booking.</p>

                <h3>2.3 Pricing</h3>
                <p>All prices are quoted in the local currency unless otherwise stated. Prices are subject to change without notice until a booking is confirmed. GlobeTrek Adventures reserves the right to correct any pricing errors that may occur on the website.</p>
            </div>

            <!-- Section 3: Cancellation & Refund Policy -->
            <h2>3. Cancellation &amp; Refund Policy</h2>
            <div class="policy-section">
                <h3>3.1 Cancellation by Customer</h3>
                <p>If you wish to cancel your booking, you must notify us in writing via email. The following cancellation fees apply:</p>
                <ul>
                    <li><strong>30 or more days before departure:</strong> Full refund of all payments made</li>
                    <li><strong>15 to 29 days before departure:</strong> 50% refund of the total trip cost</li>
                    <li><strong>Less than 15 days before departure:</strong> No refund</li>
                </ul>

                <h3>3.2 Cancellation by GlobeTrek Adventures</h3>
                <p>We reserve the right to cancel any trip due to unforeseen circumstances, including but not limited to natural disasters, political instability, or insufficient group size. In such cases, you will be offered a full refund or the option to transfer your booking to an alternative date or tour.</p>

                <h3>3.3 Refund Processing</h3>
                <p>Refunds will be processed within 14 business days of the cancellation approval. Refunds will be made to the original payment method used during booking.</p>
            </div>

            <!-- Section 4: Travel Requirements -->
            <h2>4. Travel Requirements</h2>
            <div class="policy-section">
                <p>Travelers are responsible for ensuring they meet all entry requirements for their destination, including valid passports, visas, and any required health documentation. GlobeTrek Adventures is not liable for any costs incurred due to failure to meet travel requirements.</p>
                <p>We recommend that all travelers obtain comprehensive travel insurance to cover unexpected events such as medical emergencies, trip cancellations, and lost belongings.</p>
            </div>

            <!-- Section 5: Health & Safety -->
            <h2>5. Health &amp; Safety</h2>
            <div class="policy-section">
                <p>Your safety is our top priority. All travelers must follow the instructions of our guides and local authorities. GlobeTrek Adventures reserves the right to refuse or terminate participation in any activity if a traveler's behavior poses a risk to themselves or others.</p>
                <p>Travelers with pre-existing medical conditions should inform us at the time of booking so we can make appropriate arrangements.</p>
            </div>

            <!-- Section 6: Limitation of Liability -->
            <h2>6. Limitation of Liability</h2>
            <div class="policy-section">
                <p>GlobeTrek Adventures acts as an intermediary between travelers and service providers (airlines, hotels, local operators). We are not liable for any loss, damage, injury, or delay caused by third-party service providers.</p>
                <p>In no event shall GlobeTrek Adventures, its founder Insath Raif, or its team be liable for any indirect, incidental, or consequential damages arising from the use of our services.</p>
            </div>

            <!-- Section 7: Intellectual Property -->
            <h2>7. Intellectual Property</h2>
            <div class="policy-section">
                <p>All content on the GlobeTrek Adventures website, including text, images, logos, and software, is the intellectual property of GlobeTrek Adventures and Insath Raif. Unauthorized reproduction, distribution, or modification of any content is strictly prohibited.</p>
            </div>

            <!-- Section 8: Governing Law -->
            <h2>8. Governing Law</h2>
            <div class="policy-section">
                <p>These Terms and Conditions are governed by and construed in accordance with the laws of Sri Lanka. Any disputes arising from these terms shall be subject to the exclusive jurisdiction of the courts in Colombo, Sri Lanka.</p>
            </div>

            <!-- Section 9: Changes to Terms -->
            <h2>9. Changes to Terms</h2>
            <div class="policy-section">
                <p>GlobeTrek Adventures reserves the right to modify these Terms and Conditions at any time. Changes will be effective immediately upon posting on the website. Your continued use of the platform after any changes constitutes acceptance of the updated terms.</p>
            </div>

            <!-- Section 10: Contact Information -->
            <h2>10. Contact Information</h2>
            <div class="policy-section">
                <p>If you have any questions about these Terms and Conditions, please contact us:</p>
                <ul>
                    <li>Email: <a href="mailto:info@globetrek.lk">info@globetrek.lk</a></li>
                    <li>Phone: +94 11 234 5678</li>
                    <li>Address: 123, Main Street, Negombo, Sri Lanka</li>
                </ul>
            </div>

            <!-- Developer Credit -->
            <div class="developer-credit">
                <p>
                    These Terms &amp; Conditions were carefully crafted by <span class="dev-name">Insath Raif</span>, a software engineering student and knowledgeable developer, to ensure transparency and protect the rights of both GlobeTrek Adventures and our valued customers.
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