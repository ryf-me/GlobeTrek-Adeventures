<?php
/**
 * File: includes/navbar.php
 * Purpose: Site-wide navigation bar with responsive menu and user profile dropdown
 *
 * This file provides:
 *   1. Sticky navigation bar with logo and brand name
 *   2. 8 main navigation links with active page highlighting
 *   3. Role-based admin/staff links
 *   4. User profile dropdown with avatar/initials, navigation, and logout
 *   5. Guest authentication links (Login/Sign Up)
 *   6. CSRF token injection for JavaScript/AJAX requests
 *   7. Mobile hamburger menu toggle
 *
 * Dependencies:
 *   - config/csrf.php (for CSRF token generation and csrf_field())
 *   - config/session.php (for session access)
 *
 * Used By:
 *   - index.php (homepage)
 *   - All pages/ files (public-facing pages)
 *   - NOT used by admin/ pages (they have their own header)
 *
 * Parent Files: Any page that includes this file must set $basePath
 * Child Files: None (no includes)
 *
 * Required Variables:
 *   - $basePath (string): Relative path to project root (e.g., '' for root, '../' for pages/)
 *
 * @package GlobeTrek\Includes
 */

// === SESSION INITIALIZATION ===
// Ensure a session is active before accessing $_SESSION variables
if (session_status() === PHP_SESSION_NONE) session_start();

// === CSRF TOKEN LOADING ===
// Load the CSRF helper for token generation
require_once __DIR__ . '/../config/csrf.php';

