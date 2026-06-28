<?php
/**
 * File: admin/accommodation-edit.php
 * Purpose: Create or edit an accommodation record. Handles form display, validation, image upload, and slug generation.
 * Dependencies: admin/includes/header.php (auth, DB, CSRF), admin/includes/sidebar.php, admin/includes/footer.php, config/helpers.php (csrf_field, CURRENCY_CODE)
 * Used By: Admin staff creating/editing accommodations via admin/accommodations.php
 * Parent Files: admin/accommodations.php (links to this page)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Edit Accommodation';
require_once __DIR__ . '/includes/header.php';

// === LOAD EXISTING ACCOMMODATION (EDIT MODE) ===
$accomId = (int)($_GET['id'] ?? 0);
$isEdit = $accomId > 0;
$accom = null;

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM accommodations WHERE id = :id");
    $stmt->execute([':id' => $accomId]);
    $accom = $stmt->fetch();
    // Redirect if accommodation not found — prevents accessing edit form for a deleted record.
    if (!$accom) { header('Location: accommodations.php'); exit; }
}

$errors = [];

// === FORM SUBMISSION HANDLER ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation first — all subsequent processing depends on a valid token.
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
    // Sanitize text inputs via trim().
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $shortDescription = trim($_POST['short_description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $propertyType = trim($_POST['property_type'] ?? '');
    // Cast numeric inputs to float for proper price/rating handling.
    $pricePerNight = (float)($_POST['price_per_night'] ?? 0);
    $rating = (float)($_POST['rating'] ?? 0);
    // Checkbox inputs: present in POST only when checked; default to 0.
    $hasWifi = isset($_POST['has_wifi']) ? 1 : 0;
    $hasPool = isset($_POST['has_pool']) ? 1 : 0;
    $hasSpa = isset($_POST['has_spa']) ? 1 : 0;
    $hasRestaurant = isset($_POST['has_restaurant']) ? 1 : 0;
    $hasFitness = isset($_POST['has_fitness']) ? 1 : 0;
    $providerName = trim($_POST['provider_name'] ?? '');
    $providerEmail = trim($_POST['provider_email'] ?? '');
    $providerPhone = trim($_POST['provider_phone'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Validation: required fields.
    if ($name === '') $errors[] = 'Name is required.';
    if ($location === '') $errors[] = 'Location is required.';
    if ($pricePerNight <= 0) $errors[] = 'Price per night must be positive.';

    // === IMAGE UPLOAD HANDLER ===
    // Preserve the existing image path unless a new file is uploaded.
    $imagePath = $accom['image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        // Whitelist of allowed image extensions — prevents uploading executable files.
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Image must be JPG, PNG, or WebP.';
        // Enforce 5MB file size limit to prevent storage abuse.
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image must be under 5MB.';
        } else {
            $uploadDir = __DIR__ . '/../images/accommodations/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            // Generate a unique filename using timestamp + random bytes to prevent collisions and overwrites.
            $filename = 'accom_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $imagePath = 'images/accommodations/' . $filename;
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }
    }

    // === DATABASE INSERT / UPDATE ===
    if (empty($errors)) {
        // Generate URL-friendly slug from the accommodation name for SEO-friendly URLs.
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        if ($isEdit) {
            $stmt = $db->prepare("UPDATE accommodations SET name=:name, slug=:slug, description=:desc, short_description=:short, location=:loc, property_type=:type, price_per_night=:price, rating=:rating, image=:image, has_wifi=:wifi, has_pool=:pool, has_spa=:spa, has_restaurant=:rest, has_fitness=:fit, provider_name=:prov_name, provider_email=:prov_email, provider_phone=:prov_phone, is_featured=:feat, is_active=:act WHERE id=:id");
            $stmt->execute([':name'=>$name, ':slug'=>$slug, ':desc'=>$description, ':short'=>$shortDescription, ':loc'=>$location, ':type'=>$propertyType, ':price'=>$pricePerNight, ':rating'=>$rating, ':image'=>$imagePath, ':wifi'=>$hasWifi, ':pool'=>$hasPool, ':spa'=>$hasSpa, ':rest'=>$hasRestaurant, ':fit'=>$hasFitness, ':prov_name'=>$providerName, ':prov_email'=>$providerEmail, ':prov_phone'=>$providerPhone, ':feat'=>$isFeatured, ':act'=>$isActive, ':id'=>$accomId]);
        } else {
            $stmt = $db->prepare("INSERT INTO accommodations (name, slug, description, short_description, location, property_type, price_per_night, rating, image, has_wifi, has_pool, has_spa, has_restaurant, has_fitness, provider_name, provider_email, provider_phone, is_featured, is_active) VALUES (:name, :slug, :desc, :short, :loc, :type, :price, :rating, :image, :wifi, :pool, :spa, :rest, :fit, :prov_name, :prov_email, :prov_phone, :feat, :act)");
            $stmt->execute([':name'=>$name, ':slug'=>$slug, ':desc'=>$description, ':short'=>$shortDescription, ':loc'=>$location, ':type'=>$propertyType, ':price'=>$pricePerNight, ':rating'=>$rating, ':image'=>$imagePath, ':wifi'=>$hasWifi, ':pool'=>$hasPool, ':spa'=>$hasSpa, ':rest'=>$hasRestaurant, ':fit'=>$hasFitness, ':prov_name'=>$providerName, ':prov_email'=>$providerEmail, ':prov_phone'=>$providerPhone, ':feat'=>$isFeatured, ':act'=>$isActive]);
        }
        // PRG pattern: redirect after successful save.
        header('Location: accommodations.php?saved=1');
        exit;
    }
    }
}

// === SIDEBAR ===
include __DIR__ . '/includes/sidebar.php';

// === DEFAULT VALUES FOR NEW ACCOMMODATION ===
// If not editing, populate fields with sensible defaults for the form.
if (!$accom) {
    $accom = ['name'=>'','description'=>'','short_description'=>'','location'=>'','property_type'=>'Hotel','price_per_night'=>0,'rating'=>0,'image'=>'','has_wifi'=>0,'has_pool'=>0,'has_spa'=>0,'has_restaurant'=>0,'has_fitness'=>0,'provider_name'=>'','provider_email'=>'','provider_phone'=>'','is_featured'=>0,'is_active'=>1];
}
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title"><?= $isEdit ? 'Edit Accommodation' : 'Add Accommodation' ?></h1>
        </div>
        <div class="adm-topbar-right">
            <a href="accommodations.php" class="adm-btn adm-btn-secondary"><span class="material-symbols-outlined">arrow_back</span> Back</a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === VALIDATION ERRORS === -->
        <?php foreach ($errors as $err): ?>
            <div class="adm-alert adm-alert-error"><span class="material-symbols-outlined">error</span> <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <!-- === ACCOMMODATION EDIT FORM === -->
        <!-- enctype="multipart/form-data" is required for file uploads. -->
        <form method="post" enctype="multipart/form-data" novalidate>
            <?php csrf_field(); ?>

            <!-- === ACCOMMODATION DETAILS SECTION === -->
            <div class="adm-form-card">
                <h2>Accommodation Details</h2>
                <div class="adm-form-grid">
                    <div class="adm-form-field full-width">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($accom['name']) ?>" required>
                    </div>
                    <div class="adm-form-field full-width">
                        <label for="short_description">Short Description</label>
                        <input type="text" id="short_description" name="short_description" value="<?= htmlspecialchars($accom['short_description'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field full-width">
                        <label for="description">Full Description</label>
                        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($accom['description'] ?? '') ?></textarea>
                    </div>
                    <div class="adm-form-field">
                        <label for="location">Location *</label>
                        <input type="text" id="location" name="location" value="<?= htmlspecialchars($accom['location']) ?>" required>
                    </div>
                    <!-- Property type dropdown with predefined options -->
                    <div class="adm-form-field">
                        <label for="property_type">Property Type</label>
                        <select id="property_type" name="property_type">
                            <?php foreach (['Hotel','Villa','Boutique','Resort'] as $type): ?>
                                <option value="<?= $type ?>" <?= ($accom['property_type'] ?? 'Hotel') === $type ? 'selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-field">
                        <label for="price_per_night">Price per Night (<?= CURRENCY_CODE ?>) *</label>
                        <input type="number" id="price_per_night" name="price_per_night" min="0" step="0.01" value="<?= $accom['price_per_night'] ?>" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="rating">Rating</label>
                        <input type="number" id="rating" name="rating" min="0" max="5" step="0.1" value="<?= $accom['rating'] ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="image">Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <?php if (!empty($accom['image'])): ?>
                            <div class="adm-form-field-hint">Current: <?= htmlspecialchars($accom['image']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="adm-form-field">
                        <div class="adm-form-check" style="margin-top:1.5rem;">
                            <input type="checkbox" id="is_featured" name="is_featured" <?= $accom['is_featured'] ? 'checked' : '' ?>>
                            <label for="is_featured">Featured</label>
                        </div>
                        <div class="adm-form-check" style="margin-top:0.5rem;">
                            <input type="checkbox" id="is_active" name="is_active" <?= $accom['is_active'] ? 'checked' : '' ?>>
                            <label for="is_active">Active</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === AMENITIES SECTION === -->
            <div class="adm-form-card">
                <h2>Amenities</h2>
                <div class="adm-form-grid">
                    <div class="adm-form-field">
                        <div class="adm-form-check">
                            <input type="checkbox" id="has_wifi" name="has_wifi" <?= $accom['has_wifi'] ? 'checked' : '' ?>>
                            <label for="has_wifi">WiFi</label>
                        </div>
                    </div>
                    <div class="adm-form-field">
                        <div class="adm-form-check">
                            <input type="checkbox" id="has_pool" name="has_pool" <?= $accom['has_pool'] ? 'checked' : '' ?>>
                            <label for="has_pool">Pool</label>
                        </div>
                    </div>
                    <div class="adm-form-field">
                        <div class="adm-form-check">
                            <input type="checkbox" id="has_spa" name="has_spa" <?= $accom['has_spa'] ? 'checked' : '' ?>>
                            <label for="has_spa">Spa</label>
                        </div>
                    </div>
                    <div class="adm-form-field">
                        <div class="adm-form-check">
                            <input type="checkbox" id="has_restaurant" name="has_restaurant" <?= $accom['has_restaurant'] ? 'checked' : '' ?>>
                            <label for="has_restaurant">Restaurant</label>
                        </div>
                    </div>
                    <div class="adm-form-field">
                        <div class="adm-form-check">
                            <input type="checkbox" id="has_fitness" name="has_fitness" <?= $accom['has_fitness'] ? 'checked' : '' ?>>
                            <label for="has_fitness">Fitness Center</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === PROVIDER INFORMATION SECTION === -->
            <div class="adm-form-card">
                <h2>Provider Information</h2>
                <div class="adm-form-grid">
                    <div class="adm-form-field">
                        <label for="provider_name">Provider Name</label>
                        <input type="text" id="provider_name" name="provider_name" value="<?= htmlspecialchars($accom['provider_name'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="provider_email">Provider Email</label>
                        <input type="email" id="provider_email" name="provider_email" value="<?= htmlspecialchars($accom['provider_email'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="provider_phone">Provider Phone</label>
                        <input type="text" id="provider_phone" name="provider_phone" value="<?= htmlspecialchars($accom['provider_phone'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- === FORM ACTIONS === -->
            <div class="adm-form-card">
                <div class="adm-form-actions">
                    <a href="accommodations.php" class="adm-btn adm-btn-secondary">Cancel</a>
                    <button type="submit" class="adm-btn adm-btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Accommodation' ?></button>
                </div>
            </div>
        </form>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
