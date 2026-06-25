<?php
/**
 * Staff Create/Edit Form
 *
 * Creates a new user account + staff profile, or edits an existing staff profile.
 * Only accessible by admins.
 */
$pageTitle = 'Edit Staff';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../config/logger.php';

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$staffId = (int)($_GET['id'] ?? 0);
$isEdit = $staffId > 0;
$staff = null;
$user = null;
$staffPermissions = [];

$departmentOptions = [
    'operations' => 'Operations',
    'customer_service' => 'Customer Service',
    'sales' => 'Sales',
    'marketing' => 'Marketing',
];

$allPermissions = [
    'manage_bookings'       => 'Manage Bookings',
    'manage_inquiries'      => 'Manage Inquiries',
    'manage_packages'       => 'Manage Packages',
    'manage_accommodations' => 'Manage Accommodations',
    'manage_transportation' => 'Manage Transportation',
    'manage_guides'         => 'Manage Guides',
    'manage_destinations'   => 'Manage Destinations',
    'manage_contacts'       => 'Manage Contact Messages',
    'manage_custom_trips'   => 'Manage Custom Trips',
    'manage_payments'       => 'Manage Payments',
    'manage_newsletters'    => 'Manage Newsletters',
    'manage_testimonials'   => 'Manage Testimonials',
    'view_reports'          => 'View Reports',
    'view_customers'        => 'View Customer Data',
];

$departmentPermissions = [
    'operations'       => ['manage_bookings', 'manage_packages', 'manage_accommodations', 'manage_transportation', 'manage_guides', 'manage_destinations'],
    'customer_service' => ['manage_inquiries', 'manage_contacts', 'manage_custom_trips', 'view_customers', 'manage_testimonials'],
    'sales'            => ['view_reports', 'manage_payments', 'view_customers', 'manage_bookings'],
    'marketing'        => ['manage_newsletters', 'manage_destinations', 'manage_testimonials', 'view_reports'],
];

// Load existing staff
if ($isEdit) {
    $stmt = $db->prepare(
        "SELECT sp.*, u.full_name, u.email, u.phone, u.profile_photo
         FROM staff_profiles sp
         JOIN users u ON sp.user_id = u.id
         WHERE sp.id = :id"
    );
    $stmt->execute([':id' => $staffId]);
    $staff = $stmt->fetch();

    if (!$staff) {
        header('Location: staff.php');
        exit;
    }

    $user = $staff;

    // Load extra permissions
    $stmt = $db->prepare("SELECT permission FROM staff_permissions WHERE staff_id = :sid");
    $stmt->execute([':sid' => $staffId]);
    $staffPermissions = array_column($stmt->fetchAll(), 'permission');
}

