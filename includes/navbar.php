<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
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
            <li><span class="user-greeting">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span></li>
            <li><a class="login-btn" href="<?php echo $basePath; ?>pages/logout.php">Logout</a></li>
        <?php else: ?>
            <li><a class="login-btn" href="<?php echo $basePath; ?>pages/login.php">Login</a></li>
            <li><a class="signup-btn" href="<?php echo $basePath; ?>pages/signup.php">Sign Up</a></li>
        <?php endif; ?>
    </ul>
</nav>
