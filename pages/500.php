<?php
/**
 * File: pages/500.php
 * Purpose: Custom 500 error page displayed when a server error occurs
 * Dependencies: Google Fonts (Fraunces, Manrope), Material Symbols Icons
 * Used By: Web server configuration (Apache/Nginx) for 500 error handling
 * Parent Files: None (standalone error page)
 * Child Files: None
 * @package GlobeTrek\Pages
 */

// Set HTTP response code to 500 (Internal Server Error)
http_response_code(500);
$basePath = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error — Globe Trek Adventures</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- === INLINE STYLES (Self-contained error page) === -->
    <style>
        /* CSS Variables for consistent theming */
        :root {
            --primary: #0B6E4F;
            --primary-light: #14A76C;
            --dark: #1A1A2E;
            --text: #3A3A4A;
            --bg: #F8FAF9;
            --white: #FFFFFF;
        }
        
        /* Reset and base styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        /* Body layout - centered content */
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
        
        /* Large 500 error code display */
        .error-code {
            font-family: 'Fraunces', serif;
            font-size: 8rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
            opacity: 0.3;
        }
        
        /* Error page title */
        .error-title {
            font-family: 'Fraunces', serif;
            font-size: 2rem;
            font-weight: 600;
            color: var(--dark);
            margin: 1rem 0 0.5rem;
        }
        
        /* Error description message */
        .error-message {
            font-size: 1.1rem;
            color: var(--text);
            max-width: 500px;
            margin-bottom: 2rem;
        }
        
        /* Back to Home button */
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
        
        /* Button hover state */
        .error-btn:hover { background: var(--primary-light); }
    </style>
</head>
<body>
    <!-- Error Code Display -->
    <div class="error-code">500</div>
    
    <!-- Error Title -->
    <h1 class="error-title">Server Error</h1>
    
    <!-- Error Description -->
    <p class="error-message">Something went wrong on our end. Please try again later or contact support if the issue persists.</p>
    
    <!-- Back to Home Button -->
    <a href="<?php echo $basePath; ?>index.php" class="error-btn">
        <span class="material-symbols-outlined">home</span>
        Back to Home
    </a>
</body>
</html>