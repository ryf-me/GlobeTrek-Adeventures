-- Reviews System Migration
-- Run this SQL to add user-linked reviews with approval workflow

USE globetrek;

-- Add user_id column (nullable for backward compat with existing seeded data)
ALTER TABLE testimonials
    ADD COLUMN user_id INT DEFAULT NULL AFTER id;

ALTER TABLE testimonials
    ADD CONSTRAINT fk_testimonials_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Add package_id column for package-specific reviews (nullable)
ALTER TABLE testimonials
    ADD COLUMN package_id INT DEFAULT NULL AFTER user_id;

ALTER TABLE testimonials
    ADD CONSTRAINT fk_testimonials_package
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL;

-- Replace is_approved with status ENUM
ALTER TABLE testimonials
    ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER content;

-- Migrate existing data: set status based on is_approved
UPDATE testimonials SET status = 'approved' WHERE is_approved = 1;
UPDATE testimonials SET status = 'pending' WHERE is_approved = 0;

-- Drop old is_approved column
ALTER TABLE testimonials DROP COLUMN is_approved;

-- Indexes for performance
CREATE INDEX idx_testimonials_status ON testimonials(status);
CREATE INDEX idx_testimonials_user ON testimonials(user_id);
CREATE INDEX idx_testimonials_package ON testimonials(package_id);
