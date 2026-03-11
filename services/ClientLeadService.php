<?php
/**
 * mia/services/ClientLeadService.php
 *
 * CRUD and stats for leads belonging to a specific client.
 * All queries are scoped by client_id to enforce data isolation.
 */

declare(strict_types=1);

class ClientLeadService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get();
    }

    // ── Leads ─────────────────────────────────────────────────────────────────

    public function allForClient(int $clientId, string $status = ''): array
    {
        if ($status) {
            $stmt = $this->db->prepare(
                'SELECT * FROM mia_client_leads WHERE client_id = ? AND status = ? ORDER BY created_at DESC'
            );
            $stmt->execute([$clientId, $status]);
        } else {
            $stmt = $this->db->prepare(
                'SELECT * FROM mia_client_leads WHERE client_id = ? ORDER BY created_at DESC'
            );
            $stmt->execute([$clientId]);
        }
        return array_map([ClientLead::class, 'fromRow'], $stmt->fetchAll());
    }

    public function recent(int $clientId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM mia_client_leads WHERE client_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$clientId, $limit]);
        return array_map([ClientLead::class, 'fromRow'], $stmt->fetchAll());
    }

    public function findById(int $id, int $clientId): ?ClientLead
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM mia_client_leads WHERE id = ? AND client_id = ? LIMIT 1'
        );
        $stmt->execute([$id, $clientId]);
        $row = $stmt->fetch();
        return $row ? ClientLead::fromRow($row) : null;
    }

    public function create(int $clientId, array $data): ClientLead
    {
        $stmt = $this->db->prepare(
            'INSERT INTO mia_client_leads (client_id, contact_name, phone, source, status, value_estimate, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $clientId,
            trim($data['contact_name'] ?? ''),
            trim($data['phone'] ?? ''),
            $data['source'] ?? 'whatsapp',
            $data['status'] ?? 'new',
            (float)($data['value_estimate'] ?? 0),
            trim($data['notes'] ?? ''),
        ]);
        return $this->findById((int)$this->db->lastInsertId(), $clientId);
    }

    public function update(int $id, int $clientId, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE mia_client_leads
             SET status = ?, contact_name = ?, notes = ?, value_estimate = ?, updated_at = NOW()
             WHERE id = ? AND client_id = ?'
        );
        $stmt->execute([
            $data['status']         ?? 'new',
            trim($data['contact_name'] ?? ''),
            trim($data['notes']     ?? ''),
            (float)($data['value_estimate'] ?? 0),
            $id,
            $clientId,
        ]);
    }

    // ── Messages for a lead ───────────────────────────────────────────────────

    public function messagesForLead(int $leadId, int $clientId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM mia_client_messages
             WHERE lead_id = ? AND client_id = ?
             ORDER BY created_at ASC'
        );
        $stmt->execute([$leadId, $clientId]);
        return array_map([ClientMessage::class, 'fromRow'], $stmt->fetchAll());
    }

    public function saveMessage(int $clientId, int $leadId, string $phone, string $message, string $direction, string $handledBy): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO mia_client_messages (client_id, lead_id, phone, direction, message, handled_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$clientId, $leadId, $phone, $message, $direction, $handledBy]);
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function stats(int $clientId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'new') AS new_leads,
                SUM(status = 'interested') AS interested,
                SUM(status = 'closed_won') AS won,
                SUM(status = 'closed_lost') AS lost,
                SUM(value_estimate) AS pipeline_value
             FROM mia_client_leads
             WHERE client_id = ?"
        );
        $stmt->execute([$clientId]);
        return $stmt->fetch() ?: [];
    }

    public function todayMessages(int $clientId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM mia_client_messages
             WHERE client_id = ? AND DATE(created_at) = CURDATE()"
        );
        $stmt->execute([$clientId]);
        return (int)$stmt->fetchColumn();
    }

    // ── Analytics ────────────────────────────────────────────────────────────

    public function analyticsData(int $clientId): array
    {
        // Leads per week — last 8 weeks
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%u') AS yw,
                    DATE_FORMAT(MIN(created_at), '%d %b')  AS label,
                    COUNT(*) AS cnt
             FROM mia_client_leads
             WHERE client_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
             GROUP BY yw ORDER BY yw"
        );
        $stmt->execute([$clientId]);
        $leadsPerWeek = $stmt->fetchAll();

        // Status breakdown
        $stmt = $this->db->prepare(
            "SELECT status, COUNT(*) AS cnt
             FROM mia_client_leads
             WHERE client_id = ?
             GROUP BY status"
        );
        $stmt->execute([$clientId]);
        $statusBreakdown = $stmt->fetchAll();

        // Messages per day — last 14 days
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(created_at, '%d %b') AS label,
                    COUNT(*) AS cnt
             FROM mia_client_messages
             WHERE client_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
             GROUP BY DATE(created_at), DATE_FORMAT(created_at, '%d %b')
             ORDER BY DATE(created_at)"
        );
        $stmt->execute([$clientId]);
        $messagesPerDay = $stmt->fetchAll();

        // Average messages per lead
        $stmt = $this->db->prepare(
            "SELECT COALESCE(AVG(mc), 0) AS avg_msgs
             FROM (
                 SELECT lead_id, COUNT(*) AS mc
                 FROM mia_client_messages
                 WHERE client_id = ?
                 GROUP BY lead_id
             ) sub"
        );
        $stmt->execute([$clientId]);
        $avgMsgs = round((float)$stmt->fetchColumn(), 1);

        // Conversion rate: won / total
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(status = 'closed_won') AS won
             FROM mia_client_leads WHERE client_id = ?"
        );
        $stmt->execute([$clientId]);
        $conv = $stmt->fetch();
        $convRate = ($conv['total'] > 0)
            ? round(($conv['won'] / $conv['total']) * 100, 1)
            : 0;

        return [
            'leads_per_week'   => $leadsPerWeek,
            'status_breakdown' => $statusBreakdown,
            'messages_per_day' => $messagesPerDay,
            'avg_msgs'         => $avgMsgs,
            'conv_rate'        => $convRate,
            'total_leads'      => (int)($conv['total'] ?? 0),
            'won_leads'        => (int)($conv['won']   ?? 0),
        ];
    }
}
