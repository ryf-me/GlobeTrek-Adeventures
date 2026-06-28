<?php
/**
 * File: admin/guide-edit.php
 * Purpose: Create or edit a tour guide record. Handles form display, validation, image upload, and tag syncing.
 * Dependencies: admin/includes/header.php (auth, DB, CSRF), admin/includes/sidebar.php, admin/includes/footer.php, config/helpers.php (csrf_field)
 * Used By: Admin staff creating/editing guides via admin/guides.php
 * Parent Files: admin/guides.php (links to this page)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Edit Guide';
require_once __DIR__ . '/includes/header.php';

// === LOAD EXISTING GUIDE (EDIT MODE) ===
$guideId = (int)($_GET['id'] ?? 0);
$isEdit = $guideId > 0;
$guide = null;

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM guides WHERE id = :id");
    $stmt->execute([':id' => $guideId]);
    $guide = $stmt->fetch();
    // Redirect if guide not found — prevents accessing edit form for a deleted record.
    if (!$guide) { header('Location: guides.php'); exit; }

    // Load existing tags for this guide to pre-fill the tags input field.
    $tagStmt = $db->prepare("SELECT t.name FROM tags t JOIN guide_tags gt ON t.id = gt.tag_id WHERE gt.guide_id = :gid ORDER BY t.name");
    $tagStmt->execute([':gid' => $guideId]);
    $existingTags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
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
    $specialty = trim($_POST['specialty'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $profileLink = trim($_POST['profile_link'] ?? '#');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Validation: name is the only required field.
    if ($name === '') $errors[] = 'Name is required.';

    // === IMAGE UPLOAD HANDLER ===
    // Preserve the existing image path unless a new file is uploaded.
    $imagePath = $guide['image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        // Whitelist of allowed image extensions — prevents uploading executable files.
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) { $errors[] = 'Image must be JPG, PNG, or WebP.'; }
        else {
            $uploadDir = __DIR__ . '/../images/guides/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            // Generate a unique filename using timestamp + random bytes to prevent collisions and overwrites.
            $filename = 'guide_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $imagePath = 'images/guides/' . $filename;
            }
        }
    }

    // === DATABASE INSERT / UPDATE ===
    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE guides SET name=:name, specialty=:spec, region=:region, description=:desc, profile_link=:link, image=:image, is_featured=:feat, is_active=:act WHERE id=:id");
            $stmt->execute([':name'=>$name, ':spec'=>$specialty, ':region'=>$region, ':desc'=>$description, ':link'=>$profileLink, ':image'=>$imagePath, ':feat'=>$isFeatured, ':act'=>$isActive, ':id'=>$guideId]);
            $entityId = $guideId;
        } else {
            $stmt = $db->prepare("INSERT INTO guides (name, specialty, region, description, profile_link, image, is_featured, is_active) VALUES (:name, :spec, :region, :desc, :link, :image, :feat, :act)");
            $stmt->execute([':name'=>$name, ':spec'=>$specialty, ':region'=>$region, ':desc'=>$description, ':link'=>$profileLink, ':image'=>$imagePath, ':feat'=>$isFeatured, ':act'=>$isActive]);
            $entityId = $db->lastInsertId();
        }

        // === TAG SYNC ===
        // Parse comma-separated tag input, then replace all existing tags.
        // Strategy: delete all existing links, then re-insert. Simple and avoids complex diffing.
        $tagInput = trim($_POST['tags'] ?? '');
        $tagNames = $tagInput !== '' ? array_unique(array_map('trim', explode(',', $tagInput))) : [];
        // Remove all old tag associations for this guide.
        $delStmt = $db->prepare("DELETE FROM guide_tags WHERE guide_id = :gid");
        $delStmt->execute([':gid' => $entityId]);
        foreach ($tagNames as $tagName) {
            if ($tagName === '') continue;
            // Generate a URL-friendly slug from the tag name.
            $tagSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $tagName));
            $tagSlug = trim($tagSlug, '-');
            // Find or create the tag in the tags table.
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
            // INSERT IGNORE prevents duplicate link records if a tag was somehow already associated.
            $insLink = $db->prepare("INSERT IGNORE INTO guide_tags (guide_id, tag_id) VALUES (:gid, :tid)");
            $insLink->execute([':gid' => $entityId, ':tid' => $tagIdVal]);
        }

        // PRG pattern: redirect after successful save.
        header('Location: guides.php?saved=1');
        exit;
    }
    }
}

// === SIDEBAR ===
include __DIR__ . '/includes/sidebar.php';

// === DEFAULT VALUES FOR NEW GUIDE ===
// If not editing, populate fields with sensible defaults for the form.
if (!$guide) $guide = ['name'=>'','specialty'=>'','region'=>'','description'=>'','profile_link'=>'#','image'=>'','is_featured'=>0,'is_active'=>1];
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title"><?= $isEdit ? 'Edit Guide' : 'Add Guide' ?></h1>
        </div>
        <div class="adm-topbar-right">
            <a href="guides.php" class="adm-btn adm-btn-secondary"><span class="material-symbols-outlined">arrow_back</span> Back</a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === VALIDATION ERRORS === -->
        <?php foreach ($errors as $err): ?>
            <div class="adm-alert adm-alert-error"><span class="material-symbols-outlined">error</span> <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <!-- === GUIDE EDIT FORM === -->
        <!-- enctype="multipart/form-data" is required for file uploads. -->
        <form method="post" enctype="multipart/form-data" novalidate>
            <?php csrf_field(); ?>
            <div class="adm-form-card">
                <h2>Guide Details</h2>
                <div class="adm-form-grid">
                    <div class="adm-form-field">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($guide['name']) ?>" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="specialty">Specialty</label>
                        <input type="text" id="specialty" name="specialty" value="<?= htmlspecialchars($guide['specialty'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="region">Region</label>
                        <input type="text" id="region" name="region" value="<?= htmlspecialchars($guide['region'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="profile_link">Profile Link</label>
                        <input type="url" id="profile_link" name="profile_link" value="<?= htmlspecialchars($guide['profile_link'] ?? '#') ?>">
                    </div>
                    <div class="adm-form-field full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($guide['description'] ?? '') ?></textarea>
                    </div>
                    <div class="adm-form-field">
                        <label for="image">Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div>
                    <div class="adm-form-field">
                        <div class="adm-form-check" style="margin-top:1.5rem;">
                            <input type="checkbox" id="is_featured" name="is_featured" <?= $guide['is_featured'] ? 'checked' : '' ?>>
                            <label for="is_featured">Featured</label>
                        </div>
                        <div class="adm-form-check" style="margin-top:0.5rem;">
                            <input type="checkbox" id="is_active" name="is_active" <?= $guide['is_active'] ? 'checked' : '' ?>>
                            <label for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="adm-form-actions">
                    <a href="guides.php" class="adm-btn adm-btn-secondary">Cancel</a>
                    <button type="submit" class="adm-btn adm-btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Guide' ?></button>
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
