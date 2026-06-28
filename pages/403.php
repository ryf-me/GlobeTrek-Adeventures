<?php
/**
 * File: pages/403.php
 * Purpose: Custom 403 error page displayed when access is forbidden
 * Dependencies: Google Fonts (Fraunces, Manrope), Material Symbols Icons
 * Used By: Web server configuration (Apache/Nginx) for 403 error handling
 * Parent Files: None (standalone error page)
 * @package GlobeTrek\Pages
 */

http_response_code(403);
$basePath = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied — Globe Trek Adventures</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0B6E4F;
            --primary-light: #14A76C;
            --dark: #1A1A2E;
            --text: #3A3A4A;
            --bg: #F8FAF9;
            --white: #FFFFFF;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Manrope', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }
        .error-code {
            font-family: 'Fraunces', serif;
            font-size: 8rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
            opacity: 0.3;
        }
        .error-title {
            font-family: 'Fraunces', serif;
            font-size: 2rem;
            font-weight: 600;
            color: var(--dark);
            margin: 1rem 0 0.5rem;
        }
        .error-message {
            font-size: 1.1rem;
            color: var(--text);
            max-width: 500px;
            margin-bottom: 2rem;
        }
        .error-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 2rem;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: background 0.3s;
        }
        .error-btn:hover { background: var(--primary-light); }
    </style>
</head>
<body>
    <div class="error-code">403</div>
    <h1 class="error-title">Access Denied</h1>
    <p class="error-message">You don't have permission to access this page. Please check your credentials or contact support.</p>
    <a href="<?php echo $basePath; ?>index.php" class="error-btn">
        <span class="material-symbols-outlined">home</span>
        Back to Home
    </a>
</body>
</html>
