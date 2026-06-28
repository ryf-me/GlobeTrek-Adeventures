<?php
/**
 * File: pages/booking.php
 * Purpose: Booking Step 2 — Collects traveller details (name, email, phone, nationality, special requests), validates input, creates a pending booking record, and redirects to payment.
 * Dependencies: config/database.php, config/csrf.php, config/currency.php, includes/navbar.php, css/booking.css, js/script.js
 * Used By: package-details.php (linked from "Book Now" button)
 * Parent Files: package-details.php
 * Child Files: payment.php (redirect target after successful booking creation)
 * @package GlobeTrek\Pages
 */
require_once __DIR__ . '/../config/session.php';

// === AUTH CHECK ===
// Only logged-in users can make bookings; redirect guests to login.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/currency.php';
$db = getDB();

$basePath = '../';

// === PACKAGE LOOKUP ===
// Fetch the selected package by ID; only active packages are bookable.
$packageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $db->prepare("SELECT * FROM packages WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $packageId]);
$package = $stmt->fetch();

if (!$package) {
    echo '<!DOCTYPE html><html><head><title>Not Found</title></head><body><h1>Package not found.</h1><a href="packages.php">Back to Packages</a></body></html>';
    exit;
}

// === PRICE CALCULATION ===
// Read guest count from URL parameter, default to 2 adults with 10% tax.
$guestCount = isset($_GET['travelers']) ? max(1, (int)$_GET['travelers']) : 2;
$subtotal = $package['price'] * $guestCount;
$taxes = round($subtotal * 0.10);
$total = $subtotal + $taxes;

// === FORM FIELDS & ERRORS ===
$fields = [
    'firstName' => '',
    'lastName' => '',
    'email' => '',
    'phone' => '',
    'nationality' => '',
    'specialRequests' => '',
];
$errors = [];
$submitted = false;

// === FORM HANDLING ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation — prevents cross-site request forgery attacks
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    }

    // Populate fields from POST data (trimmed)
    foreach ($fields as $key => $value) {
        $fields[$key] = trim($_POST[$key] ?? '');
    }

    // === SERVER-SIDE VALIDATION ===
    if ($fields['firstName'] === '') {
        $errors['firstName'] = 'Please enter your first name.';
    }
    if ($fields['lastName'] === '') {
        $errors['lastName'] = 'Please enter your last name.';
    }
    if ($fields['email'] === '') {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($fields['phone'] === '') {
        $errors['phone'] = 'Please enter your phone number.';
    }
    if ($fields['nationality'] === '') {
        $errors['nationality'] = 'Please select your nationality.';
    }

    $submitted = empty($errors);

    if ($submitted) {
        // Generate unique booking reference with GT- prefix
        $bookingRef = 'GT-' . strtoupper(uniqid());
        $userId = $_SESSION['user_id'] ?? null;

        // === INSERT BOOKING RECORD ===
        // Status is 'pending' until payment is completed.
        $insertStmt = $db->prepare(
            "INSERT INTO bookings (user_id, package_id, booking_reference, first_name, last_name, email, phone, nationality, special_requests, num_travellers, total_price, status)
             VALUES (:user_id, :package_id, :booking_reference, :first_name, :last_name, :email, :phone, :nationality, :special_requests, :num_travellers, :total_price, 'pending')"
        );
        $insertStmt->execute([
            ':user_id' => $userId,
            ':package_id' => $package['id'],
            ':booking_reference' => $bookingRef,
            ':first_name' => $fields['firstName'],
            ':last_name' => $fields['lastName'],
            ':email' => $fields['email'],
            ':phone' => $fields['phone'],
            ':nationality' => $fields['nationality'],
            ':special_requests' => $fields['specialRequests'],
            ':num_travellers' => $guestCount,
            ':total_price' => $total,
        ]);

        // Store booking ref in session for payment page to retrieve
        $_SESSION['payment_booking_ref'] = $bookingRef;
        header('Location: payment.php?ref=' . urlencode($bookingRef));
        exit;
    }
}

// === HELPER: RETAIN FORM VALUES ===
// Returns escaped value for repopulating form fields after validation failure.
function old_value(string $field, array $fields): string
{
    return htmlspecialchars($fields[$field] ?? '', ENT_QUOTES, 'UTF-8');
}

// === HELPER: DISPLAY FIELD ERRORS ===
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
    <title>Booking - <?= htmlspecialchars($package['title']) ?> - GlobeTrek</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/booking.css">
