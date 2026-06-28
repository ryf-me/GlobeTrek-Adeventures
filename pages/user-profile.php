<?php
/**
 * File: pages/user-profile.php
 * Purpose: Allows authenticated users to view and update their profile information,
 *          including name, phone, DOB, gender, location, bio, and profile photo.
 * Dependencies: config/database.php, config/csrf.php, js/script.js
 * Used By: User sidebar navigation (user-sidebar.php)
 * Parent Files: None (standalone page rendered in browser)
 * Child Files: Includes navbar.php, user-sidebar.php, footer.php
 * @package GlobeTrek\Pages
 */

session_start();

// === AUTH GUARD ===
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// === DATABASE & CONFIG ===
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
$db = getDB();
$userId = $_SESSION['user_id'];

// === FLASH MESSAGES ===
$success = '';
$errors = [];

// === FETCH CURRENT USER DATA ===
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

// Safety redirect if user record no longer exists
if (!$user) {
    header('Location: login.php');
    exit;
}

// === FORM FIELD DEFAULTS ===
// Pre-populate form fields from database; use null coalescing for optional columns
$fields = [
    'full_name'    => $user['full_name'],
    'phone'        => $user['phone'] ?? '',
    'date_of_birth'=> $user['date_of_birth'] ?? '',
    'gender'       => $user['gender'] ?? '',
    'country'      => $user['country'] ?? '',
    'city'         => $user['city'] ?? '',
    'bio'          => $user['bio'] ?? '',
];
$profilePhoto = $user['profile_photo'] ?? '';

// === HANDLE FORM SUBMISSION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation — prevents cross-site request forgery attacks
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    }

    // Sanitize and store form field values
    $fields['full_name']     = trim($_POST['full_name'] ?? '');
    $fields['phone']         = trim($_POST['phone'] ?? '');
    $fields['date_of_birth'] = $_POST['date_of_birth'] ?? '';
    $fields['gender']        = $_POST['gender'] ?? '';
    $fields['country']       = trim($_POST['country'] ?? '');
    $fields['city']          = trim($_POST['city'] ?? '');
    $fields['bio']           = trim($_POST['bio'] ?? '');

    // === VALIDATION ===
    if ($fields['full_name'] === '') {
        $errors['full_name'] = 'Please enter your name.';
    }
    if (strlen($fields['bio']) > 500) {
        $errors['bio'] = 'Bio must be 500 characters or fewer.';
    }

    // === PROFILE PHOTO UPLOAD ===
    // Only process if a file was uploaded without errors
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $tmpFile = $_FILES['profile_photo']['tmp_name'];
        $fileSize = $_FILES['profile_photo']['size'];
        $fileType = $_FILES['profile_photo']['type'];
        $fileExt = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));

        // Security: whitelist allowed image extensions and MIME types
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($fileExt, $allowedExts) || !in_array($fileType, $allowedTypes)) {
            $errors['profile_photo'] = 'Only JPG, PNG, GIF, and WebP images are allowed.';
        } elseif ($fileSize > 2 * 1024 * 1024) {
            // Enforce 2MB file size limit
            $errors['profile_photo'] = 'Profile photo must be under 2MB.';
        } else {
            // Generate unique filename to prevent collisions and path traversal
            $newFilename = 'user_' . $userId . '_' . time() . '.' . $fileExt;
            $uploadDir = __DIR__ . '/../images/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $destPath = $uploadDir . $newFilename;

            if (move_uploaded_file($tmpFile, $destPath)) {
                // Delete old photo file if it exists to prevent orphaned files
                if ($profilePhoto && file_exists(__DIR__ . '/../' . $profilePhoto)) {
                    unlink(__DIR__ . '/../' . $profilePhoto);
                }
                $profilePhoto = 'images/profiles/' . $newFilename;
            } else {
                $errors['profile_photo'] = 'Failed to upload photo. Please try again.';
            }
        }
    }

    // === HANDLE PHOTO REMOVAL ===
    // User can explicitly remove their profile photo via a hidden form field
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
        if ($profilePhoto && file_exists(__DIR__ . '/../' . $profilePhoto)) {
            unlink(__DIR__ . '/../' . $profilePhoto);
        }
        $profilePhoto = '';
    }

    // === UPDATE DATABASE ===
    // Only persist if no validation errors occurred
    if (empty($errors)) {
        $stmt = $db->prepare(
            "UPDATE users SET
                full_name = :full_name,
                phone = :phone,
                date_of_birth = :dob,
                gender = :gender,
                country = :country,
                city = :city,
                bio = :bio,
                profile_photo = :photo
             WHERE id = :id"
        );
        $stmt->execute([
            ':full_name' => $fields['full_name'],
            ':phone'     => $fields['phone'],
            ':dob'       => $fields['date_of_birth'] ?: null,
            ':gender'    => $fields['gender'] ?: null,
            ':country'   => $fields['country'] ?: null,
            ':city'      => $fields['city'] ?: null,
            ':bio'       => $fields['bio'] ?: null,
            ':photo'     => $profilePhoto ?: null,
            ':id'        => $userId,
        ]);

        // Update session data so navbar/sidebar reflect new name and photo immediately
        $_SESSION['user_name'] = $fields['full_name'];
        $_SESSION['user_profile_photo'] = $profilePhoto;
        $success = 'Profile updated successfully.';
    }
}

