-- BloodMate Database Schema (PostgreSQL version)
-- Note: the target database itself is created by Render's dashboard,
-- not by this script. Connect directly to it before running this file.

-- Enum types (Postgres doesn't support inline ENUM columns like MySQL)
DO $$ BEGIN
    CREATE TYPE blood_group_enum AS ENUM ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-');
EXCEPTION WHEN duplicate_object THEN null; END $$;

DO $$ BEGIN
    CREATE TYPE urgency_level_enum AS ENUM ('Low', 'Medium', 'High', 'Critical');
EXCEPTION WHEN duplicate_object THEN null; END $$;

DO $$ BEGIN
    CREATE TYPE request_status_enum AS ENUM ('Pending', 'Approved', 'Fulfilled', 'Declined');
EXCEPTION WHEN duplicate_object THEN null; END $$;

DO $$ BEGIN
    CREATE TYPE admin_role_enum AS ENUM ('Admin', 'Super Admin');
EXCEPTION WHEN duplicate_object THEN null; END $$;

-- Generic trigger function to auto-update an "updated_at" column
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Same, but for blood_inventory's "last_updated" column
CREATE OR REPLACE FUNCTION set_last_updated() RETURNS TRIGGER AS $$
BEGIN
    NEW.last_updated = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create donors table
CREATE TABLE IF NOT EXISTS donors (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    blood_group blood_group_enum NOT NULL,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100) UNIQUE,
    city VARCHAR(50) NOT NULL,
    address TEXT,
    last_donation_date DATE,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TRIGGER IF EXISTS trg_donors_updated_at ON donors;
CREATE TRIGGER trg_donors_updated_at BEFORE UPDATE ON donors
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Create recipient_requests table
CREATE TABLE IF NOT EXISTS recipient_requests (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    blood_group_required blood_group_enum NOT NULL,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    hospital_location VARCHAR(200) NOT NULL,
    urgency_level urgency_level_enum DEFAULT 'Medium',
    units_needed INT DEFAULT 1,
    request_date DATE NOT NULL,
    status request_status_enum DEFAULT 'Pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TRIGGER IF EXISTS trg_recipient_requests_updated_at ON recipient_requests;
CREATE TRIGGER trg_recipient_requests_updated_at BEFORE UPDATE ON recipient_requests
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Create blood_inventory table
CREATE TABLE IF NOT EXISTS blood_inventory (
    id SERIAL PRIMARY KEY,
    blood_group blood_group_enum NOT NULL UNIQUE,
    units_available INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TRIGGER IF EXISTS trg_blood_inventory_last_updated ON blood_inventory;
CREATE TRIGGER trg_blood_inventory_last_updated BEFORE UPDATE ON blood_inventory
    FOR EACH ROW EXECUTE FUNCTION set_last_updated();

-- Create admin_users table
CREATE TABLE IF NOT EXISTS admin_users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role admin_role_enum DEFAULT 'Admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create users table for regular user authentication
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TRIGGER IF EXISTS trg_users_updated_at ON users;
CREATE TRIGGER trg_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Create donation_history table
CREATE TABLE IF NOT EXISTS donation_history (
    id SERIAL PRIMARY KEY,
    donor_id INT,
    recipient_request_id INT,
    donation_date DATE NOT NULL,
    units_donated INT DEFAULT 1,
    blood_group blood_group_enum NOT NULL,
    hospital_location VARCHAR(200),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE SET NULL,
    FOREIGN KEY (recipient_request_id) REFERENCES recipient_requests(id) ON DELETE SET NULL
);

-- Create contact_messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert initial blood inventory data (skip if already present)
INSERT INTO blood_inventory (blood_group, units_available)
SELECT * FROM (VALUES
    ('A+'::blood_group_enum, 25),
    ('A-'::blood_group_enum, 15),
    ('B+'::blood_group_enum, 30),
    ('B-'::blood_group_enum, 12),
    ('AB+'::blood_group_enum, 8),
    ('AB-'::blood_group_enum, 5),
    ('O+'::blood_group_enum, 40),
    ('O-'::blood_group_enum, 20)
) AS v(blood_group, units_available)
WHERE NOT EXISTS (SELECT 1 FROM blood_inventory WHERE blood_inventory.blood_group = v.blood_group);

-- Insert default admin user (password: admin123 - CHANGE THIS after first login)
INSERT INTO admin_users (username, password_hash, email, full_name, role)
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@bloodmate.com', 'System Administrator', 'Super Admin'
WHERE NOT EXISTS (SELECT 1 FROM admin_users WHERE username = 'admin');

-- Indexes
CREATE INDEX IF NOT EXISTS idx_donors_blood_group ON donors(blood_group);
CREATE INDEX IF NOT EXISTS idx_donors_city ON donors(city);
CREATE INDEX IF NOT EXISTS idx_donors_available ON donors(is_available);
CREATE INDEX IF NOT EXISTS idx_requests_blood_group ON recipient_requests(blood_group_required);
CREATE INDEX IF NOT EXISTS idx_requests_status ON recipient_requests(status);
CREATE INDEX IF NOT EXISTS idx_requests_urgency ON recipient_requests(urgency_level);
CREATE INDEX IF NOT EXISTS idx_donation_history_date ON donation_history(donation_date);

-- Views for common queries
CREATE OR REPLACE VIEW available_donors AS
SELECT d.*,
       (CURRENT_DATE - d.last_donation_date) AS days_since_last_donation
FROM donors d
WHERE d.is_available = TRUE
  AND (d.last_donation_date IS NULL OR (CURRENT_DATE - d.last_donation_date) >= 56);

CREATE OR REPLACE VIEW pending_requests AS
SELECT r.*,
       (CURRENT_DATE - r.request_date) AS days_pending
FROM recipient_requests r
WHERE r.status = 'Pending'
ORDER BY r.urgency_level DESC, r.request_date ASC;

CREATE OR REPLACE VIEW blood_compatibility AS
SELECT 'A+' AS recipient_blood_group, 'A+,A-,O+,O-' AS compatible_donors
UNION SELECT 'A-', 'A-,O-'
UNION SELECT 'B+', 'B+,B-,O+,O-'
UNION SELECT 'B-', 'B-,O-'
UNION SELECT 'AB+', 'A+,A-,B+,B-,AB+,AB-,O+,O-'
UNION SELECT 'AB-', 'A-,B-,AB-,O-'
UNION SELECT 'O+', 'O+,O-'
UNION SELECT 'O-', 'O-';