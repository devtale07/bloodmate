-- BloodMate Database Schema (SQLite version)
-- SQLite-compatible schema for BloodMate

-- Create donors table
CREATE TABLE IF NOT EXISTS donors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    age INTEGER NOT NULL,
    blood_group TEXT NOT NULL CHECK(blood_group IN ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-')),
    phone TEXT NOT NULL,
    email TEXT UNIQUE,
    city TEXT NOT NULL,
    address TEXT,
    last_donation_date DATE,
    is_available INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create recipient_requests table
CREATE TABLE IF NOT EXISTS recipient_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    blood_group_required TEXT NOT NULL CHECK(blood_group_required IN ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-')),
    phone TEXT NOT NULL,
    email TEXT,
    hospital_location TEXT NOT NULL,
    urgency_level TEXT DEFAULT 'Medium' CHECK(urgency_level IN ('Low', 'Medium', 'High', 'Critical')),
    units_needed INTEGER DEFAULT 1,
    request_date DATE NOT NULL,
    status TEXT DEFAULT 'Pending' CHECK(status IN ('Pending', 'Approved', 'Fulfilled', 'Declined')),
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create blood_inventory table
CREATE TABLE IF NOT EXISTS blood_inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    blood_group TEXT NOT NULL UNIQUE CHECK(blood_group IN ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-')),
    units_available INTEGER DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create admin_users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    full_name TEXT NOT NULL,
    role TEXT DEFAULT 'Admin' CHECK(role IN ('Admin', 'Super Admin')),
    is_active INTEGER DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create users table for regular user authentication
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    full_name TEXT NOT NULL,
    phone TEXT,
    is_active INTEGER DEFAULT 1,
    is_verified INTEGER DEFAULT 0,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create donation_history table
CREATE TABLE IF NOT EXISTS donation_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    donor_id INTEGER,
    recipient_request_id INTEGER,
    donation_date DATE NOT NULL,
    units_donated INTEGER DEFAULT 1,
    blood_group TEXT NOT NULL CHECK(blood_group IN ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-')),
    hospital_location TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE SET NULL,
    FOREIGN KEY (recipient_request_id) REFERENCES recipient_requests(id) ON DELETE SET NULL
);

-- Create contact_messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    is_read INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert initial blood inventory data
INSERT OR IGNORE INTO blood_inventory (blood_group, units_available) VALUES
('A+', 25),
('A-', 15),
('B+', 30),
('B-', 12),
('AB+', 8),
('AB-', 5),
('O+', 40),
('O-', 20);

-- Insert default admin user (password: admin123 - CHANGE THIS after first login)
INSERT OR IGNORE INTO admin_users (username, password_hash, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@bloodmate.com', 'System Administrator', 'Super Admin');

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_donors_blood_group ON donors(blood_group);
CREATE INDEX IF NOT EXISTS idx_donors_city ON donors(city);
CREATE INDEX IF NOT EXISTS idx_donors_available ON donors(is_available);
CREATE INDEX IF NOT EXISTS idx_requests_blood_group ON recipient_requests(blood_group_required);
CREATE INDEX IF NOT EXISTS idx_requests_status ON recipient_requests(status);
CREATE INDEX IF NOT EXISTS idx_requests_urgency ON recipient_requests(urgency_level);
CREATE INDEX IF NOT EXISTS idx_donation_history_date ON donation_history(donation_date);

-- Create triggers for auto-updating timestamps
CREATE TRIGGER IF NOT EXISTS trg_donors_updated_at 
AFTER UPDATE ON donors
BEGIN
    UPDATE donors SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS trg_recipient_requests_updated_at 
AFTER UPDATE ON recipient_requests
BEGIN
    UPDATE recipient_requests SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS trg_blood_inventory_last_updated 
AFTER UPDATE ON blood_inventory
BEGIN
    UPDATE blood_inventory SET last_updated = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS trg_users_updated_at 
AFTER UPDATE ON users
BEGIN
    UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;
