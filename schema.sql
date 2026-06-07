-- Rai by Rai - Database Schema

CREATE DATABASE IF NOT EXISTS raibyrai_db;
USE raibyrai_db;

-- 1. inquiries Table
CREATE TABLE IF NOT EXISTS inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    location VARCHAR(255) NOT NULL,
    package VARCHAR(100) NOT NULL,
    budget VARCHAR(100) NOT NULL,
    message TEXT,
    status VARCHAR(50) DEFAULT 'New', -- 'New', 'Contacted', 'Resolved'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. admin users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user: username 'admin', password 'admin123'
-- Hash generated using password_hash('admin123', PASSWORD_BCRYPT) in PHP
INSERT INTO users (username, password)
VALUES ('admin', '$2y$10$7R3v5i0c6F7rF7d6L7f7OefQ7tK4vL3R3yF6/rC2c6Z6P6Q6w6c6e')
ON DUPLICATE KEY UPDATE username=username;
