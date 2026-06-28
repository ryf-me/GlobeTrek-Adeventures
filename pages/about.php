<?php
/**
 * File: pages/about.php
 * Purpose: About us page showcasing company story, team, values, mission, and timeline
 * Dependencies: css/style.css, css/navbar.css, css/about.css, css/footer.css, includes/navbar.php, includes/footer.php, js/script.js
 * Used By: Main website navigation, footer links
 * Parent Files: index.php (via navigation), navbar.php (via link)
 * Child Files: None
 * @package GlobeTrek\Pages
 */

session_start();
$basePath = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - GlobeTrek</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/about.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="about-page">
    <!-- Navigation Bar -->
    <?php include '../includes/navbar.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="ab-hero" aria-labelledby="ab-hero-title">
            <img class="ab-hero-bg" src="https://images.pexels.com/photos/29644514/pexels-photo-29644514.jpeg" alt="">
            <div class="ab-hero-content">
                <!-- Breadcrumb Navigation -->
                <nav class="ab-breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo $basePath; ?>index.php">Home</a>
                    <span class="ab-breadcrumb-sep material-symbols-outlined">chevron_right</span>
                    <span class="ab-breadcrumb-current">About Us</span>
                </nav>
                <h1 id="ab-hero-title">About GlobeTrek</h1>
                <p class="ab-hero-subtitle">Your trusted travel partner in Sri Lanka</p>
                <p class="ab-hero-desc">We are a team of passionate travelers and local experts dedicated to showcasing the beauty, culture, and adventure of Sri Lanka to the world.</p>
                <!-- Watch Our Story Button (placeholder) -->
                <a class="ab-watch-btn" href="#" onclick="return false;">
                    <div class="ab-watch-icon">
                        <span class="material-symbols-outlined">play_arrow</span>
                    </div>
                    <div class="ab-watch-text">
                        <strong>Watch Our Story</strong>
                        <span>2:15 min</span>
                    </div>
                </a>
            </div>
        </section>

        <!-- Our Story Section -->
        <section class="ab-story-section" aria-labelledby="ab-story-title">
            <div class="ab-story-content">
                <p class="ab-story-label">Our Story</p>
                <h2 id="ab-story-title" class="ab-story-title">The Journey Behind GlobeTrek</h2>
                <p class="ab-story-text">GlobeTrek was born out of a deep love for travel and a passion for Sri Lanka. What started as a small team of travel enthusiasts has grown into a trusted travel company that has helped thousands of travelers discover the true essence of our island.</p>
                <p class="ab-story-text">We believe that travel is more than visiting places – it's about creating unforgettable memories, connecting with people, and experiencing life in a new way.</p>
                <a href="packages.php" class="ab-discover-btn">
                    Discover Our Tours
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </div>

            <!-- Video Thumbnail Section -->
            <div class="ab-video-section">
                <div class="ab-video-thumb" role="button" tabindex="0" aria-label="Play our story video">
                    <img src="https://images.pexels.com/photos/2187605/pexels-photo-2187605.jpeg" alt="GlobeTrek team exploring Sri Lanka">
                    <div class="ab-video-play">
                        <span class="material-symbols-outlined">play_arrow</span>
                    </div>
                </div>
            </div>

            <!-- Statistics Sidebar -->
            <div class="ab-stats-sidebar">
                <div class="ab-stat-card">
                    <div class="ab-stat-icon">
                        <span class="material-symbols-outlined">groups</span>
                    </div>
                    <div>
                        <div class="ab-stat-number">10,000+</div>
                        <div class="ab-stat-label">Happy Travelers</div>
                    </div>
                </div>
                <div class="ab-stat-card">
                    <div class="ab-stat-icon">
                        <span class="material-symbols-outlined">luggage</span>
                    </div>
                    <div>
                        <div class="ab-stat-number">150+</div>
                        <div class="ab-stat-label">Tour Packages</div>
                    </div>
                </div>
                <div class="ab-stat-card">
                    <div class="ab-stat-icon">
                        <span class="material-symbols-outlined">location_on</span>
                    </div>
                    <div>
                        <div class="ab-stat-number">50+</div>
                        <div class="ab-stat-label">Destinations</div>
                    </div>
                </div>
                <div class="ab-stat-card">
                    <div class="ab-stat-icon">
                        <span class="material-symbols-outlined">person</span>
                    </div>
                    <div>
                        <div class="ab-stat-number">20+</div>
                        <div class="ab-stat-label">Expert Guides</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Values Section — What We Stand For -->
        <section class="ab-values-section" aria-labelledby="ab-values-title">
            <div class="ab-values-inner">
                <h2 id="ab-values-title" class="ab-section-title">What We Stand For</h2>
                <div class="ab-section-underline"></div>
                <div class="ab-values-grid">
                    <div class="ab-value-card">
                        <div class="ab-value-icon">
                            <span class="material-symbols-outlined">favorite</span>
                        </div>
                        <h3>Authentic Experiences</h3>
                        <p>We create genuine experiences that connect you with the true soul of Sri Lanka.</p>
                    </div>
                    <div class="ab-value-card">
                        <div class="ab-value-icon">
                            <span class="material-symbols-outlined">verified_user</span>
                        </div>
                        <h3>Trust &amp; Safety</h3>
                        <p>Your safety and peace of mind are our top priorities at every step of your journey.</p>
                    </div>
                    <div class="ab-value-card">
                        <div class="ab-value-icon">
                            <span class="material-symbols-outlined">star</span>
                        </div>
                        <h3>Quality Service</h3>
                        <p>We go the extra mile to deliver personalized service and unforgettable moments.</p>
                    </div>
                    <div class="ab-value-card">
                        <div class="ab-value-icon">
                            <span class="material-symbols-outlined">eco</span>
                        </div>
                        <h3>Sustainable Travel</h3>
                        <p>We promote responsible tourism that protects our environment and supports local communities.</p>
                    </div>
                    <div class="ab-value-card">
                        <div class="ab-value-icon">
                            <span class="material-symbols-outlined">handshake</span>
                        </div>
                        <h3>Travel with Heart</h3>
                        <p>We treat every traveler like family and every journey like our own.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team Section — Meet Our Team -->
        <section class="ab-team-section" aria-labelledby="ab-team-title">
            <h2 id="ab-team-title" class="ab-section-title">Meet Our Team</h2>
            <div class="ab-section-underline"></div>
            <div class="ab-team-grid">
                <!-- Team Member 1 -->
                <div class="ab-team-card">
                    <img class="ab-team-photo" src="https://images.pexels.com/photos/2379004/pexels-photo-2379004.jpeg" alt="Kasun Bandara">
                    <h3 class="ab-team-name">Kasun Bandara</h3>
                    <p class="ab-team-role">Founder &amp; CEO</p>
                    <p class="ab-team-bio">Travel enthusiast with 15+ years of experience in the tourism industry.</p>
                    <div class="ab-team-socials">
                        <a href="#" class="ab-team-social" aria-label="Facebook" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="ab-team-social" aria-label="LinkedIn" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="#" class="ab-team-social" aria-label="Instagram" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                    </div>
                </div>
                <!-- Team Member 2 -->
                <div class="ab-team-card">
                    <img class="ab-team-photo" src="https://images.pexels.com/photos/3769021/pexels-photo-3769021.jpeg" alt="Nipuni Silva">
                    <h3 class="ab-team-name">Nipuni Silva</h3>
                    <p class="ab-team-role">Operations Manager</p>
                    <p class="ab-team-bio">Expert in travel planning and operations with a passion for detail.</p>
                    <div class="ab-team-socials">
                        <a href="#" class="ab-team-social" aria-label="Facebook" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="ab-team-social" aria-label="LinkedIn" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="#" class="ab-team-social" aria-label="Instagram" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                    </div>
                </div>
                <!-- Team Member 3 -->
                <div class="ab-team-card">
                    <img class="ab-team-photo" src="https://images.pexels.com/photos/2182970/pexels-photo-2182970.jpeg" alt="Tharindu Perera">
                    <h3 class="ab-team-name">Tharindu Perera</h3>
                    <p class="ab-team-role">Head Tour Guide</p>
                    <p class="ab-team-bio">Wildlife expert and certified guide with deep knowledge of Sri Lanka.</p>
                    <div class="ab-team-socials">
                        <a href="#" class="ab-team-social" aria-label="Facebook" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="ab-team-social" aria-label="LinkedIn" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="#" class="ab-team-social" aria-label="Instagram" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                    </div>
                </div>
                <!-- Team Member 4 -->
                <div class="ab-team-card">
                    <img class="ab-team-photo" src="https://images.pexels.com/photos/1222271/pexels-photo-1222271.jpeg" alt="Chamara Wijesinghe">
                    <h3 class="ab-team-name">Chamara Wijesinghe</h3>
                    <p class="ab-team-role">Travel Consultant</p>
                    <p class="ab-team-bio">Helping travelers find the perfect experiences tailored to their needs.</p>
                    <div class="ab-team-socials">
                        <a href="#" class="ab-team-social" aria-label="Facebook" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="ab-team-social" aria-label="LinkedIn" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="#" class="ab-team-social" aria-label="Instagram" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                    </div>
                </div>
                <!-- Team Member 5 -->
                <div class="ab-team-card">
                    <img class="ab-team-photo" src="https://images.pexels.com/photos/1181519/pexels-photo-1181519.jpeg" alt="Dilini Kottegoda">
                    <h3 class="ab-team-name">Dilini Kottegoda</h3>
                    <p class="ab-team-role">Customer Support Lead</p>
                    <p class="ab-team-bio">Ensuring every traveler has a smooth and memorable experience.</p>
                    <div class="ab-team-socials">
                        <a href="#" class="ab-team-social" aria-label="Facebook" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="ab-team-social" aria-label="LinkedIn" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="#" class="ab-team-social" aria-label="Instagram" onclick="return false;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission & Vision Section -->
        <section class="ab-mission-vision-section" aria-label="Our Mission and Vision">
            <div class="ab-mv-card">
                <img class="ab-mv-card-bg" src="https://images.pexels.com/photos/15286/pexels-photo.jpg" alt="">
                <div class="ab-mv-card-content">
                    <div class="ab-mv-icon">
                        <span class="material-symbols-outlined">flag</span>
                    </div>
                    <h3>Our Mission</h3>
                    <p>To inspire and empower travelers to explore Sri Lanka responsibly by providing authentic, safe, and memorable travel experiences.</p>
                </div>
            </div>
            <div class="ab-mv-card">
                <img class="ab-mv-card-bg" src="https://images.pexels.com/photos/2166559/pexels-photo-2166559.jpeg" alt="">
                <div class="ab-mv-card-content">
                    <div class="ab-mv-icon">
                        <span class="material-symbols-outlined">visibility</span>
                    </div>
                    <h3>Our Vision</h3>
                    <p>To be the most trusted and preferred travel company in Sri Lanka, recognized for excellence, innovation, and sustainability.</p>
                </div>
            </div>
        </section>

        <!-- Timeline Section — Our Journey So Far -->
        <section class="ab-timeline-section" aria-labelledby="ab-timeline-title">
            <div class="ab-timeline-inner">
                <h2 id="ab-timeline-title" class="ab-section-title">Our Journey So Far</h2>
                <div class="ab-section-underline"></div>
                <div class="ab-timeline-track">
                    <div class="ab-timeline-item">
                        <div class="ab-timeline-dot">
                            <span class="material-symbols-outlined">rocket_launch</span>
                        </div>
                        <div class="ab-timeline-year">2018</div>
                        <p class="ab-timeline-desc">GlobeTrek was founded with a small team and big dreams.</p>
                    </div>
                    <div class="ab-timeline-item">
                        <div class="ab-timeline-dot">
                            <span class="material-symbols-outlined">luggage</span>
                        </div>
                        <div class="ab-timeline-year">2019</div>
                        <p class="ab-timeline-desc">Launched our first tour packages and served 1000+ travelers.</p>
                    </div>
                    <div class="ab-timeline-item">
                        <div class="ab-timeline-dot">
                            <span class="material-symbols-outlined">explore</span>
                        </div>
                        <div class="ab-timeline-year">2021</div>
                        <p class="ab-timeline-desc">Expanded our team and destinations across Sri Lanka.</p>
                    </div>
                    <div class="ab-timeline-item">
                        <div class="ab-timeline-dot">
                            <span class="material-symbols-outlined">public</span>
                        </div>
                        <div class="ab-timeline-year">2023</div>
                        <p class="ab-timeline-desc">Reached 10,000+ happy travelers from around the world.</p>
                    </div>
                    <div class="ab-timeline-item">
                        <div class="ab-timeline-dot">
                            <span class="material-symbols-outlined">favorite</span>
                        </div>
                        <div class="ab-timeline-year">2024+</div>
                        <p class="ab-timeline-desc">Continuing our journey to create unforgettable experiences for all.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Banner Section -->
        <section class="ab-cta-section" aria-label="Plan your adventure">
            <img class="ab-cta-bg" src="https://images.pexels.com/photos/2387793/pexels-photo-2387793.jpeg" alt="">
            <div class="ab-cta-inner">
                <div class="ab-cta-text">
                    <h2>Let's Create Your Next Adventure</h2>
                    <p>Whether it's a relaxing getaway or an adventurous expedition, we're here to make it unforgettable.</p>
                </div>
                <a href="custom-trips.php" class="ab-cta-btn">
                    Plan My Custom Trip
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="../js/script.js"></script>
</body>
</html>