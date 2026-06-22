<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$activePage = $activePage ?? 'profile';
$basePath = $basePath ?? '../';
?>
<aside class="usr-sidebar">
    <nav class="usr-sidebar-nav">
        <a class="usr-sidebar-link <?= $activePage === 'profile' ? 'active' : '' ?>" href="<?= $basePath ?>pages/user-profile.php">
            <span class="material-symbols-outlined">person</span>
            Profile Info
        </a>
        <a class="usr-sidebar-link <?= $activePage === 'bookings' ? 'active' : '' ?>" href="<?= $basePath ?>pages/my-bookings.php">
            <span class="material-symbols-outlined">flight_takeoff</span>
            My Bookings
        </a>
        <a class="usr-sidebar-link <?= $activePage === 'wishlist' ? 'active' : '' ?>" href="<?= $basePath ?>pages/wishlist.php">
            <span class="material-symbols-outlined">favorite</span>
            Wishlist
        </a>
        <a class="usr-sidebar-link <?= $activePage === 'inquiries' ? 'active' : '' ?>" href="<?= $basePath ?>pages/inquiries.php">
            <span class="material-symbols-outlined">chat_bubble</span>
            Inquiries
        </a>
        <a class="usr-sidebar-link <?= $activePage === 'settings' ? 'active' : '' ?>" href="<?= $basePath ?>pages/settings.php">
            <span class="material-symbols-outlined">settings</span>
            Settings
        </a>
    </nav>
</aside>
