<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$basePath = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile Dropdown - GlobeTrek</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Winky+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <style>
        .material-symbols-outlined {
            font-size: 20px;
            vertical-align: middle;
        }

        /* User Profile Dropdown Trigger */
        .user-profile-trigger {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0.35rem 0.6rem;
            border-radius: 4px;
            transition: background 0.2s ease;
        }

        .user-profile-trigger:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #1a1c1c;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
            flex-shrink: 0;
            user-select: none;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-name-label {
            color: #1a1c1c;
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.2;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .trigger-chevron {
            font-size: 18px;
            color: #747878;
            transition: transform 0.25s ease;
        }

        .user-profile-trigger[aria-expanded="true"] .trigger-chevron {
            transform: rotate(180deg);
        }

        /* Dropdown Panel */
        .profile-dropdown {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            width: 280px;
            background: #fff;
            border: 1px solid #c4c7c7;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            z-index: 200;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
        }

        .profile-dropdown.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-user-info {
            padding: 1rem 1rem 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid #e2e2e2;
        }

        .dropdown-user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #1a1c1c;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .dropdown-user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .dropdown-user-details {
            min-width: 0;
        }

        .dropdown-user-name {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: #1a1c1c;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dropdown-user-email {
            margin: 0;
            font-size: 0.8rem;
            color: #5e5e5e;
            line-height: 1.35;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dropdown-nav {
            padding: 0.35rem 0;
        }

        .dropdown-nav-item {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.6rem 1rem;
            color: #1a1c1c;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            line-height: 1.3;
            transition: background 0.15s ease;
        }

        .dropdown-nav-item:hover {
            background: #f5f7fa;
        }

        .dropdown-nav-item:focus-visible {
            outline: 2px solid #000;
            outline-offset: -2px;
        }

        .dropdown-nav-item .material-symbols-outlined {
            font-size: 20px;
            color: #5e5e5e;
        }

        .dropdown-divider {
            height: 1px;
            background: #e2e2e2;
            margin: 0.25rem 0;
        }

        .dropdown-logout {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.6rem 1rem;
            color: #ba1a1a;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.3;
            transition: background 0.15s ease;
        }

        .dropdown-logout:hover {
            background: rgba(186, 26, 26, 0.05);
        }

        .dropdown-logout:focus-visible {
            outline: 2px solid #ba1a1a;
            outline-offset: -2px;
        }

        .dropdown-logout .material-symbols-outlined {
            font-size: 20px;
        }

        /* Demo Content */
        .demo-section {
            max-width: 800px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }

        .demo-section h1 {
            font-family: 'Fraunces', Georgia, serif;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 700;
            color: #111;
            margin-bottom: 0.5rem;
        }

        .demo-section .subtitle {
            color: #5e5e5e;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }

        .demo-instruction {
            background: #f5f7fa;
            border: 1px solid #c4c7c7;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .demo-instruction h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #111;
            margin: 0 0 0.75rem;
            text-align: left;
        }

        .demo-instruction ol {
            margin: 0;
            padding-left: 1.25rem;
            color: #333;
            font-size: 0.95rem;
            line-height: 1.8;
        }

        .demo-instruction code {
            background: #e8e8e8;
            padding: 0.15rem 0.4rem;
            font-size: 0.85rem;
            font-family: monospace;
        }

        .demo-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .demo-feature-card {
            border: 1px solid #d9d9d9;
            padding: 1.25rem;
            background: #fff;
        }

        .demo-feature-card h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #111;
            margin: 0 0 0.4rem;
        }

        .demo-feature-card p {
            margin: 0;
            color: #5e5e5e;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .demo-feature-card .material-symbols-outlined {
            font-size: 24px;
            color: #111;
            margin-bottom: 0.5rem;
            display: block;
        }

        @media (max-width: 768px) {
            .user-name-label {
                display: none;
            }

            .profile-dropdown {
                width: 260px;
                right: -0.5rem;
            }

            .demo-section {
                padding: 2.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <!-- Demo Content -->
    <main>
        <section class="demo-section">
            <h1>User Profile Dropdown</h1>
            <p class="subtitle">A dropdown menu component for the navigation bar. Click the user avatar in the top-right corner to toggle it.</p>

            <div class="demo-instruction">
                <h2>How to use</h2>
                <ol>
                    <li>Click the user avatar in the navbar to open the dropdown</li>
                    <li>Click outside the dropdown or press <code>Escape</code> to close it</li>
                    <li>On mobile, the user name hides and only the avatar circle is shown</li>
                </ol>
            </div>

            <div class="demo-features">
                <div class="demo-feature-card">
                    <span class="material-symbols-outlined">touch_app</span>
                    <h3>Click to Toggle</h3>
                    <p>Click the avatar or name to open/close the dropdown menu.</p>
                </div>
                <div class="demo-feature-card">
                    <span class="material-symbols-outlined">keyboard</span>
                    <h3>Keyboard Accessible</h3>
                    <p>Press Enter or Space to toggle. Escape closes the menu.</p>
                </div>
                <div class="demo-feature-card">
                    <span class="material-symbols-outlined">smartphone</span>
                    <h3>Responsive</h3>
                    <p>On smaller screens, the name hides and only the avatar is shown.</p>
                </div>
                <div class="demo-feature-card">
                    <span class="material-symbols-outlined">lock</span>
                    <h3>Click Outside to Close</h3>
                    <p>Clicking anywhere outside the dropdown automatically closes it.</p>
                </div>
            </div>
        </section>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
</body>
</html>
