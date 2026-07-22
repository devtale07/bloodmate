-- Add username and password_hash fields to donors table (PostgreSQL version)
-- Note: Postgres doesn't support "AFTER column" placement - column order
-- doesn't matter functionally, so it's omitted here.

DO $$ BEGIN
    ALTER TABLE donors ADD COLUMN username VARCHAR(50) UNIQUE;
EXCEPTION WHEN duplicate_column THEN null; END $$;

DO $$ BEGIN
    ALTER TABLE donors ADD COLUMN password_hash VARCHAR(255);
EXCEPTION WHEN duplicate_column THEN null; END $$;

CREATE INDEX IF NOT EXISTS idx_donors_username ON donors(username);