CREATE DATABASE IF NOT EXISTS globetrek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE globetrek;

-- Users table
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
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Packages table
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

-- Destinations table
CREATE TABLE IF NOT EXISTS destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(500),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Guides table
CREATE TABLE IF NOT EXISTS guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    specialty VARCHAR(200),
    region VARCHAR(100),
    description TEXT,
    image VARCHAR(500),
    profile_link VARCHAR(500) DEFAULT '#',
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bookings table
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

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Accommodations table
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
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Transportations table
CREATE TABLE IF NOT EXISTS transportations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(255),
    location VARCHAR(200) NOT NULL,
    vehicle_type ENUM('Three-Wheeler', 'Car', 'Bike', 'Minivan') NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL,
    rating DECIMAL(2,1) DEFAULT 0,
    image VARCHAR(500),
    has_ac TINYINT(1) DEFAULT 0,
    has_driver TINYINT(1) DEFAULT 0,
    has_insurance TINYINT(1) DEFAULT 0,
    is_available TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Newsletter subscriptions table
CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Inquiries table
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

-- Inquiry replies table
CREATE TABLE IF NOT EXISTS inquiry_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inquiry_id INT NOT NULL,
    sender_id INT DEFAULT NULL,
    sender_role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inquiry_id) REFERENCES inquiries(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Custom trip requests table
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