</head>
<body class="booking-page">
    <main class="booking-shell">
        <!-- === BREADCRUMBS === -->
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="../index.php#home">Home</a>
            <span aria-hidden="true">/</span>
            <a href="packages.php">Tour Packages</a>
            <span aria-hidden="true">/</span>
            <a href="package-details.php?id=<?= $package['id'] ?>"><?= htmlspecialchars($package['title']) ?></a>
            <span aria-hidden="true">/</span>
            <span>Booking</span>
        </nav>

        <!-- === PROGRESS BAR === -->
        <!-- Visual indicator of booking flow: 1. Select → 2. Details → 3. Review → 4. Payment -->
        <div class="progress-bar" aria-label="Booking progress">
            <div class="progress-step completed">
                <div class="step-circle">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <span class="step-label">1. Select Package</span>
            </div>
            <div class="progress-line completed"></div>
            <div class="progress-step active">
                <div class="step-circle">2</div>
                <span class="step-label">2. Traveller Details</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step upcoming">
                <div class="step-circle">3</div>
                <span class="step-label">3. Review &amp; Confirm</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step upcoming">
                <div class="step-circle">4</div>
                <span class="step-label">4. Payment</span>
            </div>
        </div>

        <div class="booking-grid">
            <!-- === TRAVELLER DETAILS FORM === -->
            <div class="form-card">
                <h2>Traveller Details</h2>

                <?php if ($submitted): ?>
                    <div class="form-alert success" role="status">
                        Your booking has been submitted successfully. Booking reference: <strong><?= htmlspecialchars($bookingRef ?? '') ?></strong>
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="form-alert error" role="alert">
                        Please review the highlighted fields and try again.
                    </div>
                <?php endif; ?>

                <form id="booking-form" class="booking-form" method="post" action="booking.php?id=<?= $package['id'] ?>" novalidate>
                    <?php csrf_field(); ?>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="firstName">First Name</label>
                            <input id="firstName" name="firstName" type="text" value="<?= old_value('firstName', $fields) ?>" placeholder="e.g. Jane" aria-invalid="<?= isset($errors['firstName']) ? 'true' : 'false' ?>">
                            <?php if (isset($errors['firstName'])): ?>
                                <p class="field-error"><?= field_error('firstName', $errors) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="lastName">Last Name</label>
                            <input id="lastName" name="lastName" type="text" value="<?= old_value('lastName', $fields) ?>" placeholder="e.g. Doe" aria-invalid="<?= isset($errors['lastName']) ? 'true' : 'false' ?>">
                            <?php if (isset($errors['lastName'])): ?>
                                <p class="field-error"><?= field_error('lastName', $errors) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" value="<?= old_value('email', $fields) ?>" placeholder="jane@example.com" aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>">
                            <?php if (isset($errors['email'])): ?>
                                <p class="field-error"><?= field_error('email', $errors) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="phone">Phone Number</label>
                            <input id="phone" name="phone" type="tel" value="<?= old_value('phone', $fields) ?>" placeholder="+1 (555) 000-0000" aria-invalid="<?= isset($errors['phone']) ? 'true' : 'false' ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <p class="field-error"><?= field_error('phone', $errors) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-field full-width">
                        <label for="nationality">Nationality</label>
                        <select id="nationality" name="nationality" aria-invalid="<?= isset($errors['nationality']) ? 'true' : 'false' ?>">
                            <option value="" disabled <?= old_value('nationality', $fields) === '' ? 'selected' : '' ?>>Select your nationality</option>
                            <option value="us" <?= old_value('nationality', $fields) === 'us' ? 'selected' : '' ?>>United States</option>
                            <option value="ca" <?= old_value('nationality', $fields) === 'ca' ? 'selected' : '' ?>>Canada</option>
                            <option value="uk" <?= old_value('nationality', $fields) === 'uk' ? 'selected' : '' ?>>United Kingdom</option>
                            <option value="au" <?= old_value('nationality', $fields) === 'au' ? 'selected' : '' ?>>Australia</option>
                            <option value="lk" <?= old_value('nationality', $fields) === 'lk' ? 'selected' : '' ?>>Sri Lanka</option>
                        </select>
                        <?php if (isset($errors['nationality'])): ?>
                            <p class="field-error"><?= field_error('nationality', $errors) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field full-width">
                        <label for="specialRequests">Special Requests <span class="optional">(Optional)</span></label>
                        <textarea id="specialRequests" name="specialRequests" rows="4" class="dashed" placeholder="Any dietary requirements, accessibility needs, etc."><?= old_value('specialRequests', $fields) ?></textarea>
                    </div>
                </form>
            </div>

            <!-- === BOOKING SUMMARY SIDEBAR === -->
            <aside class="booking-sidebar" aria-label="Booking summary">
                <div class="sidebar-card">
                    <h3>Booking Summary</h3>
                    <div class="package-summary">
                        <img class="package-thumb" src="<?= htmlspecialchars($basePath . $package['image']) ?>" alt="<?= htmlspecialchars($package['title']) ?> package image">
                        <div class="package-info">
                            <span class="package-title"><?= htmlspecialchars($package['title']) ?></span>
                            <span class="package-duration"><?= htmlspecialchars($package['duration_days'] . ' Days / ' . $package['duration_nights'] . ' Nights') ?></span>
                        </div>
                    </div>
                    <div class="summary-details">
                        <div class="detail-row">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span><?= date('d M Y') ?></span>
                        </div>
                        <div class="detail-row">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <span><?= $guestCount ?> Adults</span>
                        </div>
                    </div>
                </div>

                <div class="sidebar-card">
                    <h3>Price Details</h3>
                    <div class="price-breakdown">
                        <div class="price-row">
                            <span>Price/Person (<?= formatPrice($package['price']) ?> x <?= $guestCount ?>)</span>
                            <span class="price-value"><?= formatPrice($subtotal) ?></span>
                        </div>
                        <div class="price-row">
                            <span>Taxes &amp; Fees</span>
                            <span class="price-value"><?= formatPrice($taxes) ?></span>
                        </div>
                    </div>
                    <div class="price-total">
                        <span class="total-label">Total</span>
                        <span class="total-value"><?= formatPrice($total) ?></span>
                    </div>
                    <!-- Submit button triggers the booking form via form attribute -->
                    <button type="submit" form="booking-form" class="pay-button">
                        Pay now
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"></path>
                            <path d="M13 6l6 6-6 6"></path>
                        </svg>
                    </button>
                </div>
            </aside>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>
