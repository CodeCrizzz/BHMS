-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'tenant') DEFAULT 'tenant',
    room_assigned VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms Table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_no VARCHAR(20),
    price DECIMAL(10,2),
    status ENUM('available', 'occupied') DEFAULT 'available'
);

-- Messages (Chat)
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    message TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payments Table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    amount DECIMAL(10,2),
    description VARCHAR(255),
    status ENUM('pending', 'paid') DEFAULT 'pending',
    date_created DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE rooms ADD COLUMN capacity INT NOT NULL DEFAULT 1;

ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL;

-- Default Admin Account (Email: admin@local, Pass: admin123)
INSERT INTO users (fullname, email, password, role) 
VALUES ('Admin', 'admin@local', 'admin123', 'admin');

-- for Profile Enhancements
ALTER TABLE users 
ADD COLUMN dob DATE NULL,
ADD COLUMN gender ENUM('Male', 'Female', 'Other') NULL,
ADD COLUMN contact_number VARCHAR(20) NULL,
ADD COLUMN current_address TEXT NULL,
ADD COLUMN permanent_address TEXT NULL,
ADD COLUMN emergency_name VARCHAR(100) NULL,
ADD COLUMN emergency_relationship VARCHAR(50) NULL,
ADD COLUMN emergency_phone VARCHAR(20) NULL,
ADD COLUMN lease_start_date DATE DEFAULT CURRENT_DATE;

-- Add profile_image column
ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT 'default.png';

-- Table for Admin Announcements
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    message TEXT,
    date_posted TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for Tenant Requests (Maintenance/Complaints)
CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    request_type VARCHAR(100), -- e.g. Maintenance, Complaint
    description TEXT,
    status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert a dummy announcement so you can see it immediately
INSERT INTO announcements (title, message) 
VALUES ('Welcome to BHMS!', 'Rent is due on the 5th of every month. Please check your billing tab.');

---  Add image_path column to send images in chat messages
ALTER TABLE messages ADD COLUMN image_path VARCHAR(255) DEFAULT NULL;

-- To remove the image_path column if needed
ALTER TABLE messages DROP COLUMN image_path;