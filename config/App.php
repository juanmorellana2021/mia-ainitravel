<?php
/**
 * mia/config/App.php
 *
 * Application-wide constants and settings.
 */

declare(strict_types=1);

class App
{
    // ── Branding ─────────────────────────────────────────────────────────
    public const NAME       = 'Mia by AiniTravel';
    public const TAGLINE    = 'Responde todos tus leads de WhatsApp automáticamente. 24/7.';
    public const URL        = 'https://mia.ainitravel.com';
    public const WHATSAPP   = '+51920076034';  // Mia WhatsApp Business number

    // ── Pricing (PEN — Soles) ────────────────────────────────────────────
    public const CURRENCY        = 'S/';
    public const PLAN_BASIC      = 399;
    public const PLAN_PRO        = 699;
    public const PLAN_ENTERPRISE = 1199;
    public const SETUP_FEE       = 500;
    public const FREE_TRIAL_DAYS = 7;

    // ── Asset base path (auto-detect local vs subdomain) ─────────────────
    public static function basePath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (str_contains($uri, '/mia/')) {
            return '/mia';
        }
        return '';  // Subdomain root
    }

    public static function asset(string $path): string
    {
        return self::basePath() . '/assets/' . ltrim($path, '/');
    }

    // ── Stripe (fill in your keys from dashboard.stripe.com) ─────────────
    public const STRIPE_SECRET          = 'sk_placeholder_add_your_key_here';
    public const STRIPE_WEBHOOK         = 'whsec_placeholder_add_webhook_secret';
    public const STRIPE_PRICE_BASIC     = 'price_placeholder_basic';
    public const STRIPE_PRICE_PRO       = 'price_placeholder_pro';
    public const STRIPE_PRICE_ENTERPRISE = 'price_placeholder_enterprise';

    // ── Admin credentials (hash in production) ────────────────────────────
    public const ADMIN_USER = 'admin';
    public const ADMIN_HASH = '$2y$10$MiaAdminDefaultHashChangeMe000000000000000000000';

    // ── CSRF helpers ─────────────────────────────────────────────────────
    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function csrfVerify(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!isset($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $token)) {
            http_response_code(403);
            die('<h1>403 Forbidden</h1><p>Token de seguridad inválido. Vuelve atrás e inténtalo de nuevo.</p>');
        }
    }
}
