<?php
/**
 * mia/services/BroadcastService.php
 *
 * Send a WhatsApp message to all captured leads via the bot admin API.
 * Logs every broadcast so clients can see history.
 */

declare(strict_types=1);

class BroadcastService
{
    private PDO $db;

    // Max leads per broadcast to avoid long-running PHP requests
    private const BATCH_LIMIT = 50;

    // Bot admin send endpoint (localhost only)
    private const BOT_URL = 'http://127.0.0.1:3001/send';

    public function __construct()
    {
        $this->db = Database::get();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS mia_broadcast_logs (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                client_id    INT  NOT NULL,
                message      TEXT NOT NULL,
                total_sent   INT  DEFAULT 0,
                total_failed INT  DEFAULT 0,
                recipients   TEXT,
                created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_client (client_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * All leads for a client that have a phone number.
     */
    public function leadsWithPhone(int $clientId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, contact_name, phone, status, created_at
             FROM mia_client_leads
             WHERE client_id = ? AND phone != ''
             ORDER BY created_at DESC
             LIMIT " . self::BATCH_LIMIT
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    /**
     * Send the message to all leads and log the result.
     */
    public function send(int $clientId, string $message, array $selectedPhones = []): array
    {
        $allLeads = $this->leadsWithPhone($clientId);

        // Filter to only selected phones when a non-empty list is provided
        $leads = (!empty($selectedPhones))
            ? array_values(array_filter($allLeads, fn($l) => in_array($l['phone'], $selectedPhones, true)))
            : $allLeads;

        $sent   = 0;
        $failed = 0;
        $phones = [];

        foreach ($leads as $lead) {
            $phone    = $lead['phone'];
            $phones[] = $phone;

            $payload = json_encode(['to' => $phone, 'message' => $message]);

            $ch = curl_init(self::BOT_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]);

            $res  = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if (!$err && $code === 200) {
                $sent++;
            } else {
                $failed++;
                error_log("[Mia Broadcast] Failed to send to {$phone}: HTTP {$code} {$err}");
            }

            // 1 second between sends to respect WhatsApp rate limits
            if ($sent + $failed < count($leads)) {
                sleep(1);
            }
        }

        $stmt = $this->db->prepare(
            'INSERT INTO mia_broadcast_logs (client_id, message, total_sent, total_failed, recipients)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $clientId,
            $message,
            $sent,
            $failed,
            json_encode($phones),
        ]);

        error_log("[Mia Broadcast] client_id={$clientId} sent={$sent} failed={$failed}");
        return ['sent' => $sent, 'failed' => $failed, 'total' => count($leads)];
    }

    /**
     * Last 20 broadcasts for a client.
     */
    public function history(int $clientId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM mia_broadcast_logs
             WHERE client_id = ?
             ORDER BY created_at DESC
             LIMIT 20'
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }
}
