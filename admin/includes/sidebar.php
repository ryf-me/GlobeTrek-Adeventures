<?php
/**
 * File: admin/includes/sidebar.php
 * Purpose: Admin/Staff sidebar navigation — renders role-filtered navigation links based on department permissions.
 * Dependencies: admin/includes/header.php (must be included first — provides $db, hasPermission(), getStaffProfile(), $departmentLabels)
 * Used By: admin/index.php, admin/packages.php, admin/destinations.php, and all other admin pages
 * Parent Files: admin/index.php, admin/packages.php, admin/package-edit.php, admin/destinations.php, admin/destination-edit.php
 * Child Files: none (outputs sidebar HTML)
 * @package GlobeTrek\Admin
 */

// === CURRENT PAGE DETECTION ===
// Used to apply the 'active' CSS class to the matching sidebar link.
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// === ROLE FLAGS ===
// Determines which navigation sections the current user can see.
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$isStaff = ($_SESSION['user_role'] ?? '') === 'staff';

// === STAFF DEPARTMENT LOOKUP ===
// Staff sidebar items are filtered by their department's permissions.
$sidebarStaffDept = null;
if ($isStaff) {
    $staffProf = getStaffProfile($db);
    $sidebarStaffDept = $staffProf['department'] ?? null;
}

