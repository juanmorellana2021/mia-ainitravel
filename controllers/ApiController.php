<?php
/**
 * mia/controllers/ApiController.php
 *
 * Two endpoints:
 *  POST /api/chat         — Mia sales bot (sells Mia to new businesses)
 *  POST /api/client-chat  — Mia running on behalf of a subscribed client's WA number
 *
 * Both are localhost-only (called by the bot server), authenticated with X-Mia-Bot-Key.
 */

declare(strict_types=1);

class ApiController
{
    private function guardBotRequest(): bool
    {
        header('Content-Type: application/json');
        $caller   = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $botKey   = $_SERVER['HTTP_X_MIA_BOT_KEY'] ?? '';
        $validKey = ($botKey === 'mia-bot-secret-2026');
        $local    = in_array($caller, ['127.0.0.1', '::1', 'localhost'], true);
        if (!$local && !$validKey) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return false;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
            return false;
        }
        return true;
    }

    // ── Mia sales bot (Juan's number — sells Mia to new hotel owners) ─────────
    public function chat(): void
    {
        if (!$this->guardBotRequest()) return;

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);

        if (!is_array($data) || empty($data['message']) || empty($data['from'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Missing message or from']);
            return;
        }

        $phone   = trim((string) $data['from']);
        $message = trim((string) $data['message']);

        try {
            $service = new MiaSalesService();
            $result  = $service->process($phone, $message);

            echo json_encode([
                'success' => true,
                'reply'   => $result['reply'] ?? '',
                'from'    => $phone,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('[Mia ApiController] Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
                'reply'   => 'Lo siento, estoy teniendo problemas técnicos. Intenta de nuevo en un momento. 🙏',
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ── Client bot (answers guests on behalf of a subscribed client) ──────────
    // Called by bot server with: { from, message, client_id }
    public function clientChat(): void
    {
        if (!$this->guardBotRequest()) return;

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);

        if (!is_array($data) || empty($data['message']) || empty($data['from']) || empty($data['client_id'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Missing message, from, or client_id']);
            return;
        }

        $phone    = trim((string) $data['from']);
        $message  = trim((string) $data['message']);
        $clientId = (int) $data['client_id'];

        $client = (new ClientService())->findById($clientId);
        if (!$client) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Client not found']);
            return;
        }

        if (!$client->isActive()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Client subscription inactive']);
            return;
        }

        try {
            $service = new ClientBotService($client);
            $result  = $service->process($phone, $message);

            echo json_encode([
                'success' => true,
                'reply'   => $result['reply'] ?? '',
                'from'    => $phone,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("[ClientBot:{$clientId}] Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
                'reply'   => 'Un momento, estoy teniendo un pequeño problema técnico 🙏',
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ── WA status callback (called by bot.js when client session connects/disconnects) ─
    // POST /api/client-status  { client_id, status, phone? }
    public function clientStatus(): void
    {
        if (!$this->guardBotRequest()) return;

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);

        if (!is_array($data) || empty($data['client_id']) || empty($data['status'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Missing client_id or status']);
            return;
        }

        $clientId = (int) $data['client_id'];
        $status   = trim((string) $data['status']);   // 'connected' | 'disconnected'
        $phone    = isset($data['phone']) ? trim((string) $data['phone']) : null;

        (new ClientService())->updateWaStatus($clientId, $status, $phone ?: null);

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }
}
