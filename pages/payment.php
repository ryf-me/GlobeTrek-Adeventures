<?php
/**
 * Payment Page (Step 4 — Final)
 *
 * Processes simulated credit card and PayPal payments. Validates card
 * details, generates transaction ID, updates booking to 'confirmed',
 * and creates a payment record. Includes CSRF protection.
 * Requires user to be logged in.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'payment.php' . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '');
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
$db = getDB();

$bookingRef = isset($_GET['ref']) ? trim($_GET['ref']) : ($_SESSION['payment_booking_ref'] ?? '');

if ($bookingRef === '') {
    header('Location: packages.php');
    exit;
}

$stmt = $db->prepare(
    "SELECT b.*, p.title, p.price, p.image, p.duration_days, p.duration_nights
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     WHERE b.booking_reference = :ref AND b.user_id = :user_id"
);
$stmt->execute([':ref' => $bookingRef, ':user_id' => $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: packages.php');
    exit;
}

$guestCount = $booking['num_travellers'];
$basePrice = $booking['price'] * $guestCount;
$taxes = round($basePrice * 0.10);
$serviceFees = round($basePrice * 0.025);
$total = $basePrice + $taxes + $serviceFees;

$fields = [
    'cardholderName' => '',
    'cardNumber' => '',
    'expiryDate' => '',
    'cvv' => '',
    'addressLine1' => '',
    'addressLine2' => '',
    'city' => '',
    'state' => '',
    'zip' => '',
    'country' => '',
    'paymentMethod' => 'credit_card',
];
$errors = [];
$submitted = false;
$paymentSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    }

    foreach ($fields as $key => $value) {
        $fields[$key] = trim($_POST[$key] ?? '');
    }

    $fields['paymentMethod'] = in_array($fields['paymentMethod'], ['credit_card', 'paypal']) ? $fields['paymentMethod'] : 'credit_card';

    if ($fields['paymentMethod'] === 'credit_card') {
        if ($fields['cardholderName'] === '') {
            $errors['cardholderName'] = 'Please enter the cardholder name.';
        }
        if ($fields['cardNumber'] === '') {
            $errors['cardNumber'] = 'Please enter the card number.';
        } elseif (!preg_replace('/\s+/', '', $fields['cardNumber']) === '' || strlen(preg_replace('/\s+/', '', $fields['cardNumber'])) !== 16 || !ctype_digit(preg_replace('/\s+/', '', $fields['cardNumber']))) {
            $errors['cardNumber'] = 'Please enter a valid 16-digit card number.';
        }
        if ($fields['expiryDate'] === '') {
            $errors['expiryDate'] = 'Please enter the expiry date.';
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $fields['expiryDate'])) {
            $errors['expiryDate'] = 'Please enter a valid date (MM/YY).';
        } else {
            $parts = explode('/', $fields['expiryDate']);
            $month = (int) $parts[0];
            $year = (int) ('20' . $parts[1]);
            $now = new DateTime();
            $expiry = new DateTime("$year-$month-28 23:59:59");
            if ($expiry < $now) {
                $errors['expiryDate'] = 'This card has expired.';
            }
        }
        if ($fields['cvv'] === '') {
            $errors['cvv'] = 'Please enter the CVV.';
        } elseif (!preg_match('/^\d{3,4}$/', $fields['cvv'])) {
            $errors['cvv'] = 'Please enter a valid CVV (3-4 digits).';
        }
    }

    if ($fields['addressLine1'] === '') {
        $errors['addressLine1'] = 'Please enter your address.';
    }
    if ($fields['city'] === '') {
        $errors['city'] = 'Please enter your city.';
    }
    if ($fields['state'] === '') {
        $errors['state'] = 'Please enter your state/province.';
    }
    if ($fields['zip'] === '') {
        $errors['zip'] = 'Please enter your ZIP/postal code.';
    }
    if ($fields['country'] === '' || $fields['country'] === 'Select Country') {
        $errors['country'] = 'Please select your country.';
    }

    $submitted = empty($errors);

    if ($submitted) {
        $cardLastFour = '';
        $cardBrand = '';
        if ($fields['paymentMethod'] === 'credit_card') {
            $cleanCard = preg_replace('/\s+/', '', $fields['cardNumber']);
            $cardLastFour = substr($cleanCard, -4);
            $firstDigit = substr($cleanCard, 0, 1);
            if ($firstDigit === '4') $cardBrand = 'Visa';
            elseif ($firstDigit === '5') $cardBrand = 'Mastercard';
            elseif ($firstDigit === '3') $cardBrand = 'Amex';
            else $cardBrand = 'Card';
        }

        $transactionId = 'TXN-' . strtoupper(bin2hex(random_bytes(8)));

        $updateStmt = $db->prepare(
            "UPDATE bookings
             SET status = 'confirmed',
                 payment_method = :payment_method,
                 card_last_four = :card_last_four
             WHERE booking_reference = :ref AND user_id = :user_id"
        );
        $updateStmt->execute([
            ':payment_method' => $fields['paymentMethod'],
            ':card_last_four' => $cardLastFour,
            ':ref' => $bookingRef,
            ':user_id' => $_SESSION['user_id'],
        ]);

        $billingAddress = trim($fields['addressLine1'] . ', ' . $fields['addressLine2'] . ', ' . $fields['city'] . ', ' . $fields['state'] . ' ' . $fields['zip'] . ', ' . $fields['country']);
        $billingAddress = preg_replace('/,\s*,/', ',', $billingAddress);

        $payStmt = $db->prepare(
            "INSERT INTO payments (booking_id, user_id, amount, payment_method, card_last_four, card_brand, transaction_id, status, billing_address)
             VALUES (:booking_id, :user_id, :amount, :method, :card_four, :card_brand, :txn_id, 'completed', :billing)"
        );
        $payStmt->execute([
            ':booking_id' => $booking['id'],
            ':user_id' => $_SESSION['user_id'],
            ':amount' => $total,
            ':method' => $fields['paymentMethod'],
            ':card_four' => $cardLastFour,
            ':card_brand' => $cardBrand,
            ':txn_id' => $transactionId,
            ':billing' => $billingAddress,
        ]);

        $paymentSuccess = true;
        unset($_SESSION['payment_booking_ref']);
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
    <title>Payment - <?= htmlspecialchars($booking['title']) ?> - GlobeTrek</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/payment.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="payment-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main class="payment-shell">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="../index.php#home">Home</a>
            <span aria-hidden="true">/</span>
            <a href="packages.php">Tour Packages</a>
            <span aria-hidden="true">/</span>
            <a href="package-details.php?id=<?= $booking['package_id'] ?>"><?= htmlspecialchars($booking['title']) ?></a>
            <span aria-hidden="true">/</span>
            <span>Payment</span>
        </nav>

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
            <div class="progress-step completed">
                <div class="step-circle">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <span class="step-label">2. Traveller Details</span>
            </div>
            <div class="progress-line completed"></div>
            <div class="progress-step completed">
                <div class="step-circle">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <span class="step-label">3. Review &amp; Confirm</span>
            </div>
            <div class="progress-line completed"></div>
            <div class="progress-step active">
                <div class="step-circle">4</div>
                <span class="step-label">4. Payment</span>
            </div>
        </div>

        <div class="payment-grid">
            <div class="form-card">
                <?php if ($paymentSuccess): ?>
                    <div class="payment-success">
                        <div class="success-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                        <h2>Payment Successful!</h2>
                        <p class="success-message">Your booking has been confirmed. A confirmation email will be sent to your registered email address.</p>
                        <p class="booking-ref">Booking Reference: <?= htmlspecialchars($booking['booking_reference']) ?></p>
                        <div class="success-actions">
                            <a href="packages.php" class="btn-primary">Browse More Packages</a>
                            <a href="../index.php#home" class="btn-secondary">Back to Home</a>
                        </div>
                    </div>
                <?php else: ?>
                    <h2>Payment Method</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="form-alert error" role="alert">
                            Please review the highlighted fields and try again.
                        </div>
                    <?php endif; ?>

                    <form id="payment-form" class="payment-form" method="post" action="payment.php?ref=<?= htmlspecialchars($booking['booking_reference']) ?>" novalidate>
                        <?php csrf_field(); ?>
                        <div class="payment-tabs" role="tablist">
                            <button type="button" class="payment-tab <?= $fields['paymentMethod'] === 'credit_card' ? 'active' : '' ?>" role="tab" data-method="credit_card" aria-selected="<?= $fields['paymentMethod'] === 'credit_card' ? 'true' : 'false' ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                                Credit/Debit Card
                            </button>
                            <button type="button" class="payment-tab <?= $fields['paymentMethod'] === 'paypal' ? 'active' : '' ?>" role="tab" data-method="paypal" aria-selected="<?= $fields['paymentMethod'] === 'paypal' ? 'true' : 'false' ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2a10 10 0 1 0 10 10H12V2z"></path>
                                    <path d="M12 12 2.1 9.7"></path>
                                    <path d="m12 12 4.2 7"></path>
                                    <path d="M16.2 19H21V7"></path>
                                </svg>
                                PayPal
                            </button>
                            <input type="hidden" name="paymentMethod" id="paymentMethod" value="<?= old_value('paymentMethod', $fields) ?>">
                        </div>

                        <div id="card-fields" <?= $fields['paymentMethod'] === 'paypal' ? 'style="display:none"' : '' ?>>
                            <div class="form-section">
                                <div class="form-field full-width">
                                    <label for="cardholderName">Cardholder Name</label>
                                    <input id="cardholderName" name="cardholderName" type="text" value="<?= old_value('cardholderName', $fields) ?>" placeholder="John Doe" aria-invalid="<?= isset($errors['cardholderName']) ? 'true' : 'false' ?>">
                                    <?php if (isset($errors['cardholderName'])): ?>
                                        <p class="field-error"><?= field_error('cardholderName', $errors) ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="form-field full-width">
                                    <label for="cardNumber">Card Number</label>
                                    <div class="input-with-icon">
                                        <input id="cardNumber" name="cardNumber" type="text" value="<?= old_value('cardNumber', $fields) ?>" placeholder="0000 0000 0000 0000" maxlength="19" aria-invalid="<?= isset($errors['cardNumber']) ? 'true' : 'false' ?>">
                                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                            <line x1="1" y1="10" x2="23" y2="10"></line>
                                        </svg>
                                    </div>
                                    <?php if (isset($errors['cardNumber'])): ?>
                                        <p class="field-error"><?= field_error('cardNumber', $errors) ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="form-row">
                                    <div class="form-field">
                                        <label for="expiryDate">Expiry Date</label>
                                        <input id="expiryDate" name="expiryDate" type="text" value="<?= old_value('expiryDate', $fields) ?>" placeholder="MM/YY" maxlength="5" aria-invalid="<?= isset($errors['expiryDate']) ? 'true' : 'false' ?>">
                                        <?php if (isset($errors['expiryDate'])): ?>
                                            <p class="field-error"><?= field_error('expiryDate', $errors) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-field">
                                        <label for="cvv">CVV</label>
                                        <input id="cvv" name="cvv" type="text" value="<?= old_value('cvv', $fields) ?>" placeholder="123" maxlength="4" aria-invalid="<?= isset($errors['cvv']) ? 'true' : 'false' ?>">
                                        <?php if (isset($errors['cvv'])): ?>
                                            <p class="field-error"><?= field_error('cvv', $errors) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="paypal-fields" <?= $fields['paymentMethod'] === 'credit_card' ? 'style="display:none"' : '' ?>>
                            <div class="form-alert success" style="background: rgba(231, 111, 81, 0.06); border-color: rgba(231, 111, 81, 0.18); color: var(--ink);">
                                You will be redirected to PayPal to complete your payment after clicking "Complete Payment".
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="form-section-title">Billing Address</h3>

                            <div class="form-field full-width">
                                <label for="addressLine1">Address Line 1</label>
                                <input id="addressLine1" name="addressLine1" type="text" value="<?= old_value('addressLine1', $fields) ?>" placeholder="123 Main St" aria-invalid="<?= isset($errors['addressLine1']) ? 'true' : 'false' ?>">
                                <?php if (isset($errors['addressLine1'])): ?>
                                    <p class="field-error"><?= field_error('addressLine1', $errors) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field full-width">
                                <label for="addressLine2">Address Line 2 <span class="optional">(Optional)</span></label>
                                <input id="addressLine2" name="addressLine2" type="text" value="<?= old_value('addressLine2', $fields) ?>" placeholder="Apt, Suite, etc." class="dashed">
                            </div>

                            <div class="form-row three-col">
                                <div class="form-field">
                                    <label for="city">City</label>
                                    <input id="city" name="city" type="text" value="<?= old_value('city', $fields) ?>" placeholder="City" aria-invalid="<?= isset($errors['city']) ? 'true' : 'false' ?>">
                                    <?php if (isset($errors['city'])): ?>
                                        <p class="field-error"><?= field_error('city', $errors) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="form-field">
                                    <label for="state">State/Province</label>
                                    <input id="state" name="state" type="text" value="<?= old_value('state', $fields) ?>" placeholder="State" aria-invalid="<?= isset($errors['state']) ? 'true' : 'false' ?>">
                                    <?php if (isset($errors['state'])): ?>
                                        <p class="field-error"><?= field_error('state', $errors) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="form-field">
                                    <label for="zip">ZIP/Postal Code</label>
                                    <input id="zip" name="zip" type="text" value="<?= old_value('zip', $fields) ?>" placeholder="ZIP" aria-invalid="<?= isset($errors['zip']) ? 'true' : 'false' ?>">
                                    <?php if (isset($errors['zip'])): ?>
                                        <p class="field-error"><?= field_error('zip', $errors) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-field full-width">
                                <label for="country">Country</label>
                                <select id="country" name="country" aria-invalid="<?= isset($errors['country']) ? 'true' : 'false' ?>">
                                    <option value="Select Country" <?= old_value('country', $fields) === '' || old_value('country', $fields) === 'Select Country' ? 'selected' : '' ?>>Select Country</option>
                                    <option value="United States" <?= old_value('country', $fields) === 'United States' ? 'selected' : '' ?>>United States</option>
                                    <option value="Canada" <?= old_value('country', $fields) === 'Canada' ? 'selected' : '' ?>>Canada</option>
                                    <option value="United Kingdom" <?= old_value('country', $fields) === 'United Kingdom' ? 'selected' : '' ?>>United Kingdom</option>
                                    <option value="Australia" <?= old_value('country', $fields) === 'Australia' ? 'selected' : '' ?>>Australia</option>
                                    <option value="Sri Lanka" <?= old_value('country', $fields) === 'Sri Lanka' ? 'selected' : '' ?>>Sri Lanka</option>
                                    <option value="Germany" <?= old_value('country', $fields) === 'Germany' ? 'selected' : '' ?>>Germany</option>
                                    <option value="France" <?= old_value('country', $fields) === 'France' ? 'selected' : '' ?>>France</option>
                                    <option value="Japan" <?= old_value('country', $fields) === 'Japan' ? 'selected' : '' ?>>Japan</option>
                                </select>
                                <?php if (isset($errors['country'])): ?>
                                    <p class="field-error"><?= field_error('country', $errors) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <div class="payment-actions">
                        <a href="booking.php?id=<?= $booking['package_id'] ?>" class="btn-back">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 12H5"></path>
                                <path d="M12 19l-7-7 7-7"></path>
                            </svg>
                            Back
                        </a>
                        <button type="submit" form="payment-form" class="btn-pay">
                            Complete Payment
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14"></path>
                                <path d="M13 6l6 6-6 6"></path>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="booking-sidebar" aria-label="Booking summary">
                <div class="sidebar-card">
                    <h3>Booking Summary</h3>
                    <div class="package-summary">
                        <img class="package-thumb" src="<?= htmlspecialchars($booking['image']) ?>" alt="<?= htmlspecialchars($booking['title']) ?> package image">
                        <div class="package-info">
                            <span class="package-title"><?= htmlspecialchars($booking['title']) ?></span>
                            <span class="package-duration"><?= htmlspecialchars($booking['duration_days'] . ' Days / ' . $booking['duration_nights'] . ' Nights') ?></span>
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
                            <span>Base Price (Rs.<?= number_format($booking['price'], 2) ?> x <?= $guestCount ?>)</span>
                            <span class="price-value">Rs.<?= number_format($basePrice, 2) ?></span>
                        </div>
                        <div class="price-row">
                            <span>Taxes (10%)</span>
                            <span class="price-value">Rs.<?= number_format($taxes, 2) ?></span>
                        </div>
                        <div class="price-row">
                            <span>Service Fees</span>
                            <span class="price-value">Rs.<?= number_format($serviceFees, 2) ?></span>
                        </div>
                    </div>
                    <div class="price-total">
                        <span class="total-label">Total</span>
                        <span class="total-value">Rs.<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="secure-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Secure SSL Checkout
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script>
    (function() {
        const tabs = document.querySelectorAll('.payment-tab');
        const methodInput = document.getElementById('paymentMethod');
        const cardFields = document.getElementById('card-fields');
        const paypalFields = document.getElementById('paypal-fields');

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var method = this.getAttribute('data-method');
                methodInput.value = method;

                tabs.forEach(function(t) {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');

                if (method === 'credit_card') {
                    cardFields.style.display = '';
                    paypalFields.style.display = 'none';
                } else {
                    cardFields.style.display = 'none';
                    paypalFields.style.display = '';
                }
            });
        });

        var cardNumberInput = document.getElementById('cardNumber');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function(e) {
                var value = this.value.replace(/\D/g, '');
                var formatted = '';
                for (var i = 0; i < value.length && i < 16; i++) {
                    if (i > 0 && i % 4 === 0) formatted += ' ';
                    formatted += value[i];
                }
                this.value = formatted;
            });
        }

        var expiryInput = document.getElementById('expiryDate');
        if (expiryInput) {
            expiryInput.addEventListener('input', function(e) {
                var value = this.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                this.value = value;
            });
        }

        var cvvInput = document.getElementById('cvv');
        if (cvvInput) {
            cvvInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '').substring(0, 4);
            });
        }
    })();
    </script>
</body>
</html>
