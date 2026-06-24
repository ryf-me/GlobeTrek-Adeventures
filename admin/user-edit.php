<?php
$pageTitle = 'Edit User';
require_once __DIR__ . '/includes/header.php';

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) { header('Location: users.php'); exit; }

$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();
if (!$user) { header('Location: users.php'); exit; }

include __DIR__ . '/includes/sidebar.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? null;
    $country = trim($_POST['country'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $role = $_POST['role'] ?? $user['role'];
    $newPassword = trim($_POST['new_password'] ?? '');

    if ($role !== $user['role'] && !in_array($role, ['user', 'staff', 'admin'])) {
        $role = $user['role'];
    }

    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($email === '') $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    if ($email !== $user['email']) {
        $check = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $check->execute([':email' => $email, ':id' => $userId]);
        if ($check->fetch()) $errors[] = 'Email is already taken.';
    }

    if (empty($errors)) {
        $sql = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone, gender = :gender, country = :country, city = :city, bio = :bio, role = :role WHERE id = :id";
        $params = [':full_name'=>$fullName, ':email'=>$email, ':phone'=>$phone, ':gender'=>$gender, ':country'=>$country, ':city'=>$city, ':bio'=>$bio, ':role'=>$role, ':id'=>$userId];

        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                $errors[] = 'Password must be at least 6 characters.';
            } else {
                $sql = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone, gender = :gender, country = :country, city = :city, bio = :bio, role = :role, password = :password WHERE id = :id";
                $params[':password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
        }

        if (empty($errors)) {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $success = true;
            // Refresh data
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
            // Sync session if admin edited their own profile
            if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $userId) {
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
            }
        }
    }
    }
}
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title">Edit User</h1>
        </div>
        <div class="adm-topbar-right">
            <a href="users.php" class="adm-btn adm-btn-secondary"><span class="material-symbols-outlined">arrow_back</span> Back to Users</a>
        </div>
    </div>

    <div class="adm-content">
        <?php if ($success): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> User updated successfully.</div>
        <?php endif; ?>
        <?php foreach ($errors as $err): ?>
            <div class="adm-alert adm-alert-error"><span class="material-symbols-outlined">error</span> <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <div class="adm-form-card">
            <h2>User Details</h2>
            <form method="post" novalidate>
                <?php csrf_field(); ?>
                <div class="adm-form-grid">
                    <div class="adm-form-field">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Not specified</option>
                            <option value="Male" <?= ($user['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($user['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($user['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="adm-form-field">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" value="<?= htmlspecialchars($user['country'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="role">Role</label>
                        <select id="role" name="role">
                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div class="adm-form-field">
                        <label for="new_password">New Password (leave blank to keep current)</label>
                        <input type="password" id="new_password" name="new_password" minlength="6">
                    </div>
                    <div class="adm-form-field full-width">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="3"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="adm-form-actions">
                    <a href="users.php" class="adm-btn adm-btn-secondary">Cancel</a>
                    <button type="submit" class="adm-btn adm-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
