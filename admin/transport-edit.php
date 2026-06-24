<?php
$pageTitle = 'Edit Transport';
require_once __DIR__ . '/includes/header.php';

$transportId = (int)($_GET['id'] ?? 0);
$isEdit = $transportId > 0;
$transport = null;

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM transportations WHERE id = :id");
    $stmt->execute([':id' => $transportId]);
    $transport = $stmt->fetch();
    if (!$transport) { header('Location: transportation.php'); exit; }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $shortDescription = trim($_POST['short_description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $vehicleType = $_POST['vehicle_type'] ?? 'Car';
    $pricePerDay = (float)($_POST['price_per_day'] ?? 0);
    $rating = (float)($_POST['rating'] ?? 0);
    $hasAc = isset($_POST['has_ac']) ? 1 : 0;
    $hasDriver = isset($_POST['has_driver']) ? 1 : 0;
    $hasInsurance = isset($_POST['has_insurance']) ? 1 : 0;
    $providerName = trim($_POST['provider_name'] ?? '');
    $providerEmail = trim($_POST['provider_email'] ?? '');
    $providerPhone = trim($_POST['provider_phone'] ?? '');
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '') $errors[] = 'Name is required.';
    if ($location === '') $errors[] = 'Location is required.';
    if ($pricePerDay <= 0) $errors[] = 'Price per day must be a positive number.';

    $imagePath = $transport['image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) { $errors[] = 'Image must be JPG, PNG, or WebP.'; }
        else {
            $uploadDir = __DIR__ . '/../images/transport/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'transport_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $imagePath = 'images/transport/' . $filename;
            }
        }
    }

    if (empty($errors)) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        if ($isEdit) {
            $stmt = $db->prepare("UPDATE transportations SET name=:name, slug=:slug, description=:desc, short_description=:short, location=:loc, vehicle_type=:type, price_per_day=:price, rating=:rating, image=:image, has_ac=:ac, has_driver=:driver, has_insurance=:insurance, provider_name=:prov_name, provider_email=:prov_email, provider_phone=:prov_phone, is_available=:avail, is_featured=:feat, is_active=:act WHERE id=:id");
            $stmt->execute([':name'=>$name, ':slug'=>$slug, ':desc'=>$description, ':short'=>$shortDescription, ':loc'=>$location, ':type'=>$vehicleType, ':price'=>$pricePerDay, ':rating'=>$rating, ':image'=>$imagePath, ':ac'=>$hasAc, ':driver'=>$hasDriver, ':insurance'=>$hasInsurance, ':prov_name'=>$providerName, ':prov_email'=>$providerEmail, ':prov_phone'=>$providerPhone, ':avail'=>$isAvailable, ':feat'=>$isFeatured, ':act'=>$isActive, ':id'=>$transportId]);
        } else {
            $stmt = $db->prepare("INSERT INTO transportations (name, slug, description, short_description, location, vehicle_type, price_per_day, rating, image, has_ac, has_driver, has_insurance, provider_name, provider_email, provider_phone, is_available, is_featured, is_active) VALUES (:name, :slug, :desc, :short, :loc, :type, :price, :rating, :image, :ac, :driver, :insurance, :prov_name, :prov_email, :prov_phone, :avail, :feat, :act)");
            $stmt->execute([':name'=>$name, ':slug'=>$slug, ':desc'=>$description, ':short'=>$shortDescription, ':loc'=>$location, ':type'=>$vehicleType, ':price'=>$pricePerDay, ':rating'=>$rating, ':image'=>$imagePath, ':ac'=>$hasAc, ':driver'=>$hasDriver, ':insurance'=>$hasInsurance, ':prov_name'=>$providerName, ':prov_email'=>$providerEmail, ':prov_phone'=>$providerPhone, ':avail'=>$isAvailable, ':feat'=>$isFeatured, ':act'=>$isActive]);
        }
        header('Location: transportation.php?saved=1');
        exit;
    }
    }
}

include __DIR__ . '/includes/sidebar.php';

