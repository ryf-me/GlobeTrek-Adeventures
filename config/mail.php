<?php
/**
 * Mail Configuration — Gmail SMTP
 *
 * To use Gmail SMTP, you need a Google App Password:
 * 1. Enable 2-Step Verification on your Google account
 * 2. Go to https://myaccount.google.com/apppasswords
 * 3. Generate an app password for "Mail"
 * 4. Use that 16-character password below (not your regular Gmail password)
 */

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'insathraifyk3@gmail.com');        // Your Gmail address
define('MAIL_PASSWORD', 'yuxdxdanxuwwkkjk');       // 16-char Google App Password
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_FROM_ADDRESS', 'insathraifyk3@gmail.com');
define('MAIL_FROM_NAME', 'GlobeTrek Adventures');
