-- Add profile_pic_url column to store the original WhatsApp CDN URL.
-- This URL contains a stable hash segment that persists even when a contact's
-- LID changes, allowing us to re-identify the same contact across sessions.

ALTER TABLE mia_client_leads
    ADD COLUMN profile_pic_url VARCHAR(600) DEFAULT NULL
    AFTER profile_pic;
