-- Initial schema for Geotagged Incident Reporting System

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','client') NOT NULL DEFAULT 'client',
  full_name VARCHAR(255) DEFAULT NULL,
  organization VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: seed an admin user placeholder (password must be set via app)
-- INSERT INTO users (username, email, password_hash, role, full_name, organization)
-- VALUES ('admin', 'admin@example.com', '$2y$10$replace_with_real_bcrypt_hash', 'admin', 'Administrator', 'MDRRMO');


