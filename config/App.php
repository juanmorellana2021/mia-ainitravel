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
    public const WHATSAPP   = '+51XXXXXXXXX';  // Sales demo number

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

    // ── Admin credentials (hash in production) ────────────────────────────
    public const ADMIN_USER = 'admin';
    public const ADMIN_HASH = '$2y$10$MiaAdminDefaultHashChangeMe000000000000000000000';
}
