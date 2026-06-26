<?php
/**
 * Admin/Staff Sidebar
 *
 * Navigation items are filtered based on user role and department permissions.
 * Admins see everything. Staff see only what their department has access to.
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$isStaff = ($_SESSION['user_role'] ?? '') === 'staff';

// Get staff department for sidebar filtering
$sidebarStaffDept = null;
if ($isStaff) {
    $staffProf = getStaffProfile($db);
    $sidebarStaffDept = $staffProf['department'] ?? null;
}

// Dashboard URL based on role
$dashboardUrl = $isAdmin ? 'index.php' : 'staff-dashboard.php';
?>
<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<aside class="adm-sidebar" id="adminSidebar">
    <div class="adm-sidebar-brand">
        <img src="../images/logo.png" alt="GlobeTrek">
        <span><?= $isAdmin ? 'GlobeTrek Admin' : 'GlobeTrek Staff' ?></span>
    </div>

    <nav class="adm-sidebar-nav">
        <!-- Main -->
        <div class="adm-sidebar-section">Main</div>
        <a href="<?= $dashboardUrl ?>" class="adm-sidebar-link <?= $currentPage === 'index' || $currentPage === 'staff-dashboard' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">dashboard</span>
            Dashboard
        </a>

        <?php if ($isAdmin || hasPermission('manage_packages', $db) || hasPermission('manage_destinations', $db) || hasPermission('manage_accommodations', $db) || hasPermission('manage_transportation', $db) || hasPermission('manage_guides', $db) || hasPermission('manage_testimonials', $db)): ?>
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
        <a href="tags.php" class="adm-sidebar-link <?= $currentPage === 'tags' || $currentPage === 'tag-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">label</span>
            Tags
        </a>
        <?php endif; ?>

        <?php if ($isAdmin || hasPermission('manage_bookings', $db) || hasPermission('manage_inquiries', $db) || hasPermission('manage_contacts', $db) || hasPermission('manage_custom_trips', $db)): ?>
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
        <div class="adm-sidebar-section">Users</div>
        <a href="users.php" class="adm-sidebar-link <?= $currentPage === 'users' || $currentPage === 'user-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">group</span>
            Users
        </a>
        <a href="newsletters.php" class="adm-sidebar-link <?= $currentPage === 'newsletters' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">campaign</span>
            Newsletter
        </a>

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

    <div class="adm-sidebar-footer">
        <div class="adm-sidebar-user">
            <div class="adm-sidebar-avatar"><?= $adminInitials ?></div>
            <div class="adm-sidebar-user-info">
                <div class="adm-sidebar-user-name"><?= $adminName ?></div>
                <div class="adm-sidebar-user-role"><?= $isAdmin ? 'Administrator' : ($departmentLabels[$sidebarStaffDept] ?? 'Staff') ?></div>
            </div>
        </div>
    </div>
</aside>
