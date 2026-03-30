-- Migration 007: Clear remaining phone values that equal the lid column
-- Catches cases where LID length < 14 (e.g. 13-digit LIDs like 3715297763367)
UPDATE mia_client_leads
SET phone = ''
WHERE phone != ''
  AND lid != ''
  AND phone = lid
  AND phone NOT LIKE '120363%';
