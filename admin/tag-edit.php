<?php
/**
 * File: admin/tag-edit.php
 * Purpose: Create or edit a tag. Handles form display, validation, and database persistence.
 * Dependencies: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * Used By: Admin/staff users; accessed from tags.php
 * Parent Files: admin/tags.php (linked from tag list)
 * Child Files: admin/includes/header.php, admin/includes/sidebar.php, admin/includes/footer.php
 * @package GlobeTrek\Admin
 */

$pageTitle = 'Edit Tag';

// === INITIALIZATION ===
require_once __DIR__ . '/includes/header.php';

// === LOAD EXISTING TAG ===
// Determine if we're editing an existing tag or creating a new one
$tagId = (int)($_GET['id'] ?? 0);
$isEdit = $tagId > 0;
$tag = null;

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM tags WHERE id = :id");
    $stmt->execute([':id' => $tagId]);
    $tag = $stmt->fetch();
    // Redirect to tag list if the tag doesn't exist
    if (!$tag) { header('Location: tags.php'); exit; }
}

$errors = [];

// === FORM SUBMISSION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
    $name = trim($_POST['name'] ?? '');

    // Validate required fields
    if ($name === '') $errors[] = 'Tag name is required.';

    if (empty($errors)) {
        // Generate URL-friendly slug from tag name
        // Replace non-alphanumeric characters with hyphens, then trim leading/trailing hyphens
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        // Check for duplicate tag name — exclude current tag when editing
        $checkSql = "SELECT id FROM tags WHERE name = :name";
        $checkParams = [':name' => $name];
        if ($isEdit) {
            $checkSql .= " AND id != :id";
            $checkParams[':id'] = $tagId;
        }
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute($checkParams);
        if ($checkStmt->fetch()) {
            $errors[] = 'A tag with that name already exists.';
        }
    }

    if (empty($errors)) {
        // Insert new tag or update existing tag
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE tags SET name=:name, slug=:slug WHERE id=:id");
            $stmt->execute([':name'=>$name, ':slug'=>$slug, ':id'=>$tagId]);
        } else {
            $stmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (:name, :slug)");
            $stmt->execute([':name'=>$name, ':slug'=>$slug]);
        }
        header('Location: tags.php?saved=1');
        exit;
    }
    }
}

// === SIDEBAR ===
include __DIR__ . '/includes/sidebar.php';

// Default tag data for new tags (empty fields)
if (!$tag) $tag = ['name'=>'', 'slug'=>''];
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <!-- === TOP BAR === -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title"><?= $isEdit ? 'Edit Tag' : 'Add Tag' ?></h1>
        </div>
        <div class="adm-topbar-right">
            <a href="tags.php" class="adm-btn adm-btn-secondary"><span class="material-symbols-outlined">arrow_back</span> Back to Tags</a>
        </div>
    </div>

    <div class="adm-content">
        <!-- === ERROR DISPLAY === -->
        <?php foreach ($errors as $err): ?>
            <div class="adm-alert adm-alert-error"><span class="material-symbols-outlined">error</span> <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <!-- === TAG FORM === -->
        <form method="post" novalidate>
            <?php csrf_field(); ?>
            <div class="adm-form-card">
                <h2>Tag Details</h2>
                <div class="adm-form-grid">
                    <div class="adm-form-field full-width">
                        <label for="name">Tag Name *</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($tag['name']) ?>" required placeholder="e.g. Beach, Adventure, Culture">
                        <div class="adm-form-field-hint">Enter a descriptive name for this tag. It can be assigned to packages, destinations, and guides.</div>
                    </div>
                </div>
                <div class="adm-form-actions">
                    <a href="tags.php" class="adm-btn adm-btn-secondary">Cancel</a>
                    <button type="submit" class="adm-btn adm-btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Tag' ?></button>
                </div>
            </div>
        </form>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
