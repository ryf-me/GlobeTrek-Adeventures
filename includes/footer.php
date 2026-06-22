<?php
$newsletterMessage = '';
$newsletterMessageClass = '';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_email'])) {
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();

    $email = filter_var(trim($_POST['newsletter_email']), FILTER_SANITIZE_EMAIL);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $checkStmt = $db->prepare("SELECT id FROM newsletter_subscriptions WHERE email = :email LIMIT 1");
        $checkStmt->execute([':email' => $email]);
        if ($checkStmt->fetch()) {
            $newsletterMessage = 'This email is already subscribed.';
            $newsletterMessageClass = 'error';
        } else {
            $insertStmt = $db->prepare("INSERT INTO newsletter_subscriptions (email) VALUES (:email)");
            $insertStmt->execute([':email' => $email]);
            $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            $newsletterMessage = "Thank you! $safeEmail has been subscribed.";
            $newsletterMessageClass = 'success';
        }
    } else {
        $newsletterMessage = 'Please enter a valid email address.';
        $newsletterMessageClass = 'error';
    }
}
?>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<!-- Footer Section-->
    <footer class="site-footer">
        <div class="footer-main">
            <div class="footer-brand">
                <div class="footer-logo">
                    <img src="<?php echo $basePath; ?>images/logo.png" alt="Globe Trek Adventures logo" />
                    <span class="brand">GlobeTrek</span>
                </div>
                <address>
                    123, Main Street, Negombo<br />
                    +94 11 234 5678<br />
                    <a href="mailto:info@globetrek.lk">info@globetrek.lk</a>
                </address>
            </div>

            <div class="footer-column">
                <h2>Quick Links</h2>
                <ul>
                    <li><a href="<?php echo $basePath; ?>index.php#home">Home</a></li>
                    <li><a href="<?php echo $basePath; ?>pages/packages.php">Packages</a></li>
                    <li><a href="<?php echo $basePath; ?>pages/destinations.php">Destinations</a></li>
                    <li><a href="<?php echo $basePath; ?>pages/guides.php">Guides</a></li>
                    <li><a href="<?php echo $basePath; ?>pages/accommodations.php">Accommodations</a></li>
                    <li><a href="<?php echo $basePath; ?>pages/transportation.php">Transportation</a></li>
                    <li><a href="<?php echo $basePath; ?>pages/custom-trips.php">Custom Trips</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h2>Support</h2>
                <ul>
                    <li><a href="<?php echo $basePath; ?>pages/faq.php">FAQ</a></li>
                    <li><a href="<?php echo $basePath; ?>pages/terms.php">Terms &amp; Conditions</a></li>
                    <li><a href="<?php echo $basePath; ?>pages/privacy.php">Privacy Policy</a></li>
                    <li><a href="<?php echo $basePath; ?>pages/payment-policy.php">Payment Policy</a></li>
                </ul>
            </div>

            <div class="footer-column footer-newsletter">
                <h2>Newsletter</h2>
                <p>Stay updated with our latest offers.</p>
                <form class="newsletter-form-footer" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <label class="sr-only" for="footer-newsletter-email">Email address</label>
                    <input id="footer-newsletter-email" type="email" name="newsletter_email" placeholder="Enter your email" required />
                    <button type="submit">Subscribe</button>
                </form>
                <?php if ($newsletterMessage !== ''): ?>
                    <p class="newsletter-message <?php echo $newsletterMessageClass; ?>"><?php echo $newsletterMessage; ?></p>
                <?php endif; ?>
                <div class="footer-socials" aria-label="Social links">
                    <a href="#" aria-label="Share GlobeTrek">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="18" cy="5" r="3" />
                            <circle cx="6" cy="12" r="3" />
                            <circle cx="18" cy="19" r="3" />
                            <path d="M8.7 10.7l6.6-3.4M8.7 13.3l6.6 3.4" />
                        </svg>
                    </a>
                    <a href="#" aria-label="Follow GlobeTrek on Instagram">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="5" y="5" width="14" height="14" rx="4" />
                            <circle cx="12" cy="12" r="3" />
                            <path d="M16.5 7.5h.1" />
                        </svg>
                    </a>
                    <a href="#" aria-label="Email GlobeTrek">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="4" y="6" width="16" height="12" rx="2" />
                            <path d="M5 8l7 5 7-5" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 GlobeTrek Adventures. All rights reserved.</p>
        </div>
        
    <!-- Inquiries Floating Button -->
<a href="<?php echo $basePath; ?><?php echo isset($_SESSION['user_id']) ? 'pages/inquiries.php' : 'pages/login.php'; ?>" class="inquiries-fab" aria-label="Inquiries">
    <span class="material-symbols-outlined">chat_bubble</span>
</a>
    </footer>
