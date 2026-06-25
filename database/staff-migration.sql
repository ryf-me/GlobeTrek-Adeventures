-- Staff Management System Migration
-- Run this SQL to add staff management tables to the GlobeTrek database

USE globetrek;

-- Staff profiles table (extends users for staff role)
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

-- Granular permissions for staff (beyond department defaults)
CREATE TABLE IF NOT EXISTS staff_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    permission VARCHAR(100) NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_permission (staff_id, permission)
) ENGINE=InnoDB;

-- Staff assignments to bookings/inquiries
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

-- Indexes for performance
CREATE INDEX idx_staff_profiles_department ON staff_profiles(department);
CREATE INDEX idx_staff_profiles_available ON staff_profiles(is_available);
CREATE INDEX idx_staff_assignments_entity ON staff_assignments(entity_type, entity_id);
CREATE INDEX idx_staff_assignments_staff ON staff_assignments(staff_id);
