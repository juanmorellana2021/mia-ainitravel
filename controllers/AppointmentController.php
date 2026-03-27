<?php
/**
 * mia/controllers/AppointmentController.php
 *
 * HTTP handlers for the Scheduler / Citas feature.
 * All routes are plan-gated to Business+ (pro, enterprise*).
 */

declare(strict_types=1);

class AppointmentController
{
    private const ALLOWED_PLANS = ['trial','basic','pro','enterprise','enterprise_duo','enterprise_chain','enterprise_corp'];

    private function requireClient(): Client
    {
        if (empty($_SESSION['mia_client_id'])) {
            header('Location: ' . App::basePath() . '/login');
            exit;
        }
        $client = (new ClientService())->findById((int)$_SESSION['mia_client_id']);
        if (!$client) {
            session_destroy();
            header('Location: ' . App::basePath() . '/login');
            exit;
        }
        return $client;
    }

    private function requirePlan(Client $client): void
    {
        if (!in_array($client->plan, self::ALLOWED_PLANS, true)) {
            header('Location: ' . App::basePath() . '/dashboard/billing?upgrade=appointments');
            exit;
        }
    }

    // ── GET /dashboard/appointments ───────────────────────────────────────────

    public function index(): void
    {
        $client = $this->requireClient();
        $this->requirePlan($client);

        $svc        = new AppointmentService();
        $upcoming   = $svc->getByClient($client->id, 'upcoming');
        $past       = $svc->getByClient($client->id, 'past');
        $avail      = $svc->getAvailability($client->id);
        $tz         = $avail?->timezone ?? 'America/Lima';
        $pageTitle  = 'Citas — Mia';
        $activeNav  = 'appointments';
        $base       = App::basePath();

        require __DIR__ . '/../views/client/appointments.php';
    }

    // ── GET /dashboard/appointments/settings ─────────────────────────────────

    public function settings(): void
    {
        $client = $this->requireClient();
        $this->requirePlan($client);

        $svc       = new AppointmentService();
        $avail     = $svc->getAvailability($client->id);
        $saved     = isset($_GET['saved']);
        $pageTitle = 'Config. Citas — Mia';
        $activeNav = 'appointments';
        $base      = App::basePath();

        require __DIR__ . '/../views/client/appointment_settings.php';
    }

    // ── POST /dashboard/appointments/settings/save ────────────────────────────

    public function saveSettings(): void
    {
        App::csrfVerify();
        $client = $this->requireClient();
        $this->requirePlan($client);

        (new AppointmentService())->saveAvailability($client->id, $_POST);
        header('Location: ' . App::basePath() . '/dashboard/appointments/settings?saved=1');
        exit;
    }

    // ── POST /dashboard/appointments/{id}/cancel ──────────────────────────────

    public function cancel(int $id): void
    {
        App::csrfVerify();
        $client = $this->requireClient();
        $this->requirePlan($client);

        (new AppointmentService())->cancel($id, $client->id);
        header('Location: ' . App::basePath() . '/dashboard/appointments?cancelled=1');
        exit;
    }

    // ── GET /api/appointments/slots?client_id=&date= ─────────────────────────
    // Public AJAX endpoint used by bot and potential booking widget.
    // Requires a simple token check to avoid enumeration.

    public function slots(): void
    {
        header('Content-Type: application/json');

        $clientId = (int)($_GET['client_id'] ?? 0);
        $date     = trim($_GET['date'] ?? '');

        if ($clientId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(400);
            echo json_encode(['error' => 'Parámetros inválidos']);
            return;
        }

        // Validate date is not in the past
        if ($date < date('Y-m-d')) {
            echo json_encode(['slots' => []]);
            return;
        }

        $slots = (new AppointmentService())->findSlots($clientId, $date);
        echo json_encode(['slots' => $slots]);
    }
}
