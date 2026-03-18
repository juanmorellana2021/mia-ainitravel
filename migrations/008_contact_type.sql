-- Migration 008: contact_type on mia_client_leads
-- Lets clients mark contacts so Mia treats them differently:
--   lead      = normal sales bot (default)
--   friend    = casual chat, no sales pitch, no lead-capture sequences
--   staff     = silent ignore — Mia does not reply at all
--   proveedor = professional manager-assistant mode, no sales

ALTER TABLE mia_client_leads
    ADD COLUMN contact_type ENUM('lead','friend','staff','proveedor')
        NOT NULL DEFAULT 'lead'
    AFTER status;
