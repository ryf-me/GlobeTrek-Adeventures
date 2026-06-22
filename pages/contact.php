<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

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
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/contact.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="contact-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main class="contact-page-main">
        <section class="contact-hero" aria-labelledby="contact-title">
            <h1 id="contact-title">Get in Touch</h1>
            <p class="contact-hero-desc">
                We're here to help you plan your next great adventure. Reach out to our team with any questions or inquiries.
            </p>
        </section>

        <section class="contact-workspace" aria-labelledby="message-title">
            <div class="contact-form-panel">
                <h2 id="message-title">Send a Message</h2>

                <?php if ($submitted): ?>
                    <div class="form-alert success" role="status">
                        Thank you. Your message has been sent successfully.
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="form-alert error" role="alert">
                        Please review the highlighted fields and try again.
                    </div>
                <?php endif; ?>

                <form class="contact-form" method="post" action="contact.php#message-title" novalidate>
                    <div class="form-field">
                        <label for="name">Name</label>
                        <input id="name" name="name" type="text" value="<?= old_value('name', $fields) ?>" placeholder="Enter your full name" aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>">
                        <?php if (isset($errors['name'])): ?>
                            <p class="field-error"><?= field_error('name', $errors) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="email">Email Address</label>
                        <input id="email" name="email" type="email" value="<?= old_value('email', $fields) ?>" placeholder="Enter your email address" aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>">
                        <?php if (isset($errors['email'])): ?>
                            <p class="field-error"><?= field_error('email', $errors) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="subject">Subject</label>
                        <input id="subject" name="subject" type="text" value="<?= old_value('subject', $fields) ?>" placeholder="What is this regarding?" aria-invalid="<?= isset($errors['subject']) ? 'true' : 'false' ?>">
                        <?php if (isset($errors['subject'])): ?>
                            <p class="field-error"><?= field_error('subject', $errors) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" placeholder="Type your message here..." aria-invalid="<?= isset($errors['message']) ? 'true' : 'false' ?>"><?= old_value('message', $fields) ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <p class="field-error"><?= field_error('message', $errors) ?></p>
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

                <div class="social-follow">
                    <span class="social-label">Follow Us</span>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook" class="social-icon-placeholder"></a>
                        <a href="#" aria-label="Instagram" class="social-icon-placeholder"></a>
                        <a href="#" aria-label="Twitter" class="social-icon-placeholder"></a>
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