// === TEMPLATE HELPER FUNCTIONS ===

/**
 * Render a field value for re-populating form inputs after submission.
 * Uses ENT_QUOTES to escape both single and double quotes in attributes.
 */
function usr_old(string $field, array $fields): string
{
    return htmlspecialchars($fields[$field] ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Render a validation error message for a given field.
 */
function usr_error(string $field, array $errors): string
{
    return htmlspecialchars($errors[$field] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Info - GlobeTrek</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Winky+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/user-sidebar.css">
    <link rel="stylesheet" href="../css/user-profile.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="usr-page">
    <!-- === NAVBAR === -->
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <div class="usr-layout">
            <!-- === SIDEBAR === -->
            <?php $activePage = 'profile'; include '../includes/user-sidebar.php'; ?>

            <!-- === MAIN CONTENT === -->
            <div class="usr-canvas">
                <div class="usr-page-header">
                    <h1>Profile Information</h1>
                    <p>Update your personal details and how others see you.</p>
                </div>

                <!-- === FLASH MESSAGES === -->
                <?php if ($success !== ''): ?>
                    <div class="usr-alert success" role="status">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="usr-alert error" role="alert">
                        Please review the highlighted fields and try again.
                    </div>
                <?php endif; ?>

                <!-- === PROFILE FORM === -->
                <!-- enctype="multipart/form-data" required for file upload -->
                <form class="usr-form" method="post" action="user-profile.php" enctype="multipart/form-data" novalidate>
                    <?php csrf_field(); ?>

                    <!-- === PROFILE PHOTO SECTION === -->
                    <div class="usr-section">
                        <div class="usr-photo-row">
                            <div class="usr-avatar">
                                <?php if ($profilePhoto): ?>
                                    <img src="../<?php echo htmlspecialchars($profilePhoto); ?>" alt="Profile photo">
                                <?php else: ?>
                                    <span class="material-symbols-outlined">person</span>
                                <?php endif; ?>
                            </div>
                            <div class="usr-photo-info">
                                <p class="usr-photo-label">Profile Photo</p>
                                <p class="usr-photo-hint">Recommended size: 256x256px. Max 2MB.</p>
                                <div class="usr-photo-actions">
                                    <!-- Hidden file input triggered by styled label -->
                                    <label class="usr-btn usr-btn-primary" for="photo-upload">Change Photo</label>
                                    <input type="file" id="photo-upload" name="profile_photo" accept="image/*" class="sr-only">
                                    <?php if ($profilePhoto): ?>
                                        <!-- Remove photo uses a special POST value; JS confirm dialog for safety -->
                                        <button type="submit" name="remove_photo" value="1" class="usr-btn usr-btn-outline"
                                                onclick="return confirm('Remove your profile photo?')">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($errors['profile_photo'])): ?>
                                    <p class="field-error"><?php echo usr_error('profile_photo', $errors); ?></p>
                                <?php endif; ?>
                                <p id="photo-preview-name" class="usr-photo-hint" style="margin-top:0.25rem;font-weight:600;color:var(--inq-on-surface,#1a1c1c);"></p>
                            </div>
                        </div>
                    </div>

                    <!-- === PERSONAL DETAILS === -->
                    <div class="usr-section">
                        <h3 class="usr-section-title">Personal Details</h3>
                        <div class="usr-fields-grid">
                            <div class="usr-field">
                                <label for="full-name">Full Name</label>
                                <input id="full-name" name="full_name" type="text"
                                       value="<?php echo usr_old('full_name', $fields); ?>"
                                       aria-invalid="<?php isset($errors['full_name']) ? 'true' : 'false'; ?>">
                                <?php if (isset($errors['full_name'])): ?>
                                    <p class="field-error"><?php echo usr_error('full_name', $errors); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="usr-field">
                                <label for="email">
                                    Email Address
                                    <button type="button" class="usr-change-link">Change</button>
                                </label>
                                <!-- Email is display-only; cannot be changed from this form -->
                                <input id="email" type="email"
                                       value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                       disabled>
                            </div>

                            <div class="usr-field">
                                <label for="phone">Phone Number</label>
                                <input id="phone" name="phone" type="tel"
                                       value="<?php echo usr_old('phone', $fields); ?>"
                                       placeholder="+1 (555) 000-0000">
                            </div>

                            <div class="usr-field">
                                <label for="dob">Date of Birth</label>
                                <input id="dob" name="date_of_birth" type="date"
                                       value="<?php echo htmlspecialchars($fields['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>

                            <div class="usr-field usr-field-full">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="" <?php echo ($fields['gender'] ?? '') === '' ? 'selected' : ''; ?>>Prefer not to say</option>
                                    <option value="Female" <?php echo ($fields['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Male" <?php echo ($fields['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Non-binary" <?php echo ($fields['gender'] ?? '') === 'Non-binary' ? 'selected' : ''; ?>>Non-binary</option>
                                    <option value="Other" <?php echo ($fields['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- === LOCATION === -->
                    <div class="usr-section">
                        <h3 class="usr-section-title">Location</h3>
                        <div class="usr-fields-grid">
                            <div class="usr-field">
                                <label for="country">Country/Region</label>
                                <select id="country" name="country">
                                    <option value="" <?php echo ($fields['country'] ?? '') === '' ? 'selected' : ''; ?>>Select country</option>
                                    <option value="United States" <?php echo ($fields['country'] ?? '') === 'United States' ? 'selected' : ''; ?>>United States</option>
                                    <option value="Canada" <?php echo ($fields['country'] ?? '') === 'Canada' ? 'selected' : ''; ?>>Canada</option>
                                    <option value="United Kingdom" <?php echo ($fields['country'] ?? '') === 'United Kingdom' ? 'selected' : ''; ?>>United Kingdom</option>
                                    <option value="Australia" <?php echo ($fields['country'] ?? '') === 'Australia' ? 'selected' : ''; ?>>Australia</option>
                                    <option value="Japan" <?php echo ($fields['country'] ?? '') === 'Japan' ? 'selected' : ''; ?>>Japan</option>
                                    <option value="Germany" <?php echo ($fields['country'] ?? '') === 'Germany' ? 'selected' : ''; ?>>Germany</option>
                                    <option value="France" <?php echo ($fields['country'] ?? '') === 'France' ? 'selected' : ''; ?>>France</option>
                                    <option value="Sri Lanka" <?php echo ($fields['country'] ?? '') === 'Sri Lanka' ? 'selected' : ''; ?>>Sri Lanka</option>
                                    <option value="India" <?php echo ($fields['country'] ?? '') === 'India' ? 'selected' : ''; ?>>India</option>
                                    <option value="Other" <?php echo ($fields['country'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="usr-field">
                                <label for="city">City</label>
                                <input id="city" name="city" type="text"
                                       value="<?php echo usr_old('city', $fields); ?>"
                                       placeholder="Enter your city">
                            </div>
                        </div>
                    </div>

                    <!-- === BIO === -->
                    <div class="usr-section">
                        <h3 class="usr-section-title">Bio</h3>
                        <div class="usr-field">
                            <label for="bio">About Me</label>
                            <textarea id="bio" name="bio" rows="4"
                                      maxlength="500"
                                      placeholder="Tell us about yourself..."><?php echo usr_old('bio', $fields); ?></textarea>
                            <?php if (isset($errors['bio'])): ?>
                                <p class="field-error"><?php echo usr_error('bio', $errors); ?></p>
                            <?php endif; ?>
                            <p class="usr-char-count">
                                <span id="bio-count"><?php echo mb_strlen($fields['bio'] ?? ''); ?></span> / 500 characters
                            </p>
                        </div>
                    </div>

                    <!-- === SUBMIT === -->
                    <div class="usr-actions-bar">
                        <button type="submit" class="usr-btn usr-btn-primary usr-btn-submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- === FOOTER === -->
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script>
    // === BIO CHARACTER COUNTER ===
    // Live-updates the character count display as user types
    var bioTextarea = document.getElementById('bio');
    var bioCount = document.getElementById('bio-count');
    if (bioTextarea && bioCount) {
        bioTextarea.addEventListener('input', function() {
            bioCount.textContent = this.value.length;
        });
    }

    // === PHOTO FILE NAME PREVIEW ===
    // Shows the selected file name below the upload button for user feedback
    var photoInput = document.getElementById('photo-upload');
    var photoName = document.getElementById('photo-preview-name');
    if (photoInput && photoName) {
        photoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                photoName.textContent = this.files[0].name;
            } else {
                photoName.textContent = '';
            }
        });
    }
    </script>
</body>
</html>