// === CSRF TOKEN INJECTION TO JAVASCRIPT ===
// Expose the CSRF token as a global JavaScript variable
// This allows AJAX requests to include the CSRF token in their headers
// The token is HTML-escaped to prevent XSS injection
?>
<script>var csrfToken = "<?php echo htmlspecialchars(getCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>";</script>
<?php
// === GOOGLE MATERIAL ICONS ===
// Load the Material Symbols Outlined icon font for navigation icons
?>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<?php
// === ACTIVE PAGE DETECTION ===
// Determine the current page name from the URL for active link highlighting
// basename() extracts the filename from the full path (e.g., 'packages.php')
// Then we normalize: 'index.php' → 'home', 'packages.php' → 'packages'
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPage = ($currentPage === 'index.php') ? 'home' : str_replace('.php', '', $currentPage);
?>
<!-- === NAVIGATION BAR === -->
<nav class="navbar">
    <!-- === LOGO & BRAND === -->
    <!-- Clickable logo linking to the homepage with anchor -->
    <a class="logo" href="<?php echo $basePath; ?>index.php#home">
        <img src="<?php echo $basePath; ?>images/logo.png" alt="GlobeTrek Adventures logo" />
        <div class="logo-text">
            <span class="brand-name">GlobeTrek</span>
            <span class="brand-tagline">Explore. Experience. Remember.</span>
        </div>
    </a>

    <!-- === MOBILE MENU TOGGLE === -->
    <!-- Hamburger button for responsive mobile navigation -->
    <!-- ARIA attributes for accessibility -->
    <button class="menu-toggle" aria-label="Open navigation" aria-expanded="false">
        &#9776; <!-- Unicode hamburger menu icon -->
    </button>

    <!-- === NAVIGATION LINKS === -->
    <ul class="nav-links">
        <!-- Main navigation items with active state highlighting -->
        <!-- Each link gets class="active" when its page matches $currentPage -->
        <li<?php if ($currentPage === 'home') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>index.php#home">Home</a></li>
        <li<?php if ($currentPage === 'destinations') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/destinations.php">Destinations</a></li>
        <li<?php if ($currentPage === 'packages') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/packages.php">Packages</a></li>
        <li<?php if ($currentPage === 'guides') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/guides.php">Guides</a></li>
        <li<?php if ($currentPage === 'accommodations') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/accommodations.php">Accommodations</a></li>
        <li<?php if ($currentPage === 'transportation') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/transportation.php">Transport</a></li>
        <li<?php if ($currentPage === 'about') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/about.php">About Us</a></li>
        <li<?php if ($currentPage === 'contact') echo ' class="active"'; ?>><a href="<?php echo $basePath; ?>pages/contact.php">Contact Us</a></li>

        <!-- === PHONE NUMBER DISPLAY === -->
        <li class="nav-phone">
            <span class="material-symbols-outlined">call</span>
            <span>+94 77 123 4567</span>
        </li>

        <?php
        // === AUTHENTICATED USER SECTION ===
        // Show admin/staff links and profile dropdown for logged-in users
        ?>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php
            // === ROLE-BASED ADMIN/STAFF LINKS ===
            // Admins see "Admin" link → admin/index.php
            // Staff see "Staff" link → admin/staff-dashboard.php
            // Regular users see no admin link
            ?>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <li><a href="<?php echo $basePath; ?>admin/index.php">Admin</a></li>
            <?php elseif (($_SESSION['user_role'] ?? '') === 'staff'): ?>
                <li><a href="<?php echo $basePath; ?>admin/staff-dashboard.php">Staff</a></li>
            <?php endif; ?>
            <li>
                <?php
                // === USER DATA PREPARATION ===
                // Extract user info from session and escape for XSS safety

                // Get the user's full name (escaped for HTML)
                $udName = htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8');

                // === INITIALS GENERATION ALGORITHM ===
                // Split the name by spaces, take the first character of each word,
                // convert to uppercase, and concatenate (e.g., "John Doe" → "JD")
                $udWords = explode(' ', $udName);
                $udInitials = '';
                foreach ($udWords as $udW) {
                    if ($udW !== '') $udInitials .= mb_strtoupper(mb_substr($udW, 0, 1));
                }
                // Fallback to 'U' if no initials could be generated
                if ($udInitials === '') $udInitials = 'U';

                // Get user email and profile photo (escaped for HTML)
                $udEmail = htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES, 'UTF-8');
                $udPhoto = htmlspecialchars($_SESSION['user_profile_photo'] ?? '', ENT_QUOTES, 'UTF-8');
                ?>

                <!-- === PROFILE TRIGGER (Avatar Button) === -->
                <!-- Clickable avatar that opens the profile dropdown -->
                <!-- ARIA attributes for keyboard accessibility -->
                <div class="user-profile-trigger" id="profileTrigger" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-label="User menu">
                    <div class="user-avatar">
                        <?php if ($udPhoto): ?>
                            <!-- Show profile photo if available -->
                            <img src="<?php echo $basePath . $udPhoto; ?>" alt="Profile photo">
                        <?php else: ?>
                            <!-- Show initials if no profile photo -->
                            <?php echo $udInitials; ?>
                        <?php endif; ?>
                    </div>
                    <!-- Chevron icon rotates when dropdown opens -->
                    <span class="material-symbols-outlined trigger-chevron">expand_more</span>

                    <!-- === PROFILE DROPDOWN MENU === -->
                    <!-- Hidden by default, shown on click/keyboard activation -->
                    <div class="profile-dropdown" id="profileDropdown" role="menu">
                        <!-- User info header with avatar and details -->
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

                        <!-- === DROPDOWN NAVIGATION LINKS === -->
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
                            <a class="dropdown-nav-item" href="<?php echo $basePath; ?>pages/my-reviews.php" role="menuitem">
                                <span class="material-symbols-outlined">rate_review</span>
                                My Reviews
                            </a>
                            <a class="dropdown-nav-item" href="<?php echo $basePath; ?>pages/settings.php" role="menuitem">
                                <span class="material-symbols-outlined">settings</span>
                                Settings
                            </a>
                        </nav>

                        <!-- Divider line -->
                        <div class="dropdown-divider"></div>

                        <!-- Logout button (styled in red) -->
                        <a class="dropdown-logout" href="<?php echo $basePath; ?>pages/logout.php" role="menuitem">
                            <span class="material-symbols-outlined">logout</span>
                            Log Out
                        </a>
                    </div>
                </div>
            </li>
        <?php else: ?>
            <!-- === GUEST AUTHENTICATION LINKS === -->
            <!-- Show Login and Sign Up buttons for non-authenticated visitors -->
            <li><a class="login-btn" href="<?php echo $basePath; ?>pages/login.php">Log In</a></li>
            <li><a class="signup-btn" href="<?php echo $basePath; ?>pages/signup.php">Sign Up</a></li>
        <?php endif; ?>
    </ul>
</nav>
