<?php
/**
 * mia/controllers/SequenceController.php
 *
 * HTTP handler for follow-up automation sequences.
 * Routes handled:
 *   GET  /dashboard/sequences          → index()
 *   GET  /dashboard/sequences/new      → edit() (blank)
 *   GET  /dashboard/sequences/{id}     → edit() (existing)
 *   POST /dashboard/sequences/save     → save()
 *   POST /dashboard/sequences/{id}/archive → archive()
 *   POST /dashboard/sequences/{id}/enroll/{lead_id} → enroll()
 *   POST /dashboard/sequences/{id}/unenroll/{lead_id} → unenroll()
 */

declare(strict_types=1);

class SequenceController
{
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
        $_SESSION['mia_client'] = (new BillingService())->clientToSession($client);
        return $client;
    }

    // ── List all sequences ────────────────────────────────────────────────────

    public function index(): void
    {
        $client  = $this->requireClient();
        $service = new SequenceService();

        $sequences   = $service->all($client->id);
        $enrollments = $service->allEnrollments($client->id, 50);

        $base         = App::basePath();
        $pageTitle    = 'Automatizaciones — Mia';
        $pageTopTitle = 'Automatizaciones de seguimiento';
        $activeNav    = 'sequences';

        require __DIR__ . '/../views/client/sequences.php';
    }

    // ── Create / Edit form ────────────────────────────────────────────────────

    public function edit(int $id = 0): void
    {
        $client  = $this->requireClient();
        $service = new SequenceService();

        if ($id > 0) {
            $sequence = $service->find($id, $client->id);
            if (!$sequence) {
                header('Location: ' . App::basePath() . '/dashboard/sequences');
                exit;
            }
            $steps = $service->steps($id);
        } else {
            $sequence = null;
            $steps    = [];
        }

        $base         = App::basePath();
        $pageTitle    = ($id > 0 ? 'Editar' : 'Nueva') . ' automatización — Mia';
        $pageTopTitle = ($id > 0 ? 'Editar' : 'Nueva') . ' automatización';
        $activeNav    = 'sequences';

        require __DIR__ . '/../views/client/sequence_edit.php';
    }

    // ── Save (create or update) ───────────────────────────────────────────────

    public function save(): void
    {
        App::csrfVerify();
        $client  = $this->requireClient();
        $service = new SequenceService();

        $id      = (int)($_POST['sequence_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $trigger = trim($_POST['trigger'] ?? 'manual');

        if ($name === '') {
            $back = $id > 0
                ? App::basePath() . '/dashboard/sequences/' . $id . '?error=name'
                : App::basePath() . '/dashboard/sequences/new?error=name';
            header('Location: ' . $back);
            exit;
        }

        // Build steps array from POST arrays
        $delays   = $_POST['delay_days'] ?? [];
        $messages = $_POST['step_message'] ?? [];
        $steps    = [];
        foreach ($messages as $i => $msg) {
            $msg = trim($msg);
            if ($msg === '') {
                continue;
            }
            $steps[] = [
                'delay_days' => max(0, (int)($delays[$i] ?? 1)),
                'message'    => $msg,
            ];
        }

        if (empty($steps)) {
            $back = $id > 0
                ? App::basePath() . '/dashboard/sequences/' . $id . '?error=steps'
                : App::basePath() . '/dashboard/sequences/new?error=steps';
            header('Location: ' . $back);
            exit;
        }

        $newId = $service->save($client->id, $id, $name, $trigger, $steps);

        header('Location: ' . App::basePath() . '/dashboard/sequences?saved=' . $newId);
        exit;
    }

    // ── Archive ───────────────────────────────────────────────────────────────

    public function archive(int $id): void
    {
        App::csrfVerify();
        $client = $this->requireClient();
        (new SequenceService())->archive($id, $client->id);
        header('Location: ' . App::basePath() . '/dashboard/sequences?archived=1');
        exit;
    }

    // ── Enroll a lead ─────────────────────────────────────────────────────────

    public function enroll(int $sequenceId, int $leadId): void
    {
        App::csrfVerify();
        $client = $this->requireClient();
        (new SequenceService())->enroll($leadId, $sequenceId, $client->id);
        header('Location: ' . App::basePath() . '/dashboard/leads/' . $leadId . '?enrolled=1');
        exit;
    }

    // ── Unenroll a lead ───────────────────────────────────────────────────────

    public function unenroll(int $sequenceId, int $leadId): void
    {
        App::csrfVerify();
        $client = $this->requireClient();
        (new SequenceService())->unenroll($leadId, $sequenceId, $client->id);
        header('Location: ' . App::basePath() . '/dashboard/leads/' . $leadId . '?unenrolled=1');
        exit;
    }
}
