<?php
/**
 * File: pages/contact.php
 * Purpose: Contact form page for users to send messages to GlobeTrek support
 * Dependencies: config/database.php, config/csrf.php, config/rate-limiter.php, css/style.css, css/navbar.css, css/contact.css, css/footer.php, includes/navbar.php, includes/footer.php, js/script.js
 * Used By: Main website navigation, footer links
 * Parent Files: index.php (via navigation), navbar.php (via link)
 * Child Files: None
 * @package GlobeTrek\Pages
 */

session_start();

// === DATABASE AND SECURITY SETUP ===
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/rate-limiter.php';
$db = getDB();

// === FORM FIELD INITIALIZATION ===
$fields = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'message' => '',
];
$errors = [];
$submitted = false;

// === FORM PROCESSING ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation for security
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    }

    // Rate limiting: 5 submissions per hour per IP to prevent spam
    if (empty($errors) && !checkRateLimit('contact_form', 5, 3600, false)) {
        $errors['general'] = 'Too many requests. Please try again later.';
    }

    // === FIELD VALIDATION ===
    if (empty($errors)) {
        $fields['name'] = trim($_POST['name'] ?? '');
        $fields['email'] = trim($_POST['email'] ?? '');
        $fields['phone'] = trim($_POST['phone'] ?? '');
        $fields['subject'] = trim($_POST['subject'] ?? '');
        $fields['message'] = trim($_POST['message'] ?? '');

        // Required field validation
        if ($fields['name'] === '') {
            $errors['name'] = 'Please enter your name.';
        }
        if ($fields['email'] === '') {
            $errors['email'] = 'Please enter your email address.';
        } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if ($fields['subject'] === '') {
            $errors['subject'] = 'Please select a subject.';
        }
        if ($fields['message'] === '') {
            $errors['message'] = 'Please enter your message.';
        }
    }

    $submitted = empty($errors);

    // === DATABASE INSERTION ===
    if ($submitted) {
        // Prepend phone number to message if provided
        $msg = $fields['message'];
        if ($fields['phone'] !== '') {
            $msg = '[Phone: ' . $fields['phone'] . "]\n\n" . $msg;
        }

        // Insert contact message into database
        $stmt = $db->prepare(
            "INSERT INTO contact_messages (name, email, subject, message)
             VALUES (:name, :email, :subject, :message)"
        );
        $stmt->execute([
            ':name' => $fields['name'],
            ':email' => $fields['email'],
            ':subject' => $fields['subject'],
            ':message' => $msg,
        ]);
        // Clear form fields after successful submission
        $fields = array_fill_keys(array_keys($fields), '');
    }
}

// === HELPER FUNCTIONS ===
function old_value(string $field, array $fields): string
{
    // Return escaped old form value for repopulation
    return htmlspecialchars($fields[$field] ?? '', ENT_QUOTES, 'UTF-8');
}

function field_error(string $field, array $errors): string
{
    // Return escaped error message for display
    return htmlspecialchars($errors[$field] ?? '', ENT_QUOTES, 'UTF-8');
}

