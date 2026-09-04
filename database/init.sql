-- =============================================================================
-- DATABASE: globetrek
-- PURPOSE:  Core schema for GlobeTrek Adventures — a Sri Lanka travel platform
--           enabling users to browse packages, destinations, guides,
--           accommodations, and transport; make bookings and payments;
--           submit inquiries; manage wishlists; and handle staff operations.
--
-- TABLES (28 total):
--   Core entities : users, packages, destinations, guides
--   Booking flow  : bookings, payments, wishlist
--   Communication : contact_messages, inquiries, inquiry_replies,
--                    custom_trip_requests, testimonials, guide_reviews
--   Services      : accommodations, transportations
--   Newsletter    : newsletter_subscriptions, newsletter_subscribers
--   Auth/Security : email_verifications, password_resets, otps,
--                    login_attempts, remember_tokens
--   Tags          : tags, package_tags, destination_tags, guide_tags
--   Staff mgmt    : staff_profiles, staff_permissions, staff_assignments
--   Audit         : activity_logs
--
-- KEY RELATIONSHIPS:
--   users        → bookings, payments, wishlist, inquiries, testimonials,
--                  guide_reviews, activity_logs, email_verifications,
--                  remember_tokens, staff_profiles
--   packages     → bookings, testimonials, wishlist, package_tags, inquiries
--   destinations → wishlist, destination_tags
--   guides       → guide_reviews, guide_tags
--   bookings     → payments, staff_assignments
--   inquiries    → inquiry_replies, staff_assignments
--   staff_profiles → staff_permissions, staff_assignments
--
-- SEED DATA:
--   6 packages, 12 destinations, 6 guides, 10 transportations,
--   6 accommodations, 3 inquiries, 5 inquiry replies,
--   6 testimonials, 5 guide reviews
--
-- ENGINE: InnoDB throughout (FK support, row-level locking)
-- CHARSET: utf8mb4 (full Unicode support)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS globetrek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE globetrek;

-- ============================================================
-- SCHEMA
-- ============================================================

-- =============================================================================
-- Table: users
-- Purpose: Stores registered user accounts — both customers and administrators.
-- Key Columns:
--   id                     – auto-increment PK
--   email                  – unique login identifier
--   password               – hashed password (bcrypt/argon2)
--   role                   – 'user', 'staff', or 'admin' — drives RBAC
--   email_verified         – flag checked before granting full access
--   notification_preferences – JSON blob for per-user alert settings
-- Relationships:
--   Referenced by: bookings, payments, wishlist, inquiries,
--                  inquiry_replies, testimonials, guide_reviews,
--                  activity_logs, email_verifications, remember_tokens,
--                  staff_profiles, otps (indirect)
-- =============================================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    gender VARCHAR(30) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    profile_photo VARCHAR(500) DEFAULT NULL,
    role ENUM('user', 'staff', 'admin') DEFAULT 'user',
    email_verified TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    notification_preferences JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================================
-- Table: packages
-- Purpose: Travel packages offered by GlobeTrek (e.g. "Island Escape").
-- Key Columns:
--   id                     – auto-increment PK
--   slug                   – URL-friendly unique identifier
--   duration_days/nights   – trip length
--   price                  – per-person price in LKR
--   destination_category   – free-text region bucket for filtering
--   is_featured            – controls homepage spotlight carousel
-- Relationships:
--   Referenced by: bookings, testimonials, wishlist, package_tags, inquiries
-- =============================================================================
CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(255),
    duration_days INT NOT NULL,
    duration_nights INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(500),
    destination_category VARCHAR(100),
    price_range VARCHAR(50),
    max_group_size INT DEFAULT 12,
    difficulty_level VARCHAR(50) DEFAULT 'Moderate',
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================================
-- Table: destinations
-- Purpose: Individual travel destinations within Sri Lanka (e.g. Sigiriya).
-- Key Columns:
--   id              – auto-increment PK
--   slug            – URL-friendly unique identifier
--   region          – province / geographic region
--   category        – type tag (Cultural, Beach, Heritage, etc.)
--   rating          – aggregated average rating (0.0–5.0)
--   review_count    – total reviews received
-- Relationships:
--   Referenced by: wishlist, destination_tags
-- =============================================================================
CREATE TABLE IF NOT EXISTS destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    region VARCHAR(100) DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    image VARCHAR(500),
    rating DECIMAL(2,1) DEFAULT 0.0,
    review_count INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================================
-- Table: guides
-- Purpose: Tour guides available for booking, with specialties and ratings.
-- Key Columns:
--   id                – auto-increment PK
--   specialty         – primary tour focus (e.g. "Wildlife & Safari")
--   region            – area of operation
--   rating            – average client rating (0.0–5.0)
--   languages         – comma-separated language list
--   years_experience  – years guiding professionally
-- Relationships:
--   Referenced by: guide_reviews, guide_tags
-- =============================================================================
CREATE TABLE IF NOT EXISTS guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    specialty VARCHAR(200),
    region VARCHAR(100),
    description TEXT,
    rating DECIMAL(2,1) DEFAULT 4.5,
    languages VARCHAR(255) DEFAULT 'English, Sinhala',
    years_experience INT DEFAULT 5,
    image VARCHAR(500),
    profile_link VARCHAR(500) DEFAULT '#',
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================================
-- Table: bookings
-- Purpose: Customer reservations for travel packages.
-- Key Columns:
--   id                  – auto-increment PK
--   user_id             – FK → users (nullable for guest bookings)
--   package_id          – FK → packages (cascades on delete)
--   booking_reference   – human-readable unique ref (e.g. "BK-XXXXX")
--   status              – 'pending', 'confirmed', or 'cancelled'
--   total_price         – final charged amount
--   travel_date         – scheduled departure date
-- Relationships:
--   References: users (SET NULL on delete), packages (CASCADE on delete)
--   Referenced by: payments, staff_assignments
-- =============================================================================
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    package_id INT NOT NULL,
    booking_reference VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    nationality VARCHAR(50),
    special_requests TEXT,
    num_travellers INT DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(20) DEFAULT NULL,
    card_last_four VARCHAR(4) DEFAULT NULL,
    travel_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================================
