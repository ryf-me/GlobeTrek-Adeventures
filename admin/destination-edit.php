<?php
/**
 * File: admin/destination-edit.php
 * Purpose: Create/edit form for travel destinations — handles image upload, tag sync, slug generation, and form validation.
 * Dependencies: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php, config/database.php, config/csrf.php
 * Used By: admin/destinations.php (via "Add Destination" and "Edit" links)
 * Parent Files: admin/destinations.php
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Edit Destination';
require_once __DIR__ . '/includes/header.php';

// === DETERMINE EDIT vs CREATE MODE ===
$destId = (int)($_GET['id'] ?? 0);
$isEdit = $destId > 0;
$dest = null;

// === LOAD EXISTING DESTINATION DATA ===
if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM destinations WHERE id = :id");
    $stmt->execute([':id' => $destId]);
    $dest = $stmt->fetch();

    // Redirect if destination not found
    if (!$dest) { header('Location: destinations.php'); exit; }

    // === LOAD EXISTING TAGS ===
    // Fetch tag names for this destination via the destination_tags pivot table.
    $tagStmt = $db->prepare("SELECT t.name FROM tags t JOIN destination_tags dt ON t.id = dt.tag_id WHERE dt.destination_id = :did ORDER BY t.name");
    $tagStmt->execute([':did' => $destId]);
    $existingTags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
}

$errors = [];

// === FORM SUBMISSION HANDLER ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
    // === SANITIZE INPUT ===
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    // Checkboxes: present = checked, absent = unchecked
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // === VALIDATION ===
    if ($name === '') $errors[] = 'Name is required.';

    // === IMAGE UPLOAD HANDLING ===
    // Keep existing image if no new file is uploaded.
    $imagePath = $dest['image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        // Validate file extension
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) { $errors[] = 'Image must be JPG, PNG, or WebP.'; }
        else {
            // Ensure upload directory exists
            $uploadDir = __DIR__ . '/../images/destinations/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // Generate unique filename to prevent overwrites
            $filename = 'dest_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $imagePath = 'images/destinations/' . $filename;
            }
        }
    }

    // === SAVE TO DATABASE ===
    if (empty($errors)) {
        // Auto-generate slug from name
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        if ($isEdit) {
            // UPDATE existing destination
            $stmt = $db->prepare("UPDATE destinations SET name=:name, slug=:slug, description=:desc, image=:image, is_featured=:feat, is_active=:act WHERE id=:id");
            $stmt->execute([':name'=>$name, ':slug'=>$slug, ':desc'=>$description, ':image'=>$imagePath, ':feat'=>$isFeatured, ':act'=>$isActive, ':id'=>$destId]);
            $entityId = $destId;
        } else {
            // INSERT new destination
            $stmt = $db->prepare("INSERT INTO destinations (name, slug, description, image, is_featured, is_active) VALUES (:name, :slug, :desc, :image, :feat, :act)");
            $stmt->execute([':name'=>$name, ':slug'=>$slug, ':desc'=>$description, ':image'=>$imagePath, ':feat'=>$isFeatured, ':act'=>$isActive]);
            $entityId = $db->lastInsertId();
        }

        // === TAG SYNC ===
        // Parse comma-separated tags, delete existing associations, then re-create them.
        // New tags are automatically created if they don't exist (find-or-create).
        $tagInput = trim($_POST['tags'] ?? '');
        $tagNames = $tagInput !== '' ? array_unique(array_map('trim', explode(',', $tagInput))) : [];

        // Remove all existing tag associations for this destination
        $delStmt = $db->prepare("DELETE FROM destination_tags WHERE destination_id = :did");
        $delStmt->execute([':did' => $entityId]);

        foreach ($tagNames as $tagName) {
            if ($tagName === '') continue;
            // Generate tag slug
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
            // INSERT IGNORE prevents duplicate key errors on re-submission
            $insLink = $db->prepare("INSERT IGNORE INTO destination_tags (destination_id, tag_id) VALUES (:did, :tid)");
            $insLink->execute([':did' => $entityId, ':tid' => $tagIdVal]);
        }

        header('Location: destinations.php?saved=1');
        exit;
    }
    }
}

include __DIR__ . '/includes/sidebar.php';

// === DEFAULT VALUES FOR NEW DESTINATION ===
// Pre-fill form with sensible defaults when creating a new destination.
if (!$dest) $dest = ['name'=>'','description'=>'','image'=>'','is_featured'=>0,'is_active'=>1];
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title"><?= $isEdit ? 'Edit Destination' : 'Add Destination' ?></h1>
        </div>
        <div class="adm-topbar-right">
            <a href="destinations.php" class="adm-btn adm-btn-secondary"><span class="material-symbols-outlined">arrow_back</span> Back</a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === VALIDATION ERRORS === -->
        <?php foreach ($errors as $err): ?>
            <div class="adm-alert adm-alert-error"><span class="material-symbols-outlined">error</span> <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <!-- === DESTINATION FORM === -->
        <!-- enctype="multipart/form-data" required for image file upload -->
        <form method="post" enctype="multipart/form-data" novalidate>
            <?php csrf_field(); ?>
            <div class="adm-form-card">
                <h2>Destination Details</h2>
                <div class="adm-form-grid">
                    <div class="adm-form-field full-width">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($dest['name']) ?>" required>
                    </div>
                    <div class="adm-form-field full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($dest['description'] ?? '') ?></textarea>
                    </div>
                    <div class="adm-form-field">
                        <label for="image">Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <?php if (!empty($dest['image'])): ?>
                            <div class="adm-form-field-hint">Current: <?= htmlspecialchars($dest['image']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="adm-form-field">
                        <div class="adm-form-check" style="margin-top:1.5rem;">
                            <input type="checkbox" id="is_featured" name="is_featured" <?= $dest['is_featured'] ? 'checked' : '' ?>>
                            <label for="is_featured">Featured</label>
                        </div>
                        <div class="adm-form-check" style="margin-top:0.5rem;">
                            <input type="checkbox" id="is_active" name="is_active" <?= $dest['is_active'] ? 'checked' : '' ?>>
                            <label for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <!-- === FORM ACTIONS === -->
                <div class="adm-form-actions">
                    <a href="destinations.php" class="adm-btn adm-btn-secondary">Cancel</a>
                    <button type="submit" class="adm-btn adm-btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Destination' ?></button>
                </div>
            </div>

            <!-- === TAGS SECTION === -->
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
        </form>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
