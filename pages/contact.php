<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/rate-limiter.php';
$db = getDB();

$fields = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'message' => '',
];
$errors = [];
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    }

    if (empty($errors) && !checkRateLimit('contact', 10, 3600, false)) {
        $errors['general'] = 'Too many submissions. Please try again later.';
    }

    foreach ($fields as $key => $value) {
        $fields[$key] = trim($_POST[$key] ?? '');
    }

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
        $errors['message'] = 'Please write your message.';
    }

    $submitted = empty($errors);

    if ($submitted) {
        $stmt = $db->prepare(
            "INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)"
        );
        $stmt->execute([
            ':name' => $fields['name'],
            ':email' => $fields['email'],
            ':subject' => $fields['subject'],
            ':message' => $fields['message'],
        ]);
        $fields = array_fill_keys(array_keys($fields), '');
    }
}

function old_value(string $field, array $fields): string
{
    return htmlspecialchars($fields[$field] ?? '', ENT_QUOTES, 'UTF-8');
}

function field_error(string $field, array $errors): string
{
    return htmlspecialchars($errors[$field] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact GlobeTrek Adventures</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/contact.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="contact-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main class="contact-page-main">

        <!-- Hero Section -->
        <section class="contact-hero" aria-labelledby="contact-title">
            <div class="contact-hero-overlay"></div>
            <div class="contact-hero-content">
                <nav class="contact-breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo $basePath; ?>index.php">Home</a>
                    <span class="breadcrumb-sep">&gt;</span>
                    <span class="breadcrumb-current">Contact Us</span>
                </nav>
                <h1 id="contact-title">Contact Us</h1>
                <p class="contact-hero-subtitle">We'd love to hear from you!</p>
                <p class="contact-hero-desc">
                    Have a question, need travel advice, or ready to plan your next adventure?<br>
                    Our team is here to help you every step of the way.
                </p>
                <div class="contact-trust-badges">
                    <div class="trust-badge">
                        <span class="material-symbols-outlined trust-icon">headset_mic</span>
                        <div>
                            <strong>24/7 Support</strong>
                            <span>We're here anytime</span>
                        </div>
                    </div>
                    <div class="trust-badge">
                        <span class="material-symbols-outlined trust-icon">verified_user</span>
                        <div>
                            <strong>Trusted &amp; Secure</strong>
                            <span>Your data is safe</span>
                        </div>
                    </div>
                    <div class="trust-badge">
                        <span class="material-symbols-outlined trust-icon">quickreply</span>
                        <div>
                            <strong>Quick Response</strong>
                            <span>Get replies fast</span>
                        </div>
                    </div>
                    <div class="trust-badge">
                        <span class="material-symbols-outlined trust-icon">explore</span>
                        <div>
                            <strong>Local Experts</strong>
                            <span>Travel with confidence</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Form + Info Panel -->
        <section class="contact-workspace" aria-labelledby="message-title">
            <div class="contact-form-panel">
                <h2 id="message-title">Send Us a Message</h2>
                <p class="form-subtitle">Fill out the form below and our travel experts will get back to you shortly.</p>

                <?php if ($submitted): ?>
                    <div class="form-alert success" role="status">
                        <span class="material-symbols-outlined" aria-hidden="true">check_circle</span>
                        Thank you. Your message has been sent successfully.
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="form-alert error" role="alert">
                        <span class="material-symbols-outlined" aria-hidden="true">error</span>
                        Please review the highlighted fields and try again.
                    </div>
                <?php endif; ?>

                <form class="contact-form" method="post" action="contact.php#message-title" novalidate>
                    <?php csrf_field(); ?>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="name">Your Name <span class="required">*</span></label>
                            <input id="name" name="name" type="text" value="<?= old_value('name', $fields) ?>" placeholder="Your Name" aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>">
                            <?php if (isset($errors['name'])): ?>
                                <p class="field-error"><?= field_error('name', $errors) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input id="email" name="email" type="email" value="<?= old_value('email', $fields) ?>" placeholder="Email Address" aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>">
                            <?php if (isset($errors['email'])): ?>
                                <p class="field-error"><?= field_error('email', $errors) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input id="phone" name="phone" type="tel" value="<?= old_value('phone', $fields) ?>" placeholder="Phone Number" aria-invalid="<?= isset($errors['phone']) ? 'true' : 'false' ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <p class="field-error"><?= field_error('phone', $errors) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="subject">Subject <span class="required">*</span></label>
                            <select id="subject" name="subject" aria-invalid="<?= isset($errors['subject']) ? 'true' : 'false' ?>">
                                <option value="" disabled <?= $fields['subject'] === '' ? 'selected' : '' ?>>Select a subject</option>
                                <option value="General Inquiry" <?= $fields['subject'] === 'General Inquiry' ? 'selected' : '' ?>>General Inquiry</option>
                                <option value="Booking Question" <?= $fields['subject'] === 'Booking Question' ? 'selected' : '' ?>>Booking Question</option>
                                <option value="Custom Tour" <?= $fields['subject'] === 'Custom Tour' ? 'selected' : '' ?>>Custom Tour</option>
                                <option value="Feedback" <?= $fields['subject'] === 'Feedback' ? 'selected' : '' ?>>Feedback</option>
                                <option value="Other" <?= $fields['subject'] === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                            <?php if (isset($errors['subject'])): ?>
                                <p class="field-error"><?= field_error('subject', $errors) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="message">Your Message <span class="required">*</span></label>
                        <textarea id="message" name="message" rows="5" placeholder="Tell us how we can help you..." aria-invalid="<?= isset($errors['message']) ? 'true' : 'false' ?>"><?= old_value('message', $fields) ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <p class="field-error"><?= field_error('message', $errors) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-submit-row">
                        <button type="submit">
                            <span class="material-symbols-outlined" aria-hidden="true">send</span>
                            Send Message
                        </button>
                        <div class="form-privacy-note">
                            <span class="material-symbols-outlined" aria-hidden="true">shield</span>
                            We respect your privacy and never share your information.
                        </div>
                    </div>
                </form>
            </div>

            <aside class="contact-info-panel" aria-labelledby="info-title">
                <div class="info-panel-inner">
                    <h2 id="info-title">Get in Touch</h2>
                    <p class="info-subtitle">Reach out to us through any of these channels.</p>

                    <div class="info-list">
                        <div class="info-item">
                            <div class="info-icon-circle">
                                <span class="material-symbols-outlined" aria-hidden="true">call</span>
                            </div>
                            <div>
                                <h3>Phone</h3>
                                <p>+94 77 123 4567</p>
                                <p>+94 11 234 5678</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon-circle">
                                <span class="material-symbols-outlined" aria-hidden="true">mail</span>
                            </div>
                            <div>
                                <h3>Email</h3>
                                <p>info@globetrek.lk</p>
                                <p>bookings@globetrek.lk</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon-circle">
                                <span class="material-symbols-outlined" aria-hidden="true">location_on</span>
                            </div>
                            <div>
                                <h3>Address</h3>
                                <p>123, Main Street,<br>Kandy, Sri Lanka</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon-circle">
                                <span class="material-symbols-outlined" aria-hidden="true">schedule</span>
                            </div>
                            <div>
                                <h3>Office Hours</h3>
                                <p>Mon - Sun: 8:00 AM - 8:00 PM<br>(Sri Lanka Time)</p>
                            </div>
                        </div>
                    </div>

                    <div class="info-socials">
                        <a href="#" aria-label="Facebook" class="social-circle"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
                        <a href="#" aria-label="Instagram" class="social-circle"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
                        <a href="#" aria-label="WhatsApp" class="social-circle"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>
                        <a href="#" aria-label="TikTok" class="social-circle"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg></a>
                    </div>
                </div>

                <div class="info-map-bg">
                    <div class="info-map-label">We are here!</div>
                </div>
            </aside>
        </section>

        <!-- Map + FAQ Section -->
        <section class="contact-map-faq-row">
            <div class="contact-map-wrapper">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63371.81303540787!2d79.82118564999999!3d7.2080562!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae2f8de0eb17645%3A0x57bcf9b04a6497a2!2sNegombo%2C%20Sri%20Lanka!5e0!3m2!1sen!2s!4v1719333333333!5m2!1sen!2s"
                    width="100%"
                    height="100%"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="GlobeTrek office location on Google Maps">
                </iframe>
                <div class="map-overlay-card">
                    <div class="map-card-header">
                        <span class="material-symbols-outlined">location_on</span>
                        <div>
                            <strong>GlobeTrek Travels (Pvt) Ltd</strong>
                            <span>123, Main Street, Negombo 21100, Sri Lanka</span>
                        </div>
                    </div>
                    <div class="map-card-rating">
                        <span class="rating-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                        <span>4.8</span>
                        <a href="#" target="_blank" rel="noopener noreferrer">View larger map</a>
                    </div>
                </div>
            </div>

            <div class="contact-faq-panel" aria-labelledby="faq-title">
                <h2 id="faq-title">Frequently Asked Questions</h2>
                <div class="faq-accordion">
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span>How can I book a tour with GlobeTrek?</span>
                            <span class="material-symbols-outlined faq-toggle">add</span>
                        </button>
                        <div class="faq-answer">
                            <p>You can book a tour by browsing our packages, selecting your preferred dates, and completing the online booking form. Our team will then confirm your reservation via email.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span>Can I customize my travel itinerary?</span>
                            <span class="material-symbols-outlined faq-toggle">add</span>
                        </button>
                        <div class="faq-answer">
                            <p>Absolutely! We specialize in tailor-made tours. Contact us with your preferences and our travel experts will design a personalized itinerary just for you.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span>What is your cancellation policy?</span>
                            <span class="material-symbols-outlined faq-toggle">add</span>
                        </button>
                        <div class="faq-answer">
                            <p>We offer free cancellation up to 14 days before your trip. Cancellations within 7-14 days incur a 50% fee, and within 7 days are non-refundable. Please see our full policy for details.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span>Do you offer airport transfers?</span>
                            <span class="material-symbols-outlined faq-toggle">add</span>
                        </button>
                        <div class="faq-answer">
                            <p>Yes, we provide airport pickup and drop-off services at Bandaranaike International Airport. This can be added to any package or booked separately.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span>What payment methods do you accept?</span>
                            <span class="material-symbols-outlined faq-toggle">add</span>
                        </button>
                        <div class="faq-answer">
                            <p>We accept Visa, MasterCard, bank transfers, and cash payments. International wire transfers and online payment links are also available for overseas clients.</p>
                        </div>
                    </div>
                </div>
                <p class="faq-contact-link">Still have questions? <a href="contact.php">Contact our support team</a></p>
            </div>
        </section>

        <!-- CTA + Features Section -->
        <section class="contact-cta-section">
            <div class="cta-features">
                <div class="cta-feature">
                    <div class="cta-feature-icon">
                        <span class="material-symbols-outlined">tour</span>
                    </div>
                    <strong>Custom Tours</strong>
                    <span>Tailor-made itineraries just for you.</span>
                </div>
                <div class="cta-feature">
                    <div class="cta-feature-icon">
                        <span class="material-symbols-outlined">savings</span>
                    </div>
                    <strong>Best Price Guarantee</strong>
                    <span>Get the best value for your money.</span>
                </div>
                <div class="cta-feature">
                    <div class="cta-feature-icon">
                        <span class="material-symbols-outlined">headset_mic</span>
                    </div>
                    <strong>24/7 Support</strong>
                    <span>We're with you at every step of your journey.</span>
                </div>
                <div class="cta-feature">
                    <div class="cta-feature-icon">
                        <span class="material-symbols-outlined">verified_user</span>
                    </div>
                    <strong>Safe &amp; Secure</strong>
                    <span>Your travel and data are always protected.</span>
                </div>
            </div>
            <div class="cta-right">
                <p class="cta-ready">Ready to start your adventure?</p>
                <h2 class="cta-heading">Let's Plan Your Dream Trip</h2>
                <a href="custom-trips.php" class="cta-btn">
                    Plan My Custom Trip
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </div>
        </section>

    </main>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script>
    (function() {
        document.querySelectorAll('.faq-question').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var expanded = this.getAttribute('aria-expanded') === 'true';
                var item = this.closest('.faq-item');
                var answer = item.querySelector('.faq-answer');
                var icon = item.querySelector('.faq-toggle');
                if (expanded) {
                    this.setAttribute('aria-expanded', 'false');
                    item.classList.remove('faq-open');
                    answer.style.maxHeight = null;
                    icon.textContent = 'add';
                } else {
                    document.querySelectorAll('.faq-item.faq-open').forEach(function(openItem) {
                        openItem.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
                        openItem.classList.remove('faq-open');
                        openItem.querySelector('.faq-answer').style.maxHeight = null;
                        openItem.querySelector('.faq-toggle').textContent = 'add';
                    });
                    this.setAttribute('aria-expanded', 'true');
                    item.classList.add('faq-open');
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                    icon.textContent = 'remove';
                }
            });
        });
    })();
    </script>
</body>
</html>
