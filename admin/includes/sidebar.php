<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<div class="adm-sidebar-overlay" id="sidebarOverlay"></div>
<aside class="adm-sidebar" id="adminSidebar">
    <div class="adm-sidebar-brand">
        <img src="../images/logo.png" alt="GlobeTrek">
        <span>GlobeTrek Admin</span>
    </div>

    <nav class="adm-sidebar-nav">
        <div class="adm-sidebar-section">Main</div>
        <a href="index.php" class="adm-sidebar-link <?= $currentPage === 'index' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">dashboard</span>
            Dashboard
        </a>

        <div class="adm-sidebar-section">Content</div>
        <a href="packages.php" class="adm-sidebar-link <?= $currentPage === 'packages' || $currentPage === 'package-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">luggage</span>
            Packages
        </a>
        <a href="destinations.php" class="adm-sidebar-link <?= $currentPage === 'destinations' || $currentPage === 'destination-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">location_on</span>
            Destinations
        </a>
        <a href="accommodations.php" class="adm-sidebar-link <?= $currentPage === 'accommodations' || $currentPage === 'accommodation-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">hotel</span>
            Accommodations
        </a>
        <a href="transportation.php" class="adm-sidebar-link <?= $currentPage === 'transportation' || $currentPage === 'transport-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">directions_car</span>
            Transportation
        </a>
        <a href="guides.php" class="adm-sidebar-link <?= $currentPage === 'guides' || $currentPage === 'guide-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">person_raised_hand</span>
            Guides
        </a>

        <div class="adm-sidebar-section">Operations</div>
        <a href="bookings.php" class="adm-sidebar-link <?= $currentPage === 'bookings' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">flight_takeoff</span>
            Bookings
        </a>
        <a href="inquiries.php" class="adm-sidebar-link <?= $currentPage === 'inquiries' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">chat</span>
            Inquiries
        </a>
        <a href="contacts.php" class="adm-sidebar-link <?= $currentPage === 'contacts' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">mail</span>
            Contact Messages
        </a>
        <a href="custom-trips.php" class="adm-sidebar-link <?= $currentPage === 'custom-trips' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">route</span>
            Custom Trips
        </a>

        <div class="adm-sidebar-section">Users</div>
        <a href="users.php" class="adm-sidebar-link <?= $currentPage === 'users' || $currentPage === 'user-edit' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">group</span>
            Users
        </a>
        <a href="newsletters.php" class="adm-sidebar-link <?= $currentPage === 'newsletters' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">campaign</span>
            Newsletter
        </a>

        <div class="adm-sidebar-section">Reports</div>
        <a href="reports.php" class="adm-sidebar-link <?= $currentPage === 'reports' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">bar_chart</span>
            Sales Reports
        </a>
        <a href="customer-reports.php" class="adm-sidebar-link <?= $currentPage === 'customer-reports' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">people</span>
            Customer Reports
        </a>
        <a href="providers.php" class="adm-sidebar-link <?= $currentPage === 'providers' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">handshake</span>
            Providers
        </a>
        <a href="system-logs.php" class="adm-sidebar-link <?= $currentPage === 'system-logs' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">history</span>
            System Logs
        </a>
    </nav>

    <div class="adm-sidebar-footer">
        <div class="adm-sidebar-user">
            <div class="adm-sidebar-avatar"><?= $adminInitials ?></div>
            <div class="adm-sidebar-user-info">
                <div class="adm-sidebar-user-name"><?= $adminName ?></div>
                <div class="adm-sidebar-user-role">Administrator</div>
            </div>
        </div>
    </div>
</aside>
