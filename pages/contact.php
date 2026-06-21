<?php
$fields = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => '',
];
$errors = [];
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $errors['subject'] = 'Please add a subject.';
    }

    if ($fields['message'] === '') {
        $errors['message'] = 'Please write your message.';
    }

    $submitted = empty($errors);

    if ($submitted) {
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
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/contact.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="contact-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <section class="contact-hero" aria-labelledby="contact-title">
            <h1 id="contact-title">Get in Touch</h1>
            <p class="contact-hero-desc">
                We're here to help you plan your next great adventure. Reach out to our team with any questions or inquiries.
            </p>
            <div class="contact-hero-card" aria-label="Response time">
                <span>24h</span>
                <p>Typical response time for new travel inquiries.</p>
            </div>
        </section>

        <section class="contact-workspace" aria-labelledby="message-title">
            <div class="contact-form-panel">
                <h2 id="message-title">Send a Message</h2>

                <?php if ($submitted): ?>
                    <div class="form-alert success" role="status">
                        Thank you. Your message has been checked and is ready for our team to review.
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="form-alert error" role="alert">
                        Please review the highlighted fields and try again.
                    </div>
                <?php endif; ?>

                <form class="contact-form" method="post" action="contact.php#message-title" novalidate>
                    <div class="form-field">
                        <label for="name">Name</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="<?php echo old_value('name', $fields); ?>"
                            placeholder="Enter your full name"
                            aria-invalid="<?php echo isset($errors['name']) ? 'true' : 'false'; ?>"
                            aria-describedby="<?php echo isset($errors['name']) ? 'name-error' : ''; ?>"
                        >
                        <?php if (isset($errors['name'])): ?>
                            <p class="field-error" id="name-error"><?php echo field_error('name', $errors); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="email">Email Address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="<?php echo old_value('email', $fields); ?>"
                            placeholder="Enter your email address"
                            aria-invalid="<?php echo isset($errors['email']) ? 'true' : 'false'; ?>"
                            aria-describedby="<?php echo isset($errors['email']) ? 'email-error' : ''; ?>"
                        >
                        <?php if (isset($errors['email'])): ?>
                            <p class="field-error" id="email-error"><?php echo field_error('email', $errors); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="subject">Subject</label>
                        <input
                            id="subject"
                            name="subject"
                            type="text"
                            value="<?php echo old_value('subject', $fields); ?>"
                            placeholder="What is this regarding?"
                            aria-invalid="<?php echo isset($errors['subject']) ? 'true' : 'false'; ?>"
                            aria-describedby="<?php echo isset($errors['subject']) ? 'subject-error' : ''; ?>"
                        >
                        <?php if (isset($errors['subject'])): ?>
                            <p class="field-error" id="subject-error"><?php echo field_error('subject', $errors); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="message">Message</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="5"
                            placeholder="Type your message here..."
                            aria-invalid="<?php echo isset($errors['message']) ? 'true' : 'false'; ?>"
                            aria-describedby="<?php echo isset($errors['message']) ? 'message-error' : ''; ?>"
                        ><?php echo old_value('message', $fields); ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <p class="field-error" id="message-error"><?php echo field_error('message', $errors); ?></p>
                        <?php endif; ?>
                    </div>

                    <button type="submit">Send Message</button>
                </form>
            </div>

            <aside class="contact-info-panel" aria-labelledby="info-title">
                <div>
                    <h2 id="info-title">Contact Information</h2>
                    <p class="info-intro">
                        Our dedicated support team is available to assist you Monday through Friday, 9:00 AM to 6:00 PM IST.
                    </p>
                </div>

                <div class="info-list">
                    <div class="info-item">
                        <span class="material-symbols-outlined info-icon" aria-hidden="true">location_on</span>
                        <div>
                            <h3>Office Address</h3>
                            <p>123, Main Street, Negombo</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <span class="material-symbols-outlined info-icon" aria-hidden="true">phone</span>
                        <div>
                            <h3>Phone Number</h3>
                            <p><a href="tel:+94112345678">+94 11 234 5678</a></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <span class="material-symbols-outlined info-icon" aria-hidden="true">mail</span>
                        <div>
                            <h3>Email Address</h3>
                            <p><a href="mailto:info@globetrek.lk">info@globetrek.lk</a></p>
                        </div>
                    </div>
                </div>

                <div class="office-hours">
                    <span>Office Hours</span>
                    <p>Monday to Friday, 9:00 AM - 6:00 PM</p>
                </div>

                <div class="social-links">
                    <span class="social-label">Follow Us</span>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook" class="social-icon">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </a>
                        <a href="#" aria-label="Instagram" class="social-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="5"/><circle cx="17.5" cy="6.5" r="1.5" fill="currentColor" stroke="none"/></svg>
                        </a>
                        <a href="#" aria-label="Twitter" class="social-icon">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                    </div>
                </div>
            </aside>
        </section>

        <section class="contact-map" aria-label="Office location map">
            <div class="map-placeholder">
                <span class="material-symbols-outlined map-icon">map</span>
                <span class="map-label">Interactive Map View</span>
            </div>
        </section>

        <section class="faq-teaser" aria-labelledby="faq-title">
            <h3 id="faq-title">Have questions? Check out our help center</h3>
            <p>Find quick answers to common questions about bookings, cancellations, and travel requirements.</p>
            <a href="#" class="faq-btn">Visit Help Center</a>
        </section>
    </main>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
</body>
</html>
