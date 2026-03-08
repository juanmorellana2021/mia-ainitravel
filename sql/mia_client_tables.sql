-- =============================================================
-- Mia Client Portal — Database Tables
-- Run once on the hotel_booking_system database
-- =============================================================

SET NAMES utf8mb4;

-- ── 1. Business clients (Mia's paying customers) ─────────────────────────────
CREATE TABLE IF NOT EXISTS mia_clients (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    business_name       VARCHAR(255) NOT NULL,
    contact_name        VARCHAR(255) NOT NULL,
    email               VARCHAR(255) NOT NULL,
    password_hash       VARCHAR(255) NOT NULL,
    phone               VARCHAR(50)  DEFAULT '',
    business_type       VARCHAR(50)  DEFAULT 'other',        -- hotel, agency, operator, other
    whatsapp_number     VARCHAR(50)  DEFAULT '',             -- the WhatsApp number Mia manages for them
    plan                VARCHAR(20)  DEFAULT 'trial',        -- trial, basic, pro, enterprise
    plan_status         VARCHAR(20)  DEFAULT 'trial',        -- trial, active, suspended, cancelled
    stripe_customer_id  VARCHAR(100) DEFAULT NULL,
    trial_ends_at       DATETIME     DEFAULT NULL,
    created_at          DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Leads managed by each client's Mia bot ────────────────────────────────
CREATE TABLE IF NOT EXISTS mia_client_leads (
    id              INT           AUTO_INCREMENT PRIMARY KEY,
    client_id       INT           NOT NULL,
    contact_name    VARCHAR(255)  DEFAULT '',
    phone           VARCHAR(50)   NOT NULL,
    source          VARCHAR(50)   DEFAULT 'whatsapp',   -- facebook, whatsapp, instagram, website
    status          VARCHAR(30)   DEFAULT 'new',        -- new, interested, demo, closed_won, closed_lost
    value_estimate  DECIMAL(10,2) DEFAULT 0.00,
    notes           TEXT,
    created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    FOREIGN KEY (client_id) REFERENCES mia_clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. WhatsApp message thread per client / lead ──────────────────────────────
CREATE TABLE IF NOT EXISTS mia_client_messages (
    id          INT  AUTO_INCREMENT PRIMARY KEY,
    client_id   INT  NOT NULL,
    lead_id     INT  DEFAULT NULL,
    phone       VARCHAR(50)  NOT NULL,
    direction   ENUM('inbound','outbound') NOT NULL,
    message     TEXT NOT NULL,
    handled_by  VARCHAR(20)  DEFAULT 'mia',   -- mia, human
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_lead   (lead_id),
    FOREIGN KEY (client_id) REFERENCES mia_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id)   REFERENCES mia_client_leads(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Subscription / payment records ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mia_subscriptions (
    id                      INT  AUTO_INCREMENT PRIMARY KEY,
    client_id               INT  NOT NULL,
    plan                    VARCHAR(20)  NOT NULL,
    amount_cents            INT  NOT NULL,
    currency                VARCHAR(5)   DEFAULT 'PEN',
    status                  VARCHAR(20)  DEFAULT 'pending',  -- pending, active, failed, cancelled
    stripe_customer_id      VARCHAR(100) DEFAULT NULL,
    stripe_subscription_id  VARCHAR(100) DEFAULT NULL,
    stripe_session_id       VARCHAR(100) DEFAULT NULL,
    billing_period_start    DATETIME     DEFAULT NULL,
    billing_period_end      DATETIME     DEFAULT NULL,
    paid_at                 DATETIME     DEFAULT NULL,
    created_at              DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    FOREIGN KEY (client_id) REFERENCES mia_clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
