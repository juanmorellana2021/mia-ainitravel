-- Migration: Replace Stripe columns with Mercado Pago columns
-- Run on VPS: mysql -u root mia_db < migrations/003_mercadopago_columns.sql

-- ── mia_subscriptions: add MP columns ───────────────────────────────────
ALTER TABLE mia_subscriptions
    ADD COLUMN mp_preapproval_id VARCHAR(100) DEFAULT NULL AFTER status,
    ADD COLUMN mp_payer_email    VARCHAR(255) DEFAULT NULL AFTER mp_preapproval_id,
    ADD COLUMN mp_init_point     TEXT         DEFAULT NULL AFTER mp_payer_email,
    ADD INDEX idx_mp_preapproval_id (mp_preapproval_id);

-- ── Drop old Stripe columns (run separately, ignore errors if missing) ──
ALTER TABLE mia_subscriptions DROP COLUMN stripe_customer_id;
ALTER TABLE mia_subscriptions DROP COLUMN stripe_subscription_id;
ALTER TABLE mia_subscriptions DROP COLUMN stripe_session_id;
ALTER TABLE mia_clients DROP COLUMN stripe_customer_id;