// === DASHBOARD URL BASED ON ROLE ===
// Admins go to index.php; staff go to staff-dashboard.php.
$dashboardUrl = $isAdmin ? 'index.php' : 'staff-dashboard.php';
?>
<!-- Sidebar overlay for mobile — clicking it closes the sidebar -->
<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<aside class="adm-sidebar" id="adminSidebar">
    <!-- === BRAND / LOGO === -->
    <div class="adm-sidebar-brand">
        <img src="../images/logo.png" alt="GlobeTrek">
        <span><?= $isAdmin ? 'GlobeTrek Admin' : 'GlobeTrek Staff' ?></span>
    </div>

    <nav class="adm-sidebar-nav">
        <!-- === MAIN SECTION === -->
        <!-- Dashboard link is always visible to all authenticated users -->
        <div class="adm-sidebar-section">Main</div>
        <a href="<?= $dashboardUrl ?>" class="adm-sidebar-link <?= $currentPage === 'index' || $currentPage === 'staff-dashboard' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">dashboard</span>
            Dashboard
        </a>

        <?php if ($isAdmin || hasPermission('manage_packages', $db) || hasPermission('manage_destinations', $db) || hasPermission('manage_accommodations', $db) || hasPermission('manage_transportation', $db) || hasPermission('manage_guides', $db) || hasPermission('manage_testimonials', $db)): ?>
        <!-- === CONTENT SECTION === -->
        <!-- Visible if user has any content-management permission -->
        <div class="adm-sidebar-section">Content</div>
        <?php if ($isAdmin || hasPermission('manage_packages', $db)): ?>
        <a href="packages.php" class="adm-sidebar-link <?= $currentPage === 'packages' || $currentPage === 'package-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">luggage</span>
            Packages
        </a>
        <?php endif; ?>
        <?php if ($isAdmin || hasPermission('manage_destinations', $db)): ?>
        <a href="destinations.php" class="adm-sidebar-link <?= $currentPage === 'destinations' || $currentPage === 'destination-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">location_on</span>
            Destinations
        </a>
        <?php endif; ?>
        <?php if ($isAdmin || hasPermission('manage_accommodations', $db)): ?>
        <a href="accommodations.php" class="adm-sidebar-link <?= $currentPage === 'accommodations' || $currentPage === 'accommodation-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">hotel</span>
            Accommodations
        </a>
        <?php endif; ?>
        <?php if ($isAdmin || hasPermission('manage_transportation', $db)): ?>
        <a href="transportation.php" class="adm-sidebar-link <?= $currentPage === 'transportation' || $currentPage === 'transport-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">directions_car</span>
            Transportation
        </a>
        <?php endif; ?>
        <?php if ($isAdmin || hasPermission('manage_guides', $db)): ?>
        <a href="guides.php" class="adm-sidebar-link <?= $currentPage === 'guides' || $currentPage === 'guide-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">person_raised_hand</span>
            Guides
        </a>
        <?php endif; ?>
        <?php if ($isAdmin || hasPermission('manage_testimonials', $db)): ?>
        <a href="testimonials.php" class="adm-sidebar-link <?= $currentPage === 'testimonials' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">star</span>
            Testimonials
        </a>
        <a href="guide-reviews.php" class="adm-sidebar-link <?= $currentPage === 'guide-reviews' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">reviews</span>
            Guide Reviews
        </a>
        <?php endif; ?>
        <!-- Tags are visible to anyone who can manage any content type -->
        <a href="tags.php" class="adm-sidebar-link <?= $currentPage === 'tags' || $currentPage === 'tag-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">label</span>
            Tags
        </a>
        <?php endif; ?>

        <?php if ($isAdmin || hasPermission('manage_bookings', $db) || hasPermission('manage_inquiries', $db) || hasPermission('manage_contacts', $db) || hasPermission('manage_custom_trips', $db)): ?>
        <!-- === OPERATIONS SECTION === -->
        <!-- Visible if user has any operations-related permission -->
        <div class="adm-sidebar-section">Operations</div>
        <?php if ($isAdmin || hasPermission('manage_bookings', $db)): ?>
        <a href="bookings.php" class="adm-sidebar-link <?= $currentPage === 'bookings' || $currentPage === 'booking-detail' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">flight_takeoff</span>
            Bookings
        </a>
        <?php endif; ?>
        <?php if ($isAdmin || hasPermission('manage_inquiries', $db)): ?>
        <a href="inquiries.php" class="adm-sidebar-link <?= $currentPage === 'inquiries' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">chat</span>
            Inquiries
        </a>
        <?php endif; ?>
        <?php if ($isAdmin || hasPermission('manage_contacts', $db)): ?>
        <a href="contacts.php" class="adm-sidebar-link <?= $currentPage === 'contacts' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">mail</span>
            Contact Messages
        </a>
        <?php endif; ?>
        <?php if ($isAdmin || hasPermission('manage_custom_trips', $db)): ?>
        <a href="custom-trips.php" class="adm-sidebar-link <?= $currentPage === 'custom-trips' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">route</span>
            Custom Trips
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
        <!-- === USERS SECTION (admin only) === -->
        <div class="adm-sidebar-section">Users</div>
        <a href="users.php" class="adm-sidebar-link <?= $currentPage === 'users' || $currentPage === 'user-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">group</span>
            Users
        </a>
        <a href="newsletters.php" class="adm-sidebar-link <?= $currentPage === 'newsletters' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">campaign</span>
            Newsletter
        </a>

        <!-- === STAFF SECTION (admin only) === -->
        <div class="adm-sidebar-section">Staff</div>
        <a href="staff.php" class="adm-sidebar-link <?= $currentPage === 'staff' || $currentPage === 'staff-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">badge</span>
            Staff Members
        </a>
        <a href="staff-assignments.php" class="adm-sidebar-link <?= $currentPage === 'staff-assignments' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">assignment_ind</span>
            Assignments
        </a>
        <?php endif; ?>

        <?php if ($isAdmin || hasPermission('view_reports', $db) || hasPermission('manage_payments', $db)): ?>
        <!-- === REPORTS SECTION === -->
        <!-- Visible to admins and staff with reports/payments permissions -->
        <div class="adm-sidebar-section">Reports</div>
        <?php if ($isAdmin || hasPermission('view_reports', $db)): ?>
        <a href="reports.php" class="adm-sidebar-link <?= $currentPage === 'reports' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">bar_chart</span>
            Sales Reports
        </a>
        <a href="customer-reports.php" class="adm-sidebar-link <?= $currentPage === 'customer-reports' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">people</span>
            Customer Reports
        </a>
        <?php endif; ?>
        <?php if ($isAdmin || hasPermission('manage_payments', $db)): ?>
        <a href="providers.php" class="adm-sidebar-link <?= $currentPage === 'providers' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">handshake</span>
            Providers
        </a>
        <?php endif; ?>
        <!-- System tools are admin-only -->
        <?php if ($isAdmin): ?>
        <a href="system-logs.php" class="adm-sidebar-link <?= $currentPage === 'system-logs' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">history</span>
            System Logs
        </a>
        <a href="backup.php" class="adm-sidebar-link <?= $currentPage === 'backup' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">backup</span>
            Database Backup
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </nav>

    <!-- === SIDEBAR FOOTER — Current user info === -->
    <div class="adm-sidebar-footer">
        <div class="adm-sidebar-user">
            <!-- User initials avatar -->
            <div class="adm-sidebar-avatar"><?= $adminInitials ?></div>
            <div class="adm-sidebar-user-info">
                <div class="adm-sidebar-user-name"><?= $adminName ?></div>
                <!-- Show department name for staff, or "Administrator" for admins -->
                <div class="adm-sidebar-user-role"><?= $isAdmin ? 'Administrator' : ($departmentLabels[$sidebarStaffDept] ?? 'Staff') ?></div>
            </div>
        </div>
    </div>
</aside>
