-- Migration 006: Clear LID values stored as phone field
-- Leads from WhatsApp ad-clicks get a LID (≥14 digits) as their initial identifier.
-- These should ONLY live in the `lid` column; `phone` must hold real dialable numbers.

-- Fix any lid column values still containing @lid suffix (should already be clean, but just in case)
UPDATE mia_client_leads
SET lid = REPLACE(lid, '@lid', '')
WHERE lid LIKE '%@lid';

-- Clear phone for leads where the phone IS a LID (≥14 digits, not a group chat 120363...)
-- Group chat IDs (120363...) are left as-is since that number IS their identity
UPDATE mia_client_leads
SET phone = ''
WHERE LENGTH(phone) >= 14
  AND phone NOT LIKE '120363%';
