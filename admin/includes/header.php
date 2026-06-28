<?php
/**
 * File: admin/includes/header.php
 * Purpose: Admin panel header — performs authentication, loads database/CSRF/currency configs, defines permission helpers, and outputs the HTML <head>.
 * Dependencies: config/database.php, config/csrf.php, config/currency.php
 * Used By: All admin pages (require_once at top)
 * Parent Files: admin/index.php, admin/packages.php, admin/package-edit.php, admin/destinations.php, admin/destination-edit.php, and all other admin pages
 * Child Files: none (outputs HTML doctype + head)
 * @package GlobeTrek\Admin
 */

// === SESSION CHECK ===
// Start session if not already active.
if (session_status() === PHP_SESSION_NONE) session_start();

// === AUTHENTICATION GATE ===
// Only users with 'admin' or 'staff' roles may access any admin page.
// Unauthenticated or unauthorized users are redirected to the login page.
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'staff'])) {
    header('Location: ../pages/login.php');
    exit;
}

// === CONFIGURATION LOADING ===
// Load database connection, CSRF token helper, and currency configuration.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../../config/currency.php';
$db = getDB();

// === ADMIN USER INFO ===
// Extract and sanitize the logged-in user's name/email for display in the sidebar and header.
$adminName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$adminEmail = htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES, 'UTF-8');

// === INITIALS GENERATION ===
// Split name into words, take the first character of each word, and uppercase it.
// Falls back to 'A' if no initials could be generated.
$adminWords = explode(' ', $adminName);
$adminInitials = '';
foreach ($adminWords as $w) { if ($w !== '') $adminInitials .= mb_strtoupper(mb_substr($w, 0, 1)); }
if ($adminInitials === '') $adminInitials = 'A';

// === CURRENT PAGE DETECTION ===
// Extract the current page name (without .php extension) for active-link highlighting in sidebar.
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

/**
 * === DEPARTMENT PERMISSION MAP ===
 * Each department has a set of default permissions that staff members in that department inherit.
 * Admins bypass this map entirely (they have all permissions).
 */
$departmentPermissions = [
    'operations'      => ['manage_bookings', 'manage_packages', 'manage_accommodations', 'manage_transportation', 'manage_guides', 'manage_destinations'],
    'customer_service'=> ['manage_inquiries', 'manage_contacts', 'manage_custom_trips', 'view_customers', 'manage_testimonials'],
    'sales'           => ['view_reports', 'manage_payments', 'view_customers', 'manage_bookings'],
    'marketing'       => ['manage_newsletters', 'manage_destinations', 'manage_testimonials', 'view_reports'],
];

/**
 * === PERMISSION CHECK FUNCTION ===
 * Checks if the current user has a specific permission.
 * - Admins always return true (full access).
 * - Staff members are checked against their department's default permissions first,
 *   then against individually granted permissions in the staff_permissions table.
 *
 * @param string $permission The permission key to check (e.g. 'manage_bookings')
 * @param object|null $db Optional database connection (uses global $db if null)
 * @return bool
 */
function hasPermission(string $permission, ?object $db = null): bool {
    // Admins have unrestricted access
    if (($_SESSION['user_role'] ?? '') === 'admin') return true;
    // Non-staff users have no permissions
    if (($_SESSION['user_role'] ?? '') !== 'staff') return false;

    global $departmentPermissions;

    if ($db === null) $db = getDB();
    $userId = $_SESSION['user_id'];

    // Look up the staff profile to get their department
    $stmt = $db->prepare("SELECT sp.id, sp.department FROM staff_profiles sp WHERE sp.user_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $userId]);
    $staff = $stmt->fetch();

    if (!$staff) return false;

    // Check if the permission is in the department's default set
    $deptPerms = $departmentPermissions[$staff['department']] ?? [];
    if (in_array($permission, $deptPerms)) return true;

    // Check for individually granted permissions (overrides/extras)
    $stmt = $db->prepare("SELECT 1 FROM staff_permissions WHERE staff_id = :sid AND permission = :perm LIMIT 1");
    $stmt->execute([':sid' => $staff['id'], ':perm' => $permission]);
    return $stmt->fetch() !== false;
}

/**
 * Returns the current staff profile array, or null if the user is not staff.
 * Used by the sidebar and other staff-specific components.
 */
function getStaffProfile(?object $db = null): ?array {
    if (($_SESSION['user_role'] ?? '') !== 'staff') return null;
    if ($db === null) $db = getDB();
    $stmt = $db->prepare("SELECT sp.* FROM staff_profiles sp WHERE sp.user_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Returns all staff profiles with their user info, ordered by name.
 * Used by admin for staff management and assignment dropdowns.
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
 * Returns all assignments for the current staff member, including
 * booking/inquiry details via LEFT JOINs. Used by staff-dashboard.php.
 */
function getMyAssignments(?object $db = null): array {
    if (($_SESSION['user_role'] ?? '') !== 'staff') return [];
    if ($db === null) $db = getDB();

    $staff = getStaffProfile($db);
    if (!$staff) return [];

    // Polymorphic join: assignment references either a booking or an inquiry via entity_type + entity_id
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
 * Department display labels — maps department keys to human-readable names.
 * Used in sidebar and staff-related UI elements.
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
    <!-- Page title set by each admin page via $pageTitle before including this file -->
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> - GlobeTrek Admin</title>
    <!-- Google Fonts: Hanken Grotesk for UI text -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Material Symbols for icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <!-- Admin-specific stylesheet -->
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="adm-page">
