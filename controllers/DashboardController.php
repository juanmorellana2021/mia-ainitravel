<?php
/**
 * mia/controllers/DashboardController.php
 *
 * Client-facing dashboard: overview, leads, messages.
 */

declare(strict_types=1);

class DashboardController
{
    // ── Auth guard ────────────────────────────────────────────────────────────

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
        // Keep session fresh
        $_SESSION['mia_client'] = (new BillingService())->clientToSession($client);
        return $client;
    }

    // ── Dashboard overview ────────────────────────────────────────────────────

    public function index(): void
    {
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();

        $stats         = $leadService->stats($client->id);
        $recentLeads   = $leadService->recent($client->id, 8);
        $todayMessages = $leadService->todayMessages($client->id);
        $welcome       = !empty($_GET['welcome']);

        require __DIR__ . '/../views/client/dashboard.php';
    }

    // ── Leads list ────────────────────────────────────────────────────────────

    public function leads(): void
    {
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();

        $filter = $_GET['status'] ?? '';
        $leads  = $leadService->allForClient($client->id, $filter);
        $stats  = $leadService->stats($client->id);

        require __DIR__ . '/../views/client/leads.php';
    }

    // ── Lead detail ───────────────────────────────────────────────────────────

    public function leadDetail(int $id): void
    {
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();

        $lead = $leadService->findById($id, $client->id);
        if (!$lead) {
            http_response_code(404);
            require __DIR__ . '/../views/pages/404.php';
            return;
        }

        $messages = $leadService->messagesForLead($lead->id, $client->id);

        require __DIR__ . '/../views/client/lead_detail.php';
    }

    // ── Lead update ───────────────────────────────────────────────────────────

    public function leadUpdate(int $id): void
    {
        App::csrfVerify();
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();

        $lead = $leadService->findById($id, $client->id);
        if (!$lead) {
            http_response_code(404);
            return;
        }

        $leadService->update($id, $client->id, [
            'status'         => $_POST['status']         ?? $lead->status,
            'contact_name'   => $_POST['contact_name']   ?? $lead->contact_name,
            'notes'          => $_POST['notes']          ?? $lead->notes,
            'value_estimate' => $_POST['value_estimate'] ?? $lead->value_estimate,
        ]);

        header('Location: ' . App::basePath() . '/dashboard/leads/' . $id . '?saved=1');
        exit;
    }

    // ── Lead chat: AJAX endpoints ─────────────────────────────────────────────

    public function leadMessages(int $id): void
    {
        header('Content-Type: application/json');
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();
        $lead        = $leadService->findById($id, $client->id);
        if (!$lead) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            return;
        }
        $messages = $leadService->messagesForLead($lead->id, $client->id);
        echo json_encode(array_map(fn($m) => [
            'id'         => $m->id,
            'direction'  => $m->direction,
            'message'    => $m->message,
            'handled_by' => $m->handled_by,
            'created_at' => $m->created_at,
        ], $messages));
    }

    public function leadSend(int $id): void
    {
        header('Content-Type: application/json');
        App::csrfVerify();
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();
        $lead        = $leadService->findById($id, $client->id);
        if (!$lead) {
            http_response_code(404);
            echo json_encode(['error' => 'Lead not found']);
            return;
        }
        $text = trim($_POST['message'] ?? '');
        if (empty($text)) {
            http_response_code(400);
            echo json_encode(['error' => 'Empty message']);
            return;
        }
        $leadService->saveMessage($client->id, $lead->id, $lead->phone, $text, 'outbound', 'human');
        $delivered = $this->sendViaBot($client->id, $lead->phone, $text);
        echo json_encode(['success' => true, 'delivered' => $delivered]);
    }

    private function sendViaBot(int $clientId, string $phone, string $message): bool
    {
        $payload = json_encode([
            'client_id' => $clientId,
            'to'        => preg_replace('/[^0-9+]/', '', $phone),
            'message'   => $message,
        ]);
        $ctx = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
            'content'       => $payload,
            'timeout'       => 8,
            'ignore_errors' => true,
        ]]);
        $result = @file_get_contents('http://localhost:3001/send-client', false, $ctx);
        if ($result === false) return false;
        $data = json_decode($result, true);
        return !empty($data['success']);
    }

    // ── Analytics ─────────────────────────────────────────────────────────────

    public function analytics(): void
    {
        $client = $this->requireClient();
        $data   = (new ClientLeadService())->analyticsData($client->id);
        require __DIR__ . '/../views/client/analytics.php';
    }

    // ── Messages inbox ────────────────────────────────────────────────────────

    public function messages(): void
    {
        $client = $this->requireClient();

        $filter   = $_GET['handled_by'] ?? '';
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = 30;
        $offset   = ($page - 1) * $perPage;

        $db = Database::get();

        if ($filter) {
            $stmt = $db->prepare(
                'SELECT m.*, l.contact_name AS lead_name
                 FROM mia_client_messages m
                 LEFT JOIN mia_client_leads l ON l.id = m.lead_id
                 WHERE m.client_id = ? AND m.handled_by = ?
                 ORDER BY m.created_at DESC
                 LIMIT ? OFFSET ?'
            );
            $stmt->execute([$client->id, $filter, $perPage, $offset]);
        } else {
            $stmt = $db->prepare(
                'SELECT m.*, l.contact_name AS lead_name
                 FROM mia_client_messages m
                 LEFT JOIN mia_client_leads l ON l.id = m.lead_id
                 WHERE m.client_id = ?
                 ORDER BY m.created_at DESC
                 LIMIT ? OFFSET ?'
            );
            $stmt->execute([$client->id, $perPage, $offset]);
        }

        $messages = $stmt->fetchAll();

        require __DIR__ . '/../views/client/messages.php';
    }
}
