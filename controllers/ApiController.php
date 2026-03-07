<?php
/**
 * mia/controllers/ApiController.php
 *
 * Handles the WhatsApp bot API endpoint for Mia sales conversations.
 * Mirrors the whatsapp/api pattern — localhost-only, POST.
 */

declare(strict_types=1);

class ApiController
{
    public function chat(): void
    {
        header('Content-Type: application/json');

        // Security: localhost-only
        $caller = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if (!in_array($caller, ['127.0.0.1', '::1', 'localhost'], true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
            return;
        }

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
}
