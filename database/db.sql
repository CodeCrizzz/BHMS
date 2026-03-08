-- Create Database (optional)
CREATE DATABASE IF NOT EXISTS bhms;
USE bhms;

-- =========================
-- 1. Rooms Table
-- =========================
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_no VARCHAR(20) NOT NULL UNIQUE,
    capacity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'occupied') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- 2. Users Table
-- =========================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'tenant') NOT NULL DEFAULT 'tenant',

    -- Room Relationship
    room_id INT NULL,

    -- Profile Info
    profile_image VARCHAR(255) DEFAULT 'default.png',
    dob DATE NULL,
    gender ENUM('Male', 'Female', 'Other') NULL,
    contact_number VARCHAR(20) NULL,
    current_address TEXT NULL,
    permanent_address TEXT NULL,

    -- Emergency Info
    emergency_name VARCHAR(100) NULL,
    emergency_relationship VARCHAR(50) NULL,
    emergency_phone VARCHAR(20) NULL,
    lease_start_date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE SET NULL
);

-- =========================
-- 3. Messages Table
-- =========================
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX (sender_id),
    INDEX (receiver_id)
);

-- =========================
-- 4. Payments Table
-- =========================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255),
    status ENUM('pending', 'paid') DEFAULT 'pending',
    date_created DATE DEFAULT CURRENT_DATE,

    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (tenant_id)
);

-- =========================
-- 5. Announcements Table
-- =========================
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    date_posted TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- 6. Requests Table
-- =========================
CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    request_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (tenant_id)
);

-- =========================
-- 7. Messages Table
-- =========================
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add is_read column to messages table
ALTER TABLE messages ADD COLUMN is_read TINYINT(1) DEFAULT 0;

-- =========================
-- Default Admin Account
-- =========================
INSERT INTO users (fullname, email, password, role)
VALUES ('Admin', 'admin@local', SHA2('admin123', 256), 'admin');

-- =========================
-- Sample Announcement
-- =========================
INSERT INTO announcements (title, message)
VALUES ('Welcome to BHMS!',
        'Rent is due on the 5th of every month. Please check your billing tab.');

ALTER TABLE users ADD COLUMN room_assigned VARCHAR(50) NULL;