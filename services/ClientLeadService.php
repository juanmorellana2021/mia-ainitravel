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
}
