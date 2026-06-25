<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<?php require_once __DIR__ . '/../config/csrf.php'; ?>
<script>var csrfToken = "<?php echo htmlspecialchars(getCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>";</script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPage = ($currentPage === 'index.php') ? 'home' : str_replace('.php', '', $currentPage);
?>
<nav class="navbar">
    <a class="logo" href="<?php echo $basePath; ?>index.php#home">
        <img src="<?php echo $basePath; ?>images/logo.png" alt="GlobeTrek Adventures logo" />
        <div class="logo-text">
            <span class="brand-name">GlobeTrek</span>
            <span class="brand-tagline">Explore. Experience. Remember.</span>
        </div>
    </a>

    <button class="menu-toggle" aria-label="Open navigation" aria-expanded="false">
        ☰
    </button>

    <ul class="nav-links">
        <li<?php if ($currentPage === 'home') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>index.php#home">Home</a></li>
        <li<?php if ($currentPage === 'destinations') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/destinations.php">Destinations</a></li>
        <li<?php if ($currentPage === 'packages') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/packages.php">Packages</a></li>
        <li<?php if ($currentPage === 'guides') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/guides.php">Guides</a></li>
        <li<?php if ($currentPage === 'accommodations') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/accommodations.php">Accommodations</a></li>
        <li<?php if ($currentPage === 'transportation') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/transportation.php">Transport</a></li>
        <li<?php if ($currentPage === 'about') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/about.php">About Us</a></li>
        <li<?php if ($currentPage === 'contact') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/contact.php">Contact Us</a></li>

        <li class="nav-phone">
            <span class="material-symbols-outlined">call</span>
            <span>+94 77 123 4567</span>
        </li>

        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <li><a href="<?php echo $basePath; ?>admin/index.php">Admin</a></li>
            <?php elseif (($_SESSION['user_role'] ?? '') === 'staff'): ?>
                <li><a href="<?php echo $basePath; ?>admin/staff-dashboard.php">Staff</a></li>
            <?php endif; ?>
            <li>
                <?php
                    $udName = htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
                    $udWords = explode(' ', $udName);
                    $udInitials = '';
                    foreach ($udWords as $udW) {
                        if ($udW !== '') $udInitials .= mb_strtoupper(mb_substr($udW, 0, 1));
                    }
                    if ($udInitials === '') $udInitials = 'U';
                    $udEmail = htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES, 'UTF-8');
                    $udPhoto = htmlspecialchars($_SESSION['user_profile_photo'] ?? '', ENT_QUOTES, 'UTF-8');
                ?>
                <div class="user-profile-trigger" id="profileTrigger" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-label="User menu">
                    <div class="user-avatar">
                        <?php if ($udPhoto): ?>
                            <img src="<?php echo $basePath . $udPhoto; ?>" alt="Profile photo">
                        <?php else: ?>
                            <?php echo $udInitials; ?>
                        <?php endif; ?>
                    </div>
                    <span class="material-symbols-outlined trigger-chevron">expand_more</span>

                    <div class="profile-dropdown" id="profileDropdown" role="menu">
                        <div class="dropdown-user-info">
                            <div class="dropdown-user-avatar">
                                <?php if ($udPhoto): ?>
                                    <img src="<?php echo $basePath . $udPhoto; ?>" alt="Profile photo">
                                <?php else: ?>
                                    <?php echo $udInitials; ?>
                                <?php endif; ?>
                            </div>
                            <div class="dropdown-user-details">
                                <p class="dropdown-user-name"><?php echo $udName; ?></p>
                                <?php if ($udEmail !== ''): ?>
                                    <p class="dropdown-user-email"><?php echo $udEmail; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <nav class="dropdown-nav" aria-label="User menu">
                            <a class="dropdown-nav-item" href="<?php echo $basePath; ?>pages/user-profile.php" role="menuitem">
                                <span class="material-symbols-outlined">person</span>
                                Profile Info
                            </a>
                            <a class="dropdown-nav-item" href="<?php echo $basePath; ?>pages/my-bookings.php" role="menuitem">
                                <span class="material-symbols-outlined">flight_takeoff</span>
                                My Bookings
                            </a>
                            <a class="dropdown-nav-item" href="<?php echo $basePath; ?>pages/wishlist.php" role="menuitem">
                                <span class="material-symbols-outlined">favorite</span>
                                Wishlist
                            </a>
                            <a class="dropdown-nav-item" href="<?php echo $basePath; ?>pages/inquiries.php" role="menuitem">
                                <span class="material-symbols-outlined">chat_bubble</span>
                                Inquiries
                            </a>
                            <a class="dropdown-nav-item" href="<?php echo $basePath; ?>pages/settings.php" role="menuitem">
                                <span class="material-symbols-outlined">settings</span>
                                Settings
                            </a>
                        </nav>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-logout" href="<?php echo $basePath; ?>pages/logout.php" role="menuitem">
                            <span class="material-symbols-outlined">logout</span>
                            Log Out
                        </a>
                    </div>
                </div>
            </li>
        <?php else: ?>
            <li><a class="login-btn" href="<?php echo $basePath; ?>pages/login.php">Log In</a></li>
            <li><a class="signup-btn" href="<?php echo $basePath; ?>pages/signup.php">Sign Up</a></li>
        <?php endif; ?>
    </ul>
</nav>
