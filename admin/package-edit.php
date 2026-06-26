<?php
$pageTitle = 'Edit Package';
require_once __DIR__ . '/includes/header.php';

$pkgId = (int)($_GET['id'] ?? 0);
$isEdit = $pkgId > 0;
$pkg = null;

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM packages WHERE id = :id");
    $stmt->execute([':id' => $pkgId]);
    $pkg = $stmt->fetch();
    if (!$pkg) { header('Location: packages.php'); exit; }

    // Load existing tags
    $tagStmt = $db->prepare("SELECT t.name FROM tags t JOIN package_tags pt ON t.id = pt.tag_id WHERE pt.package_id = :pid ORDER BY t.name");
    $tagStmt->execute([':pid' => $pkgId]);
    $existingTags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $shortDescription = trim($_POST['short_description'] ?? '');
    $durationDays = (int)($_POST['duration_days'] ?? 0);
    $durationNights = (int)($_POST['duration_nights'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $destinationCategory = trim($_POST['destination_category'] ?? '');
    $priceRange = trim($_POST['price_range'] ?? '');
    $maxGroupSize = (int)($_POST['max_group_size'] ?? 12);
    $difficultyLevel = trim($_POST['difficulty_level'] ?? 'Moderate');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') $errors[] = 'Title is required.';
    if ($durationDays <= 0) $errors[] = 'Duration days must be positive.';
    if ($price <= 0) $errors[] = 'Price must be positive.';

    // Handle image upload
    $imagePath = $pkg['image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Image must be JPG, PNG, or WebP.';
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image must be under 5MB.';
        } else {
            $uploadDir = __DIR__ . '/../images/packages/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'pkg_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $imagePath = 'images/packages/' . $filename;
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }
    }

    if (empty($errors)) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
        $slug = trim($slug, '-');

        if ($isEdit) {
            $stmt = $db->prepare("UPDATE packages SET title=:title, slug=:slug, description=:desc, short_description=:short, duration_days=:days, duration_nights=:nights, price=:price, image=:image, destination_category=:cat, price_range=:pr, max_group_size=:mgs, difficulty_level=:dl, is_featured=:feat, is_active=:act WHERE id=:id");
            $stmt->execute([':title'=>$title, ':slug'=>$slug, ':desc'=>$description, ':short'=>$shortDescription, ':days'=>$durationDays, ':nights'=>$durationNights, ':price'=>$price, ':image'=>$imagePath, ':cat'=>$destinationCategory, ':pr'=>$priceRange, ':mgs'=>$maxGroupSize, ':dl'=>$difficultyLevel, ':feat'=>$isFeatured, ':act'=>$isActive, ':id'=>$pkgId]);
            $entityId = $pkgId;
        } else {
            $stmt = $db->prepare("INSERT INTO packages (title, slug, description, short_description, duration_days, duration_nights, price, image, destination_category, price_range, max_group_size, difficulty_level, is_featured, is_active) VALUES (:title, :slug, :desc, :short, :days, :nights, :price, :image, :cat, :pr, :mgs, :dl, :feat, :act)");
            $stmt->execute([':title'=>$title, ':slug'=>$slug, ':desc'=>$description, ':short'=>$shortDescription, ':days'=>$durationDays, ':nights'=>$durationNights, ':price'=>$price, ':image'=>$imagePath, ':cat'=>$destinationCategory, ':pr'=>$priceRange, ':mgs'=>$maxGroupSize, ':dl'=>$difficultyLevel, ':feat'=>$isFeatured, ':act'=>$isActive]);
            $entityId = $db->lastInsertId();
        }

        // Sync tags
        $tagInput = trim($_POST['tags'] ?? '');
        $tagNames = $tagInput !== '' ? array_unique(array_map('trim', explode(',', $tagInput))) : [];
        // Delete existing associations
        $delStmt = $db->prepare("DELETE FROM package_tags WHERE package_id = :pid");
        $delStmt->execute([':pid' => $entityId]);
        foreach ($tagNames as $tagName) {
            if ($tagName === '') continue;
            $tagSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $tagName));
            $tagSlug = trim($tagSlug, '-');
            // Find or create tag
            $findTag = $db->prepare("SELECT id FROM tags WHERE name = :name");
            $findTag->execute([':name' => $tagName]);
            $tagRow = $findTag->fetch();
            if ($tagRow) {
                $tagIdVal = $tagRow['id'];
            } else {
                $insTag = $db->prepare("INSERT INTO tags (name, slug) VALUES (:name, :slug)");
                $insTag->execute([':name' => $tagName, ':slug' => $tagSlug]);
                $tagIdVal = $db->lastInsertId();
            }
            $insLink = $db->prepare("INSERT IGNORE INTO package_tags (package_id, tag_id) VALUES (:pid, :tid)");
            $insLink->execute([':pid' => $entityId, ':tid' => $tagIdVal]);
        }

        header('Location: packages.php?saved=1');
        exit;
    }
    }
}

