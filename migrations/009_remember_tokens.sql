-- migrations/008_remember_tokens.sql
-- Persistent "Remember Me" login tokens for client accounts.
-- Only SHA-256 hashes are stored — never the raw token.

CREATE TABLE IF NOT EXISTS mia_remember_tokens (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id  INT UNSIGNED NOT NULL,
    token_hash CHAR(64)     NOT NULL COMMENT 'SHA-256 of the raw 32-byte hex cookie token',
    expires_at DATETIME     NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token  (token_hash),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
