-- =============================================
-- Auth System Migration
-- Adds: email verification, password reset, OTP,
-- login attempts, and user account status columns
-- =============================================

USE globetrek;

-- 1. Add email_verified and is_active columns to users
ALTER TABLE users
  ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER role,
  ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER email_verified;

-- 2. Email verification tokens
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB;

-- 3. Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- 4. OTP codes (for registration, login 2FA, password reset)
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

-- 5. Login attempts (for per-account lockout)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_time (email, attempted_at)
) ENGINE=InnoDB;
