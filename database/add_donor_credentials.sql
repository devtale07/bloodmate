-- Add username and password_hash fields to donors table
-- This enables donor login with email credentials

USE bloodmate;

ALTER TABLE donors 
ADD COLUMN username VARCHAR(50) UNIQUE AFTER email,
ADD COLUMN password_hash VARCHAR(255) AFTER username;

-- Create index for username for faster lookups
CREATE INDEX idx_donors_username ON donors(username);
