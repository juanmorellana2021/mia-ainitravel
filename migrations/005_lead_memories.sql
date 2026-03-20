-- Migration 005: Lead memories for persistent AI context
-- Stores AI-extracted facts about each contact (by phone) so Mia remembers them across conversations.
-- Phone is the primary key because it's the persistent identifier in WhatsApp — lead IDs can change.

CREATE TABLE IF NOT EXISTS mia_lead_memories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    phone       VARCHAR(50) NOT NULL,
    fact        VARCHAR(500) NOT NULL,
    source      ENUM('ai','manual') NOT NULL DEFAULT 'ai',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_client_phone (client_id, phone),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
