-- Migration: Add status and phone fields to users table
-- Run this if you have an existing database
-- Note: If columns already exist, you may need to remove the ADD COLUMN lines manually

ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER organization;

ALTER TABLE users 
ADD COLUMN status ENUM('pending','approved','active','inactive') NOT NULL DEFAULT 'pending' AFTER phone;

-- Update existing users to 'approved' status (so they can still log in)
UPDATE users SET status = 'approved' WHERE status = 'pending';
