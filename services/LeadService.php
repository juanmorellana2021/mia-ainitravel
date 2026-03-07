<?php
/**
 * mia/services/LeadService.php
 *
 * CRUD operations for leads in the database.
 */

declare(strict_types=1);

class LeadService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::get();
        $this->ensureTable();
    }

    // ── Table setup ──────────────────────────────────────────────────────
    private function ensureTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS mia_leads (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                business_name   VARCHAR(255) NOT NULL DEFAULT '',
                contact_name    VARCHAR(255) NOT NULL DEFAULT '',
                phone           VARCHAR(50)  NOT NULL DEFAULT '',
                email           VARCHAR(255) NOT NULL DEFAULT '',
                business_type   VARCHAR(50)  NOT NULL DEFAULT 'hotel',
                room_count      INT          NOT NULL DEFAULT 0,
                current_method  VARCHAR(50)  NOT NULL DEFAULT '',
                pain_point      VARCHAR(100) NOT NULL DEFAULT '',
                status          VARCHAR(30)  NOT NULL DEFAULT 'new',
                plan_interest   VARCHAR(30)  NOT NULL DEFAULT 'basic',
                notes           TEXT         NULL,
                source          VARCHAR(50)  NOT NULL DEFAULT 'whatsapp',
                created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_phone (phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    // ── READ ──────────────────────────────────────────────────────────────

    /** @return Lead[] */
    public function all(string $statusFilter = ''): array
    {
        $sql = 'SELECT * FROM mia_leads';
        $params = [];
        if ($statusFilter !== '') {
            $sql .= ' WHERE status = ?';
            $params[] = $statusFilter;
        }
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map([Lead::class, 'fromRow'], $stmt->fetchAll());
    }

    public function findById(int $id): ?Lead
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mia_leads WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Lead::fromRow($row) : null;
    }

    public function findByPhone(string $phone): ?Lead
    {
        $phone = $this->normalizePhone($phone);
        $stmt = $this->pdo->prepare('SELECT * FROM mia_leads WHERE phone = ?');
        $stmt->execute([$phone]);
        $row = $stmt->fetch();
        return $row ? Lead::fromRow($row) : null;
    }

    // ── CREATE / UPDATE ──────────────────────────────────────────────────

    public function createFromSession(SalesSession $s): Lead
    {
        $phone = $this->normalizePhone($s->phone);

        $stmt = $this->pdo->prepare("
            INSERT INTO mia_leads (business_name, contact_name, phone, email, business_type, room_count, current_method, pain_point, source)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'whatsapp')
            ON DUPLICATE KEY UPDATE
                business_name  = VALUES(business_name),
                contact_name   = VALUES(contact_name),
                email          = VALUES(email),
                business_type  = VALUES(business_type),
                room_count     = VALUES(room_count),
                current_method = VALUES(current_method),
                pain_point     = VALUES(pain_point)
        ");

        $stmt->execute([
            $s->business_name ?? '',
            $s->contact_name  ?? '',
            $phone,
            $s->email ?? '',
            $s->business_type ?? 'hotel',
            $s->room_count ?? 0,
            $s->current_method ?? '',
            $s->pain_point ?? '',
        ]);

        return $this->findByPhone($phone);
    }

    public function updateStatus(int $id, string $status, string $notes = ''): bool
    {
        $allowed = ['new', 'contacted', 'demo_done', 'trial', 'converted', 'lost'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $sql = 'UPDATE mia_leads SET status = ?';
        $params = [$status];

        if ($notes !== '') {
            $sql .= ', notes = CONCAT(IFNULL(notes,""), ?)';
            $params[] = "\n[" . date('Y-m-d H:i') . "] $notes";
        }

        $sql .= ' WHERE id = ?';
        $params[] = $id;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // ── Stats ─────────────────────────────────────────────────────────────

    public function stats(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                COUNT(*)                                    AS total,
                SUM(status = 'new')                         AS new_leads,
                SUM(status = 'contacted')                   AS contacted,
                SUM(status = 'demo_done')                   AS demo_done,
                SUM(status = 'trial')                       AS on_trial,
                SUM(status = 'converted')                   AS converted,
                SUM(status = 'lost')                        AS lost
            FROM mia_leads
        ");
        return $stmt->fetch();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}