include __DIR__ . '/includes/sidebar.php';

// Pre-fill for new
if (!$pkg) {
    $pkg = ['title'=>'','description'=>'','short_description'=>'','duration_days'=>3,'duration_nights'=>2,'price'=>0,'image'=>'','destination_category'=>'','price_range'=>'','max_group_size'=>12,'difficulty_level'=>'Moderate','is_featured'=>0,'is_active'=>1];
}
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title"><?= $isEdit ? 'Edit Package' : 'Add Package' ?></h1>
        </div>
        <div class="adm-topbar-right">
            <a href="packages.php" class="adm-btn adm-btn-secondary"><span class="material-symbols-outlined">arrow_back</span> Back to Packages</a>
        </div>
    </div>

    <div class="adm-content">
        <?php foreach ($errors as $err): ?>
            <div class="adm-alert adm-alert-error"><span class="material-symbols-outlined">error</span> <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data" novalidate>
            <?php csrf_field(); ?>
            <div class="adm-form-card">
                <h2>Basic Information</h2>
                <div class="adm-form-grid">
                    <div class="adm-form-field full-width">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($pkg['title']) ?>" required>
                    </div>
                    <div class="adm-form-field full-width">
                        <label for="short_description">Short Description</label>
                        <input type="text" id="short_description" name="short_description" value="<?= htmlspecialchars($pkg['short_description'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field full-width">
                        <label for="description">Full Description</label>
                        <textarea id="description" name="description" rows="5"><?= htmlspecialchars($pkg['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="adm-form-card">
                <h2>Tags</h2>
                <div class="adm-form-grid">
                    <div class="adm-form-field full-width">
                        <label for="tags">Tags (comma-separated)</label>
                        <input type="text" id="tags" name="tags" value="<?= htmlspecialchars(implode(', ', $existingTags ?? [])) ?>" placeholder="e.g. Beach, Adventure, Culture">
                        <div class="adm-form-field-hint">Separate multiple tags with commas. Tags help categorize and filter content.</div>
                    </div>
                </div>
            </div>

            <div class="adm-form-card">
                <h2>Details</h2>
                <div class="adm-form-grid">
                    <div class="adm-form-field">
                        <label for="duration_days">Duration Days *</label>
                        <input type="number" id="duration_days" name="duration_days" min="1" value="<?= $pkg['duration_days'] ?>" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="duration_nights">Duration Nights *</label>
                        <input type="number" id="duration_nights" name="duration_nights" min="0" value="<?= $pkg['duration_nights'] ?>" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="price">Price (Rs.) *</label>
                        <input type="number" id="price" name="price" min="0" step="0.01" value="<?= $pkg['price'] ?>" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="max_group_size">Max Group Size</label>
                        <input type="number" id="max_group_size" name="max_group_size" min="1" value="<?= $pkg['max_group_size'] ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="destination_category">Category</label>
                        <input type="text" id="destination_category" name="destination_category" value="<?= htmlspecialchars($pkg['destination_category'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="price_range">Price Range</label>
                        <select id="price_range" name="price_range">
                            <option value="">Select</option>
                            <?php foreach (['Budget','Mid-Range','Premium','Luxury'] as $pr): ?>
                                <option value="<?= $pr ?>" <?= ($pkg['price_range'] ?? '') === $pr ? 'selected' : '' ?>><?= $pr ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-field">
                        <label for="difficulty_level">Difficulty Level</label>
                        <select id="difficulty_level" name="difficulty_level">
                            <?php foreach (['Easy','Moderate','Challenging','Difficult'] as $dl): ?>
                                <option value="<?= $dl ?>" <?= ($pkg['difficulty_level'] ?? 'Moderate') === $dl ? 'selected' : '' ?>><?= $dl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-field">
                        <label for="image">Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <?php if (!empty($pkg['image'])): ?>
                            <div class="adm-form-field-hint">Current: <?= htmlspecialchars($pkg['image']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="adm-form-field">
                        <div class="adm-form-check" style="margin-top:1.5rem;">
                            <input type="checkbox" id="is_featured" name="is_featured" <?= $pkg['is_featured'] ? 'checked' : '' ?>>
                            <label for="is_featured">Featured</label>
                        </div>
                    </div>
                    <div class="adm-form-field">
                        <div class="adm-form-check" style="margin-top:1.5rem;">
                            <input type="checkbox" id="is_active" name="is_active" <?= $pkg['is_active'] ? 'checked' : '' ?>>
                            <label for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="adm-form-actions">
                    <a href="packages.php" class="adm-btn adm-btn-secondary">Cancel</a>
                    <button type="submit" class="adm-btn adm-btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Package' ?></button>
                </div>
            </div>
        </form>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
