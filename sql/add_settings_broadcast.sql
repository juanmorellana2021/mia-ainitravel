-- =============================================================
-- Mia — Settings & Broadcast additions
-- Run once on mia_db
-- =============================================================

-- ── Add notification settings to mia_clients ─────────────────────────────────
ALTER TABLE mia_clients
    ADD COLUMN notify_email        VARCHAR(255) DEFAULT NULL       AFTER whatsapp_number,
    ADD COLUMN notify_on_capture   TINYINT(1)  NOT NULL DEFAULT 1  AFTER notify_email,
    ADD COLUMN notify_daily_summary TINYINT(1) NOT NULL DEFAULT 0  AFTER notify_on_capture;

-- ── Broadcast history ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mia_broadcast_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    client_id    INT  NOT NULL,
    message      TEXT NOT NULL,
    total_sent   INT  DEFAULT 0,
    total_failed INT  DEFAULT 0,
    recipients   TEXT,                             -- JSON array of phone numbers
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    FOREIGN KEY (client_id) REFERENCES mia_clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
