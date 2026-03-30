-- Store the WhatsApp LID (internal ≥14-digit identifier) on each lead.
-- Allows direct lookup when contact.number returns a LID instead of a real phone,
-- without relying on name or profile-pic heuristics.

ALTER TABLE mia_client_leads
    ADD COLUMN lid VARCHAR(30) DEFAULT NULL
    AFTER phone;

CREATE INDEX idx_client_leads_lid ON mia_client_leads (client_id, lid);
