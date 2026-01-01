-- Migration: Create incidents table
CREATE TABLE IF NOT EXISTS incidents (
    id VARCHAR(255) NOT NULL,
    type VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'New',
    reported_by VARCHAR(255) NOT NULL,
    severity VARCHAR(50) DEFAULT NULL,
    lat DECIMAL(10, 8) DEFAULT NULL,
    lng DECIMAL(11, 8) DEFAULT NULL,
    photo_data_url LONGTEXT DEFAULT NULL,
    created_at BIGINT NOT NULL,
    updated_at BIGINT DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_reported_by (reported_by),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;






