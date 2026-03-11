<?php
/**
 * mia/services/SuperAdminService.php
 *
 * Data queries for the superadmin panel.
 * All methods are read-only stats OR explicit mutations (update/delete).
 */

declare(strict_types=1);

class SuperAdminService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get();
    }

    // ── Dashboard KPIs ────────────────────────────────────────────────────────

    public function stats(): array
    {
        // Client counts by plan_status
        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(plan_status = 'trial')    AS trials,
                SUM(plan_status = 'active')   AS active,
                SUM(plan_status = 'expired')  AS expired,
                SUM(plan_status = 'cancelled') AS cancelled
             FROM mia_clients"
        );
        $clients = $stmt->fetch();

        // New signups this week
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM mia_clients
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        $newThisWeek = (int)$stmt->fetchColumn();

        // MRR — sum of active subscription amounts this month (in cents → PEN)
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(amount_cents), 0) AS mrr
             FROM mia_subscriptions
             WHERE status = 'active'
               AND billing_period_start <= CURDATE()
               AND (billing_period_end IS NULL OR billing_period_end >= CURDATE())"
        );
        $mrrCents = (int)$stmt->fetchColumn();

        // Today's messages across all clients
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM mia_client_messages WHERE DATE(created_at) = CURDATE()"
        );
        $todayMessages = (int)$stmt->fetchColumn();

        // Bot connections (clients with bot_wa_status = 'connected')
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM mia_clients WHERE bot_wa_status = 'connected'"
        );
        $botConnections = (int)$stmt->fetchColumn();

        // Total leads
        $stmt = $this->db->query("SELECT COUNT(*) FROM mia_client_leads");
        $totalLeads = (int)$stmt->fetchColumn();

        return [
            'clients'        => $clients,
            'new_this_week'  => $newThisWeek,
            'mrr_cents'      => $mrrCents,
            'today_messages' => $todayMessages,
            'bot_connections'=> $botConnections,
            'total_leads'    => $totalLeads,
        ];
    }

    // ── Client list ───────────────────────────────────────────────────────────

    public function allClients(string $search = '', string $statusFilter = ''): array
    {
        $where  = [];
        $params = [];

        if ($search) {
            $where[]  = '(c.business_name LIKE ? OR c.email LIKE ? OR c.contact_name LIKE ?)';
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($statusFilter) {
            $where[]  = 'c.plan_status = ?';
            $params[] = $statusFilter;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare(
            "SELECT
                c.id, c.business_name, c.contact_name, c.email, c.phone,
                c.plan, c.plan_status, c.bot_wa_status, c.trial_ends_at,
                c.created_at,
                COUNT(DISTINCT l.id)  AS lead_count,
                COUNT(DISTINCT m.id)  AS message_count,
                SUM(m.direction = 'inbound' AND DATE(m.created_at) = CURDATE()) AS today_msgs
             FROM mia_clients c
             LEFT JOIN mia_client_leads    l ON l.client_id = c.id
             LEFT JOIN mia_client_messages m ON m.client_id = c.id
             {$whereClause}
             GROUP BY c.id
             ORDER BY c.created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Single client full profile ────────────────────────────────────────────

    public function clientFull(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM mia_clients WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        if (!$client) return null;

        // Subscriptions
        $stmt = $this->db->prepare(
            'SELECT * FROM mia_subscriptions WHERE client_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$id]);
        $subscriptions = $stmt->fetchAll();

        // Lead stats
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'closed_won')  AS won,
                SUM(status = 'new')         AS new_leads,
                SUM(value_estimate)         AS pipeline
             FROM mia_client_leads WHERE client_id = ?"
        );
        $stmt->execute([$id]);
        $leadStats = $stmt->fetch();

        // Message stats
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(direction = 'inbound')  AS inbound,
                SUM(direction = 'outbound') AS outbound,
                SUM(handled_by = 'human')   AS human_handled
             FROM mia_client_messages WHERE client_id = ?"
        );
        $stmt->execute([$id]);
        $msgStats = $stmt->fetch();

        return [
            'client'        => $client,
            'subscriptions' => $subscriptions,
            'lead_stats'    => $leadStats,
            'msg_stats'     => $msgStats,
        ];
    }

    // ── Recent signups ────────────────────────────────────────────────────────

    public function recentSignups(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, business_name, email, plan, plan_status, created_at
             FROM mia_clients ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    // ── Mutations ──────────────────────────────────────────────────────────────

    public function updateClient(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE mia_clients
             SET business_name = ?, contact_name = ?, email = ?,
                 plan = ?, plan_status = ?, trial_ends_at = ?,
                 whatsapp_number = ?,
                 updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            trim($data['business_name']),
            trim($data['contact_name']),
            strtolower(trim($data['email'])),
            $data['plan'],
            $data['plan_status'],
            $data['trial_ends_at'] ?: null,
            trim($data['whatsapp_number'] ?? ''),
            $id,
        ]);
    }

    public function resetPassword(int $id, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            'UPDATE mia_clients SET password_hash = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$hash, $id]);
    }

    public function deleteClient(int $id): void
    {
        // Delete in dependency order
        $this->db->prepare('DELETE FROM mia_client_messages WHERE client_id = ?')->execute([$id]);
        $this->db->prepare('DELETE FROM mia_client_leads    WHERE client_id = ?')->execute([$id]);
        $this->db->prepare('DELETE FROM mia_subscriptions   WHERE client_id = ?')->execute([$id]);
        $this->db->prepare('DELETE FROM mia_broadcast_logs  WHERE client_id = ?')->execute([$id]);
        $this->db->prepare('DELETE FROM mia_clients         WHERE id        = ?')->execute([$id]);
    }
}
