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
require_once __DIR__ . '/controllers/PageController.php';
require_once __DIR__ . '/controllers/LeadController.php';
require_once __DIR__ . '/controllers/ApiController.php';

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

    // Admin — leads
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
