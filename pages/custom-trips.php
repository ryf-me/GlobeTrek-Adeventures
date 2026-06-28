/**
 * File: pages/custom-trips.php
 * Purpose: Custom trip request form for logged-in users to submit personalized travel plans
 * Dependencies: config/database.php, config/csrf.php, config/rate-limiter.php, css/style.css, css/navbar.css, css/custom-trips.css, css/footer.css, includes/navbar.php, includes/footer.php, js/script.js, flatpickr.js
 * Used By: Main website navigation, user dashboard
 * Parent Files: index.php (via navigation), navbar.php (via link)
 * Child Files: None
 * @package GlobeTrek\Pages
 */

<?php
session_start();

// === AUTHENTICATION CHECK ===
// Only logged-in users can submit custom trip requests
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// === DATABASE AND SECURITY SETUP ===
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/rate-limiter.php';
$db = getDB();

// === FORM FIELD INITIALIZATION ===
$fields = [
    'destination' => '',
    'duration' => '',
    'travelers' => '',
    'dates' => '',
    'style' => '',
    'interests' => [],
    'name' => '',
    'email' => '',
    'details' => '',
];
$errors = [];
$submitted = false;

// === FORM PROCESSING ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation for security
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    }

    // Rate limiting: 5 custom trip requests per hour per IP
    if (empty($errors) && !checkRateLimit('custom_trips', 5, 3600, false)) {
        $errors['general'] = 'Too many requests. Please try again later.';
    }

    // === FIELD POPULATION AND VALIDATION ===
    if (empty($errors)) {
        $fields['destination'] = trim($_POST['destination'] ?? '');
        $fields['duration'] = trim($_POST['duration'] ?? '');
        $fields['travelers'] = trim($_POST['travelers'] ?? '');
        $fields['dates'] = trim($_POST['dates'] ?? '');
        $fields['style'] = trim($_POST['style'] ?? '');
        $fields['interests'] = $_POST['interests'] ?? [];
        $fields['name'] = trim($_POST['name'] ?? '');
        $fields['email'] = trim($_POST['email'] ?? '');
        $fields['details'] = trim($_POST['details'] ?? '');

    // Validate required fields
    if ($fields['destination'] === '') {
        $errors['destination'] = 'Please enter a destination.';
    }
    // Validate duration is a positive integer
    if ($fields['duration'] === '' || !is_numeric($fields['duration']) || (int)$fields['duration'] < 1) {
        $errors['duration'] = 'Please enter a valid duration in days.';
    }
    // Validate number of travelers is a positive integer
    if ($fields['travelers'] === '' || !is_numeric($fields['travelers']) || (int)$fields['travelers'] < 1) {
        $errors['travelers'] = 'Please enter a valid number of travelers.';
    }
    if ($fields['name'] === '') {
        $errors['name'] = 'Please enter your full name.';
    }
    if ($fields['email'] === '') {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
}
    $submitted = empty($errors);

    // === DATABASE INSERTION ===
    if ($submitted) {
        // Insert custom trip request into database
        $stmt = $db->prepare(
            "INSERT INTO custom_trip_requests (full_name, email, destination, duration_days, num_travelers, estimated_dates, travel_style, interests, additional_details)
             VALUES (:name, :email, :destination, :duration, :travelers, :dates, :style, :interests, :details)"
        );
        $stmt->execute([
            ':name' => $fields['name'],
            ':email' => $fields['email'],
            ':destination' => $fields['destination'],
            ':duration' => (int)$fields['duration'],
            ':travelers' => (int)$fields['travelers'],
            ':dates' => $fields['dates'] ?: null,
            ':style' => $fields['style'] ?: null,
            // Encode interests array as JSON for storage
            ':interests' => !empty($fields['interests']) ? json_encode($fields['interests']) : null,
            ':details' => $fields['details'] ?: null,
        ]);
        // Clear form fields after successful submission
        $fields = array_fill_keys(array_keys($fields), '');
        $fields['interests'] = [];
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

function is_checked(string $value, array $interests): string
{
    // Return 'checked' attribute if this interest was previously selected
    return in_array($value, $interests) ? 'checked' : '';
}

function style_selected(string $value, string $current): string
{
    // Return 'selected' attribute if this travel style was previously chosen
    return $value === $current ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Trips - GlobeTrek</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <!-- Flatpickr Date Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/custom-trips.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="custom-trips-page">
    <!-- Navigation Bar -->
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="ct-hero" aria-labelledby="ct-hero-title">
            <h1 id="ct-hero-title">Craft Your Perfect Journey</h1>
            <p class="ct-hero-desc">
                Our travel experts will design a bespoke itinerary tailored to your unique preferences, budget, and travel style. Share your vision, and we'll handle the rest.
            </p>
        </section>

        <?php if ($submitted): ?>
            <!-- Success State -->
            <section class="ct-success" role="status">
                <div class="ct-success-inner">
                    <span class="material-symbols-outlined ct-success-icon" aria-hidden="true">check_circle</span>
                    <h2>Request Submitted!</h2>
                    <p>Thank you. A travel expert will contact you shortly to discuss your dream trip.</p>
                </div>
            </section>
        <?php else: ?>
            <!-- Custom Trip Request Form -->
            <section class="ct-workspace" aria-labelledby="ct-form-title">
                <div class="ct-form-column">
                    <!-- Error Display -->
                    <?php if (!empty($errors)): ?>
                        <div class="form-alert error" role="alert">
                            Please review the highlighted fields and try again.
                        </div>
                    <?php endif; ?>

                    <!-- Custom Trip Form -->
                    <form class="ct-form" method="post" action="custom-trips.php" novalidate>
                        <?php csrf_field(); ?>
                        
                        <!-- Trip Basics Fieldset -->
                        <fieldset class="ct-fieldset">
                            <legend class="ct-fieldset-legend">
                                <span class="ct-legend-number">1</span>
                                <span>Trip Basics</span>
                            </legend>
                            <div class="ct-field-grid">
                                <!-- Destination Field -->
                                <div class="form-field">
                                    <label for="destination">Destination(s)</label>
                                    <input id="destination" name="destination" type="text" value="<?= old_value('destination', $fields) ?>" placeholder="e.g., Japan, Europe, Patagonia" aria-invalid="<?= isset($errors['destination']) ? 'true' : 'false' ?>">
                                    <?php if (isset($errors['destination'])): ?>
                                        <p class="field-error"><?= field_error('destination', $errors) ?></p>
                                    <?php endif; ?>
                                </div>
                                <!-- Duration Field -->
                                <div class="form-field">
                                    <label for="duration">Duration (Days)</label>
                                    <input id="duration" name="duration" type="number" min="1" value="<?= old_value('duration', $fields) ?>" placeholder="e.g., 14" aria-invalid="<?= isset($errors['duration']) ? 'true' : 'false' ?>">
                                    <?php if (isset($errors['duration'])): ?>
                                        <p class="field-error"><?= field_error('duration', $errors) ?></p>
                                    <?php endif; ?>
                                </div>
                                <!-- Number of Travelers Field -->
                                <div class="form-field">
                                    <label for="travelers">Number of Travelers</label>
                                    <input id="travelers" name="travelers" type="number" min="1" value="<?= old_value('travelers', $fields) ?>" placeholder="e.g., 2" aria-invalid="<?= isset($errors['travelers']) ? 'true' : 'false' ?>">
                                    <?php if (isset($errors['travelers'])): ?>
                                        <p class="field-error"><?= field_error('travelers', $errors) ?></p>
                                    <?php endif; ?>
                                </div>
                                <!-- Date Range Picker -->
                                <div class="form-field">
                                    <label for="dates">Estimated Dates (Optional)</label>
                                    <input id="dates" name="dates" type="text" value="<?= old_value('dates', $fields) ?>" placeholder="Select date range" readonly class="ct-date-range">
                                </div>
                            </div>
                        </fieldset>

                        <!-- Preferences Fieldset -->
                        <fieldset class="ct-fieldset">
                            <legend class="ct-fieldset-legend">
                                <span class="ct-legend-number">2</span>
                                <span>Preferences</span>
                            </legend>
                            <!-- Travel Style Radio Buttons -->
                            <div class="form-field">
                                <span class="ct-radio-label">Travel Style</span>
                                <div class="ct-style-options">
                                    <label class="ct-style-chip">
                                        <input type="radio" name="style" value="luxury" <?= style_selected('luxury', $fields['style']) ?>>
                                        <span>Luxury</span>
                                    </label>
                                    <label class="ct-style-chip">
                                        <input type="radio" name="style" value="adventure" <?= style_selected('adventure', $fields['style']) ?>>
                                        <span>Adventure</span>
                                    </label>
                                    <label class="ct-style-chip">
                                        <input type="radio" name="style" value="cultural" <?= style_selected('cultural', $fields['style']) ?>>
                                        <span>Cultural Immersion</span>
                                    </label>
                                    <label class="ct-style-chip">
                                        <input type="radio" name="style" value="relaxation" <?= style_selected('relaxation', $fields['style']) ?>>
                                        <span>Relaxation</span>
                                    </label>
                                </div>
                            </div>
                            <!-- Interests Checkboxes -->
                            <div class="form-field">
                                <span class="ct-radio-label">Interests (Select all that apply)</span>
                                <div class="ct-interests-grid">
                                    <label class="ct-interest-check">
                                        <input type="checkbox" name="interests[]" value="food_drink" <?= is_checked('food_drink', $fields['interests']) ?>>
                                        <span>Food &amp; Drink</span>
                                    </label>
                                    <label class="ct-interest-check">
                                        <input type="checkbox" name="interests[]" value="nature_wildlife" <?= is_checked('nature_wildlife', $fields['interests']) ?>>
                                        <span>Nature &amp; Wildlife</span>
                                    </label>
                                    <label class="ct-interest-check">
                                        <input type="checkbox" name="interests[]" value="history_arts" <?= is_checked('history_arts', $fields['interests']) ?>>
                                        <span>History &amp; Arts</span>
                                    </label>
                                    <label class="ct-interest-check">
                                        <input type="checkbox" name="interests[]" value="active_outdoors" <?= is_checked('active_outdoors', $fields['interests']) ?>>
                                        <span>Active &amp; Outdoors</span>
                                    </label>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Personal Details Fieldset -->
                        <fieldset class="ct-fieldset">
                            <legend class="ct-fieldset-legend">
                                <span class="ct-legend-number">3</span>
                                <span>Personal Details</span>
                            </legend>
                            <div class="ct-field-grid">
                                <!-- Full Name Field -->
                                <div class="form-field">
                                    <label for="name">Full Name</label>
                                    <input id="name" name="name" type="text" value="<?= old_value('name', $fields) ?>" placeholder="John Doe" aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>">
                                    <?php if (isset($errors['name'])): ?>
                                        <p class="field-error"><?= field_error('name', $errors) ?></p>
                                    <?php endif; ?>
                                </div>
                                <!-- Email Field -->
                                <div class="form-field">
                                    <label for="email">Email Address</label>
                                    <input id="email" name="email" type="email" value="<?= old_value('email', $fields) ?>" placeholder="john@example.com" aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>">
                                    <?php if (isset($errors['email'])): ?>
                                        <p class="field-error"><?= field_error('email', $errors) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Additional Information Fieldset -->
                        <fieldset class="ct-fieldset">
                            <legend class="ct-fieldset-legend">
                                <span class="ct-legend-number">4</span>
                                <span>Additional Information</span>
                            </legend>
                            <div class="form-field">
                                <label for="details">Tell us more about your dream trip</label>
                                <textarea id="details" name="details" rows="5" placeholder="Any specific requirements, must-sees, or accessibility needs?"><?= old_value('details', $fields) ?></textarea>
                            </div>
                        </fieldset>

                        <!-- Submit Button -->
                        <div class="ct-submit-row">
                            <button type="submit" class="ct-submit-btn">
                                <span>Submit Request</span>
                                <span class="material-symbols-outlined ct-submit-arrow" aria-hidden="true">arrow_forward</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sidebar Information -->
                <aside class="ct-sidebar-column">
                    <!-- How It Works Steps -->
                    <div class="ct-sidebar-card ct-how-it-works">
                        <h2>How It Works</h2>
                        <ol class="ct-steps">
                            <li class="ct-step">
                                <span class="ct-step-num ct-step-active">1</span>
                                <div class="ct-step-body">
                                    <h3>Submit Request</h3>
                                    <p>Fill out this form with your initial ideas and preferences.</p>
                                </div>
                            </li>
                            <li class="ct-step">
                                <span class="ct-step-num">2</span>
                                <div class="ct-step-body">
                                    <h3>Consultation</h3>
                                    <p>A travel expert will contact you to discuss your vision in detail.</p>
                                </div>
                            </li>
                            <li class="ct-step">
                                <span class="ct-step-num">3</span>
                                <div class="ct-step-body">
                                    <h3>Custom Itinerary</h3>
                                    <p>Receive a fully personalized itinerary ready for your review and booking.</p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <!-- Decorative Image -->
                    <div class="ct-sidebar-image">
                        <span class="material-symbols-outlined ct-placeholder-icon" aria-hidden="true">landscape</span>
                        <span class="ct-placeholder-label">Your Adventure Awaits</span>
                    </div>
                </aside>
            </section>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="../js/script.js"></script>
    <!-- Flatpickr Date Picker Library -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    // Date picker initialization and duration auto-calculation
    (function () {
        var dateInput = document.getElementById('dates');
        var durationInput = document.getElementById('duration');
        if (!dateInput || typeof flatpickr === 'undefined') return;

        // Helper function to format dates as YYYY-MM-DD
        var fmt = function (d) {
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + day;
        };

        // Initialize Flatpickr date range picker
        var fp = flatpickr(dateInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            minDate: 'today',
            disableMobile: true,
            altInput: true,
            altFormat: 'F j, Y',
            // Auto-calculate duration when date range is selected
            onChange: function (selectedDates) {
                if (selectedDates.length === 2) {
                    dateInput.value = fmt(selectedDates[0]) + ' to ' + fmt(selectedDates[1]);
                    // Calculate difference in days
                    var diff = Math.ceil((selectedDates[1] - selectedDates[0]) / 86400000);
                    if (durationInput && diff > 0) durationInput.value = diff;
                }
            }
        });

        // Auto-update date range when duration is changed manually
        if (durationInput) {
            durationInput.addEventListener('input', function () {
                var days = parseInt(this.value, 10);
                if (!days || days < 1) return;
                var selected = fp.selectedDates;
                if (selected.length === 0) return;
                var start = selected[0];
                var end = new Date(start);
                end.setDate(start.getDate() + days);
                fp.setDate([start, end], true);
            });
        }
    })();
    </script>
</body>
</html>