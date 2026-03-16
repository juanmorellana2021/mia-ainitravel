-- migrations/007_onboarding.sql
-- Add onboarding_done flag to mia_clients
-- Run on VPS: mysql -u miauser -p mia_db < migrations/007_onboarding.sql
-- Safe to run multiple times (ALTER TABLE will error if column already exists,
-- which is harmless — ClientService also applies it via ensureOnboardingColumn()).

ALTER TABLE mia_clients
    ADD COLUMN onboarding_done TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '0 = not yet onboarded, 1 = completed onboarding wizard'
    AFTER notify_daily_summary;
