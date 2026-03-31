-- Migration 011: Human takeover / bot pause
-- When a human replies from the dashboard, the bot is paused for 2 hours on that lead.
ALTER TABLE mia_client_leads
    ADD COLUMN bot_paused_until DATETIME NULL DEFAULT NULL;
