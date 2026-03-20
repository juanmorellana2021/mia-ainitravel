-- mia/migrations/004_client_photos.sql
-- Business photo gallery: up to 30 photos per client

CREATE TABLE IF NOT EXISTS mia_client_photos (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id  INT UNSIGNED NOT NULL,
    filename   VARCHAR(120) NOT NULL,
    caption    VARCHAR(200) NOT NULL DEFAULT '',
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
