GlobeTrek Adventures - Project Setup Guide
==========================================

A full-stack PHP web application for a Sri Lankan travel agency featuring
tour packages, destinations, guides, accommodations, transportation,
bookings, payments, and an admin management panel.


1. PREREQUISITES
----------------

   - XAMPP (Apache + MySQL + PHP 8.x) - https://www.apachefriends.org
   - Composer (optional, dependencies are pre-installed) - https://getcomposer.org


2. INSTALLATION STEPS
---------------------

   Step 1: Place Project
   ---------------------
   - Copy the "GlobeTrek-Adeventures" folder into C:\xampp\htdocs\

   Step 2: Start Services
   ----------------------
   - Open XAMPP Control Panel
   - Click "Start" next to Apache
   - Click "Start" next to MySQL

   Step 3: Create Database
   -----------------------
   - Open your browser and go to: http://localhost/phpmyadmin
   - Click "New" on the left sidebar
   - Under "Database name", type: globetrek
   - Click "Create"

   Step 4: Import Database
   -----------------------
   - After creating the database, you will be inside it
   - Click the "Import" tab at the top
   - Click "Choose File" and select: database/init.sql
   - Scroll down and click "Go"

   Step 5: Access the Site
   -----------------------
   - Homepage:    http://localhost/GlobeTrek-Adeventures/
   - Admin Panel: http://localhost/GlobeTrek-Adeventures/admin/


3. DEFAULT CREDENTIALS
----------------------

   - No admin account is pre-seeded in the database
   - Register a new account via the signup page at:
     http://localhost/GlobeTrek-Adeventures/pages/signup.php
   - The init.sql includes sample data (packages, destinations,
     guides, accommodations, transportation, testimonials, reviews,
     and inquiries)


4. OPTIONAL: EMAIL CONFIGURATION
--------------------------------

   - Edit config/mail.php with your Gmail address and App Password
   - To generate an App Password:
     1. Enable 2-Step Verification on your Google account
     2. Go to: https://myaccount.google.com/apppasswords
     3. Generate an app password for "Mail"
     4. Use that 16-character password in config/mail.php


5. TROUBLESHOOTING
------------------

   Problem: "Database connection failed" or connection error
   Solution: Make sure MySQL is running in XAMPP and the database
             is named exactly "globetrek"

   Problem: Blank page or PHP errors
   Solution: Ensure PHP version is 8.x (check in XAMPP dashboard)

   Problem: 404 errors on pages
   Solution: Make sure Apache mod_rewrite module is enabled
             (XAMPP > Apache > Config > httpd.conf > uncomment
             LoadModule rewrite_module)

   Problem: composer install fails
   Solution: The vendor/ folder is already included. If missing,
             run: composer install in the project root folder
