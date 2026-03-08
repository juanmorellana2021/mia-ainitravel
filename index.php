<?php
/**
 * mia/index.php — Front Controller
 *
 * Routes all requests through a single entry point.
 * Maps URI paths to Controller actions (MVC).
 *
 * URL: mia.ainitravel.com  (or /mia/ locally)
 */

declare(strict_types=1);

session_start();

// ── Autoload ─────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/App.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Lead.php';
require_once __DIR__ . '/models/SalesSession.php';
require_once __DIR__ . '/services/LeadService.php';
require_once __DIR__ . '/services/MiaSalesService.php';
require_once __DIR__ . '/models/Client.php';
require_once __DIR__ . '/models/ClientLead.php';
require_once __DIR__ . '/models/ClientMessage.php';
require_once __DIR__ . '/models/Subscription.php';
require_once __DIR__ . '/services/ClientService.php';
require_once __DIR__ . '/services/ClientLeadService.php';
require_once __DIR__ . '/services/BillingService.php';
require_once __DIR__ . '/controllers/PageController.php';
require_once __DIR__ . '/controllers/LeadController.php';
require_once __DIR__ . '/controllers/ApiController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/BillingController.php';

// ── Route resolution ─────────────────────────────────────────────────────────
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Strip the base path (handles both /mia/ local dev and subdomain root)
$basePaths = ['mia'];
foreach ($basePaths as $base) {
    if (str_starts_with($uri, $base)) {
        $uri = trim(substr($uri, strlen($base)), '/');
        break;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

// ── Route table ──────────────────────────────────────────────────────────────
match (true) {
    // Landing pages
    $uri === '' || $uri === 'index'
        => (new PageController())->landing(),

    $uri === 'pricing'
        => (new PageController())->pricing(),

    $uri === 'demo'
        => (new PageController())->demo(),

    $uri === 'features'
        => (new PageController())->features(),

    // WhatsApp bot API (Mia sales conversations)
    $uri === 'api/chat' && $method === 'POST'
        => (new ApiController())->chat(),

    // ── Client auth ───────────────────────────────────────────────────────
    $uri === 'login' && $method === 'GET'
        => (new AuthController())->loginForm(),

    $uri === 'login' && $method === 'POST'
        => (new AuthController())->loginSubmit(),

    $uri === 'register' && $method === 'GET'
        => (new AuthController())->registerForm(),

    $uri === 'register' && $method === 'POST'
        => (new AuthController())->registerSubmit(),

    $uri === 'logout'
        => (new AuthController())->logout(),

    // ── Client dashboard ─────────────────────────────────────────────────
    $uri === 'dashboard'
        => (new DashboardController())->index(),

    $uri === 'dashboard/leads' && $method === 'GET'
        => (new DashboardController())->leads(),

    str_starts_with($uri, 'dashboard/leads/') && $method === 'GET'
        => (new DashboardController())->leadDetail((int)basename($uri)),

    str_starts_with($uri, 'dashboard/leads/') && $method === 'POST'
        => (new DashboardController())->leadUpdate((int)basename($uri)),

    $uri === 'dashboard/messages'
        => (new DashboardController())->messages(),

    // ── Billing ───────────────────────────────────────────────────────────
    $uri === 'dashboard/billing' && $method === 'GET'
        => (new BillingController())->index(),

    $uri === 'dashboard/billing/subscribe' && $method === 'POST'
        => (new BillingController())->subscribe(),

    $uri === 'dashboard/billing/cancel' && $method === 'POST'
        => (new BillingController())->cancel(),

    $uri === 'dashboard/billing/webhook' && $method === 'POST'
        => (new BillingController())->webhook(),

    // ── Admin — leads ─────────────────────────────────────────────────────
    $uri === 'admin/login' && $method === 'GET'
        => (new LeadController())->loginForm(),

    $uri === 'admin/login' && $method === 'POST'
        => (new LeadController())->loginSubmit(),

    $uri === 'admin/logout'
        => (new LeadController())->logout(),

    $uri === 'admin' || $uri === 'admin/leads'
        => (new LeadController())->index(),

    str_starts_with($uri, 'admin/leads/') && $method === 'GET'
        => (new LeadController())->show((int)basename($uri)),

    str_starts_with($uri, 'admin/leads/') && $method === 'POST'
        => (new LeadController())->update((int)basename($uri)),

    // 404
    default => (new PageController())->notFound(),
};