function subject_selected(string $value, string $current): string
{
    // Return 'selected' attribute if this subject was previously chosen
    return $value === $current ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - GlobeTrek</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/contact.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="contact-page">
    <!-- Navigation Bar -->
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="c-hero" aria-labelledby="c-hero-title">
            <img class="c-hero-bg" src="https://images.pexels.com/photos/29813522/pexels-photo-29813522.jpeg" alt="">
            <div class="c-hero-content">
                <!-- Breadcrumb Navigation -->
                <nav class="c-breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo $basePath; ?>index.php">Home</a>
                    <span class="c-breadcrumb-sep material-symbols-outlined">chevron_right</span>
                    <span class="c-breadcrumb-current">Contact Us</span>
                </nav>
                <h1 id="c-hero-title">Contact Us</h1>
                <p class="c-hero-subtitle">We'd love to hear from you!</p>
                <p class="c-hero-desc">Have a question, need travel advice, or ready to plan your next adventure? Our team is here to help you every step of the way.</p>
                
                <!-- Trust Badges -->
                <div class="c-hero-badges">
                    <div class="c-hero-badge">
                        <div class="c-hero-badge-icon">
                            <span class="material-symbols-outlined">headset_mic</span>
                        </div>
                        <div class="c-hero-badge-text">
                            <strong>24/7 Support</strong>
                            <span>We're here anytime</span>
                        </div>
                    </div>
                    <div class="c-hero-badge">
                        <div class="c-hero-badge-icon">
                            <span class="material-symbols-outlined">verified_user</span>
                        </div>
                        <div class="c-hero-badge-text">
                            <strong>Trusted &amp; Secure</strong>
                            <span>Your data is safe</span>
                        </div>
                    </div>
                    <div class="c-hero-badge">
                        <div class="c-hero-badge-icon">
                            <span class="material-symbols-outlined">quickreply</span>
                        </div>
                        <div class="c-hero-badge-text">
                            <strong>Quick Response</strong>
                            <span>Get replies fast</span>
                        </div>
                    </div>
                    <div class="c-hero-badge">
                        <div class="c-hero-badge-icon">
                            <span class="material-symbols-outlined">explore</span>
                        </div>
                        <div class="c-hero-badge-text">
                            <strong>Local Experts</strong>
                            <span>Travel with confidence</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($submitted): ?>
            <!-- Success State -->
            <section class="c-success" role="status">
                <div class="c-success-inner">
                    <span class="material-symbols-outlined c-success-icon" aria-hidden="true">check_circle</span>
                    <h2>Message Sent!</h2>
                    <p>Thank you for reaching out. Our team will get back to you within 24 hours.</p>
                </div>
            </section>
        <?php else: ?>
            <!-- Contact Form + Sidebar -->
            <section class="c-contact-section" aria-labelledby="c-form-title">
                <!-- Send Us a Message -->
                <div class="c-form-card">
                    <h2 id="c-form-title">Send Us a Message</h2>
                    <p>Fill out the form below and our travel experts will get back to you shortly.</p>

                    <!-- Error Display -->
                    <?php if (!empty($errors)): ?>
                        <div class="c-form-alert error" role="alert">
                            Please review the highlighted fields and try again.
                        </div>
                    <?php endif; ?>

                    <!-- Contact Form -->
                    <form class="c-form" method="post" action="contact.php" novalidate>
                        <?php csrf_field(); ?>
                        <div class="c-form-row">
                            <!-- Name Field -->
                            <div class="c-field">
                                <label for="c-name">Your Name <span class="c-required">*</span></label>
                                <input id="c-name" name="name" type="text" value="<?= old_value('name', $fields) ?>" placeholder="John Doe" aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>">
                                <?php if (isset($errors['name'])): ?>
                                    <p class="c-field-error"><?= field_error('name', $errors) ?></p>
                                <?php endif; ?>
                            </div>
                            <!-- Email Field -->
                            <div class="c-field">
                                <label for="c-email">Email Address <span class="c-required">*</span></label>
                                <input id="c-email" name="email" type="email" value="<?= old_value('email', $fields) ?>" placeholder="john@example.com" aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <p class="c-field-error"><?= field_error('email', $errors) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="c-form-row">
                            <!-- Phone Field (Optional) -->
                            <div class="c-field">
                                <label for="c-phone">Phone Number</label>
                                <input id="c-phone" name="phone" type="tel" value="<?= old_value('phone', $fields) ?>" placeholder="+94 77 123 4567">
                            </div>
                            <!-- Subject Field -->
                            <div class="c-field">
                                <label for="c-subject">Subject <span class="c-required">*</span></label>
                                <select id="c-subject" name="subject" aria-invalid="<?= isset($errors['subject']) ? 'true' : 'false' ?>">
                                    <option value="" disabled <?= $fields['subject'] === '' ? 'selected' : '' ?>>Select a subject</option>
                                    <option value="General Inquiry" <?= subject_selected('General Inquiry', $fields['subject']) ?>>General Inquiry</option>
                                    <option value="Booking Question" <?= subject_selected('Booking Question', $fields['subject']) ?>>Booking Question</option>
                                    <option value="Custom Trip" <?= subject_selected('Custom Trip', $fields['subject']) ?>>Custom Trip</option>
                                    <option value="Support" <?= subject_selected('Support', $fields['subject']) ?>>Support</option>
                                    <option value="Feedback" <?= subject_selected('Feedback', $fields['subject']) ?>>Feedback</option>
                                    <option value="Other" <?= subject_selected('Other', $fields['subject']) ?>>Other</option>
                                </select>
                                <?php if (isset($errors['subject'])): ?>
                                    <p class="c-field-error"><?= field_error('subject', $errors) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Message Field -->
                        <div class="c-field">
                            <label for="c-message">Your Message <span class="c-required">*</span></label>
                            <textarea id="c-message" name="message" rows="5" placeholder="Tell us how we can help you..." aria-invalid="<?= isset($errors['message']) ? 'true' : 'false' ?>"><?= old_value('message', $fields) ?></textarea>
                            <?php if (isset($errors['message'])): ?>
                                <p class="c-field-error"><?= field_error('message', $errors) ?></p>
                            <?php endif; ?>
                        </div>
                        <!-- Submit Button -->
                        <div class="c-submit-row">
                            <button type="submit" class="c-submit-btn">
                                <span class="material-symbols-outlined" aria-hidden="true">send</span>
                                <span>Send Message</span>
                            </button>
                            <div class="c-privacy-note">
                                <span class="material-symbols-outlined" aria-hidden="true">lock</span>
                                <span>We respect your privacy and never share your information.</span>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Get in Touch Sidebar -->
                <div class="c-sidebar-card">
                    <h2>Get in Touch</h2>
                    <p>Reach out to us through any of these channels.</p>

                    <!-- Contact Information -->
                    <div class="c-contact-list">
                        <div class="c-contact-item">
                            <div class="c-contact-icon">
                                <span class="material-symbols-outlined">call</span>
                            </div>
                            <div class="c-contact-info">
                                <h3>Phone</h3>
                                <p>+94 77 123 4567<br>+94 11 234 5678</p>
                            </div>
                        </div>
                        <div class="c-contact-item">
                            <div class="c-contact-icon">
                                <span class="material-symbols-outlined">mail</span>
                            </div>
                            <div class="c-contact-info">
                                <h3>Email</h3>
                                <p><a href="mailto:info@globetrek.lk">info@globetrek.lk</a><br><a href="mailto:bookings@globetrek.lk">bookings@globetrek.lk</a></p>
                            </div>
                        </div>
                        <div class="c-contact-item c-map-pin-container">
                            <div class="c-contact-icon">
                                <span class="material-symbols-outlined">location_on</span>
                            </div>
                            <div class="c-contact-info">
                                <h3>Address</h3>
                                <p>123, Main Street,<br>Negombo, Sri Lanka</p>
                                <span class="c-map-pin-label">We are here!</span>
                            </div>
                        </div>
                        <div class="c-contact-item">
                            <div class="c-contact-icon">
                                <span class="material-symbols-outlined">schedule</span>
                            </div>
                            <div class="c-contact-info">
                                <h3>Office Hours</h3>
                                <p>Mon - Sun: 8:00 AM - 8:00 PM<br>(Sri Lanka Time)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media Links -->
                    <div class="c-social-icons">
                        <a href="https://web.facebook.com/" class="c-social-icon" aria-label="Facebook" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="https://www.instagram.com/" class="c-social-icon" aria-label="Instagram" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                        <a href="https://wa.me/94771234567" class="c-social-icon" aria-label="WhatsApp" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </a>
                        <a href="https://www.tiktok.com/" class="c-social-icon" aria-label="TikTok" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                        </a>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Map Section -->
        <section class="c-map-section" aria-label="Office location map">
            <div class="c-map-wrapper">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63371.81615862!2d79.82118565!3d7.2080586!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae2f6ef90699e63%3A0x6e3649f2ad964014!2sNegombo%2C%20Sri%20Lanka!5e0!3m2!1sen!2s!4v1719000000000!5m2!1sen!2s" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="GlobeTrek office location in Negombo, Sri Lanka"></iframe>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="c-faq-section" aria-labelledby="c-faq-title">
            <div>
                <h2 id="c-faq-title">Frequently Asked Questions</h2>
                <div class="c-faq-list">
                    <div class="c-faq-item">
                        <button class="c-faq-question" aria-expanded="false">
                            <span>How can I book a tour with GlobeTrek?</span>
                            <span class="c-faq-toggle" aria-hidden="true">+</span>
                        </button>
                        <div class="c-faq-answer">
                            <div class="c-faq-answer-inner">
                                Booking is simple! Browse our <a href="packages.php">Packages</a> page, select your preferred tour, choose your travel dates, and complete the booking form. You'll receive a confirmation email with all the details. For custom itineraries, visit our <a href="custom-trips.php">Custom Trips</a> page.
                            </div>
                        </div>
                    </div>
                    <div class="c-faq-item">
                        <button class="c-faq-question" aria-expanded="false">
                            <span>Can I customize my travel itinerary?</span>
                            <span class="c-faq-toggle" aria-hidden="true">+</span>
                        </button>
                        <div class="c-faq-answer">
                            <div class="c-faq-answer-inner">
                                Absolutely! We specialize in crafting personalized travel experiences. Submit a request through our <a href="custom-trips.php">Custom Trips</a> page, and our team will design an itinerary tailored to your interests, budget, and schedule.
                            </div>
                        </div>
                    </div>
                    <div class="c-faq-item">
                        <button class="c-faq-question" aria-expanded="false">
                            <span>What is your cancellation policy?</span>
                            <span class="c-faq-toggle" aria-hidden="true">+</span>
                        </button>
                        <div class="c-faq-answer">
                            <div class="c-faq-answer-inner">
                                Cancellations made 30 or more days before departure receive a full refund. 15-29 days prior receive a 50% refund. Less than 15 days before departure are non-refundable. Please refer to our <a href="cancellation-policy.php">Cancellation Policy</a> for full details.
                            </div>
                        </div>
                    </div>
                    <div class="c-faq-item">
                        <button class="c-faq-question" aria-expanded="false">
                            <span>Do you offer airport transfers?</span>
                            <span class="c-faq-toggle" aria-hidden="true">+</span>
                        </button>
                        <div class="c-faq-answer">
                            <div class="c-faq-answer-inner">
                                Yes! Airport transfers can be arranged as part of your tour package or as a standalone service. Simply mention your flight details when booking, and we'll ensure a comfortable pick-up and drop-off.
                            </div>
                        </div>
                    </div>
                    <div class="c-faq-item">
                        <button class="c-faq-question" aria-expanded="false">
                            <span>What payment methods do you accept?</span>
                            <span class="c-faq-toggle" aria-hidden="true">+</span>
                        </button>
                        <div class="c-faq-answer">
                            <div class="c-faq-answer-inner">
                                We accept all major credit and debit cards (Visa, MasterCard, American Express), bank transfers, and popular digital payment platforms. All transactions are securely processed with industry-standard encryption.
                            </div>
                        </div>
                    </div>
                </div>
                <p class="c-faq-still">Still have questions? <a href="#c-form-title">Contact our support team</a></p>
            </div>
        </section>

        <!-- Features Bar -->
        <section class="c-features-section" aria-label="Why choose GlobeTrek">
            <div class="c-features-inner">
                <div class="c-feature-item">
                    <div class="c-feature-icon">
                        <span class="material-symbols-outlined">route</span>
                    </div>
                    <h3>Custom Tours</h3>
                    <p>Tailor-made itineraries just for you.</p>
                </div>
                <div class="c-feature-item">
                    <div class="c-feature-icon">
                        <span class="material-symbols-outlined">savings</span>
                    </div>
                    <h3>Best Price Guarantee</h3>
                    <p>Get the best value for your money.</p>
                </div>
                <div class="c-feature-item">
                    <div class="c-feature-icon">
                        <span class="material-symbols-outlined">headset_mic</span>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>We're with you at every step of your journey.</p>
                </div>
                <div class="c-feature-item">
                    <div class="c-feature-icon">
                        <span class="material-symbols-outlined">verified_user</span>
                    </div>
                    <h3>Safe &amp; Secure</h3>
                    <p>Your travel and data are always protected.</p>
                </div>
                <div class="c-features-cta">
                    <span class="c-features-cta-label">Ready to start your adventure?</span>
                    <h2>Let's Plan Your Dream Trip</h2>
                    <a href="custom-trips.php" class="c-features-cta-btn">
                        Plan My Custom Trip
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="../js/script.js"></script>
    <script>
    // FAQ accordion functionality
    document.querySelectorAll('.c-faq-question').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var item = this.closest('.c-faq-item');
            var isActive = item.classList.contains('active');

            // Close other open FAQ items
            item.closest('.c-faq-list').querySelectorAll('.c-faq-item.active').forEach(function(openItem) {
                if (openItem !== item) {
                    openItem.classList.remove('active');
                    openItem.querySelector('.c-faq-question').setAttribute('aria-expanded', 'false');
                }
            });

            // Toggle current FAQ item
            item.classList.toggle('active');
            this.setAttribute('aria-expanded', !isActive);
        });
    });

    // Smooth scroll to contact form from "Still have questions" link
    document.querySelector('.c-faq-still a')?.addEventListener('click', function(e) {
        e.preventDefault();
        var formSection = document.querySelector('.c-contact-section');
        if (formSection) {
            formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
    </script>
</body>
</html>