-- Table: contact_messages
-- Purpose: General contact-form submissions from website visitors.
-- Key Columns:
--   id       – auto-increment PK
--   message  – full message body
--   is_read  – tracks whether staff have viewed it
-- Relationships:
--   Standalone — not FK-linked to other tables
-- =============================================================================
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================================
-- Table: accommodations
-- Purpose: Hotels, villas, and resorts available for booking alongside packages.
-- Key Columns:
--   id                – auto-increment PK
--   slug              – URL-friendly unique identifier
--   property_type     – 'Hotel', 'Villa', 'Boutique', or 'Resort'
--   price_per_night   – nightly rate in USD
--   has_*             – boolean amenity flags (wifi, pool, spa, etc.)
--   provider_*        – third-party provider contact details
-- Relationships:
--   Standalone — linked to bookings at the application layer, not via FK
-- =============================================================================
CREATE TABLE IF NOT EXISTS accommodations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(255),
    location VARCHAR(200) NOT NULL,
    property_type ENUM('Hotel', 'Villa', 'Boutique', 'Resort') NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    rating DECIMAL(2,1) DEFAULT 0,
    image VARCHAR(500),
    has_wifi TINYINT(1) DEFAULT 0,
    has_pool TINYINT(1) DEFAULT 0,
    has_spa TINYINT(1) DEFAULT 0,
    has_restaurant TINYINT(1) DEFAULT 0,
    has_fitness TINYINT(1) DEFAULT 0,
    provider_name VARCHAR(150) DEFAULT NULL,
    provider_email VARCHAR(150) DEFAULT NULL,
    provider_phone VARCHAR(30) DEFAULT NULL,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================================
-- Table: transportations
-- Purpose: Vehicle rentals (tuk-tuks, cars, bikes, vans) for local travel.
-- Key Columns:
--   id              – auto-increment PK
--   slug            – URL-friendly unique identifier
--   vehicle_type    – 'Three-Wheeler', 'Car', 'Bike', or 'Minivan'
--   price_per_day   – daily rental rate in LKR
--   has_ac/driver/insurance – boolean feature flags
--   provider_*      – third-party rental provider contact details
-- Relationships:
--   Standalone — linked to bookings at the application layer, not via FK
-- =============================================================================
CREATE TABLE IF NOT EXISTS transportations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(255),
    location VARCHAR(200) NOT NULL,
    vehicle_type ENUM('Three-Wheeler', 'Car', 'Bike', 'Minivan') NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    rating DECIMAL(2,1) DEFAULT 0,
    image VARCHAR(500),
    has_ac TINYINT(1) DEFAULT 0,
    has_driver TINYINT(1) DEFAULT 0,
    has_insurance TINYINT(1) DEFAULT 0,
    is_available TINYINT(1) DEFAULT 1,
    provider_name VARCHAR(150) DEFAULT NULL,
    provider_email VARCHAR(150) DEFAULT NULL,
    provider_phone VARCHAR(30) DEFAULT NULL,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================================
-- Table: newsletter_subscriptions
-- Purpose: Newsletter opt-in tracking for email marketing campaigns.
-- Key Columns:
--   id         – auto-increment PK
--   email      – unique subscriber address
--   is_active  – allows soft-unsubscribe without row deletion
-- Relationships:
--   Standalone — no FK dependencies
-- =============================================================================
CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================================
-- Table: newsletter_subscribers
-- Purpose: Secondary subscriber list — possibly legacy or import-based.
-- Note: Overlaps with newsletter_subscriptions; kept separate for
--       backward compatibility or distinct import sources.
-- Key Columns:
--   id              – auto-increment PK
--   email           – unique subscriber address
--   subscribed_at   – subscription timestamp
-- Relationships:
--   Standalone — no FK dependencies
-- =============================================================================
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================================
-- Table: inquiries
-- Purpose: Customer support tickets tied to specific packages or bookings.
-- Key Columns:
--   id                – auto-increment PK
--   user_id           – FK → users (cascades on delete)
--   package_id        – FK → packages (SET NULL on delete)
--   inquiry_id_code   – unique human-readable reference (e.g. "INQ-44521")
--   status            – 'open' → 'waiting_for_response' → 'under_review' → 'resolved'
-- Relationships:
--   References: users (CASCADE), packages (SET NULL)
--   Referenced by: inquiry_replies, staff_assignments
-- =============================================================================
CREATE TABLE IF NOT EXISTS inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT DEFAULT NULL,
    booking_reference VARCHAR(20) DEFAULT NULL,
    inquiry_id_code VARCHAR(20) NOT NULL UNIQUE,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'waiting_for_response', 'under_review', 'resolved') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================================================
-- Table: inquiry_replies
-- Purpose: Threaded responses on support inquiries (user ↔ staff messaging).
-- Key Columns:
--   id            – auto-increment PK
--   inquiry_id    – FK → inquiries (cascades on delete)
--   sender_id     – FK → users (SET NULL if user deleted)
--   sender_role   – 'user', 'staff', or 'admin' — controls UI rendering
--   message       – reply body text
-- Relationships:
--   References: inquiries (CASCADE), users (SET NULL)
-- =============================================================================
CREATE TABLE IF NOT EXISTS inquiry_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inquiry_id INT NOT NULL,
    sender_id INT DEFAULT NULL,
    sender_role ENUM('user', 'staff', 'admin') NOT NULL DEFAULT 'user',
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inquiry_id) REFERENCES inquiries(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================================================
-- Table: custom_trip_requests
-- Purpose: Bespoke trip requests submitted by users for tailor-made itineraries.
-- Key Columns:
--   id                – auto-increment PK
--   travel_style      – 'luxury', 'adventure', 'cultural', or 'relaxation'
--   interests         – JSON array of interest tags
--   status            – 'pending' → 'reviewed' → 'completed'
-- Relationships:
--   Standalone — no FK; matched to packages at the application layer
-- =============================================================================
CREATE TABLE IF NOT EXISTS custom_trip_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    destination VARCHAR(300) DEFAULT NULL,
    duration_days INT DEFAULT NULL,
    num_travelers INT DEFAULT NULL,
    estimated_dates VARCHAR(100) DEFAULT NULL,
    travel_style ENUM('luxury', 'adventure', 'cultural', 'relaxation') DEFAULT NULL,
    interests JSON DEFAULT NULL,
    additional_details TEXT DEFAULT NULL,
    status ENUM('pending', 'reviewed', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================================
-- Table: wishlist
-- Purpose: User-curated list of saved packages and destinations.
-- Key Columns:
--   id              – auto-increment PK
--   user_id         – FK → users (cascades on delete)
--   package_id      – FK → packages (cascades on delete), nullable
--   destination_id  – FK → destinations (cascades on delete), nullable
-- Relationships:
--   References: users (CASCADE), packages (CASCADE), destinations (CASCADE)
-- Constraints:
--   UNIQUE(user_id, package_id) — prevents duplicate saves per package
-- =============================================================================
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT DEFAULT NULL,
    destination_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_package (user_id, package_id),
    UNIQUE KEY unique_wishlist_destination (user_id, destination_id)
) ENGINE=InnoDB;

-- =============================================================================
-- Table: payments
-- Purpose: Transaction records for completed and attempted bookings.
-- Key Columns:
--   id               – auto-increment PK
--   booking_id       – FK → bookings (cascades on delete)
--   user_id          – FK → users (SET NULL on delete)
--   amount           – charged amount in LKR
--   transaction_id   – gateway-assigned unique ID
--   status           – 'pending', 'completed', 'failed', or 'refunded'
--   card_last_four   – last 4 digits for receipt display
-- Relationships:
--   References: bookings (CASCADE), users (SET NULL)
-- =============================================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    card_last_four VARCHAR(4) DEFAULT NULL,
    card_brand VARCHAR(30) DEFAULT NULL,
    transaction_id VARCHAR(50) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    billing_address TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================================================
-- Table: activity_logs
-- Purpose: Audit trail of user and admin actions across the platform.
-- Key Columns:
--   id            – auto-increment PK
--   user_id       – FK → users (SET NULL if user deleted)
--   action        – verb describing what happened (e.g. "booking.created")
--   entity_type   – table name of the affected entity
--   entity_id     – PK of the affected row
--   ip_address    – client IP for security forensics
-- Relationships:
--   References: users (SET NULL)
-- =============================================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================================================
-- Table: testimonials
-- Purpose: Customer reviews for travel packages, shown on public pages.
-- Key Columns:
--   id             – auto-increment PK
--   user_id        – FK → users (SET NULL on delete), nullable for guest reviews
--   package_id     – FK → packages (SET NULL on delete)
--   rating         – 1–5 star rating
--   status         – moderation state: 'pending' → 'approved'/'rejected'
--   is_featured    – controls homepage testimonial carousel
-- Relationships:
--   References: users (SET NULL), packages (SET NULL)
-- Indexes: idx_testimonials_status, idx_testimonials_user, idx_testimonials_package
-- =============================================================================
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    package_id INT DEFAULT NULL,
    reviewer_name VARCHAR(150) NOT NULL,
    reviewer_country VARCHAR(100),
    reviewer_avatar VARCHAR(500),
    rating TINYINT NOT NULL,
    title VARCHAR(200),
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL,
    INDEX idx_testimonials_status (status),
    INDEX idx_testimonials_user (user_id),
    INDEX idx_testimonials_package (package_id)
) ENGINE=InnoDB;

