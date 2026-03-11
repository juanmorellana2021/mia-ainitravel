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
        $svc          = new SuperAdminService();
        $stats        = $svc->stats();
        $recentSignups = $svc->recentSignups(10);
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
}
