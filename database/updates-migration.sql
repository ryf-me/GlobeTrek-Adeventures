USE globetrek;

-- Add provider columns to accommodations
ALTER TABLE accommodations
    ADD COLUMN provider_name VARCHAR(150) DEFAULT NULL AFTER has_fitness,
    ADD COLUMN provider_email VARCHAR(150) DEFAULT NULL AFTER provider_name,
    ADD COLUMN provider_phone VARCHAR(30) DEFAULT NULL AFTER provider_email;

-- Add provider columns to transportations
ALTER TABLE transportations
    ADD COLUMN provider_name VARCHAR(150) DEFAULT NULL AFTER has_insurance,
    ADD COLUMN provider_email VARCHAR(150) DEFAULT NULL AFTER provider_name,
    ADD COLUMN provider_phone VARCHAR(30) DEFAULT NULL AFTER provider_email;

-- Add notification_preferences to users
ALTER TABLE users
    ADD COLUMN notification_preferences JSON DEFAULT NULL AFTER role;

-- Update role enum to include staff
ALTER TABLE users
    MODIFY COLUMN role ENUM('user', 'staff', 'admin') DEFAULT 'user';

-- Create wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, package_id)
) ENGINE=InnoDB;

-- Create payments table
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

-- Create activity_logs table
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

-- Update inquiry_replies sender_role to include staff
ALTER TABLE inquiry_replies
    MODIFY COLUMN sender_role ENUM('user', 'staff', 'admin') NOT NULL DEFAULT 'user';

-- Seed sample provider data for accommodations
UPDATE accommodations SET
    provider_name = 'Azure Hospitality Group',
    provider_email = 'reservations@azurevillas.com',
    provider_phone = '+94 11 234 5678'
WHERE id = 1;

UPDATE accommodations SET
    provider_name = 'Alpine Lodges International',
    provider_email = 'bookings@alpinelodges.com',
    provider_phone = '+41 33 456 7890'
WHERE id = 2;

UPDATE accommodations SET
    provider_name = 'Urban Stay Co.',
    provider_email = 'info@urbanstay.jp',
    provider_phone = '+81 3 1234 5678'
WHERE id = 3;

UPDATE accommodations SET
    provider_name = 'Coral Bay Resorts',
    provider_email = 'reservations@coralbay.mv',
    provider_phone = '+960 400 1234'
WHERE id = 4;

UPDATE accommodations SET
    provider_name = 'Heritage Inns Sri Lanka',
    provider_email = 'stay@heritageinns.lk',
    provider_phone = '+94 91 234 5678'
WHERE id = 5;

UPDATE accommodations SET
    provider_name = 'Wild Safari Camps',
    provider_email = 'book@wildsafari.lk',
    provider_phone = '+94 11 987 6543'
WHERE id = 6;

-- Seed sample provider data for transportations
UPDATE transportations SET
    provider_name = 'TukTuk Express',
    provider_email = 'rent@tuktukexpress.lk',
    provider_phone = '+94 77 123 4567'
WHERE id IN (1, 2, 3);

UPDATE transportations SET
    provider_name = 'Lanka Car Rentals',
    provider_email = 'info@lankacars.lk',
    provider_phone = '+94 11 456 7890'
WHERE id IN (4, 5, 6);

UPDATE transportations SET
    provider_name = 'Pedal Paradise',
    provider_email = 'rent@pedalparadise.lk',
    provider_phone = '+94 77 987 6543'
WHERE id IN (7, 8);

UPDATE transportations SET
    provider_name = 'Lanka Van Services',
    provider_email = 'bookings@lankavans.lk',
    provider_phone = '+94 11 321 6540'
WHERE id IN (9, 10);
