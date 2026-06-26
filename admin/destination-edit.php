<?php
$pageTitle = 'Edit Destination';
require_once __DIR__ . '/includes/header.php';

$destId = (int)($_GET['id'] ?? 0);
$isEdit = $destId > 0;
$dest = null;

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM destinations WHERE id = :id");
    $stmt->execute([':id' => $destId]);
    $dest = $stmt->fetch();
    if (!$dest) { header('Location: destinations.php'); exit; }

    // Load existing tags
    $tagStmt = $db->prepare("SELECT t.name FROM tags t JOIN destination_tags dt ON t.id = dt.tag_id WHERE dt.destination_id = :did ORDER BY t.name");
    $tagStmt->execute([':did' => $destId]);
    $existingTags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '') $errors[] = 'Name is required.';

    $imagePath = $dest['image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) { $errors[] = 'Image must be JPG, PNG, or WebP.'; }
        else {
            $uploadDir = __DIR__ . '/../images/destinations/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'dest_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $imagePath = 'images/destinations/' . $filename;
            }
        }
    }

    if (empty($errors)) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        if ($isEdit) {
            $stmt = $db->prepare("UPDATE destinations SET name=:name, slug=:slug, description=:desc, image=:image, is_featured=:feat, is_active=:act WHERE id=:id");
            $stmt->execute([':name'=>$name, ':slug'=>$slug, ':desc'=>$description, ':image'=>$imagePath, ':feat'=>$isFeatured, ':act'=>$isActive, ':id'=>$destId]);
            $entityId = $destId;
        } else {
            $stmt = $db->prepare("INSERT INTO destinations (name, slug, description, image, is_featured, is_active) VALUES (:name, :slug, :desc, :image, :feat, :act)");
            $stmt->execute([':name'=>$name, ':slug'=>$slug, ':desc'=>$description, ':image'=>$imagePath, ':feat'=>$isFeatured, ':act'=>$isActive]);
            $entityId = $db->lastInsertId();
        }

        // Sync tags
        $tagInput = trim($_POST['tags'] ?? '');
        $tagNames = $tagInput !== '' ? array_unique(array_map('trim', explode(',', $tagInput))) : [];
        $delStmt = $db->prepare("DELETE FROM destination_tags WHERE destination_id = :did");
        $delStmt->execute([':did' => $entityId]);
        foreach ($tagNames as $tagName) {
            if ($tagName === '') continue;
            $tagSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $tagName));
            $tagSlug = trim($tagSlug, '-');
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
            $insLink = $db->prepare("INSERT IGNORE INTO destination_tags (destination_id, tag_id) VALUES (:did, :tid)");
            $insLink->execute([':did' => $entityId, ':tid' => $tagIdVal]);
        }

        header('Location: destinations.php?saved=1');
        exit;
    }
    }
}

include __DIR__ . '/includes/sidebar.php';

if (!$dest) $dest = ['name'=>'','description'=>'','image'=>'','is_featured'=>0,'is_active'=>1];
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
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
        <?php foreach ($errors as $err): ?>
            <div class="adm-alert adm-alert-error"><span class="material-symbols-outlined">error</span> <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

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
                <div class="adm-form-actions">
                    <a href="destinations.php" class="adm-btn adm-btn-secondary">Cancel</a>
                    <button type="submit" class="adm-btn adm-btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Destination' ?></button>
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
        </form>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
