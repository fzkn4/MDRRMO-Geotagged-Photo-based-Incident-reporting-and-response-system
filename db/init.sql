-- Initial schema for Geotagged Incident Reporting System

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','client') NOT NULL DEFAULT 'client',
  full_name VARCHAR(255) DEFAULT NULL,
  organization VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  status ENUM('pending','approved','active','inactive') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial admin user will be created by running create-admin.php
-- Visit http://localhost/create-admin.php after database initialization
-- Or the admin can be created manually through the application