if (!$transport) $transport = ['name'=>'','description'=>'','short_description'=>'','location'=>'','vehicle_type'=>'Car','price_per_day'=>0,'rating'=>0,'image'=>'','has_ac'=>0,'has_driver'=>0,'has_insurance'=>0,'provider_name'=>'','provider_email'=>'','provider_phone'=>'','is_available'=>1,'is_featured'=>0,'is_active'=>1];
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title"><?= $isEdit ? 'Edit Transport' : 'Add Transport' ?></h1>
        </div>
        <div class="adm-topbar-right">
            <a href="transportation.php" class="adm-btn adm-btn-secondary"><span class="material-symbols-outlined">arrow_back</span> Back</a>
        </div>
    </div>

    <div class="adm-content">
        <?php foreach ($errors as $err): ?>
            <div class="adm-alert adm-alert-error"><span class="material-symbols-outlined">error</span> <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data" novalidate>
            <?php csrf_field(); ?>
            <div class="adm-form-card">
                <h2>Transport Details</h2>
                <div class="adm-form-grid">
                    <div class="adm-form-field">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($transport['name']) ?>" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="vehicle_type">Vehicle Type *</label>
                        <select id="vehicle_type" name="vehicle_type">
                            <?php foreach (['Three-Wheeler','Car','Bike','Minivan'] as $type): ?>
                                <option value="<?= $type ?>" <?= $transport['vehicle_type'] === $type ? 'selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-field">
                        <label for="location">Location *</label>
                        <input type="text" id="location" name="location" value="<?= htmlspecialchars($transport['location']) ?>" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="price_per_day">Price/Day (Rs.) *</label>
                        <input type="number" id="price_per_day" name="price_per_day" step="0.01" min="0" value="<?= htmlspecialchars($transport['price_per_day']) ?>" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="rating">Rating</label>
                        <input type="number" id="rating" name="rating" step="0.1" min="0" max="5" value="<?= htmlspecialchars($transport['rating']) ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="image">Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <?php if (!empty($transport['image'])): ?>
                            <div class="adm-form-field-hint">Current: <?= htmlspecialchars($transport['image']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="adm-form-field full-width">
                        <label for="short_description">Short Description</label>
                        <input type="text" id="short_description" name="short_description" value="<?= htmlspecialchars($transport['short_description'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($transport['description'] ?? '') ?></textarea>
                    </div>
                    <div class="adm-form-field">
                        <label style="margin-bottom:0.5rem;">Features</label>
                        <div class="adm-form-check">
                            <input type="checkbox" id="has_ac" name="has_ac" <?= $transport['has_ac'] ? 'checked' : '' ?>>
                            <label for="has_ac">Has AC</label>
                        </div>
                        <div class="adm-form-check" style="margin-top:0.5rem;">
                            <input type="checkbox" id="has_driver" name="has_driver" <?= $transport['has_driver'] ? 'checked' : '' ?>>
                            <label for="has_driver">Has Driver</label>
                        </div>
                        <div class="adm-form-check" style="margin-top:0.5rem;">
                            <input type="checkbox" id="has_insurance" name="has_insurance" <?= $transport['has_insurance'] ? 'checked' : '' ?>>
                            <label for="has_insurance">Has Insurance</label>
                        </div>
                    </div>
                    <div class="adm-form-field">
                        <label style="margin-bottom:0.5rem;">Status</label>
                        <div class="adm-form-check">
                            <input type="checkbox" id="is_available" name="is_available" <?= $transport['is_available'] ? 'checked' : '' ?>>
                            <label for="is_available">Available</label>
                        </div>
                        <div class="adm-form-check" style="margin-top:0.5rem;">
                            <input type="checkbox" id="is_featured" name="is_featured" <?= $transport['is_featured'] ? 'checked' : '' ?>>
                            <label for="is_featured">Featured</label>
                        </div>
                        <div class="adm-form-check" style="margin-top:0.5rem;">
                            <input type="checkbox" id="is_active" name="is_active" <?= $transport['is_active'] ? 'checked' : '' ?>>
                            <label for="is_active">Active</label>
                        </div>
                </div>
            </div>

            <div class="adm-form-card">
                <h2>Provider Information</h2>
                <div class="adm-form-grid">
                    <div class="adm-form-field">
                        <label for="provider_name">Provider Name</label>
                        <input type="text" id="provider_name" name="provider_name" value="<?= htmlspecialchars($transport['provider_name'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="provider_email">Provider Email</label>
                        <input type="email" id="provider_email" name="provider_email" value="<?= htmlspecialchars($transport['provider_email'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="provider_phone">Provider Phone</label>
                        <input type="text" id="provider_phone" name="provider_phone" value="<?= htmlspecialchars($transport['provider_phone'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="adm-form-card">
                <div class="adm-form-actions">
                    <a href="transportation.php" class="adm-btn adm-btn-secondary">Cancel</a>
                    <button type="submit" class="adm-btn adm-btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Transport' ?></button>
                </div>
            </div>
        </form>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>