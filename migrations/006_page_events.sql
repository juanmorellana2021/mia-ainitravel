-- migrations/006_page_events.sql
-- Landing page analytics tracking table
-- Run on VPS: mysql -u miauser -p mia_db < migrations/006_page_events.sql

CREATE TABLE IF NOT EXISTS `mia_page_events` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event`        VARCHAR(30)     NOT NULL COMMENT 'pageview | pageleave | cta_click',
    `session_id`   VARCHAR(64)     NOT NULL DEFAULT '' COMMENT 'client-generated visitor session UUID',
    `ip_hash`      VARCHAR(64)     NOT NULL DEFAULT '' COMMENT 'sha256 of IP for privacy',
    `page`         VARCHAR(255)    NOT NULL DEFAULT '',
    `referrer`     VARCHAR(500)    NOT NULL DEFAULT '',
    `utm_source`   VARCHAR(100)    NOT NULL DEFAULT '',
    `utm_medium`   VARCHAR(100)    NOT NULL DEFAULT '',
    `utm_campaign` VARCHAR(100)    NOT NULL DEFAULT '',
    `device`       VARCHAR(20)     NOT NULL DEFAULT '' COMMENT 'mobile | desktop | tablet',
    `duration_ms`  INT UNSIGNED    NOT NULL DEFAULT 0  COMMENT 'time-on-page for pageleave events',
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_event_date`   (`event`, `created_at`),
    KEY `idx_session`      (`session_id`),
    KEY `idx_created`      (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
