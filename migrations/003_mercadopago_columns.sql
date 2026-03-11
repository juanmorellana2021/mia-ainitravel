-- Migration: Replace Stripe columns with Mercado Pago columns
-- Run on VPS: mysql -u root mia_db < migrations/003_mercadopago_columns.sql

-- ── mia_subscriptions: drop Stripe, add MP ──────────────────────────────
ALTER TABLE mia_subscriptions
    DROP COLUMN IF EXISTS stripe_customer_id,
    DROP COLUMN IF EXISTS stripe_subscription_id,
    DROP COLUMN IF EXISTS stripe_session_id;

ALTER TABLE mia_subscriptions
    ADD COLUMN mp_preapproval_id VARCHAR(100) DEFAULT NULL AFTER status,
    ADD COLUMN mp_payer_email    VARCHAR(255) DEFAULT NULL AFTER mp_preapproval_id,
    ADD COLUMN mp_init_point     TEXT         DEFAULT NULL AFTER mp_payer_email;

ALTER TABLE mia_subscriptions
    ADD INDEX idx_mp_preapproval_id (mp_preapproval_id);

-- ── mia_clients: drop unused stripe_customer_id ─────────────────────────
ALTER TABLE mia_clients
    DROP COLUMN IF EXISTS stripe_customer_id;
