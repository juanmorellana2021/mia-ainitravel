-- mia/migrations/005_addons.sql
-- Add-on credits: extra conversations or unlimited-month purchased à la carte
-- External reference format for MP: "addon-{clientId}-{typeCode}-{YYYYMM}"
--   typeCode: e500 = extra_500 | ulm = unlimited_month | notice = limit noticed sent (no payment)

CREATE TABLE IF NOT EXISTS mia_client_addons (
    id               INT          AUTO_INCREMENT PRIMARY KEY,
    client_id        INT          NOT NULL,
    type             ENUM('extra_500','unlimited_month','notice') NOT NULL,
    month_year       VARCHAR(7)   NOT NULL COMMENT 'YYYY-MM this addon applies to',
    status           ENUM('pending','active') DEFAULT 'pending',
    amount_paid      DECIMAL(10,2) DEFAULT 0.00,
    mp_preference_id VARCHAR(120) NULL,
    mp_payment_id    VARCHAR(120) NULL,
    created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client      (client_id),
    INDEX idx_client_month(client_id, month_year, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
