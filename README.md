# GlobeTrek Adventures

A full-stack PHP web application for a Sri Lankan travel agency - featuring tour packages, destinations, guides, accommodations, transportation, bookings, payments, and a complete admin management panel.

---

## Table of Contents

- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Architecture Overview](#architecture-overview)
- [Data Flow Diagrams](#data-flow-diagrams)
- [Database Schema](#database-schema)
- [Authentication and Security](#authentication-and-security)
- [Configuration Files](#configuration-files)
- [Frontend CSS Files](#frontend-css-files)
- [Frontend JavaScript Files](#frontend-javascript-files)
- [Every PHP File](#every-php-file)
- [Admin Panel](#admin-panel)
- [Setup Instructions](#setup-instructions)
- [Dependencies](#dependencies)

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.x (vanilla, no framework) |
| Database | MySQL/MariaDB via PDO |
| Server | Apache (XAMPP) |
| Frontend | Vanilla HTML5, CSS3, JavaScript (ES6+) |
| Email | PHPMailer 6.9 via Gmail SMTP |
| PDF Export | Dompdf 3.1 |
| Excel Export | PhpSpreadsheet 1.29 |
| Icons | Google Material Symbols |
| Charts | Chart.js (admin dashboard) |
| Date Picker | Flatpickr |
| Fonts | Fraunces, Manrope, Hanken Grotesk |
| Currency | Sri Lankan Rupee (LKR / Rs.) |
---

## Project Structure

GlobeTrek-Adeventures/
  index.php                          Homepage entry point
  .htaccess                          Apache mod_rewrite + error docs
  composer.json                      PHP dependencies

  config/                            Application configuration (9 files)
    database.php                     PDO singleton connection + BASE_URL
    session.php                      Secure session config + 30-min idle timeout
    csrf.php                         CSRF token generation and validation
    rate-limiter.php                 IP and session-based rate limiting
    otp.php                          OTP generation, storage, verification
    mail.php                         Gmail SMTP configuration
    currency.php                     LKR currency formatting
    logger.php                       Activity logging to DB
    error-handler.php                Global error/exception/shutdown handler

  includes/                          Shared frontend components (6 files)
    navbar.php                       Navigation bar with profile dropdown
    footer.php                       Site footer with links and socials
    user-sidebar.php                 User dashboard sidebar navigation
    mailer.php                       PHPMailer wrapper + email template
    notifications.php                Automated email notification functions
    review-modal.php                 Reusable review submission modal

  pages/                             Public-facing pages (43 files)
    login.php                        User login with remember-me
    signup.php                       Registration with email verification
    logout.php                       Session + token cleanup
    forgot-password.php              Password reset request
    reset-password.php               Password reset with token
    verify-email.php                 Email verification handler
    resend-verification.php          Resend verification email
    packages.php                     Tour packages listing
    package-details.php              Single package view
    ajax-packages.php                AJAX package filtering endpoint
    destinations.php                 Destinations listing
    destination-details.php          Single destination view
    guides.php                       Guides listing
    guide-details.php                Single guide view
    accommodations.php               Accommodations listing
    transportation.php               Transportation listing
    booking.php                      Booking form
    booking-detail.php               Booking detail view
    cancel-booking.php               Cancel a booking
    payment.php                      Payment processing
    my-bookings.php                  User bookings list
    user-profile.php                 User profile
    settings.php                     User account settings
    wishlist.php                     User wishlist
    my-reviews.php                   User reviews list
    inquiries.php                    User inquiry management
    contact.php                      Contact form
    custom-trips.php                 Custom trip request form
    about.php, faq.php, terms.php, privacy.php, etc.

  admin/                             Admin panel (39 files)
    index.php                        Admin dashboard with KPIs and Chart.js
    includes/header.php              Auth guard + permission system
    includes/sidebar.php             Admin sidebar navigation
    includes/footer.php              Admin footer (loads admin.js)
    (30+ management pages)

  css/                               Stylesheets (30 files)
  js/                                JavaScript (3 files)
  images/                            Static image assets
  database/                          SQL migration and seed files (12 files)
  vendor/                            Composer dependencies
---

## Architecture Overview

### Routing

This project uses page-based routing - each URL maps directly to a PHP file:

- / maps to index.php
- /pages/packages.php maps to pages/packages.php
- /pages/login.php maps to pages/login.php
- /admin/index.php maps to admin/index.php

There is no routing library or REST API. All endpoints are PHP files accessed via Apache.

### Request Lifecycle

1. Browser sends request
2. Apache (.htaccess) processes it
3. PHP file loads (e.g., pages/packages.php)
4. Config files load: config/database.php (PDO), config/csrf.php, config/session.php, config/rate-limiter.php
5. Shared includes load: includes/navbar.php, includes/footer.php
6. SQL queries execute via PDO
7. HTML response renders with CSS + JS

### Database Access Pattern

All database access uses the PDO singleton pattern via getDB():

- Single connection per request, returned on every call
- Prepared statements with real parameter binding (EMULATE_PREPARES = false)
- UTF8MB4 charset for full Unicode support

### Include Pattern

Pages compose themselves from shared components. Example for pages/packages.php:

- Includes config/database.php
- Includes config/currency.php
- Includes includes/navbar.php (which includes config/csrf.php)
- Includes includes/footer.php
- Loads css/style.css, css/navbar.css, css/footer.css, css/packages.css
- Loads js/script.js
---

## Data Flow Diagrams

### 1. User Registration Flow

1. User fills signup form at pages/signup.php
2. CSRF token validated via config/csrf.php
3. Rate limit checked (3 per hour per IP) via config/rate-limiter.php
4. Input validated: email format, password policy (min 8 chars, uppercase, number, special char), name required
5. Duplicate email checked in users table
6. User inserted into users table with bcrypt-hashed password
7. OTP generated via config/otp.php storeOTP() (hashed before storage, 10-min expiry)
8. OTP email sent via includes/mailer.php
9. User enters OTP on verification page
10. verifyOTP() validates and marks as used
11. users.email_verified set to 1
12. Redirect to pages/login.php

### 2. User Login Flow

1. User submits login form at pages/login.php
2. CSRF token validated
3. IP rate limit checked (5 per 15 min per IP)
4. Account lockout checked (5 failed attempts per 15 min per email)
5. User looked up in users table
6. If not found: recordLoginAttempt(), show error
7. If deactivated: show error
8. If email not verified: show error + resend link
9. If found and password_verify() passes:
   - session_regenerate_id(true) prevents session fixation
   - Session variables set: user_id, user_name, user_email, user_role
   - If "Remember Me": setRememberToken() stores SHA-256 hash in remember_tokens table + sets cookie
   - clearLoginAttempts() removes failed attempt records
   - Redirect to index.php

### 3. Booking and Payment Flow

1. User clicks "Book Now" on pages/package-details.php
2. Redirected to pages/booking.php (Step 2: Traveller Details)
3. CSRF validation + rate limiting
4. Input validated: name, email, phone, dates, travellers
5. Booking inserted into bookings table with status pending and auto-generated booking_reference
6. Redirected to pages/payment.php (Step 4: Payment)
7. CSRF validation + card detail validation
8. Simulated payment process generates transaction ID
9. Payment record inserted into payments table
10. Booking status updated to confirmed
11. sendBookingConfirmation() email sent via includes/notifications.php
12. sendPaymentReceipt() email sent via includes/notifications.php
13. Redirect to pages/booking-detail.php

### 4. Admin Inquiry Reply Flow

1. Admin opens admin/inquiries.php
2. Auth guard: admin/includes/header.php checks session for admin/staff role
3. Permission check: hasPermission('manage_inquiries') verified
4. Admin views inquiry threads (replies from inquiry_replies table)
5. Admin submits reply form
6. Reply inserted into inquiry_replies with sender_role = 'admin'
7. Inquiry status updated
8. sendInquiryReplyNotification() from includes/notifications.php sends email to user
9. logActivity() from config/logger.php records the action

### 5. Password Reset Flow

1. User clicks "Forgot Password" at pages/forgot-password.php
2. CSRF + rate limit (3 per hour) validated
3. Email checked against users table
4. If found: secure token generated via random_bytes(), stored in password_resets table with 30-min expiry
5. Reset link email sent via includes/mailer.php
6. User clicks link, lands on pages/reset-password.php?token=X
7. Token validated against password_resets table
8. New password hashed with bcrypt, users.password updated
9. Token deleted from password_resets
10. Redirect to pages/login.php
---

## Database Schema

### Tables Overview (20+ tables)

| Table | Purpose |
|-------|---------|
| users | User accounts (3 roles: user, staff, admin) |
| packages | Tour packages |
| destinations | Travel destinations |
| guides | Tour guides |
| bookings | User bookings |
| payments | Payment records |
| contact_messages | Contact form submissions |
| accommodations | Hotels/villas/resorts |
| transportation | Vehicle rentals |
| inquiries | Support inquiries |
| inquiry_replies | Inquiry conversation threads |
| custom_trip_requests | Custom tour requests |
| wishlist | User wishlists |
| newsletter_subscriptions | Newsletter subscribers |
| testimonials | User reviews (with approval) |
| guide_reviews | Guide-specific reviews |
| tags | Content tagging system |
| package_tags | Package-to-tag associations |
| destination_tags | Destination-to-tag associations |
| guide_tags | Guide-to-tag associations |
| activity_logs | System audit trail |
| email_verifications | Email verification tokens |
| password_resets | Password reset tokens |
| otps | One-time passwords |
| login_attempts | Failed login tracking |
| remember_tokens | Persistent login tokens |
| staff_profiles | Staff department/position info |
| staff_permissions | Granular staff permissions |
| staff_assignments | Staff-to-booking/inquiry assignments |

### ER Relationships

- users references: bookings, testimonials, guide_reviews, inquiries, wishlist, remember_tokens, staff_profiles, email_verifications, activity_logs
- bookings references: users, packages, payments
- packages has many-to-many with tags via package_tags
- destinations has many-to-many with tags via destination_tags
- guides has many-to-many with tags via guide_tags, has guide_reviews
- inquiries has inquiry_replies, references users, packages
- staff_profiles references users, has staff_permissions, staff_assignments

---

## Authentication and Security

### Authentication System

| Feature | Implementation |
|---------|---------------|
| Session-based auth |  variables: user_id, user_name, user_email, user_role |
| Three roles | user, staff, admin |
| Password hashing | password_hash() with PASSWORD_DEFAULT (bcrypt) |
| Email verification | Token-based via email_verifications table (24-hour expiry) |
| Remember Me | SHA-256 hashed tokens in remember_tokens table (30-day expiry, rotated on use) |
| Session fixation prevention | session_regenerate_id(true) on login |
| 30-min idle timeout | Enforced via last_activity in session (config/session.php) |

### Security Features

| Feature | File | Details |
|---------|------|---------|
| CSRF Protection | config/csrf.php | random_bytes(32) tokens, validated on all forms via hash_equals() |
| Rate Limiting | config/rate-limiter.php | IP-based (file storage) + session-based |
| Account Lockout | config/rate-limiter.php | Per-email lockout after 5 failed logins in 15 min |
| SQL Injection Prevention | config/database.php | PDO with EMULATE_PREPARES = false (real prepared statements) |
| XSS Prevention | All templates | htmlspecialchars() on all output |
| OTP System | config/otp.php | 6-digit OTPs hashed before storage, 10-min expiry, single-use |
| Error Handling | config/error-handler.php | Global handler, no stack traces shown to users |
| Activity Logging | config/logger.php | All significant actions logged to activity_logs table |
| Content Protection | js/script.js | Copy/cut actions blocked on frontend |
| Password Policy | pages/signup.php | Min 8 chars, 1 uppercase, 1 number, 1 special char |
| Secure Session Cookies | config/session.php | httponly, SameSite=Lax, strict_mode |

### Admin Permission System (RBAC)

Defined in admin/includes/header.php:

| Department | Default Permissions |
|-----------|-------------------|
| operations | manage_bookings, manage_packages, manage_accommodations, manage_transportation, manage_guides, manage_destinations |
| customer_service | manage_inquiries, manage_contacts, manage_custom_trips, view_customers, manage_testimonials |
| sales | view_reports, manage_payments, view_customers, manage_bookings |
| marketing | manage_newsletters, manage_destinations, manage_testimonials, view_reports |

- Admins have all permissions automatically
- Staff get department-based defaults + optional extra permissions via staff_permissions table
---

## Configuration Files

### config/database.php

Purpose: PDO singleton connection + auto-detect BASE_URL

Key Function: getDB() - returns a single PDO connection per request with EMULATE_PREPARES = false

Connected by: Nearly every PHP file in the project

### config/session.php

Purpose: Secure session configuration + 30-minute idle timeout

Key Behavior: Auto-destroys expired sessions and redirects to login with ?timeout=1

Connected by: pages/login.php, pages/signup.php

### config/csrf.php

Purpose: CSRF token generation, validation, and form field output

Key Functions: generateCSRFToken(), validateCSRFToken(), csrf_field(), getCSRFToken()

Connected by: All form-handling pages (login, signup, booking, payment, contact, inquiries, settings, reviews, admin pages)

### config/rate-limiter.php

Purpose: IP-based (file) and session-based request throttling

Key Functions: checkRateLimit(), checkFileRateLimit(), checkSessionRateLimit(), checkAccountLockout(), recordLoginAttempt(), clearLoginAttempts()

Connected by: pages/login.php, pages/signup.php, pages/forgot-password.php, pages/contact.php, pages/custom-trips.php, pages/inquiries.php, pages/submit-review.php, pages/submit-guide-review.php, pages/resend-verification.php

### config/otp.php

Purpose: OTP generation, hashed storage, verification, and email sending

Key Functions: generateOTP(), storeOTP(), verifyOTP(), sendOTPEmail()

Connected by: pages/signup.php, pages/resend-verification.php

### config/mail.php

Purpose: Gmail SMTP configuration constants (MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD)

Connected by: includes/mailer.php

### config/currency.php

Purpose: Sri Lankan Rupee formatting

Key Function: formatPrice(, ) - returns "Rs. X,XXX"

Connected by: All pages displaying prices

### config/logger.php

Purpose: Activity logging to activity_logs table

Key Function: logActivity(, , , )

Connected by: pages/cancel-booking.php, admin/bookings.php, admin/inquiries.php, admin/testimonials.php, admin/guide-reviews.php, admin/staff.php, admin/staff-edit.php, admin/staff-dashboard.php, admin/staff-assignments.php, admin/backup.php, admin/ajax/assign-staff.php

### config/error-handler.php

Purpose: Global error/exception/shutdown handling

Key Functions: logError(), show_error_page()

Connected by: All PHP files (loaded early in request lifecycle)
---

## Frontend CSS Files

### Global Stylesheets (loaded on every page)

| File | Purpose |
|------|---------|
| css/style.css | Global base styles, typography, buttons, forms, cards, responsive grid |
| css/navbar.css | Navigation bar: logo, nav links, profile dropdown, mobile hamburger |
| css/footer.css | Footer: brand section, quick links, support links, social icons |

### Page-Specific Stylesheets

| CSS File | Pages That Use It | What It Styles |
|----------|-------------------|----------------|
| css/login.css | login.php, signup.php, forgot-password.php, reset-password.php | Auth pages: card layout, form groups, social buttons |
| css/admin.css | All admin/*.php pages | Admin panel: sidebar, header, cards, tables, forms, modals |
| css/packages.css | packages.php, package-details.php, index.php | Package cards, detail page, price, itinerary |
| css/destinations.css | destinations.php, destination-details.php, index.php | Destination cards, filter tabs, detail hero |
| css/guides.css | guides.php, guide-details.php, index.php | Guide cards, profile page, review display |
| css/accommodations.css | accommodations.php | Accommodation cards, filter sidebar, amenity icons |
| css/transportation.css | transportation.php | Transport cards, filter sidebar, vehicle type display |
| css/booking.css | booking.php, booking-detail.php | Booking form, stepper, detail layout |
| css/payment.css | payment.php | Payment form, card input, order summary |
| css/my-bookings.css | my-bookings.php | Booking list, tabs, status badges |
| css/user-profile.css | user-profile.php | Profile card, edit form, avatar upload |
| css/settings.css | settings.php | Password change form, notification preferences |
| css/wishlist.css | wishlist.php | Wishlist cards, remove button |
| css/inquiries.css | inquiries.php, admin-inquiries.php | Inquiry list, thread view, reply form |
| css/my-reviews.css | my-reviews.php | Review list, edit/delete actions |
| css/contact.css | contact.php | Contact form, info cards |
| css/custom-trips.css | custom-trips.php | Custom trip form, multi-step layout |
| css/about.css | about.php | About page: hero, team, mission section |
| css/faq.css | faq.php | FAQ accordion, search |
| css/terms.css | terms.php, privacy.php, payment-policy.php, cancellation-policy.php | Policy pages: typography, sections |
| css/404.css | 404.php, 500.php | Error page: centered layout, illustration |
| css/review-modal.css | Pages with review modal | Modal overlay, star rating, form |
| css/testimonial.css | index.php | Testimonials carousel, cards |
| css/stats.css | index.php | Stats counter section, background image |
| css/partners.css | index.php | Partner logos section |
| css/admin-inquiries.css | admin/inquiries.php | Admin inquiry thread view |
| css/hero.css | index.php | Hero section: search form, date picker, travelers popup |
| css/activities.css | index.php | Popular activities section |

---

## Frontend JavaScript Files

### js/script.js (626 lines)

Purpose: Main frontend JavaScript for all public-facing pages

Included by: Every public page (34 PHP files)

Functions:

| Function | Purpose |
|----------|---------|
| showFieldError(formGroup, message) | Adds error class and message to form field |
| clearFieldError(formGroup) | Removes error state from form field |
| validateEmailFormat(email) | Regex email validation |
| validateLoginForm(e) | Login form validation |
| validateSignupForm(e) | Signup form validation |
| validateForgotForm(e) | Forgot password form validation |
| validateResetForm(e) | Reset password form validation |
| updatePasswordStrength(input) | Password strength bar (0-4) |
| scrollToSection(id) | Smooth scrolls to page section |
| openModal(id) / closeModal(id) | Opens/closes modal overlays |

Additional Features:
- Content protection (blocks copy/cut)
- Mobile navigation toggle
- Password visibility toggle
- Profile dropdown (keyboard accessible)
- Stats counter animation (IntersectionObserver)
- Destination filter tabs
- Wishlist toggle
- Testimonials carousel
- Guides carousel
- Hero search date picker (Flatpickr)
- Travelers popup counter
- CSRF token injection for AJAX

### js/review-modal.js (251 lines)

Purpose: Review submission modal - star rating, review type switching, form validation

Included by: index.php, pages/package-details.php, pages/guide-details.php

| Function | Purpose |
|----------|---------|
| openReviewModal(packageId) | Opens modal, resets form, sets package ID |
| closeReviewModal() | Closes modal |
| onReviewTypeChange() | Switches between general/package/guide review types |
| initStarSelector(containerId) | Interactive star rating widget |
| initCharCounter(textareaId, countId) | Character counter for textarea |
| initFormValidation() | Validates review form before submit |

### js/admin.js (120 lines)

Purpose: Admin panel JavaScript

Included by: All admin pages (via admin/includes/footer.php)

| Feature | Description |
|---------|-------------|
| Sidebar toggle | Mobile hamburger menu for admin sidebar |
| Delete confirm | confirm() dialog before delete actions |
| Select all checkbox | Toggle all row selection checkboxes |
| Auto-hide alerts | Flash messages fade out after 4 seconds |
| Table search | Client-side table row filtering |
| Status auto-submit | Dropdown change auto-submits form with confirmation |
---

## Every PHP File - Purpose and Connections

### Root

#### index.php - Homepage

Purpose: Landing page with hero section, search bar, featured destinations, featured packages, popular activities, stats, expert guides, testimonials, and trusted partners.

Includes: config/database.php, config/currency.php, includes/navbar.php, includes/review-modal.php, includes/footer.php

CSS: style.css, navbar.css, footer.css, hero.css, packages.css, destinations.css, guides.css, testimonial.css, stats.css, partners.css, activities.css, inquiries.css, review-modal.css

JS: script.js, review-modal.js

DB Tables: packages, package_tags, tags, destinations, guides, guide_reviews, testimonials, users

---

### Config Files

| File | Purpose | Functions Defined | Called By |
|------|---------|-------------------|-----------|
| config/database.php | PDO singleton + BASE_URL | getDB() | ~70 files |
| config/session.php | Secure session + idle timeout | (procedural) | login.php, signup.php |
| config/csrf.php | CSRF tokens | generateCSRFToken(), validateCSRFToken(), csrf_field(), getCSRFToken() | All form pages |
| config/rate-limiter.php | Rate limiting | checkRateLimit(), checkAccountLockout(), recordLoginAttempt(), clearLoginAttempts() | Auth + form pages |
| config/otp.php | OTP system | generateOTP(), storeOTP(), verifyOTP(), sendOTPEmail() | signup.php, resend-verification.php |
| config/mail.php | SMTP config | (constants only) | includes/mailer.php |
| config/currency.php | Currency formatting | formatPrice() | Price-displaying pages |
| config/logger.php | Activity logging | logActivity() | Admin actions, booking cancel |
| config/error-handler.php | Error handling | logError(), show_error_page() | All files (global handler) |

---

### Includes

#### includes/mailer.php - Email Sender

Functions: sendMail(), wrapEmailTemplate()

Connected by: includes/notifications.php, config/otp.php, pages/forgot-password.php, pages/resend-verification.php

#### includes/notifications.php - Email Notifications

Functions: sendBookingConfirmation(), sendPaymentReceipt(), sendBookingStatusUpdate(), sendInquiryReplyNotification()

Connected by: pages/payment.php, admin/bookings.php, admin/inquiries.php

#### includes/navbar.php - Navigation Bar

Connected by: index.php and all public pages

#### includes/footer.php - Footer

Connected by: index.php and all public pages

#### includes/user-sidebar.php - User Dashboard Sidebar

Connected by: pages/user-profile.php, pages/my-bookings.php, pages/wishlist.php, pages/inquiries.php, pages/my-reviews.php, pages/settings.php

#### includes/review-modal.php - Review Modal

Connected by: index.php, pages/package-details.php, pages/guide-details.php
---

### Pages - Auth Flow

| File | Purpose | CSS | Key Includes |
|------|---------|-----|-------------|
| pages/login.php | Login with remember-me | login.css, style.css, navbar.css | config/session.php, config/database.php, config/csrf.php, config/rate-limiter.php |
| pages/signup.php | Registration with OTP | login.css, style.css, navbar.css | config/session.php, config/database.php, config/csrf.php, config/rate-limiter.php, config/otp.php |
| pages/logout.php | Session cleanup | (none) | config/database.php |
| pages/forgot-password.php | Reset request | login.css, style.css, navbar.css | config/database.php, config/csrf.php, config/rate-limiter.php, includes/mailer.php |
| pages/reset-password.php | Reset with token | login.css, style.css, navbar.css | config/database.php, config/csrf.php |
| pages/verify-email.php | Email verification | (none) | config/database.php |
| pages/resend-verification.php | Resend verification | login.css, style.css, navbar.css | config/database.php, config/rate-limiter.php, config/otp.php, includes/mailer.php |

### Pages - Content Listing

| File | Purpose | CSS | Key Includes |
|------|---------|-----|-------------|
| pages/packages.php | Packages listing | packages.css, style.css, navbar.css, footer.css | config/database.php, config/currency.php |
| pages/package-details.php | Package detail | packages.css, review-modal.css, style.css | config/database.php, config/currency.php, includes/review-modal.php |
| pages/ajax-packages.php | AJAX search endpoint | (none) | config/database.php, config/currency.php |
| pages/destinations.php | Destinations listing | destinations.css, style.css, navbar.css, footer.css | config/database.php |
| pages/destination-details.php | Destination detail | destinations.css, style.css, navbar.css, footer.css | config/database.php |
| pages/guides.php | Guides listing | guides.css, style.css, navbar.css, footer.css | config/database.php |
| pages/guide-details.php | Guide detail | guides.css, review-modal.css, style.css | config/database.php, includes/review-modal.php |
| pages/accommodations.php | Accommodations listing | accommodations.css, style.css, navbar.css, footer.css | config/database.php, config/currency.php |
| pages/transportation.php | Transportation listing | transportation.css, style.css, navbar.css, footer.css | config/database.php, config/currency.php |

### Pages - Booking Flow

| File | Purpose | CSS | Key Includes |
|------|---------|-----|-------------|
| pages/booking.php | Booking form | booking.css, style.css, navbar.css, footer.css | config/database.php, config/csrf.php, config/currency.php |
| pages/booking-detail.php | Booking detail | booking.css, style.css, navbar.css, footer.css | config/database.php, config/currency.php |
| pages/cancel-booking.php | Cancel handler | (none) | config/database.php, config/logger.php |
| pages/payment.php | Payment processing | payment.css, style.css, navbar.css, footer.css | config/database.php, config/csrf.php, config/currency.php, includes/notifications.php |
| pages/my-bookings.php | User bookings list | my-bookings.css, user-sidebar.css, style.css | config/database.php, config/currency.php, includes/user-sidebar.php |

### Pages - User Dashboard

| File | Purpose | CSS | Key Includes |
|------|---------|-----|-------------|
| pages/user-profile.php | Edit profile | user-profile.css, user-sidebar.css, style.css | config/database.php, config/csrf.php, includes/user-sidebar.php |
| pages/settings.php | Account settings | settings.css, user-sidebar.css, style.css | config/database.php, config/csrf.php, includes/user-sidebar.php |
| pages/wishlist.php | User wishlist | wishlist.css, user-sidebar.css, style.css | config/database.php, config/csrf.php, config/currency.php, includes/user-sidebar.php |
| pages/wishlist-toggle.php | AJAX wishlist toggle | (none) | config/csrf.php, config/database.php |
| pages/destination-wishlist-toggle.php | AJAX dest wishlist | (none) | config/csrf.php, config/database.php |
| pages/my-reviews.php | User reviews | my-reviews.css, user-sidebar.css, style.css | config/database.php, config/csrf.php, includes/user-sidebar.php |
| pages/submit-review.php | Submit package review | (none) | config/database.php, config/csrf.php, config/rate-limiter.php |
| pages/submit-guide-review.php | Submit guide review | (none) | config/database.php, config/csrf.php, config/rate-limiter.php |
| pages/inquiries.php | User inquiries | inquiries.css, user-sidebar.css, style.css | config/database.php, config/csrf.php, config/rate-limiter.php, includes/user-sidebar.php |

### Pages - Communication

| File | Purpose | CSS | Key Includes |
|------|---------|-----|-------------|
| pages/contact.php | Contact form | contact.css, style.css, navbar.css, footer.css | config/database.php, config/csrf.php, config/rate-limiter.php |
| pages/custom-trips.php | Custom trip request | custom-trips.css, style.css, navbar.css, footer.css | config/database.php, config/csrf.php, config/rate-limiter.php |
| pages/admin-inquiries.php | Admin inquiries (public) | inquiries.css, style.css, navbar.css, footer.css | config/database.php |
| pages/newsletter-subscribe.php | Newsletter (AJAX) | (none) | config/csrf.php, config/database.php |

### Pages - Static

| File | Purpose | CSS |
|------|---------|-----|
| pages/about.php | About us | about.css, style.css, navbar.css, footer.css |
| pages/faq.php | FAQ | faq.css, style.css, navbar.css, footer.css |
| pages/terms.php | Terms and conditions | terms.css, style.css, navbar.css, footer.css |
| pages/privacy.php | Privacy policy | terms.css, style.css, navbar.css, footer.css |
| pages/payment-policy.php | Payment policy | terms.css, style.css, navbar.css, footer.css |
| pages/cancellation-policy.php | Cancellation policy | terms.css, style.css, navbar.css, footer.css |
| pages/404.php | 404 error | 404.css, style.css, navbar.css, footer.css |
| pages/500.php | 500 error | 404.css, style.css, navbar.css, footer.css |
---

## Admin Panel

### Auth Guard (admin/includes/header.php)

Every admin page starts with:
1. Session check - only admin or staff roles allowed
2. Loads config/database.php, config/csrf.php, config/currency.php
3. Defines hasPermission(), getStaffProfile(), getAllStaff(), getMyAssignments()

### Admin Files

| File | Purpose | Key Functions Used |
|------|---------|-------------------|
| admin/index.php | Dashboard (KPIs, Chart.js) | hasPermission() |
| admin/logout.php | Admin logout | getDB() |
| admin/users.php | User management | hasPermission(), logActivity() |
| admin/user-edit.php | Edit user | validateCSRFToken() |
| admin/packages.php | Package management | hasPermission() |
| admin/package-edit.php | Edit package | validateCSRFToken() |
| admin/destinations.php | Destination management | hasPermission() |
| admin/destination-edit.php | Edit destination | validateCSRFToken() |
| admin/guides.php | Guide management | hasPermission() |
| admin/guide-edit.php | Edit guide | validateCSRFToken() |
| admin/accommodations.php | Accommodation management | hasPermission() |
| admin/accommodation-edit.php | Edit accommodation | validateCSRFToken() |
| admin/transportation.php | Transport management | hasPermission() |
| admin/transport-edit.php | Edit transport | validateCSRFToken() |
| admin/bookings.php | Booking management | hasPermission(), logActivity(), sendBookingStatusUpdate(), getAllStaff() |
| admin/inquiries.php | Inquiry management | hasPermission(), logActivity(), sendInquiryReplyNotification() |
| admin/contacts.php | Contact messages | hasPermission() |
| admin/custom-trips.php | Custom trips | hasPermission() |
| admin/testimonials.php | Testimonials | hasPermission(), logActivity() |
| admin/guide-reviews.php | Guide reviews | hasPermission(), logActivity() |
| admin/tags.php | Tag management | validateCSRFToken() |
| admin/tag-edit.php | Edit tag | validateCSRFToken() |
| admin/staff.php | Staff management | hasPermission(), logActivity() |
| admin/staff-edit.php | Edit staff | validateCSRFToken(), logActivity() |
| admin/staff-dashboard.php | Staff dashboard | hasPermission(), logActivity(), getStaffProfile() |
| admin/staff-assignments.php | Staff assignments | hasPermission(), logActivity(), getAllStaff() |
| admin/newsletters.php | Newsletter management | validateCSRFToken() |
| admin/providers.php | Service providers | (none) |
| admin/reports.php | Sales reports | (none) |
| admin/customer-reports.php | Customer reports | (none) |
| admin/system-logs.php | Activity logs | (none) |
| admin/backup.php | Database backup | validateCSRFToken(), logActivity() |
| admin/export-sales.php | Export sales (CSV) | formatPrice() |
| admin/export-sales-pdf.php | Export sales (PDF) | formatPrice() |
| admin/export-sales-excel.php | Export sales (Excel) | (none) |
| admin/export-customers.php | Export customers (CSV) | (none) |
| admin/export-customers-pdf.php | Export customers (PDF) | (none) |
| admin/ajax/assign-staff.php | AJAX staff assignment | validateCSRFToken(), logActivity() |

---

## Setup Instructions

### Prerequisites

1. XAMPP (Apache + MySQL + PHP 8.x)
2. Composer (for PHP dependencies)

### Steps

1. Clone the project into C:\xampp\htdocs\GlobeTrek-Adeventures\

2. Start Apache and MySQL via XAMPP Control Panel

3. Create the database:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import database/init.sql (or run database/schema.sql + database/seed.sql separately)
   - Run additional migrations in order:
     - database/auth-migration.sql
     - database/staff-migration.sql
     - database/tags-migration.sql
     - database/reviews-migration.sql
     - database/guide-reviews-migration.sql
     - database/homepage-migration.sql
     - database/destinations-upgrade.sql
     - database/remember-tokens-migration.sql
     - database/add-destination-ratings.sql

4. Install Composer dependencies:
   cd C:\xampp\htdocs\GlobeTrek-Adeventures
   composer install

5. Configure email (optional):
   - Edit config/mail.php with your Gmail address and App Password
   - Enable 2FA on your Google account and generate an App Password at https://myaccount.google.com/apppasswords

6. Access the site:
   - Homepage: http://localhost/GlobeTrek-Adeventures/
   - Admin Panel: http://localhost/GlobeTrek-Adeventures/admin/

### Default Admin Credentials

Check the seed.sql file for default admin account details.

---

## Dependencies

### Composer Packages

| Package | Version | Purpose |
|---------|---------|---------|
| dompdf/dompdf | ^3.1 | PDF generation (customer/sales report exports) |
| phpmailer/phpmailer | ^6.9 | Email via Gmail SMTP |
| phpoffice/phpspreadsheet | ^1.29 | Excel export (XLSX) |
| thecodingmachine/safe | ^2.5 | Safe PHP function wrappers |

### CDN Libraries

| Library | Purpose |
|---------|---------|
| Google Material Symbols | Icons throughout the site |
| Chart.js | Admin dashboard charts |
| Flatpickr | Date picker on homepage |
| Google Fonts | Fraunces, Manrope, Hanken Grotesk |