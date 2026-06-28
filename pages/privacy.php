<?php
/**
 * File: pages/privacy.php
 * Purpose: Privacy Policy page detailing how GlobeTrek collects, uses, and protects user data
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
    <title>Privacy Policy - GlobeTrek Adventures</title>
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
        <section class="support-hero" aria-labelledby="privacy-title">
            <img class="support-hero-bg" src="https://images.unsplash.com/photo-1558618666-fcd25c85f82e?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0" alt="">
            <div class="support-hero-content">
                <h1 id="privacy-title">Privacy Policy</h1>
                <p>Your privacy is important to us. Learn how GlobeTrek Adventures collects, uses, and protects your personal information.</p>
            </div>
        </section>

        <!-- Privacy Policy Content Section -->
        <section class="support-content" aria-labelledby="privacy-content-title">
            <!-- Section 1: Information We Collect -->
            <h2 id="privacy-content-title">1. Information We Collect</h2>
            <div class="policy-section">
                <h3>1.1 Personal Information</h3>
                <p>When you create an account, make a booking, or contact us, we may collect the following personal information:</p>
                <ul>
                    <li>Full name</li>
                    <li>Email address</li>
                    <li>Phone number</li>
                    <li>Mailing address</li>
                    <li>Payment information (credit/debit card details)</li>
                    <li>Passport details (for international travel bookings)</li>
                    <li>Travel preferences and dietary requirements</li>
                </ul>

                <h3>1.2 Automatically Collected Information</h3>
                <p>When you visit our website, we automatically collect certain information, including:</p>
                <ul>
                    <li>IP address and browser type</li>
                    <li>Device information and operating system</li>
                    <li>Pages visited and time spent on our site</li>
                    <li>Referring website or source</li>
                    <li>Cookies and similar tracking technologies</li>
                </ul>
            </div>

            <!-- Section 2: How We Use Your Information -->
            <h2>2. How We Use Your Information</h2>
            <div class="policy-section">
                <p>We use the collected information for the following purposes:</p>
                <ul>
                    <li><strong>Booking Management:</strong> To process and confirm your travel bookings, manage payments, and send booking-related communications</li>
                    <li><strong>Customer Service:</strong> To respond to your inquiries, provide support during your trip, and resolve any issues</li>
                    <li><strong>Account Management:</strong> To create and manage your user account, maintain your booking history, and personalize your experience</li>
                    <li><strong>Marketing:</strong> To send you newsletters, promotional offers, and updates about our services (with your consent)</li>
                    <li><strong>Improvement:</strong> To analyze usage patterns and improve our website, services, and user experience</li>
                    <li><strong>Legal Compliance:</strong> To comply with applicable laws, regulations, and legal processes</li>
                </ul>
            </div>

            <!-- Section 3: Information Sharing -->
            <h2>3. Information Sharing</h2>
            <div class="policy-section">
                <p>We may share your information with the following third parties:</p>
                <ul>
                    <li><strong>Travel Service Providers:</strong> Airlines, hotels, tour operators, and local guides to fulfill your booking</li>
                    <li><strong>Payment Processors:</strong> Secure third-party payment providers to process your transactions</li>
                    <li><strong>Legal Authorities:</strong> When required by law or to protect the rights and safety of GlobeTrek Adventures and its users</li>
                    <li><strong>Business Partners:</strong> With your consent, we may share information with trusted partners for promotional purposes</li>
                </ul>
                <p>We do not sell, rent, or trade your personal information to third parties for their independent marketing purposes.</p>
            </div>

            <!-- Section 4: Data Security -->
            <h2>4. Data Security</h2>
            <div class="policy-section">
                <p>GlobeTrek Adventures implements industry-standard security measures to protect your personal information, including:</p>
                <ul>
                    <li>SSL/TLS encryption for all data transmitted between your browser and our servers</li>
                    <li>Secure storage of payment information using PCI-compliant systems</li>
                    <li>Regular security audits and vulnerability assessments</li>
                    <li>Access controls and authentication protocols for our systems</li>
                </ul>
                <p>While we strive to protect your information, no method of transmission over the Internet is 100% secure. We cannot guarantee absolute security but are committed to maintaining the highest standards possible.</p>
            </div>

            <!-- Section 5: Cookies & Tracking Technologies -->
            <h2>5. Cookies &amp; Tracking Technologies</h2>
            <div class="policy-section">
                <p>We use cookies and similar technologies to enhance your browsing experience. Types of cookies we use include:</p>
                <ul>
                    <li><strong>Essential Cookies:</strong> Required for the website to function properly (e.g., session management, login)</li>
                    <li><strong>Analytics Cookies:</strong> Help us understand how visitors interact with our website</li>
                    <li><strong>Marketing Cookies:</strong> Used to deliver relevant advertisements and track campaign effectiveness</li>
                </ul>
                <p>You can control cookie preferences through your browser settings. Disabling certain cookies may affect website functionality.</p>
            </div>

            <!-- Section 6: Your Rights -->
            <h2>6. Your Rights</h2>
            <div class="policy-section">
                <p>Under applicable data protection laws, you have the following rights regarding your personal information:</p>
                <ul>
                    <li><strong>Access:</strong> Request a copy of the personal data we hold about you</li>
                    <li><strong>Correction:</strong> Request correction of inaccurate or incomplete data</li>
                    <li><strong>Deletion:</strong> Request deletion of your personal data (subject to legal retention requirements)</li>
                    <li><strong>Opt-Out:</strong> Unsubscribe from marketing communications at any time</li>
                    <li><strong>Data Portability:</strong> Request your data in a structured, commonly used format</li>
                </ul>
                <p>To exercise any of these rights, please contact us at <a href="mailto:info@globetrek.lk">info@globetrek.lk</a>.</p>
            </div>

            <!-- Section 7: Data Retention -->
            <h2>7. Data Retention</h2>
            <div class="policy-section">
                <p>We retain your personal information only for as long as necessary to fulfill the purposes for which it was collected, including:</p>
                <ul>
                    <li>Account information: Retained as long as your account is active</li>
                    <li>Booking records: Retained for 7 years for legal and tax purposes</li>
                    <li>Marketing preferences: Retained until you opt out</li>
                    <li>Analytics data: Retained in aggregated, anonymized form</li>
                </ul>
            </div>

            <!-- Section 8: Children's Privacy -->
            <h2>8. Children's Privacy</h2>
            <div class="policy-section">
                <p>GlobeTrek Adventures does not knowingly collect personal information from children under the age of 16. If we become aware that we have collected personal information from a child without parental consent, we will take steps to delete that information promptly.</p>
            </div>

            <!-- Section 9: Changes to This Policy -->
            <h2>9. Changes to This Policy</h2>
            <div class="policy-section">
                <p>We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements. We will notify you of any material changes by posting the updated policy on our website and updating the "Last Updated" date below.</p>
                <p><strong>Last Updated:</strong> June 2026</p>
            </div>

            <!-- Section 10: Contact Us -->
            <h2>10. Contact Us</h2>
            <div class="policy-section">
                <p>If you have any questions or concerns about this Privacy Policy, please contact us:</p>
                <ul>
                    <li>Email: <a href="mailto:info@globetrek.lk">info@globetrek.lk</a></li>
                    <li>Phone: +94 11 234 5678</li>
                    <li>Address: 123, Main Street, Negombo, Sri Lanka</li>
                </ul>
            </div>

            <!-- Developer Credit -->
            <div class="developer-credit">
                <p>
                    This Privacy Policy was developed by <span class="dev-name">Insath Raif</span>, a software engineering student and knowledgeable developer, with a strong commitment to data privacy and user protection. Insath Raif ensures that GlobeTrek Adventures adheres to the highest standards of data security and transparency.
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