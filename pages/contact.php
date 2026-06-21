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
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/contact.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="contact-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <section class="contact-hero" aria-labelledby="contact-title">
            <div class="contact-hero-copy">
                <p class="contact-eyebrow">Plan with a local team</p>
                <h1 id="contact-title">Get in Touch</h1>
                <p>
                    Tell us what kind of Sri Lanka journey you are dreaming about. Our team will help shape the route,
                    timing, guide support, and small details that make the trip feel personal.
                </p>
            </div>

            <div class="contact-hero-card" aria-label="GlobeTrek response promise">
                <span>24h</span>
                <p>Typical response time for new travel inquiries.</p>
            </div>
        </section>

        <section class="contact-workspace" aria-labelledby="message-title">
            <div class="contact-form-panel">
                <div class="contact-section-heading">
                    <p class="contact-eyebrow">Send a message</p>
                    <h2 id="message-title">Start the conversation.</h2>
                </div>

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
                    <div class="form-row">
                        <div class="form-field">
                            <label for="name">Name</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="<?php echo old_value('name', $fields); ?>"
                                placeholder="Your full name"
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
                                placeholder="you@example.com"
                                aria-invalid="<?php echo isset($errors['email']) ? 'true' : 'false'; ?>"
                                aria-describedby="<?php echo isset($errors['email']) ? 'email-error' : ''; ?>"
                            >
                            <?php if (isset($errors['email'])): ?>
                                <p class="field-error" id="email-error"><?php echo field_error('email', $errors); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="subject">Subject</label>
                        <input
                            id="subject"
                            name="subject"
                            type="text"
                            value="<?php echo old_value('subject', $fields); ?>"
                            placeholder="Trip planning, booking support, or guide inquiry"
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
                            rows="7"
                            placeholder="Share your travel dates, interests, group size, or any questions."
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
                    <p class="contact-eyebrow">Contact information</p>
                    <h2 id="info-title">Talk to GlobeTrek.</h2>
                    <p class="info-intro">
                        We are based in Negombo and work with guides, hosts, and travel partners across Sri Lanka.
                    </p>
                </div>

                <div class="info-list">
                    <div class="info-item">
                        <div class="info-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 21s7-5.3 7-12a7 7 0 0 0-14 0c0 6.7 7 12 7 12z"></path>
                                <circle cx="12" cy="9" r="2.5"></circle>
                            </svg>
                        </div>
                        <div>
                            <h3>Office Address</h3>
                            <p>123, Main Street, Negombo</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3>Phone Number</h3>
                            <p><a href="tel:+94112345678">+94 11 234 5678</a></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                <path d="M4 7l8 6 8-6"></path>
                            </svg>
                        </div>
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
            </aside>
        </section>

        <section class="support-teaser" aria-labelledby="support-title">
            <p class="contact-eyebrow">Need quick answers?</p>
            <h2 id="support-title">We can help with bookings, routes, guide availability, and travel requirements.</h2>
            <div class="support-actions">
                <a href="packages.php">Browse Packages</a>
                <a href="guides.php">Meet Our Guides</a>
            </div>
        </section>
    </main>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
</body>
</html>
