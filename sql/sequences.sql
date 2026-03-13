-- ─────────────────────────────────────────────────────────────────────────
-- Follow-up Automation: Sequences, Steps, Lead Enrollments
-- Run once on VPS: mysql -u root mia_db < sequences.sql
-- ─────────────────────────────────────────────────────────────────────────

-- 1. Sequence definitions ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mia_sequences (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT          NOT NULL,
    name        VARCHAR(120) NOT NULL,
    `trigger`   VARCHAR(40)  NOT NULL DEFAULT 'manual',
    -- trigger values:
    --   manual        → client enrolls leads by hand
    --   on_new        → auto-enroll when lead is created
    --   on_interested → auto-enroll when lead moves to "interested"
    status      VARCHAR(20)  NOT NULL DEFAULT 'active',
    -- active | archived
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Steps inside each sequence ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mia_sequence_steps (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    sequence_id INT  NOT NULL,
    step_order  INT  NOT NULL DEFAULT 1,
    delay_days  INT  NOT NULL DEFAULT 1,
    -- days after PREVIOUS step fired (first step = days after enrollment)
    message     TEXT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_seq (sequence_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Per-lead enrollment ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mia_lead_sequences (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    lead_id      INT         NOT NULL,
    sequence_id  INT         NOT NULL,
    client_id    INT         NOT NULL,
    current_step INT         NOT NULL DEFAULT 0,
    -- 0 = just enrolled, increments after each step fires
    next_fire_at DATETIME    NOT NULL,
    status       VARCHAR(20) NOT NULL DEFAULT 'active',
    -- active | paused | completed | cancelled
    enrolled_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_lead_seq (lead_id, sequence_id),
    INDEX idx_fire (next_fire_at, status),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
