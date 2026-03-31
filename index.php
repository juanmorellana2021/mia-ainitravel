<?php
/**
 * mia/index.php — Front Controller
 *
 * Routes all requests through a single entry point.
 * Maps URI paths to Controller actions (MVC).
 *
 * URL: mia-whatsapp.com  (or /mia/ locally)
 */

declare(strict_types=1);

require_once __DIR__ . '/config/App.php';

// ── Session: use app-local save path so the Ubuntu system cron (which reads
//    /etc/php/*/fpm/php.ini and purges /var/lib/php/sessions/ after ~3600s)
//    cannot destroy our long-lived superadmin sessions.
$_sessionSavePath = __DIR__ . '/tmp/sessions';
if (!is_dir($_sessionSavePath)) {
    @mkdir($_sessionSavePath, 0700, true);
}
session_save_path($_sessionSavePath);

$sessionTtl = App::SUPERADMIN_SESSION_TTL;
ini_set('session.gc_maxlifetime', (string)$sessionTtl);
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor',     '100');
session_set_cookie_params([
    'lifetime' => $sessionTtl,
    'path'     => App::basePath() ?: '/',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// ── Autoload ─────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Lead.php';
require_once __DIR__ . '/models/SalesSession.php';
require_once __DIR__ . '/services/LeadService.php';
require_once __DIR__ . '/services/MiaSalesService.php';
require_once __DIR__ . '/services/ClientBotService.php';
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
require_once __DIR__ . '/controllers/SettingsController.php';
require_once __DIR__ . '/services/ClientPhotoService.php';
require_once __DIR__ . '/services/ClientDocService.php';
require_once __DIR__ . '/models/LeadMemory.php';
require_once __DIR__ . '/services/LeadMemoryService.php';
require_once __DIR__ . '/controllers/BroadcastController.php';
require_once __DIR__ . '/services/BroadcastService.php';
require_once __DIR__ . '/services/NotificationService.php';
require_once __DIR__ . '/services/SuperAdminService.php';
require_once __DIR__ . '/controllers/SuperAdminController.php';
require_once __DIR__ . '/models/Sequence.php';
require_once __DIR__ . '/models/SequenceStep.php';
require_once __DIR__ . '/models/LeadSequence.php';
require_once __DIR__ . '/services/SequenceService.php';
require_once __DIR__ . '/controllers/SequenceController.php';
// ── Scheduler / Citas ────────────────────────────────────────────────────────
require_once __DIR__ . '/models/Availability.php';
require_once __DIR__ . '/models/Appointment.php';
require_once __DIR__ . '/services/AppointmentService.php';
require_once __DIR__ . '/controllers/AppointmentController.php';
// ── Add-on credits ────────────────────────────────────────────────────────────
require_once __DIR__ . '/models/ClientAddon.php';
require_once __DIR__ . '/services/AddonService.php';

// ── Remember Me: restore client session from persistent cookie ────────────────
// If there is no active client session but a 'mia_remember' cookie exists,
// validate the hashed token in DB and re-hydrate the session automatically.
if (empty($_SESSION['mia_client_id']) && !empty($_COOKIE['mia_remember'])) {
    $rawToken = $_COOKIE['mia_remember'];
    // Basic sanity: must be a 64-char hex string (bin2hex of 32 bytes)
    if (strlen($rawToken) === 64 && ctype_xdigit($rawToken)) {
        $tHash  = hash('sha256', $rawToken);
        $rmDb   = Database::get();
        $rmStmt = $rmDb->prepare(
            'SELECT client_id FROM mia_remember_tokens
              WHERE token_hash = ? AND expires_at > NOW() LIMIT 1'
        );
        $rmStmt->execute([$tHash]);
        $rmRow = $rmStmt->fetch(PDO::FETCH_ASSOC);
        if ($rmRow) {
            $rmClient = (new ClientService())->findById((int)$rmRow['client_id']);
            if ($rmClient) {
                session_regenerate_id(true);
                $_SESSION['mia_client_id'] = $rmClient->id;
                $_SESSION['mia_client']    = (new BillingService())->clientToSession($rmClient);
                // Rotate the token on every use (prevents replay attacks)
                $rmDb->prepare('DELETE FROM mia_remember_tokens WHERE token_hash = ?')
                     ->execute([$tHash]);
                $newRmToken = bin2hex(random_bytes(32));
                $newRmHash  = hash('sha256', $newRmToken);
                $newRmTtl   = App::CLIENT_REMEMBER_TTL;
                $rmDb->prepare(
                    'INSERT INTO mia_remember_tokens (client_id, token_hash, expires_at)
                     VALUES (?,?,?)'
                )->execute([
                    $rmClient->id,
                    $newRmHash,
                    date('Y-m-d H:i:s', time() + $newRmTtl),
                ]);
                setcookie('mia_remember', $newRmToken, [
                    'expires'  => time() + $newRmTtl,
                    'path'     => App::basePath() ?: '/',
                    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
        } else {
            // Token expired or invalid — clear the stale cookie
            setcookie('mia_remember', '', [
                'expires'  => 1,
                'path'     => App::basePath() ?: '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }
}

// ── Route resolution ─────────────────────────────────────────────────────────
$uri = trim((string)(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''), '/');

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

    $uri === 'privacy'
        => (new PageController())->privacy(),

    // WhatsApp bot API (Mia sales conversations — Juan's number)
    $uri === 'api/chat' && $method === 'POST'
        => (new ApiController())->chat(),

    // WhatsApp bot API (client's bot — answers their guests)
    $uri === 'api/client-chat' && $method === 'POST'
        => (new ApiController())->clientChat(),

    // LID → real phone resolution (called by bot worker on startup)
    $uri === 'api/resolve-lids' && $method === 'POST'
        => (new ApiController())->resolveLids(),
    $uri === 'api/apply-lid-resolutions' && $method === 'POST'
        => (new ApiController())->applyLidResolutions(),

    // Startup backfill: fetch profile pics + contact names for existing leads
    $uri === 'api/leads-needing-backfill' && $method === 'POST'
        => (new ApiController())->leadsNeedingBackfill(),
    $uri === 'api/apply-lead-backfill' && $method === 'POST'
        => (new ApiController())->applyLeadBackfill(),

    // WhatsApp status callback from bot server (connected / disconnected)
    $uri === 'api/client-status' && $method === 'POST'
        => (new ApiController())->clientStatus(),

    // Public page-event tracking pixel
    $uri === 'api/track'
        => (new ApiController())->track(),

    // Onboarding help chat (auth required, rate-limited)
    $uri === 'api/onboarding-help' && $method === 'POST'
        => (new ApiController())->onboardingHelp(),

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

    $uri === 'forgot-password' && $method === 'GET'
        => (new AuthController())->forgotForm(),

    $uri === 'forgot-password' && $method === 'POST'
        => (new AuthController())->forgotSubmit(),

    $uri === 'reset-password' && $method === 'GET'
        => (new AuthController())->resetForm(),

    $uri === 'reset-password' && $method === 'POST'
        => (new AuthController())->resetSubmit(),

    // ── Client dashboard ─────────────────────────────────────────────────
    $uri === 'dashboard'
        => (new DashboardController())->index(),

    $uri === 'dashboard/leads' && $method === 'GET'
        => (new DashboardController())->leads(),

    $uri === 'dashboard/leads/add' && $method === 'POST'
        => (new DashboardController())->leadAdd(),

    $uri === 'dashboard/leads/import' && $method === 'POST'
        => (new DashboardController())->leadImport(),

    $uri === 'dashboard/leads/gallery' && $method === 'GET'
        => (new DashboardController())->leadGallery(),

    $uri === 'dashboard/leads/docs' && $method === 'GET'
        => (new DashboardController())->leadDocsJson(),

    str_starts_with($uri, 'dashboard/leads/') && str_ends_with($uri, '/messages') && $method === 'GET'
        => (new DashboardController())->leadMessages((int)(explode('/', $uri)[2] ?? 0)),

    str_starts_with($uri, 'dashboard/leads/') && str_ends_with($uri, '/translate') && $method === 'POST'
        => (new DashboardController())->leadTranslate((int)(explode('/', $uri)[2] ?? 0)),

    str_starts_with($uri, 'dashboard/leads/') && str_ends_with($uri, '/send') && $method === 'POST'
        => (new DashboardController())->leadSend((int)(explode('/', $uri)[2] ?? 0)),

    str_starts_with($uri, 'dashboard/leads/') && str_ends_with($uri, '/resume-bot') && $method === 'POST'
        => (new DashboardController())->resumeBot((int)(explode('/', $uri)[2] ?? 0)),

    str_starts_with($uri, 'dashboard/leads/') && $method === 'GET'
        => (new DashboardController())->leadDetail((int)basename($uri)),

    str_starts_with($uri, 'dashboard/leads/') && $method === 'POST'
        => (new DashboardController())->leadUpdate((int)basename($uri)),

    $uri === 'dashboard/recent-leads' && $method === 'GET'
        => (new DashboardController())->recentLeadsJson(),

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

    $uri === 'dashboard/billing/addon' && $method === 'POST'
        => (new BillingController())->addonCheckout(),

    $uri === 'dashboard/billing/addon-return' && $method === 'GET'
        => (new BillingController())->addonReturn(),

    // ── Analytics ─────────────────────────────────────────────────────────────
    $uri === 'dashboard/analytics'
        => (new DashboardController())->analytics(),

    // ── Broadcast ─────────────────────────────────────────────────────────────
    $uri === 'dashboard/broadcast' && $method === 'GET'
        => (new BroadcastController())->index(),

    $uri === 'dashboard/broadcast/send' && $method === 'POST'
        => (new BroadcastController())->send(),

    // ── Follow-up sequences ────────────────────────────────────────────────────
    $uri === 'dashboard/sequences' && $method === 'GET'
        => (new SequenceController())->index(),

    $uri === 'dashboard/sequences/new' && $method === 'GET'
        => (new SequenceController())->edit(0),

    $uri === 'dashboard/sequences/save' && $method === 'POST'
        => (new SequenceController())->save(),

    // /dashboard/sequences/{id}  — edit form
    preg_match('#^dashboard/sequences/(\d+)$#', $uri, $m) && $method === 'GET'
        => (new SequenceController())->edit((int)$m[1]),

    // /dashboard/sequences/{id}/archive
    preg_match('#^dashboard/sequences/(\d+)/archive$#', $uri, $m) && $method === 'POST'
        => (new SequenceController())->archive((int)$m[1]),

    // /dashboard/sequences/{seq_id}/enroll/{lead_id}
    preg_match('#^dashboard/sequences/(\d+)/enroll/(\d+)$#', $uri, $m) && $method === 'POST'
        => (new SequenceController())->enroll((int)$m[1], (int)$m[2]),

    // /dashboard/sequences/{seq_id}/unenroll/{lead_id}
    preg_match('#^dashboard/sequences/(\d+)/unenroll/(\d+)$#', $uri, $m) && $method === 'POST'
        => (new SequenceController())->unenroll((int)$m[1], (int)$m[2]),

    // ── Appointments ──────────────────────────────────────────────────────────
    $uri === 'dashboard/appointments' && $method === 'GET'
        => (new AppointmentController())->index(),

    $uri === 'dashboard/appointments/settings' && $method === 'GET'
        => (new AppointmentController())->settings(),

    $uri === 'dashboard/appointments/settings/save' && $method === 'POST'
        => (new AppointmentController())->saveSettings(),

    preg_match('#^dashboard/appointments/(\d+)/cancel$#', $uri, $m) && $method === 'POST'
        => (new AppointmentController())->cancel((int)$m[1]),

    $uri === 'api/appointments/slots' && $method === 'GET'
        => (new AppointmentController())->slots(),

    // ── Gallery ───────────────────────────────────────────────────────────────
    $uri === 'dashboard/gallery' && $method === 'GET'
        => (new SettingsController())->gallery(),

    $uri === 'dashboard/gallery/update' && $method === 'POST'
        => (new SettingsController())->updatePhoto(),

    // ── Documents ────────────────────────────────────────────────────────────
    $uri === 'dashboard/documents' && $method === 'GET'
        => (new SettingsController())->documents(),

    $uri === 'dashboard/documents/upload' && $method === 'POST'
        => (new SettingsController())->uploadDoc(),

    $uri === 'dashboard/documents/update' && $method === 'POST'
        => (new SettingsController())->updateDoc(),

    str_starts_with($uri, 'dashboard/documents/') && str_ends_with($uri, '/delete') && $method === 'POST'
        => (new SettingsController())->deleteDoc((int)(explode('/', $uri)[2] ?? 0)),

    $uri === 'dashboard/settings/docs' && $method === 'GET'
        => (new SettingsController())->listDocs(),

    // ── Sales config ─────────────────────────────────────────────────────────
    $uri === 'dashboard/sales-config' && $method === 'GET'
        => (new SettingsController())->salesConfig(),

    $uri === 'dashboard/sales-config/save' && $method === 'POST'
        => (new SettingsController())->saveSalesConfig(),

    $uri === 'dashboard/sales-config/upload-qr' && $method === 'POST'
        => (new SettingsController())->uploadPaymentQr(),

    // ── Settings ──────────────────────────────────────────────────────────────
    $uri === 'dashboard/settings' && $method === 'GET'
        => (new SettingsController())->index(),

    $uri === 'dashboard/settings/save' && $method === 'POST'
        => (new SettingsController())->save(),

    $uri === 'dashboard/settings/finish-onboarding' && $method === 'POST'
        => (new SettingsController())->finishOnboarding(),

    $uri === 'dashboard/settings/wa-qr' && $method === 'GET'
        => (new SettingsController())->waQr(),

    $uri === 'dashboard/settings/wa-link' && $method === 'GET'
        => (new SettingsController())->waLink(),

    $uri === 'dashboard/settings/wa-connect' && $method === 'POST'
        => (new SettingsController())->waConnect(),

    $uri === 'dashboard/settings/search-business' && $method === 'POST'
        => (new SettingsController())->searchBusiness(),

    $uri === 'dashboard/settings/photos' && $method === 'GET'
        => (new SettingsController())->listPhotos(),

    $uri === 'dashboard/settings/photos/upload' && $method === 'POST'
        => (new SettingsController())->uploadPhoto(),

    preg_match('#^dashboard/settings/photos/(\d+)/delete$#', $uri, $m) && $method === 'POST'
        => (new SettingsController())->deletePhoto((int)$m[1]),

    $uri === 'dashboard/settings/seat/add' && $method === 'POST'
        => (new SettingsController())->seatAdd(),

    preg_match('#^dashboard/settings/seat/(\d+)/delete$#', $uri, $m) && $method === 'POST'
        => (new SettingsController())->seatDelete((int)$m[1]),

    // ── Superadmin ─────────────────────────────────────────────────────────
    $uri === 'superadmin' || ($uri === 'superadmin/' )
        => (function() { header('Location: ' . App::basePath() . '/superadmin/dashboard'); exit; })(),

    $uri === 'superadmin/login' && $method === 'GET'
        => (new SuperAdminController())->loginForm(),

    $uri === 'superadmin/login' && $method === 'POST'
        => (new SuperAdminController())->loginSubmit(),

    $uri === 'superadmin/logout'
        => (new SuperAdminController())->logout(),

    $uri === 'superadmin/dashboard'
        => (new SuperAdminController())->dashboard(),

    $uri === 'superadmin/clients' && $method === 'GET'
        => (new SuperAdminController())->clients(),

    str_starts_with($uri, 'superadmin/clients/') && str_ends_with($uri, '/delete') && $method === 'POST'
        => (new SuperAdminController())->clientDelete((int)(explode('/', $uri)[2] ?? 0)),

    str_starts_with($uri, 'superadmin/clients/') && $method === 'GET'
        => (new SuperAdminController())->clientDetail((int)basename($uri)),

    str_starts_with($uri, 'superadmin/clients/') && $method === 'POST'
        => (new SuperAdminController())->clientSave((int)basename($uri)),

    // ── Superadmin — Mia Bot ──────────────────────────────────────────────────
    $uri === 'superadmin/mia-bot'
        => (new SuperAdminController())->miaBot(),

    $uri === 'superadmin/mia-bot-status' && $method === 'GET'
        => (new SuperAdminController())->miaBotStatus(),

    $uri === 'superadmin/mia-brain'
        => (new SuperAdminController())->miaBrain(),

    // ── Superadmin — Prospects ────────────────────────────────────────────────
    $uri === 'superadmin/prospects' && $method === 'GET'
        => (new SuperAdminController())->prospects(),

    str_starts_with($uri, 'superadmin/prospects/') && str_ends_with($uri, '/convert') && $method === 'POST'
        => (new SuperAdminController())->prospectConvert((int)(explode('/', $uri)[2] ?? 0)),

    str_starts_with($uri, 'superadmin/prospects/') && str_ends_with($uri, '/reset-state') && $method === 'POST'
        => (new SuperAdminController())->prospectResetState((int)(explode('/', $uri)[2] ?? 0)),

    str_starts_with($uri, 'superadmin/prospects/') && str_ends_with($uri, '/chat') && $method === 'GET'
        => (new SuperAdminController())->prospectChat((int)(explode('/', $uri)[2] ?? 0)),

    str_starts_with($uri, 'superadmin/prospects/') && str_ends_with($uri, '/send') && $method === 'POST'
        => (new SuperAdminController())->prospectSendMessage((int)(explode('/', $uri)[2] ?? 0)),

    str_starts_with($uri, 'superadmin/prospects/') && str_ends_with($uri, '/messages') && $method === 'GET'
        => (new SuperAdminController())->prospectGetMessages((int)(explode('/', $uri)[2] ?? 0)),

    str_starts_with($uri, 'superadmin/prospects/') && $method === 'GET'
        => (new SuperAdminController())->prospectDetail((int)basename($uri)),

    // ── Superadmin — Analytics ───────────────────────────────────────────────
    $uri === 'superadmin/analytics'
        => (new SuperAdminController())->analytics(),

    // ── Superadmin — Client Activity ─────────────────────────────────────────
    $uri === 'superadmin/activity'
        => (new SuperAdminController())->activity(),

    // ── Admin — leads ─────────────────────────────────────────────────────────
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