// Form fields
$fields = [
    'full_name'  => $user['full_name'] ?? '',
    'email'      => $user['email'] ?? '',
    'phone'      => $user['phone'] ?? '',
    'password'   => '',
    'department' => $staff['department'] ?? '',
    'position'   => $staff['position'] ?? '',
    'hire_date'  => $staff['hire_date'] ?? '',
    'max_tasks'  => $staff['max_concurrent_tasks'] ?? 10,
    'notes'      => $staff['notes'] ?? '',
    'is_available' => $staff['is_available'] ?? 1,
];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    }

    if (empty($errors)) {
        $fields['full_name']  = trim($_POST['full_name'] ?? '');
        $fields['email']      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $fields['phone']      = trim($_POST['phone'] ?? '');
        $fields['password']   = $_POST['password'] ?? '';
        $fields['department'] = $_POST['department'] ?? '';
        $fields['position']   = trim($_POST['position'] ?? '');
        $fields['hire_date']  = $_POST['hire_date'] ?: null;
        $fields['max_tasks']  = max(1, (int)($_POST['max_tasks'] ?? 10));
        $fields['notes']      = trim($_POST['notes'] ?? '');
        $fields['is_available'] = isset($_POST['is_available']) ? 1 : 0;

        // Validation
        if ($fields['full_name'] === '') {
            $errors[] = 'Full name is required.';
        }
        if ($fields['email'] === '' || !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (!$isEdit && $fields['password'] === '') {
            $errors[] = 'Password is required for new staff members.';
        }
        if ($fields['password'] !== '' && strlen($fields['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if (!in_array($fields['department'], array_keys($departmentOptions))) {
            $errors[] = 'Please select a valid department.';
        }
        if ($fields['position'] === '') {
            $errors[] = 'Position is required.';
        }

        // Check email uniqueness
        if (empty($errors)) {
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :uid LIMIT 1");
            $checkStmt->execute([':email' => $fields['email'], ':uid' => $user['id'] ?? 0]);
            if ($checkStmt->fetch()) {
                $errors[] = 'An account with this email already exists.';
            }
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                if ($isEdit) {
                    // Update existing user
                    $updateFields = "full_name = :name, email = :email, phone = :phone";
                    $params = [':name' => $fields['full_name'], ':email' => $fields['email'], ':phone' => $fields['phone'], ':uid' => $staff['user_id']];

                    if ($fields['password'] !== '') {
                        $updateFields .= ", password = :password";
                        $params[':password'] = password_hash($fields['password'], PASSWORD_DEFAULT);
                    }

                    $stmt = $db->prepare("UPDATE users SET $updateFields WHERE id = :uid");
                    $stmt->execute($params);

                    // Update staff profile
                    $stmt = $db->prepare(
                        "UPDATE staff_profiles SET
                            department = :dept, position = :pos, hire_date = :hire,
                            is_available = :avail, max_concurrent_tasks = :max_tasks, notes = :notes
                         WHERE id = :id"
                    );
                    $stmt->execute([
                        ':dept' => $fields['department'],
                        ':pos' => $fields['position'],
                        ':hire' => $fields['hire_date'],
                        ':avail' => $fields['is_available'],
                        ':max_tasks' => $fields['max_tasks'],
                        ':notes' => $fields['notes'],
                        ':id' => $staffId,
                    ]);

                    logActivity('staff_updated', 'staff', $staffId, 'Staff profile updated: ' . $fields['full_name']);
                } else {
                    // Create new user
                    $hashedPassword = password_hash($fields['password'], PASSWORD_DEFAULT);
                    $stmt = $db->prepare(
                        "INSERT INTO users (full_name, email, phone, password, role)
                         VALUES (:name, :email, :phone, :password, 'staff')"
                    );
                    $stmt->execute([
                        ':name' => $fields['full_name'],
                        ':email' => $fields['email'],
                        ':phone' => $fields['phone'],
                        ':password' => $hashedPassword,
                    ]);
                    $newUserId = $db->lastInsertId();

                    // Create staff profile
                    $stmt = $db->prepare(
                        "INSERT INTO staff_profiles (user_id, department, position, hire_date, is_available, max_concurrent_tasks, notes)
                         VALUES (:uid, :dept, :pos, :hire, :avail, :max_tasks, :notes)"
                    );
                    $stmt->execute([
                        ':uid' => $newUserId,
                        ':dept' => $fields['department'],
                        ':pos' => $fields['position'],
                        ':hire' => $fields['hire_date'],
                        ':avail' => $fields['is_available'],
                        ':max_tasks' => $fields['max_tasks'],
                        ':notes' => $fields['notes'],
                    ]);
                    $staffId = $db->lastInsertId();

                    logActivity('staff_created', 'staff', $staffId, 'New staff member created: ' . $fields['full_name']);
                }

                // Update extra permissions
                $stmt = $db->prepare("DELETE FROM staff_permissions WHERE staff_id = :sid");
                $stmt->execute([':sid' => $staffId]);

                $selectedPerms = $_POST['permissions'] ?? [];
                // Filter out department default permissions (those are automatic)
                $deptDefaults = $departmentPermissions[$fields['department']] ?? [];
                $extraPerms = array_diff($selectedPerms, $deptDefaults);

                if (!empty($extraPerms)) {
                    $insertStmt = $db->prepare("INSERT INTO staff_permissions (staff_id, permission) VALUES (:sid, :perm)");
                    foreach ($extraPerms as $perm) {
                        if (array_key_exists($perm, $allPermissions)) {
                            $insertStmt->execute([':sid' => $staffId, ':perm' => $perm]);
                        }
                    }
                }

                $db->commit();
                $success = true;

                // Refresh data
                $stmt = $db->prepare(
                    "SELECT sp.*, u.full_name, u.email, u.phone, u.profile_photo
                     FROM staff_profiles sp
                     JOIN users u ON sp.user_id = u.id
                     WHERE sp.id = :id"
                );
                $stmt->execute([':id' => $staffId]);
                $staff = $stmt->fetch();
                $user = $staff;

                // Reload permissions
                $stmt = $db->prepare("SELECT permission FROM staff_permissions WHERE staff_id = :sid");
                $stmt->execute([':sid' => $staffId]);
                $staffPermissions = array_column($stmt->fetchAll(), 'permission');

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'An error occurred: ' . $e->getMessage();
            }
        }
    }
}

// Get current department defaults for permission display
$currentDeptDefaults = $departmentPermissions[$fields['department']] ?? [];
?>

<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('open');">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="adm-topbar-title"><?= $isEdit ? 'Edit Staff Member' : 'Add Staff Member' ?></h1>
        </div>
        <div class="adm-topbar-right">
            <a href="staff.php" class="adm-topbar-link"><span class="material-symbols-outlined">arrow_back</span><span>Back to Staff</span></a>
            <a href="index.php" class="adm-topbar-link"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="logout.php" class="adm-topbar-link"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </div>

    <div class="adm-content">
        <?php if ($success): ?>
            <div class="adm-alert adm-alert-success"><span class="material-symbols-outlined">check_circle</span> Staff member <?= $isEdit ? 'updated' : 'created' ?> successfully.</div>
        <?php endif; ?>
        <?php foreach ($errors as $err): ?>
            <div class="adm-alert adm-alert-error"><span class="material-symbols-outlined">error</span> <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <form method="post" novalidate>
            <?php csrf_field(); ?>

            <!-- Account Information -->
            <div class="adm-form-card">
                <h2>Account Information</h2>
                <p class="adm-form-field-hint" style="margin-bottom:1rem;"><?= $isEdit ? 'Update the user account details.' : 'Create a new user account for this staff member. They will be able to log in with these credentials.' ?></p>
                <div class="adm-form-grid">
                    <div class="adm-form-field">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($fields['full_name']) ?>" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($fields['email']) ?>" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($fields['phone']) ?>" placeholder="+94 11 234 5678">
                    </div>
                    <div class="adm-form-field">
                        <label for="password">Password <?= $isEdit ? '(Leave blank to keep current)' : '*' ?></label>
                        <input type="password" id="password" name="password" placeholder="<?= $isEdit ? '••••••••' : 'Minimum 6 characters' ?>" minlength="6">
                    </div>
                </div>
            </div>

            <!-- Staff Profile -->
            <div class="adm-form-card">
                <h2>Staff Profile</h2>
                <div class="adm-form-grid">
                    <div class="adm-form-field">
                        <label for="department">Department *</label>
                        <select id="department" name="department" required onchange="updatePermissionDisplay()">
                            <option value="">Select Department</option>
                            <?php foreach ($departmentOptions as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $fields['department'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-field">
                        <label for="position">Position *</label>
                        <input type="text" id="position" name="position" value="<?= htmlspecialchars($fields['position']) ?>" placeholder="e.g., Senior Booking Agent" required>
                    </div>
                    <div class="adm-form-field">
                        <label for="hire_date">Hire Date</label>
                        <input type="date" id="hire_date" name="hire_date" value="<?= htmlspecialchars($fields['hire_date']) ?>">
                    </div>
                    <div class="adm-form-field">
                        <label for="max_tasks">Max Concurrent Tasks</label>
                        <input type="number" id="max_tasks" name="max_tasks" value="<?= (int)$fields['max_tasks'] ?>" min="1" max="100">
                    </div>
                    <div class="adm-form-field full-width">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Any additional notes about this staff member..."><?= htmlspecialchars($fields['notes']) ?></textarea>
                    </div>
                    <div class="adm-form-field">
                        <div class="adm-form-check">
                            <input type="checkbox" id="is_available" name="is_available" <?= $fields['is_available'] ? 'checked' : '' ?>>
                            <label for="is_available">Available for assignments</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            <div class="adm-form-card">
                <h2>Permissions</h2>
                <p class="adm-form-field-hint" style="margin-bottom:1rem;">Department permissions are granted automatically. Select additional permissions below.</p>

                <div id="dept-perms-display" style="margin-bottom:1.5rem;">
                    <h3 style="font-size:0.9rem;color:var(--adm-on-surface-variant);margin-bottom:0.75rem;">
                        <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;">check_circle</span>
                        Department Default Permissions
                    </h3>
                    <div id="dept-perms-list" style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                        <!-- Filled by JS -->
                    </div>
                </div>

                <div>
                    <h3 style="font-size:0.9rem;color:var(--adm-on-surface-variant);margin-bottom:0.75rem;">
                        <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;">add_circle</span>
                        Additional Permissions
                    </h3>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:0.5rem;">
                        <?php foreach ($allPermissions as $permKey => $permLabel): ?>
                            <?php
                            $isDeptDefault = in_array($permKey, $currentDeptDefaults);
                            $isChecked = $isDeptDefault || in_array($permKey, $staffPermissions);
                            ?>
                            <label class="adm-form-check" style="padding:0.5rem;border:1px solid var(--adm-outline-variant);border-radius:6px;<?= $isDeptDefault ? 'background:var(--adm-success-bg);opacity:0.7;' : '' ?>">
                                <input type="checkbox" name="permissions[]" value="<?= $permKey ?>" <?= $isChecked ? 'checked' : '' ?> <?= $isDeptDefault ? 'disabled' : '' ?>>
                                <span><?= $permLabel ?></span>
                                <?php if ($isDeptDefault): ?>
                                    <span style="font-size:0.7rem;color:var(--adm-secondary);">(auto)</span>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="adm-form-card">
                <div class="adm-form-actions">
                    <a href="staff.php" class="adm-btn adm-btn-secondary">Cancel</a>
                    <button type="submit" class="adm-btn adm-btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Staff Member' ?></button>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
var deptPerms = <?= json_encode($departmentPermissions) ?>;
var allPerms = <?= json_encode($allPermissions) ?>;

function updatePermissionDisplay() {
    var dept = document.getElementById('department').value;
    var perms = deptPerms[dept] || [];
    var list = document.getElementById('dept-perms-list');
    list.innerHTML = '';

    perms.forEach(function(p) {
        var badge = document.createElement('span');
        badge.className = 'adm-status-badge adm-status-confirmed';
        badge.textContent = allPerms[p] || p;
        list.appendChild(badge);
    });

    // Update checkbox states
    var checkboxes = document.querySelectorAll('input[name="permissions[]"]');
    checkboxes.forEach(function(cb) {
        var isDefault = perms.indexOf(cb.value) !== -1;
        var wrapper = cb.closest('label');
        if (isDefault) {
            cb.checked = true;
            cb.disabled = true;
            wrapper.style.background = 'var(--adm-success-bg)';
            wrapper.style.opacity = '0.7';
        } else {
            cb.disabled = false;
            wrapper.style.background = '';
            wrapper.style.opacity = '';
        }
    });
}

// Initialize on page load
updatePermissionDisplay();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
