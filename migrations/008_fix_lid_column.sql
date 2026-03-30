-- Migration 008: Fix lid column for non-LID contacts
-- The lid column was being populated for ALL contacts (including @c.us),
-- not just @lid contacts. Clear lid where it equals the phone number
-- (those are real phone contacts, not LID contacts).
-- Real LID contacts have empty phone and only a lid value.

-- Clear lid for contacts where lid = phone (these are regular @c.us contacts
-- whose phone was incorrectly copied to the lid column)
UPDATE mia_client_leads
SET lid = NULL
WHERE lid IS NOT NULL
  AND phone != ''
  AND REPLACE(phone, '+', '') = REPLACE(lid, '+', '');

-- Also restore phone for María Magdalena (lead 99) — 233397716824104 is a LID,
-- not a real phone (15 digits). Phone should be empty, lid stays.
UPDATE mia_client_leads
SET phone = ''
WHERE id = 99 AND LENGTH(phone) >= 14;
