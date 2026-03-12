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
    public const PLAN_STARTER    = 139;
    public const PLAN_BASIC      = 299;
    public const PLAN_PRO        = 499;
    public const PLAN_ENTERPRISE       = 1199;
    public const PLAN_ENTERPRISE_DUO   = 1999;
    public const PLAN_ENTERPRISE_CHAIN = 3599;
    public const PLAN_ENTERPRISE_CORP  = 11999;
    public const SETUP_FEE             = 0;
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

    // ── Mercado Pago (mercadopago.com.pe) ─────────────────────────────────
    public const MP_PUBLIC_KEY    = 'APP_USR-4a2b9817-53ab-42da-b9f8-2e6b861b342e';
    public const MP_ACCESS_TOKEN  = 'APP_USR-3903199140335229-031021-7b10882587e251e0bba11f9a73c81412-465470205';

    // ── Admin credentials (hash in production) ────────────────────────────
    public const ADMIN_USER = 'admin';
    public const ADMIN_HASH = '$2y$10$MiaAdminDefaultHashChangeMe000000000000000000000';

    // ── Superadmin credentials ─────────────────────────────────────────────
    // Generate hash: php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
    public const SUPERADMIN_USER = 'juanmia';
    public const SUPERADMIN_HASH = '$2y$10$bT0KnFr.h19vF0TRWyw4w.uz.QtMuZ.29X9v1hhzh0PoLZUZ8jUT.';

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