-- =============================================================================
-- Table: email_verifications
-- Purpose: Time-limited tokens for verifying new user email addresses.
-- Key Columns:
--   id          – auto-increment PK
--   user_id     – FK → users (cascades on delete)
--   token       – 64-char unique hash sent in verification email
--   expires_at  – token validity window (typically 24 h)
-- Relationships:
--   References: users (CASCADE)
-- Indexes: idx_token (for fast lookup during verification flow)
-- =============================================================================
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB;

-- =============================================================================
-- Table: password_resets
-- Purpose: One-time tokens for "forgot password" flows.
-- Key Columns:
--   id          – auto-increment PK
--   email       – the account email (not FK — user may not exist yet)
--   token       – 64-char unique hash sent in reset email
--   expires_at  – token validity window (typically 1 h)
-- Relationships:
--   None (email-based lookup only for security)
-- Indexes: idx_token, idx_email
-- =============================================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- =============================================================================
-- Table: otps
-- Purpose: One-time passwords for multi-factor or step-up authentication.
-- Key Columns:
--   id         – auto-increment PK
--   user_id    – FK → users (nullable for pre-registration OTPs)
--   email      – target email address
--   otp_hash   – hashed OTP value (never stored plaintext)
--   purpose    – 'registration', 'login', or 'password_reset'
--   expires_at – token validity window (typically 5–10 min)
--   used       – flag to prevent OTP reuse
-- Relationships:
--   References: users (indirect — nullable FK)
-- Indexes: idx_email_purpose (composite for rate-limiting lookups)
-- =============================================================================
CREATE TABLE IF NOT EXISTS otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    email VARCHAR(150) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    purpose ENUM('registration','login','password_reset') NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_purpose (email, purpose)
) ENGINE=InnoDB;

-- =============================================================================
-- Table: login_attempts
-- Purpose: Tracks failed (and successful) login attempts for brute-force protection.
-- Key Columns:
--   id            – auto-increment PK
--   email         – attempted email
--   ip_address    – client IP for rate-limiting
--   attempted_at  – timestamp of the attempt
-- Relationships:
--   Standalone — used by the auth layer for IP/email throttling
-- Indexes: idx_email_time (composite for sliding-window rate limits)
-- =============================================================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_time (email, attempted_at)
) ENGINE=InnoDB;

-- =============================================================================
-- Table: remember_tokens
-- Purpose: Persistent "remember me" login tokens for long-lived sessions.
-- Key Columns:
--   id           – auto-increment PK
--   user_id      – FK → users (cascades on delete)
--   token_hash   – SHA-256 hash of the cookie token (never raw)
--   expires_at   – token validity window (typically 30 days)
-- Relationships:
--   References: users (CASCADE)
-- Indexes: idx_token_hash (for fast cookie lookup), idx_user_id
-- =============================================================================
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- =============================================================================
-- Table: tags
-- Purpose: Reusable标签 for categorizing packages, destinations, and guides.
-- Key Columns:
--   id    – auto-increment PK
--   name  – display label (unique)
--   slug  – URL-friendly identifier (unique)
-- Relationships:
--   Referenced by: package_tags, destination_tags, guide_tags
-- =============================================================================
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tag_name (name),
    UNIQUE KEY unique_tag_slug (slug)
) ENGINE=InnoDB;

