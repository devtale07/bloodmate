-- BloodMate Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS bloodmate;
USE bloodmate;

-- Create donors table
CREATE TABLE donors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100) UNIQUE,
    city VARCHAR(50) NOT NULL,
    address TEXT,
    last_donation_date DATE,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE recipient_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    blood_group_required ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    hospital_location VARCHAR(200) NOT NULL,
    urgency_level ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    units_needed INT DEFAULT 1,
    request_date DATE NOT NULL,
    status ENUM('Pending', 'Approved', 'Fulfilled', 'Declined') DEFAULT 'Pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create blood_inventory table
CREATE TABLE blood_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL UNIQUE,
    units_available INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create admin_users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Super Admin') DEFAULT 'Admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create users table for regular user authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create donation_history table
CREATE TABLE donation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT,
    recipient_request_id INT,
    donation_date DATE NOT NULL,
    units_donated INT DEFAULT 1,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    hospital_location VARCHAR(200),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE SET NULL,
    FOREIGN KEY (recipient_request_id) REFERENCES recipient_requests(id) ON DELETE SET NULL
);

-- Create contact_messages table
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert initial blood inventory data
INSERT INTO blood_inventory (blood_group, units_available) VALUES
('A+', 25),
('A-', 15),
('B+', 30),
('B-', 12),
('AB+', 8),
('AB-', 5),
('O+', 40),
('O-', 20);

-- Insert default admin user (password: admin123 - should be changed in production)
INSERT INTO admin_users (username, password_hash, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@bloodmate.com', 'System Administrator', 'Super Admin');

-- Create indexes for better performance
CREATE INDEX idx_donors_blood_group ON donors(blood_group);
CREATE INDEX idx_donors_city ON donors(city);
CREATE INDEX idx_donors_available ON donors(is_available);
CREATE INDEX idx_requests_blood_group ON recipient_requests(blood_group_required);
CREATE INDEX idx_requests_status ON recipient_requests(status);
CREATE INDEX idx_requests_urgency ON recipient_requests(urgency_level);
CREATE INDEX idx_donation_history_date ON donation_history(donation_date);

-- Create views for common queries
CREATE VIEW available_donors AS
SELECT d.*, 
       DATEDIFF(CURDATE(), d.last_donation_date) AS days_since_last_donation
FROM donors d
WHERE d.is_available = TRUE 
  AND (d.last_donation_date IS NULL OR DATEDIFF(CURDATE(), d.last_donation_date) >= 56);

CREATE VIEW pending_requests AS
SELECT r.*, 
       DATEDIFF(CURDATE(), r.request_date) AS days_pending
FROM recipient_requests r
WHERE r.status = 'Pending'
ORDER BY r.urgency_level DESC, r.request_date ASC;

CREATE VIEW blood_compatibility AS
SELECT 
    'A+' as recipient_blood_group, 'A+,A-,O+,O-' as compatible_donors
UNION SELECT 'A-', 'A-,O-'
UNION SELECT 'B+', 'B+,B-,O+,O-'
UNION SELECT 'B-', 'B-,O-'
UNION SELECT 'AB+', 'A+,A-,B+,B-,AB+,AB-,O+,O-'
UNION SELECT 'AB-', 'A-,B-,AB-,O-'
UNION SELECT 'O+', 'O+,O-'
UNION SELECT 'O-', 'O-';
