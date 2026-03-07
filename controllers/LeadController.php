<?php
/**
 * mia/controllers/LeadController.php
 *
 * Admin panel for managing sales leads.
 * Simple session-based auth.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/App.php';

class LeadController
{
    // ── Auth guard ────────────────────────────────────────────────────────
    private function requireAuth(): bool
    {
        if (empty($_SESSION['mia_admin'])) {
            header('Location: ' . App::basePath() . '/admin/login');
            exit;
        }
        return true;
    }

    // ── Login ─────────────────────────────────────────────────────────────
    public function loginForm(): void
    {
        $error = '';
        require __DIR__ . '/../views/admin/login.php';
    }

    public function loginSubmit(): void
    {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';

        // For initial setup: accept 'admin' / 'MiaAdmin2026!'
        // In production, use App::ADMIN_HASH with password_verify
        if ($user === App::ADMIN_USER && ($pass === 'MiaAdmin2026!' || password_verify($pass, App::ADMIN_HASH))) {
            $_SESSION['mia_admin'] = true;
            header('Location: ' . App::basePath() . '/admin/leads');
            exit;
        }

        $error = 'Credenciales incorrectas';
        require __DIR__ . '/../views/admin/login.php';
    }

    public function logout(): void
    {
        unset($_SESSION['mia_admin']);
        header('Location: ' . App::basePath() . '/admin/login');
        exit;
    }

    // ── Lead list ─────────────────────────────────────────────────────────
    public function index(): void
    {
        $this->requireAuth();

        $leadService  = new LeadService();
        $filter       = $_GET['status'] ?? '';
        $leads        = $leadService->all($filter);
        $stats        = $leadService->stats();

        require __DIR__ . '/../views/admin/leads.php';
    }

    // ── Lead detail ───────────────────────────────────────────────────────
    public function show(int $id): void
    {
        $this->requireAuth();

        $leadService = new LeadService();
        $lead        = $leadService->findById($id);

        if (!$lead) {
            http_response_code(404);
            require __DIR__ . '/../views/pages/404.php';
            return;
        }

        require __DIR__ . '/../views/admin/lead_detail.php';
    }

    // ── Update lead status ───────────────────────────────────────────────
    public function update(int $id): void
    {
        $this->requireAuth();

        $status = $_POST['status'] ?? '';
        $notes  = trim($_POST['notes'] ?? '');

        $leadService = new LeadService();
        $leadService->updateStatus($id, $status, $notes);

        header('Location: ' . App::basePath() . '/admin/leads/' . $id);
        exit;
    }
}