-- =============================================================================
-- Table: package_tags
-- Purpose: Many-to-many junction linking packages to tags.
-- Key Columns:
--   package_id – FK → packages (cascades on delete)
--   tag_id     – FK → tags (cascades on delete)
-- Relationships:
--   References: packages (CASCADE), tags (CASCADE)
-- Constraints:
--   Composite PK (package_id, tag_id) — one link per pair
-- =============================================================================
CREATE TABLE IF NOT EXISTS package_tags (
    package_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (package_id, tag_id),
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================================
-- Table: destination_tags
-- Purpose: Many-to-many junction linking destinations to tags.
-- Key Columns:
--   destination_id – FK → destinations (cascades on delete)
--   tag_id         – FK → tags (cascades on delete)
-- Relationships:
--   References: destinations (CASCADE), tags (CASCADE)
-- Constraints:
--   Composite PK (destination_id, tag_id) — one link per pair
-- =============================================================================
CREATE TABLE IF NOT EXISTS destination_tags (
    destination_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (destination_id, tag_id),
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================================
-- Table: guide_tags
-- Purpose: Many-to-many junction linking guides to tags.
-- Key Columns:
--   guide_id – FK → guides (cascades on delete)
--   tag_id   – FK → tags (cascades on delete)
-- Relationships:
--   References: guides (CASCADE), tags (CASCADE)
-- Constraints:
--   Composite PK (guide_id, tag_id) — one link per pair
-- =============================================================================
CREATE TABLE IF NOT EXISTS guide_tags (
    guide_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (guide_id, tag_id),
    FOREIGN KEY (guide_id) REFERENCES guides(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================================
-- Table: staff_profiles
-- Purpose: Extended profile for staff/admin users — department, role, capacity.
-- Key Columns:
--   id                     – auto-increment PK
--   user_id                – FK → users (unique, cascades on delete)
--   department             – 'operations', 'customer_service', 'sales', 'marketing'
--   position               – job title
--   is_available           – whether the staff member can take new assignments
--   max_concurrent_tasks   – workload cap for auto-assignment logic
-- Relationships:
--   References: users (CASCADE)
--   Referenced by: staff_permissions, staff_assignments
-- Indexes: idx_staff_profiles_department, idx_staff_profiles_available
-- =============================================================================
CREATE TABLE IF NOT EXISTS staff_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    department ENUM('operations', 'customer_service', 'sales', 'marketing') NOT NULL,
    position VARCHAR(100) NOT NULL,
    hire_date DATE DEFAULT NULL,
    is_available TINYINT(1) DEFAULT 1,
    max_concurrent_tasks INT DEFAULT 10,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================================
-- Table: staff_permissions
-- Purpose: Granular permission grants for staff members (RBAC detail).
-- Key Columns:
--   id          – auto-increment PK
--   staff_id    – FK → staff_profiles (cascades on delete)
--   permission  – action string (e.g. "bookings.approve", "inquiries.reply")
--   granted_at  – when the permission was granted
-- Relationships:
--   References: staff_profiles (CASCADE)
-- Constraints:
--   UNIQUE(staff_id, permission) — no duplicate grants
-- =============================================================================
CREATE TABLE IF NOT EXISTS staff_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    permission VARCHAR(100) NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_permission (staff_id, permission)
) ENGINE=InnoDB;

-- =============================================================================
-- Table: staff_assignments
-- Purpose: Maps staff members to bookings or inquiries they are responsible for.
-- Key Columns:
--   id            – auto-increment PK
--   staff_id      – FK → staff_profiles (cascades on delete)
--   entity_type   – 'booking' or 'inquiry' (polymorphic assignment)
--   entity_id     – PK of the booking or inquiry
--   assigned_by   – FK → users who made the assignment (SET NULL on delete)
-- Relationships:
--   References: staff_profiles (CASCADE), users (SET NULL)
-- Constraints:
--   UNIQUE(staff_id, entity_type, entity_id) — one assignment per entity
-- Indexes: idx_staff_assignments_entity, idx_staff_assignments_staff
-- =============================================================================
CREATE TABLE IF NOT EXISTS staff_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    entity_type ENUM('booking', 'inquiry') NOT NULL,
    entity_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT DEFAULT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_assignment (staff_id, entity_type, entity_id)
) ENGINE=InnoDB;

CREATE INDEX idx_staff_profiles_department ON staff_profiles(department);
CREATE INDEX idx_staff_profiles_available ON staff_profiles(is_available);
CREATE INDEX idx_staff_assignments_entity ON staff_assignments(entity_type, entity_id);
CREATE INDEX idx_staff_assignments_staff ON staff_assignments(staff_id);

-- =============================================================================
-- Table: guide_reviews
-- Purpose: Customer reviews specifically for individual tour guides.
-- Key Columns:
--   id             – auto-increment PK
--   user_id        – FK → users (SET NULL on delete)
--   guide_id       – FK → guides (cascades on delete)
--   rating         – 1–5 star rating
--   status         – moderation: 'pending' → 'approved'/'rejected'
-- Relationships:
--   References: users (SET NULL), guides (CASCADE)
-- Indexes: idx_guide_reviews_guide, idx_guide_reviews_status, idx_guide_reviews_user
-- =============================================================================
CREATE TABLE IF NOT EXISTS guide_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    guide_id INT NOT NULL,
    reviewer_name VARCHAR(150) NOT NULL,
    reviewer_country VARCHAR(100) DEFAULT NULL,
    reviewer_avatar VARCHAR(500) DEFAULT NULL,
    rating TINYINT NOT NULL,
    title VARCHAR(200) DEFAULT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (guide_id) REFERENCES guides(id) ON DELETE CASCADE,
    INDEX idx_guide_reviews_guide (guide_id),
    INDEX idx_guide_reviews_status (status),
    INDEX idx_guide_reviews_user (user_id)
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- =============================================================================
-- Seed: packages (6 rows)
-- Purpose: Pre-populated travel packages covering Sri Lanka's main regions:
--   beach, hill country, cultural triangle, city, and wildlife.
-- =============================================================================
INSERT INTO packages (title, slug, description, short_description, duration_days, duration_nights, price, image, destination_category, price_range, is_featured) VALUES
('Island Escape', 'island-escape', 'Immerse yourself in the breathtaking beauty of Sri Lanka with our exclusive Island Escape package. Designed for thrill-seekers and nature lovers alike, this 5-day journey takes you from pristine beaches to lush tropical jungles. Experience local culture, wildlife encounters, and unparalleled relaxation in carefully selected accommodations.', 'Explore Sri Lanka pristine beaches and jungles on this 5-day adventure.', 5, 4, 75999.00, 'https://images.unsplash.com/photo-1734279135115-6d8984e08206?q=80&w=800&auto=format&fit=crop', 'Southern &amp; Western Beaches', '50000+', 1),
('Mountain Explorer', 'mountain-explorer', 'Embark on a 6-day trekking adventure through the misty hill country of Sri Lanka. From tea plantations to cloud forests, experience the island mountainous interior like never before.', 'Discover Sri Lanka lush hill country on this 6-day trekking adventure.', 6, 5, 65999.00, 'https://picsum.photos/seed/mountain/400/250', 'Hill Country &amp; Tea Country', '50000+', 1),
('Beach Paradise', 'beach-paradise', 'Unwind on the sun-kissed shores of southern Sri Lanka. This 4-day getaway includes beach activities, water sports, and serene coastal relaxation.', 'Relax on pristine southern beaches with this 4-day coastal escape.', 4, 3, 39990.00, 'https://picsum.photos/seed/beach/400/250', 'Southern &amp; Western Beaches', '30000-49999', 1),
('Cultural Discovery', 'cultural-discovery', 'Dive deep into the rich cultural tapestry of Sri Lanka. Visit ancient temples, royal palaces, and UNESCO World Heritage sites across the cultural triangle.', 'Explore ancient temples and UNESCO sites on this 7-day cultural journey.', 7, 6, 55990.00, 'https://picsum.photos/seed/cultural/400/250', 'Cultural Triangle &amp; Temples', '50000+', 1),
('City Lights', 'city-lights', 'Experience the vibrant urban life of Colombo and surrounding cities. From bustling markets to modern skyline views, discover the dynamic side of Sri Lanka.', 'Experience the vibrant urban life of Colombo on this 3-day city break.', 3, 2, 35490.00, 'https://picsum.photos/seed/city/400/250', 'Colombo &amp; City Experiences', '30000-49999', 0),
('Wild Safari', 'wild-safari', 'Get up close with Sri Lanka incredible wildlife. Visit Yala, Udawalawe, and other renowned national parks for unforgettable safari experiences.', 'Encounter Sri Lanka incredible wildlife on this 5-day safari adventure.', 5, 4, 82490.00, 'https://picsum.photos/seed/safari/400/250', 'National Parks &amp; Wildlife', '50000+', 1);

-- =============================================================================
-- Seed: destinations (12 rows)
-- Purpose: Key Sri Lanka destinations spanning cultural, beach, heritage,
--   adventure, mountain, and wildlife categories across all provinces.
-- =============================================================================
INSERT INTO destinations (name, slug, description, region, category, image, rating, review_count, is_featured) VALUES
('Sigiriya Rock Fortress, Matale', 'sigiriya', 'A dramatic, UNESCO-protected ancient palace complex perched atop a massive 180-meter-high granite rock column. Built by King Kashyapa in the 5th century, it is famous for its colorful frescoes, graffiti-mirror wall, and monumental lion''s paw gateway.', 'Central Province', 'Cultural', 'https://images.unsplash.com/photo-1711797750174-c3750dd9d7c9?w=600&h=400&fit=crop', 4.8, 1250, 1),
('Galle Fort, Galle', 'galle', 'A living UNESCO World Heritage monument originally built by the Portuguese in 1588 and heavily fortified by the Dutch. Today, its atmospheric cobblestone streets are lined with beautifully preserved colonial villas, boutique cafes, and old churches, bounded by historic seaside ramparts.', 'Southern Province', 'Heritage', 'https://images.unsplash.com/photo-1704797390325-b057758d8c3d?w=600&h=400&fit=crop', 4.8, 1050, 1),
('Nine Arch Bridge (Ella), Badulla', 'nine-arch', 'An iconic, colonial-era railway bridge built completely out of brick, rock, and cement without using a single piece of steel. It stands hidden amid lush green tea plantations and misty mountains, drawing travelers who come to watch trains slowly pass over its line and admire arches.', 'Uva Province', 'Adventure', 'https://images.unsplash.com/photo-1550679193-d8ec2f2c3a25?w=600&h=400&fit=crop', 4.8, 1150, 0),
('Ancient City of Polonnaruwa, Polonnaruwa', 'polonnaruwa', 'Sri Lanka''s second ancient royal capital, active from the 10th to the 13th centuries. The vast, park-like archaeological site features marvelous preserved ruins, including the grand Royal Palace, massive stone stupas, and the famous Gal Vihara rock-cut Buddha statues.', 'North Central Province', 'Cultural', 'https://images.unsplash.com/photo-1709729508706-87741ec2d50a?w=600&h=400&fit=crop', 4.6, 780, 0),
('Nuwara Eliya', 'nuwara-eliya', 'Famous dubbed "Little England," this high-altitude mountain station was favored by British colonizers for its cool climate. It is the premier destination for exploring manicured green tea estates, sprawling colonial-era bungalows, and dramatic waterfalls.', 'Central Province', 'Mountain', 'https://images.unsplash.com/photo-1559038300-07cb5d6c3d27?w=600&h=400&fit=crop', 4.7, 900, 1),
('Mirissa, Matara', 'mirissa', 'A laid-back coastal paradise renowned as one of the best locations in the world for blue whale watching safaris. It is also widely visited for its crescent-shaped sandy beaches, vibrant beachside restaurants, and the iconic Coconut Turtle Hill viewpoint.', 'Southern Province', 'Beach', 'https://images.unsplash.com/photo-1734279135115-6d8984e08206?w=600&h=400&fit=crop', 4.9, 980, 1),
('Yala National Park', 'yala-national-park', 'Home to leopards, elephants and diverse wildlife. One of the most visited national parks in Sri Lanka, offering incredible safari experiences.', 'Southern Province', 'Wildlife', 'https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=600&h=400&fit=crop', 4.9, 1300, 1),
('Unawatuna', 'unawatuna', 'Tropical beach with clear waters and vibrant life. One of the most popular beach destinations in Sri Lanka with excellent snorkeling.', 'Southern Province', 'Beach', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&h=400&fit=crop', 4.7, 870, 1),
('Kandy', 'kandy', 'Cultural capital and home to the Sacred Tooth Relic. Nestled in the hills, Kandy is a UNESCO World Heritage site rich with history.', 'Central Province', 'Cultural', 'https://images.unsplash.com/photo-1586521995568-095a3c17fb89?w=600&h=400&fit=crop', 4.8, 1100, 1),
('Ella', 'ella', 'Scenic landscapes, hiking trails and breathtaking views. A charming hill country village famous for the Nine Arch Bridge and Little Adam''s Peak.', 'Uva Province', 'Adventure', 'https://images.unsplash.com/photo-1559038300-07cb5d6c3d27?w=600&h=400&fit=crop', 4.8, 1150, 1),
('Trincomalee', 'trincomalee', 'Pristine beaches and natural harbor on the east coast. Famous for whale watching, Pigeon Island, and beautiful coral reefs.', 'Eastern Province', 'Beach', 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=600&h=400&fit=crop', 4.6, 720, 0),
('Anuradhapura', 'anuradhapura', 'Ancient sacred city with well-preserved ruins of an entire civilization. One of the oldest continuously inhabited cities in the world.', 'North Central Province', 'Heritage', 'https://images.unsplash.com/photo-1588413949674-6c6e54ab6ea0?w=600&h=400&fit=crop', 4.7, 850, 0),
('Jaffna Fort & Cultural Heritage', 'jaffna', 'A historic Dutch colonial fortress in the heart of Jaffna, surrounded by a moat and ramparts. The city is rich in Hindu temples, palatial ruins, and a vibrant Tamil culture unlike anywhere else in Sri Lanka.', 'Northern Province', 'Heritage', 'https://images.unsplash.com/photo-1580674285054-bed31e145f59?w=600&h=400&fit=crop', 4.5, 560, 0),
('Dambulla Cave Temple', 'dambulla', 'A UNESCO World Heritage site featuring five magnificent cave temples filled with over 2,000 square meters of painted murals and 150+ Buddha statues. The largest and best-preserved cave temple complex in Sri Lanka.', 'North Western Province', 'Cultural', 'https://images.unsplash.com/photo-1590123579284-c39a2532363b?w=600&h=400&fit=crop', 4.7, 920, 1),
('Sinharaja Forest Reserve', 'sinharaja', 'Sri Lanka last viable area of primary tropical rainforest and a UNESCO World Heritage site. Home to an extraordinary diversity of endemic trees, insects, amphibians, and rare bird species including the Sri Lanka blue magpie.', 'Sabaragamuwa Province', 'Wildlife', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&h=400&fit=crop', 4.6, 680, 0),
('Colombo City Experience', 'colombo', 'The bustling commercial capital of Sri Lanka, blending colonial-era architecture with modern skyscrapers. Explore vibrant bazaars, the iconic Gangaramaya Temple, Galle Face Green, and world-class seafood restaurants.', 'Western Province', 'City', 'https://images.unsplash.com/photo-1570168007204-dfb528c6958f?w=600&h=400&fit=crop', 4.4, 430, 0);

-- =============================================================================
-- Seed: guides (6 rows)
-- Purpose: Sample tour guides with varied specialties covering all major
--   Sri Lanka regions and tour types.
-- =============================================================================
INSERT INTO guides (name, specialty, region, description, rating, languages, years_experience, image, profile_link, is_featured) VALUES
('Kasun Bandara', 'Hill Country & Tea Plantations', 'Central Highlands', 'Born in Nuwara Eliya, Kasun has over 15 years of experience guiding treks through Sri Lanka misty hill country. Passionate about tea culture and high-altitude flora.', 4.9, 'English, Sinhala, Tamil', 8, 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop&crop=face', '#', 1),
('Nipuni Silva', 'Cultural Heritage & Temples', 'Cultural Triangle', 'An archaeology enthusiast from Anuradhapura, Nipuni specializes in deep-dive cultural tours across the ancient cities and sacred sites of the cultural triangle.', 4.8, 'English, Sinhala', 6, 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&h=400&fit=crop&crop=face', '#', 0),
('Ravi Tennakoon', 'Wildlife & Safari', 'Southern Coast', 'An expert tracker and wildlife conservationist from Tissamaharama, Ravi leads transformative safari experiences in Yala and Bundala with minimal ecological impact.', 4.9, 'English, Sinhala, Tamil', 10, 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&h=400&fit=crop&crop=face', '#', 0),
('Malsha Fernando', 'Culinary Tours', 'Western Province', 'A culinary enthusiast from Colombo, Malsha brings travelers into local kitchens and street food markets, offering an authentic taste of Sri Lankan cuisine.', 4.7, 'English, Sinhala', 5, 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&h=400&fit=crop&crop=face', '#', 0),
('Tharaka Perera', 'Urban Exploration', 'Eastern Province', 'Tharaka uncovers the hidden cultural gems of Sri Lanka east, from Trincomalee beaches to Batticaloa lagoons, contrasting colonial history with modern island life.', 4.8, 'English, Sinhala', 7, 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop&crop=face', '#', 0),
('Dilini Jayasuriya', 'Marine & Diving', 'Southern Coast', 'A marine biologist turned dive guide from Mirissa, Dilini leads whale watching and scuba trips focused on marine conservation and reef education.', 4.9, 'English, Sinhala, Tamil', 9, 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&h=400&fit=crop&crop=face', '#', 0);

-- =============================================================================
-- Seed: transportations (10 rows)
-- Purpose: Vehicle rental options across Sri Lanka — tuk-tuks, sedans, SUVs,
--   bikes, and minivans from multiple rental providers.
-- =============================================================================
INSERT INTO transportations (name, slug, description, short_description, location, vehicle_type, price_per_day, rating, image, has_ac, has_driver, has_insurance, is_available, provider_name, provider_email, provider_phone, is_featured) VALUES
('Colombo City Tuk-Tuk', 'colombo-city-tuk-tuk', 'A lively three-wheeler perfect for navigating Colombo bustling streets. Ideal for short trips, market visits, and exploring the city compact alleys with an experienced local driver.', 'Zippy three-wheeler for quick Colombo city trips with local driver.', 'Colombo', 'Three-Wheeler', 2500.00, 4.5, 'https://images.unsplash.com/photo-1586521995568-095a3c17fb89?w=600&h=400&fit=crop', 0, 1, 1, 1, 'TukTuk Express', 'info@example.lk', '+94 77 000 0001', 1),
('Kandy Hill Country Tuk', 'kandy-hill-country-tuk', 'Explore the scenic hill roads of Kandy in this comfortable three-wheeler. Perfect for visits to the Temple of the Tooth and surrounding tea gardens.', 'Comfortable tuk for scenic Kandy hill road exploration.', 'Kandy', 'Three-Wheeler', 3000.00, 4.3, 'https://images.unsplash.com/photo-1586521995568-095a3c17fb89?w=600&h=400&fit=crop&crop=center', 0, 1, 1, 1, 'TukTuk Express', 'info@example.lk', '+94 77 000 0001', 0),
('Galle Fort Explorer Tuk', 'galle-fort-explorer-tuk', 'Cruise along the southern coast in this vibrant tuk-tuk. From Galle Fort to Unawatuna beach, experience the best of the southern coastline.', 'Vibrant tuk for southern coast and Galle Fort exploration.', 'Galle', 'Three-Wheeler', 3500.00, 4.7, 'https://images.unsplash.com/photo-1586521995568-095a3c17fb89?w=600&h=400&fit=crop&crop=bottom', 0, 1, 1, 1, 'TukTuk Express', 'info@example.lk', '+94 77 000 0001', 1),
('Premium Sedan - Colombo', 'premium-sedan-colombo', 'A sleek, air-conditioned sedan for comfortable city travel and airport transfers. Spacious interior with luggage space, ideal for business travelers and families.', 'AC sedan for comfortable Colombo city and airport transfers.', 'Colombo', 'Car', 8500.00, 4.8, 'https://images.unsplash.com/photo-1549317661-bd32c8ce0afa?w=600&h=400&fit=crop', 1, 1, 1, 1, 'Lanka Car Rentals', 'info@example.lk', '+94 11 000 0002', 1),
('Coastal Cruiser SUV', 'coastal-cruiser-suv', 'A robust SUV built for long-distance coastal drives. From Colombo to Galle, enjoy powerful AC, ample boot space, and a smooth ride on highway and beach roads.', 'Robust SUV for long-distance coastal highway drives.', 'Colombo', 'Car', 12000.00, 4.6, 'https://images.unsplash.com/photo-1549317661-bd32c8ce0afa?w=600&h=400&fit=crop&crop=right', 1, 1, 1, 1, 'Lanka Car Rentals', 'info@example.lk', '+94 11 000 0002', 1),
('Hill Country Adventure SUV', 'hill-country-adventure-suv', 'A rugged 4x4 SUV designed for mountain terrain. Perfect for winding roads to Nuwara Eliya, Ella, and Horton Plains. Expert local drivers who know every bend.', 'Rugged 4x4 SUV for Sri Lanka mountain terrain adventures.', 'Nuwara Eliya', 'Car', 14000.00, 4.7, 'https://images.unsplash.com/photo-1549317661-bd32c8ce0afa?w=600&h=400&fit=crop&crop=top', 1, 1, 1, 1, 'Lanka Car Rentals', 'info@example.lk', '+94 11 000 0002', 1),
('Sigiriya Trail Bike', 'sigiriya-trail-bike', 'A lightweight motorbike for adventurous solo travelers. Ride through paddy fields and villages to the majestic Sigiriya Rock Fortress. Helmet and insurance included.', 'Lightweight motorbike for Sigiriya trail adventures.', 'Sigiriya', 'Bike', 4500.00, 4.4, 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=600&h=400&fit=crop', 0, 0, 1, 1, 'Pedal Paradise', 'info@example.lk', '+94 77 000 0003', 0),
('Ella Gap Scenic Bike', 'ella-gap-scenic-bike', 'An off-road bike ready for the breathtaking Ella Gap. Navigate tea plantations and waterfalls with this well-maintained machine. Safety gear provided.', 'Off-road bike for breathtaking Ella Gap scenic routes.', 'Ella', 'Bike', 5000.00, 4.6, 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=600&h=400&fit=crop&crop=center', 0, 0, 1, 1, 'Pedal Paradise', 'info@example.lk', '+94 77 000 0003', 1),
('Family Minivan - 8 Seater', 'family-minivan-8-seater', 'A spacious 8-seater minivan perfect for family groups. Comfortable seating, large windows for sightseeing, and generous luggage space for multi-day tours.', 'Spacious 8-seater minivan for family group tours.', 'Colombo', 'Minivan', 15000.00, 4.8, 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=600&h=400&fit=crop', 1, 1, 1, 1, 'Lanka Van Services', 'info@example.lk', '+94 11 000 0004', 1),
('Airport Transfer Minivan', 'airport-transfer-minivan', 'Reliable minivan for airport pickups and drop-offs. Fits 6 passengers with full luggage. Meet-and-greet service with name board, water bottles, and Wi-Fi.', 'Reliable airport transfer minivan with meet-and-greet service.', 'Colombo', 'Minivan', 10000.00, 4.5, 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=600&h=400&fit=crop&crop=right', 1, 1, 1, 1, 'Lanka Van Services', 'info@example.lk', '+94 11 000 0004', 0);

-- =============================================================================
-- Seed: accommodations (6 rows)
-- Purpose: Sample lodging options across Sri Lanka — resorts, boutique inns,
--   villas, and a safari camp — from various hospitality providers.
-- =============================================================================
INSERT INTO accommodations (name, slug, description, short_description, location, property_type, price_per_night, rating, image, has_wifi, has_pool, has_spa, has_restaurant, has_fitness, provider_name, provider_email, provider_phone, is_featured) VALUES
('Coral Tide Resort', 'coral-tide-resort', 'A stunning beachfront resort overlooking the Indian Ocean in Unawatuna. Featuring private balconies, an infinity pool, and direct beach access. Perfect for romantic getaways and luxury retreats.', 'Beachfront luxury resort with infinity pool and ocean views in Unawatuna.', 'Unawatuna, Sri Lanka', 'Resort', 350.00, 4.9, 'https://images.unsplash.com/photo-1582268611958-ebfd161ef9cf?w=600&h=400&fit=crop', 1, 1, 1, 1, 1, 'Coral Tide Hospitality', 'info@example.lk', '+94 91 000 0005', 1),
('Hilltop Tea Lodge', 'hilltop-tea-lodge', 'A sustainable mountain lodge nestled among tea plantations in Hatton. Powered by solar energy, offering farm-to-table dining, guided tea estate walks, and panoramic mountain views from every room.', 'Sustainable mountain lodge with farm-to-table dining in the tea country.', 'Hatton, Sri Lanka', 'Boutique', 210.00, 4.7, 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&h=400&fit=crop', 1, 0, 0, 1, 0, 'Tea Country Retreats', 'info@example.lk', '+94 51 000 0006', 1),
('Colombo City Suites', 'colombo-city-suites', 'A sleek, modern hotel in the heart of Colombo. Clean lines, smart-room technology, and a rooftop bar with skyline views. Steps from Galle Face and top-rated restaurants.', 'Modern hotel in central Colombo with rooftop bar and skyline views.', 'Colombo, Sri Lanka', 'Hotel', 180.00, 4.5, 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop', 1, 0, 0, 1, 1, 'Urban Stay Lanka', 'info@example.lk', '+94 11 000 0007', 0),
('Lagoon Edge Villas', 'lagoon-edge-villas', 'An exclusive villa complex on the banks of the Bentota Lagoon. Private plunge pools, boat rides through mangroves, and Ayurveda spa treatments. The ultimate tropical escape.', 'Lagoon-side villas with private pools and Ayurveda spa in Bentota.', 'Bentota, Sri Lanka', 'Villa', 520.00, 4.8, 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&h=400&fit=crop', 1, 1, 1, 1, 1, 'Lagoon Edge Hospitality', 'info@example.lk', '+94 34 000 0008', 1),
('Heritage Boutique Inn', 'heritage-boutique-inn', 'A restored colonial-era boutique inn in the heart of Galle Fort. Antiques, courtyard garden, and personalized concierge service. Walk to cobblestone streets, cafes, and the ramparts.', 'Restored colonial inn in Galle Fort with courtyard garden.', 'Galle, Sri Lanka', 'Boutique', 135.00, 4.6, 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=600&h=400&fit=crop', 1, 0, 0, 1, 0, 'Heritage Inns Sri Lanka', 'info@example.lk', '+94 91 000 0009', 1),
('Safari Tented Camp', 'safari-tented-camp', 'Luxury tented accommodation on the edge of Yala National Park. Wake to wildlife sounds, enjoy bush dinners under the stars, and embark on guided safari drives at dawn.', 'Luxury tents on the edge of Yala National Park with safari drives.', 'Yala, Sri Lanka', 'Resort', 275.00, 4.7, 'https://images.unsplash.com/photo-1493246507139-91e8fad9978e?w=600&h=400&fit=crop', 1, 0, 0, 1, 0, 'Wild Safari Camps', 'info@example.lk', '+94 11 000 0010', 0);

-- =============================================================================
-- Seed: inquiries (3 rows)
-- Purpose: Sample customer support tickets demonstrating the inquiry lifecycle
--   across different statuses (under_review, waiting_for_response, resolved).
-- =============================================================================
INSERT INTO inquiries (user_id, package_id, inquiry_id_code, subject, message, status, created_at) VALUES
(1, 1, 'INQ-44521', 'Question about Island Escape package', 'Hello, I was wondering if the airport transfer is included in the premium package? We are arriving late at night and wanted to confirm transportation details before booking.', 'under_review', '2024-10-24 10:30:00'),
(1, 4, 'INQ-44102', 'Dietary requirements for Cultural Discovery tour', 'Our team has contacted the local vendors regarding a severe shellfish allergy. We are compiling a list of guaranteed safe alternatives for the street food portion of the itinerary...', 'waiting_for_response', '2024-10-18 14:15:00'),
(1, 3, 'INQ-43888', 'Group discount inquiry for 12 people', 'Hi, I am planning a corporate retreat for 12 people. Would you be able to offer a group discount for the Beach Paradise package? We are looking at dates in March.', 'resolved', '2024-10-05 09:00:00');

-- =============================================================================
-- Seed: inquiry_replies (5 rows)
-- Purpose: Sample threaded replies demonstrating staff ↔ user communication
--   on support tickets, including a multi-turn resolved conversation.
-- =============================================================================
INSERT INTO inquiry_replies (inquiry_id, sender_id, sender_role, message, created_at) VALUES
(1, NULL, 'admin', 'Thank you for reaching out! Airport transfers are included in all our premium packages. For late-night arrivals, we arrange a private pickup with a driver who will wait for you at the arrivals terminal. Would you like us to confirm the specific transfer details for your booking?', '2024-10-24 16:00:00'),
(2, NULL, 'admin', 'We have confirmed with all restaurant partners along the Cultural Discovery route. Each venue has been briefed on the shellfish allergy and will provide separate preparation areas. We will send you the detailed safe-dining guide within 24 hours.', '2024-10-19 11:30:00'),
(3, NULL, 'admin', 'Great news! For groups of 10 or more, we offer a 15% discount on the total package price. For 12 people, that would bring the per-person cost down significantly. Shall we prepare a formal quote for your corporate retreat?', '2024-10-06 08:45:00'),
(3, 1, 'user', 'That sounds perfect! Could you please send over the formal quote? We would also like to know if you can accommodate team-building activities as part of the package.', '2024-10-06 10:20:00'),
(3, NULL, 'admin', 'Absolutely! We can include team-building activities such as beach volleyball, cooking classes, and sunset boat tours. I will prepare a comprehensive quote with all options and send it to your email within 24 hours.', '2024-10-06 14:00:00');

-- =============================================================================
-- Seed: testimonials (6 rows)
-- Purpose: Customer reviews for packages — used on the public testimonials page.
--   Mix of featured and non-featured, all with 'approved' status.
-- =============================================================================
INSERT INTO testimonials (reviewer_name, reviewer_country, reviewer_avatar, rating, title, content, status, is_featured) VALUES
('Sarah Mitchell', 'United Kingdom', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face', 5, 'An Unforgettable Adventure!', 'The Island Escape package exceeded all our expectations. From the moment we landed, everything was perfectly orchestrated. The Sigiriya climb was breathtaking, and the local guides were incredibly knowledgeable. We will definitely book again!', 'approved', 1),
('Hans van der Berg', 'Netherlands', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face', 5, 'Perfect Honeymoon Trip', 'We chose the Cultural Discovery package for our honeymoon and it was magical. The ancient temples, the lush countryside, and the warm hospitality made it a trip of a lifetime. The team was responsive and accommodating throughout.', 'approved', 1),
('Yuki Tanaka', 'Japan', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face', 4, 'Great Value for Money', 'The Beach Paradise package was exactly what we needed. Beautiful beaches, comfortable accommodations, and plenty of activities. The only minor issue was a slight delay in transfer, but overall an excellent experience.', 'approved', 1),
('Marco Rossi', 'Italy', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop&crop=face', 5, 'Outstanding Safari Experience', 'The Wild Safari package was the highlight of our Sri Lanka trip. Seeing elephants and leopards in their natural habitat was incredible. Our guide Samir was exceptional - passionate, patient, and incredibly observant.', 'approved', 0),
('Emma Chen', 'Australia', 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&h=150&fit=crop&crop=face', 5, 'Dream Trip Come True', 'From the initial planning to the farewell dinner, every detail was taken care of. The Mountain Explorer package gave us stunning views and unforgettable memories. The team truly understands what travelers want.', 'approved', 0),
('James Wilson', 'Canada', 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&h=150&fit=crop&crop=face', 4, 'Professional and Caring Team', 'What sets GlobeTrek apart is their attention to detail. They arranged a surprise birthday celebration for my wife during our City Lights tour. That personal touch made all the difference. Highly recommended!', 'approved', 0);

-- =============================================================================
-- Seed: guide_reviews (5 rows)
-- Purpose: Customer reviews for individual guides — demonstrates the review
--   lifecycle including a pending review for moderation.
-- =============================================================================
INSERT INTO guide_reviews (user_id, guide_id, reviewer_name, reviewer_country, reviewer_avatar, rating, title, content, status) VALUES
(1, 1, 'Sarah Mitchell', 'United Kingdom', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face', 5, 'Kasun was amazing!', 'Kasun made our hill country trek absolutely unforgettable. His knowledge of tea plantations is incredible, and he shared stories that brought the landscape to life. Highly recommend him for any Nuwara Eliya adventure.', 'approved'),
(1, 3, 'Hans van der Berg', 'Netherlands', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face', 4, 'Great safari guide', 'Ravi is passionate about wildlife and it shows. He spotted animals we would have missed on our own. The only reason for 4 stars is that the vehicle could have been more comfortable, but that is not Ravis fault.', 'approved'),
(1, 2, 'Yuki Tanaka', 'Japan', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face', 5, 'Nipuni brought history to life', 'Our cultural triangle tour with Nipuni was exceptional. She has a deep understanding of archaeology and made every temple visit fascinating. Her enthusiasm is contagious.', 'approved'),
(1, 4, 'Emma Chen', 'Australia', 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&h=150&fit=crop&crop=face', 3, 'Decent culinary tour', 'Malsha knew the best street food spots, which was great. However, some of the kitchen visits felt rushed and we did not get enough hands-on cooking time as expected.', 'approved'),
(1, 6, 'Marco Rossi', 'Italy', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop&crop=face', 2, 'Disappointing dive experience', 'Dilini was knowledgeable about marine life, but the dive equipment was outdated and the boat trip was uncomfortable. We also waited over an hour for the boat to depart. Not what I expected for the price.', 'pending');
