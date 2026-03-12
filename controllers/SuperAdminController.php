<?php
/**
 * mia/controllers/SuperAdminController.php
 *
 * Superadmin panel — manage all clients, KPIs, billing.
 * Completely separate session from client auth (key: mia_superadmin).
 */

declare(strict_types=1);

class SuperAdminController
{
    // ── Auth guard ────────────────────────────────────────────────────────────

    private function requireSuperAdmin(): void
    {
        if (empty($_SESSION['mia_superadmin'])) {
            header('Location: ' . App::basePath() . '/superadmin/login');
            exit;
        }
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function loginForm(): void
    {
        if (!empty($_SESSION['mia_superadmin'])) {
            header('Location: ' . App::basePath() . '/superadmin/dashboard');
            exit;
        }
        $error = $_GET['error'] ?? '';
        require __DIR__ . '/../views/superadmin/login.php';
    }

    public function loginSubmit(): void
    {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';

        if ($user === App::SUPERADMIN_USER &&
            password_verify($pass, App::SUPERADMIN_HASH)) {
            session_regenerate_id(true);
            $_SESSION['mia_superadmin'] = $user;
            header('Location: ' . App::basePath() . '/superadmin/dashboard');
            exit;
        }

        header('Location: ' . App::basePath() . '/superadmin/login?error=1');
        exit;
    }

    public function logout(): void
    {
        unset($_SESSION['mia_superadmin']);
        header('Location: ' . App::basePath() . '/superadmin/login');
        exit;
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        $this->requireSuperAdmin();
        $svc            = new SuperAdminService();
        $stats          = $svc->stats();
        $recentSignups  = $svc->recentSignups(10);
        $recentProspects = $svc->recentProspects(15);
        require __DIR__ . '/../views/superadmin/dashboard.php';
    }

    // ── Clients list ──────────────────────────────────────────────────────────

    public function clients(): void
    {
        $this->requireSuperAdmin();
        $search       = trim($_GET['q']      ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $clients      = (new SuperAdminService())->allClients($search, $statusFilter);
        require __DIR__ . '/../views/superadmin/clients.php';
    }

    // ── Client detail / edit ──────────────────────────────────────────────────

    public function clientDetail(int $id): void
    {
        $this->requireSuperAdmin();
        $data = (new SuperAdminService())->clientFull($id);
        if (!$data) {
            http_response_code(404);
            echo '<h1>Client not found</h1>';
            return;
        }
        $saved = isset($_GET['saved']);
        require __DIR__ . '/../views/superadmin/client_detail.php';
    }

    public function clientSave(int $id): void
    {
        $this->requireSuperAdmin();
        App::csrfVerify();
        $svc = new SuperAdminService();

        // Check client exists
        $data = $svc->clientFull($id);
        if (!$data) {
            http_response_code(404);
            return;
        }

        $svc->updateClient($id, $_POST);

        // Optional password reset
        $newPass = trim($_POST['new_password'] ?? '');
        if ($newPass !== '') {
            if (strlen($newPass) < 8) {
                header('Location: ' . App::basePath() . '/superadmin/clients/' . $id . '?error=password_short');
                exit;
            }
            $svc->resetPassword($id, $newPass);
        }

        header('Location: ' . App::basePath() . '/superadmin/clients/' . $id . '?saved=1');
        exit;
    }

    public function clientDelete(int $id): void
    {
        $this->requireSuperAdmin();
        App::csrfVerify();
        (new SuperAdminService())->deleteClient($id);
        header('Location: ' . App::basePath() . '/superadmin/clients?deleted=1');
        exit;
    }

    // ── Mia bot connection (QR scan) ──────────────────────────────────────────

    public function miaBot(): void
    {
        $this->requireSuperAdmin();
        $pageTitle    = 'Conectar Bot Mia';
        $pageTopTitle = 'Bot Mia — Conexión WhatsApp';
        $activeNav    = 'mia_bot';
        require __DIR__ . '/../views/superadmin/mia_bot.php';
    }

    /** JSON proxy — polls the bot server and returns status+QR to the browser. */
    public function miaBotStatus(): void
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json');

        $ctx = stream_context_create(['http' => ['timeout' => 4]]);
        $raw = @file_get_contents('http://127.0.0.1:3001/qr/mia', false, $ctx);

        if ($raw === false) {
            echo json_encode(['status' => 'disconnected', 'qr_image' => null, 'phone' => null]);
            return;
        }

        $data = json_decode($raw, true);
        echo json_encode($data ?: ['status' => 'disconnected', 'qr_image' => null, 'phone' => null]);
    }

    // ── Prospects list ────────────────────────────────────────────────────────

    public function prospects(): void
    {
        $this->requireSuperAdmin();
        $search      = trim($_GET['q']     ?? '');
        $stateFilter = trim($_GET['state'] ?? '');
        $prospects   = (new SuperAdminService())->allProspects($search, $stateFilter);
        require __DIR__ . '/../views/superadmin/prospects.php';
    }

    // ── Prospect detail ───────────────────────────────────────────────────────

    public function prospectDetail(int $id): void
    {
        $this->requireSuperAdmin();
        $data = (new SuperAdminService())->prospectFull($id);
        if (!$data) {
            http_response_code(404);
            echo '<h1>Prospecto no encontrado</h1>';
            return;
        }
        require __DIR__ . '/../views/superadmin/prospect_detail.php';
    }

    /** JSON — returns conversation history for the slide-in panel */
    public function prospectChat(int $id): void
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json');
        $data = (new SuperAdminService())->prospectFull($id);
        if (!$data) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            return;
        }
        echo json_encode([
            'session' => [
                'id'            => $data['session']['id'],
                'phone'         => $data['session']['phone'],
                'state'         => $data['session']['state'],
                'business_name' => $data['session']['business_name'],
                'contact_name'  => $data['session']['contact_name'],
                'business_type' => $data['session']['business_type'],
                'email'         => $data['session']['email'],
                'updated_at'    => $data['session']['updated_at'],
                'client_id'     => $data['session']['client_id'] ?? null,
            ],
            'history' => $data['history'],
        ], JSON_UNESCAPED_UNICODE);
    }

    // ── Convert prospect → client ─────────────────────────────────────────────

    public function prospectConvert(int $id): void
    {
        $this->requireSuperAdmin();
        App::csrfVerify();

        $result = (new SuperAdminService())->convertToClient($id);

        if (isset($result['error'])) {
            header('Location: ' . App::basePath() . '/superadmin/prospects/' . $id . '?error=' . urlencode($result['error']));
            exit;
        }

        // Redirect to new client page with the temp password surfaced once
        header('Location: ' . App::basePath() . '/superadmin/clients/' . $result['client_id']
            . '?converted=1&tmp=' . urlencode($result['temp_password']));
        exit;
    }
}
