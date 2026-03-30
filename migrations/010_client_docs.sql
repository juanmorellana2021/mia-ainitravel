-- mia/migrations/010_client_docs.sql
-- Client document library: up to 50 documents per client.
-- Clients upload PDFs, Word, Excel, PowerPoint files.
-- The AI has access to these URLs and sends them as WhatsApp document attachments.

CREATE TABLE IF NOT EXISTS mia_client_docs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id     INT UNSIGNED NOT NULL,
    filename      VARCHAR(160) NOT NULL,       -- safe server-generated name
    original_name VARCHAR(255) NOT NULL DEFAULT '',  -- user's original filename
    doc_name      VARCHAR(150) NOT NULL DEFAULT '',  -- editable display name
    description   VARCHAR(500) NOT NULL DEFAULT '',
    file_type     ENUM('pdf','docx','xlsx','pptx') NOT NULL DEFAULT 'pdf',
    file_size     INT UNSIGNED NOT NULL DEFAULT 0,   -- bytes
    sort_order    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
