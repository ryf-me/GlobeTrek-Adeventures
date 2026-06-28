<?php
/**
 * File: includes/user-sidebar.php
 * Purpose: Reusable sidebar navigation for user dashboard pages
 *
 * This file provides:
 *   1. Vertical sidebar with 6 navigation links
 *   2. Active page highlighting based on $activePage variable
 *   3. Consistent navigation across all user dashboard pages
 *
 * Dependencies:
 *   - config/session.php (for session access)
 *
 * Used By:
 *   - pages/user-profile.php ($activePage = 'profile')
 *   - pages/my-bookings.php ($activePage = 'bookings')
 *   - pages/wishlist.php ($activePage = 'wishlist')
 *   - pages/inquiries.php ($activePage = 'inquiries')
 *   - pages/my-reviews.php ($activePage = 'my-reviews')
 *   - pages/settings.php ($activePage = 'settings')
 *
 * Parent Files: Any page that includes this file must set $activePage and $basePath
 * Child Files: None (no includes)
 *
 * Required Variables:
 *   - $activePage (string): Current page identifier (e.g., 'profile', 'bookings')
 *   - $basePath (string): Relative path to project root (default: '../')
 *
 * Usage Convention:
 *   // In the including page:
 *   $activePage = 'bookings';  // Set the active page
 *   include $basePath . 'includes/user-sidebar.php';
 *
 * @package GlobeTrek\Includes
 */

// === SESSION INITIALIZATION ===
if (session_status() === PHP_SESSION_NONE) session_start();

// === DEFAULT VALUES (using null coalescing for reusability) ===
// $activePage defaults to 'profile' if not set by the including page
// $basePath defaults to '../' (assuming files are in the pages/ subdirectory)
// This pattern makes the sidebar reusable across different directory depths
$activePage = $activePage ?? 'profile';
$basePath = $basePath ?? '../';
?>

<!-- === USER SIDEBAR === -->
<aside class="usr-sidebar">
    <nav class="usr-sidebar-nav">
        <!-- === SIDEBAR NAVIGATION LINKS === -->
        <!-- Each link uses a ternary to add 'active' class when matching $activePage -->

        <!-- Profile Info link -->
        <a class="usr-sidebar-link <?= $activePage === 'profile' ? 'active' : '' ?>" href="<?= $basePath ?>pages/user-profile.php">
            <span class="material-symbols-outlined">person</span>
            Profile Info
        </a>

        <!-- My Bookings link -->
        <a class="usr-sidebar-link <?= $activePage === 'bookings' ? 'active' : '' ?>" href="<?= $basePath ?>pages/my-bookings.php">
            <span class="material-symbols-outlined">flight_takeoff</span>
            My Bookings
        </a>

        <!-- Wishlist link -->
        <a class="usr-sidebar-link <?= $activePage === 'wishlist' ? 'active' : '' ?>" href="<?= $basePath ?>pages/wishlist.php">
            <span class="material-symbols-outlined">favorite</span>
            Wishlist
        </a>

        <!-- Inquiries link -->
        <a class="usr-sidebar-link <?= $activePage === 'inquiries' ? 'active' : '' ?>" href="<?= $basePath ?>pages/inquiries.php">
            <span class="material-symbols-outlined">chat_bubble</span>
            Inquiries
        </a>

        <!-- My Reviews link -->
        <a class="usr-sidebar-link <?= $activePage === 'my-reviews' ? 'active' : '' ?>" href="<?= $basePath ?>pages/my-reviews.php">
            <span class="material-symbols-outlined">rate_review</span>
            My Reviews
        </a>

        <!-- Settings link -->
        <a class="usr-sidebar-link <?= $activePage === 'settings' ? 'active' : '' ?>" href="<?= $basePath ?>pages/settings.php">
            <span class="material-symbols-outlined">settings</span>
            Settings
        </a>
    </nav>
</aside>
