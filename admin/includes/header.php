<?php
/**
 * Admin Panel Header (included on all admin pages)
 *
 * Performs session-based authentication check — only users with
 * 'admin' or 'staff' roles may access the admin panel.
 * Also loads the database connection and CSRF helper.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'staff'])) {
    header('Location: ../pages/login.php');
    exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';
$db = getDB();

$adminName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$adminEmail = htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES, 'UTF-8');
$adminWords = explode(' ', $adminName);
$adminInitials = '';
foreach ($adminWords as $w) { if ($w !== '') $adminInitials .= mb_strtoupper(mb_substr($w, 0, 1)); }
if ($adminInitials === '') $adminInitials = 'A';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

/**
 * Department-based permission map.
 * Each department has a set of default permissions.
 */
$departmentPermissions = [
    'operations'      => ['manage_bookings', 'manage_packages', 'manage_accommodations', 'manage_transportation', 'manage_guides', 'manage_destinations'],
    'customer_service'=> ['manage_inquiries', 'manage_contacts', 'manage_custom_trips', 'view_customers'],
    'sales'           => ['view_reports', 'manage_payments', 'view_customers', 'manage_bookings'],
    'marketing'       => ['manage_newsletters', 'manage_destinations', 'manage_testimonials', 'view_reports'],
];

/**
 * Check if the current user has a specific permission.
 * Admins always have all permissions.
 * Staff permissions are based on their department + any extra granted permissions.
 */
function hasPermission(string $permission, ?object $db = null): bool {
    if (($_SESSION['user_role'] ?? '') === 'admin') return true;
    if (($_SESSION['user_role'] ?? '') !== 'staff') return false;

    global $departmentPermissions;

    if ($db === null) $db = getDB();
    $userId = $_SESSION['user_id'];

    // Get staff department
    $stmt = $db->prepare("SELECT sp.id, sp.department FROM staff_profiles sp WHERE sp.user_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $userId]);
    $staff = $stmt->fetch();

    if (!$staff) return false;

    // Check department default permissions
    $deptPerms = $departmentPermissions[$staff['department']] ?? [];
    if (in_array($permission, $deptPerms)) return true;

    // Check extra granted permissions
    $stmt = $db->prepare("SELECT 1 FROM staff_permissions WHERE staff_id = :sid AND permission = :perm LIMIT 1");
    $stmt->execute([':sid' => $staff['id'], ':perm' => $permission]);
    return $stmt->fetch() !== false;
}

/**
 * Get the current staff profile (null if admin or not a staff member).
 */
function getStaffProfile(?object $db = null): ?array {
    if (($_SESSION['user_role'] ?? '') !== 'staff') return null;
    if ($db === null) $db = getDB();
    $stmt = $db->prepare("SELECT sp.* FROM staff_profiles sp WHERE sp.user_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Get all staff profiles (for admin use).
 */
function getAllStaff(?object $db = null): array {
    if ($db === null) $db = getDB();
    return $db->query(
        "SELECT sp.*, u.full_name, u.email, u.profile_photo
         FROM staff_profiles sp
         JOIN users u ON sp.user_id = u.id
         ORDER BY u.full_name ASC"
    )->fetchAll();
}

/**
 * Get assignments for the current staff member.
 */
function getMyAssignments(?object $db = null): array {
    if (($_SESSION['user_role'] ?? '') !== 'staff') return [];
    if ($db === null) $db = getDB();

    $staff = getStaffProfile($db);
    if (!$staff) return [];

    $stmt = $db->prepare(
        "SELECT sa.entity_type, sa.entity_id, sa.assigned_at,
                b.booking_reference, b.first_name, b.last_name, b.status AS booking_status,
                i.inquiry_id_code, i.subject, i.status AS inquiry_status
         FROM staff_assignments sa
         LEFT JOIN bookings b ON sa.entity_type = 'booking' AND sa.entity_id = b.id
         LEFT JOIN inquiries i ON sa.entity_type = 'inquiry' AND sa.entity_id = i.id
         WHERE sa.staff_id = :sid
         ORDER BY sa.assigned_at DESC"
    );
    $stmt->execute([':sid' => $staff['id']]);
    return $stmt->fetchAll();
}

/**
 * Department display labels.
 */
$departmentLabels = [
    'operations' => 'Operations',
    'customer_service' => 'Customer Service',
    'sales' => 'Sales',
    'marketing' => 'Marketing',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> - GlobeTrek Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="adm-page">
