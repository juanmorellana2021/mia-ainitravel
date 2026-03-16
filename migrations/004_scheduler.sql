-- migrations/004_scheduler.sql
-- Mia Scheduler / Citas feature
-- Run on VPS: mysql -u miauser -p mia_db < migrations/004_scheduler.sql

-- Availability config (one record per client)
CREATE TABLE IF NOT EXISTS `mia_availability` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `client_id`      INT UNSIGNED NOT NULL,
    `slot_minutes`   SMALLINT     NOT NULL DEFAULT 60,
    `buffer_minutes` SMALLINT     NOT NULL DEFAULT 0,
    `max_days_ahead` TINYINT      NOT NULL DEFAULT 14,
    `timezone`       VARCHAR(64)  NOT NULL DEFAULT 'America/Lima',
    `schedule`       JSON         NOT NULL COMMENT 'object keyed by mon..sun with {enabled,open,close}',
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Individual appointment bookings
CREATE TABLE IF NOT EXISTS `mia_appointments` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `client_id`     INT UNSIGNED NOT NULL,
    `lead_id`       INT UNSIGNED NULL,
    `contact_name`  VARCHAR(120) NOT NULL DEFAULT '',
    `phone`         VARCHAR(30)  NOT NULL DEFAULT '',
    `starts_at`     DATETIME     NOT NULL,
    `ends_at`       DATETIME     NOT NULL,
    `notes`         TEXT,
    `status`        ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    `reminder_sent` TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_starts` (`client_id`, `starts_at`),
    KEY `idx_reminder`      (`reminder_sent`, `starts_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
