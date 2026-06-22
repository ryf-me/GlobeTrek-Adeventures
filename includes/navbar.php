<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<nav class="navbar">
    <a class="logo" href="<?php echo $basePath; ?>index.php#home">
        <img src="<?php echo $basePath; ?>images/logo.png" alt="Globe Trek Adventures logo" />
        <span>Globe Trek</span>
    </a>

    <button class="menu-toggle" aria-label="Open navigation" aria-expanded="false">
        ☰
    </button>

    <ul class="nav-links">
        <li><a href="<?php echo $basePath; ?>index.php#home">Home</a></li>
        <li><a href="<?php echo $basePath; ?>pages/packages.php">Packages</a></li>
<li><a href="<?php echo $basePath; ?>pages/guides.php">Guides</a></li>
        <li><a href="<?php echo $basePath; ?>pages/accommodations.php">Accommodations</a></li>
        <li><a href="<?php echo $basePath; ?>pages/transportation.php">Transportation</a></li>
        <li><a href="<?php echo $basePath; ?>pages/about.php">About Us</a></li>
        <li><a href="<?php echo $basePath; ?>pages/contact.php">Contact Us</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <li><a href="<?php echo $basePath; ?>admin/index.php">Admin</a></li>
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
                ?>
                <div class="user-profile-trigger" id="profileTrigger" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-label="User menu">
                    <div class="user-avatar"><?php echo $udInitials; ?></div>
                    <span class="user-name-label"><?php echo $udName; ?></span>
                    <span class="material-symbols-outlined trigger-chevron">expand_more</span>

                    <div class="profile-dropdown" id="profileDropdown" role="menu">
                        <div class="dropdown-user-info">
                            <div class="dropdown-user-avatar"><?php echo $udInitials; ?></div>
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
            <li><a class="login-btn" href="<?php echo $basePath; ?>pages/login.php">Login</a></li>
            <li><a class="signup-btn" href="<?php echo $basePath; ?>pages/signup.php">Sign Up</a></li>
        <?php endif; ?>
    </ul>
</nav>
