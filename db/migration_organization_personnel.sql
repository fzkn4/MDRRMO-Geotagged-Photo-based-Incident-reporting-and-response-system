-- Migration: Create organization_personnel table
-- Run this to enable database storage for organization chart

CREATE TABLE IF NOT EXISTS organization_personnel (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  role VARCHAR(255) NOT NULL,
  is_ceo TINYINT(1) NOT NULL DEFAULT 0,
  reports_to INT UNSIGNED DEFAULT NULL,
  photo_data_url LONGTEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (reports_to) REFERENCES organization_personnel(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

