<?php
/**
 * mia/services/SequenceService.php
 *
 * All business logic for follow-up sequences:
 *  - CRUD for sequences and their steps
 *  - Enrolling / unenrolling leads
 *  - Firing due steps (called by cron_sequences.php)
 *  - Auto-pausing when a lead replies
 */

declare(strict_types=1);

class SequenceService
{
    private PDO $db;

    private const BOT_URL = 'http://127.0.0.1:3001/send';

    public function __construct()
    {
        $this->db = Database::get();
        $this->ensureTables();
    }

    // ── Table bootstrap ───────────────────────────────────────────────────────

    private function ensureTables(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS mia_sequences (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                client_id   INT          NOT NULL,
                name        VARCHAR(120) NOT NULL,
                `trigger`   VARCHAR(40)  NOT NULL DEFAULT 'manual',
                status      VARCHAR(20)  NOT NULL DEFAULT 'active',
                created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_client (client_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS mia_sequence_steps (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                sequence_id INT  NOT NULL,
                step_order  INT  NOT NULL DEFAULT 1,
                delay_days  INT  NOT NULL DEFAULT 1,
                message     TEXT NOT NULL,
                created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_seq (sequence_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS mia_lead_sequences (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                lead_id      INT         NOT NULL,
                sequence_id  INT         NOT NULL,
                client_id    INT         NOT NULL,
                current_step INT         NOT NULL DEFAULT 0,
                next_fire_at DATETIME    NOT NULL,
                status       VARCHAR(20) NOT NULL DEFAULT 'active',
                enrolled_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_lead_seq (lead_id, sequence_id),
                INDEX idx_fire (next_fire_at, status),
                INDEX idx_client (client_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // ── Sequence CRUD ─────────────────────────────────────────────────────────

    /** All sequences for a client (with step count). */
    public function all(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*,
                   COUNT(ss.id) AS step_count
            FROM mia_sequences s
            LEFT JOIN mia_sequence_steps ss ON ss.sequence_id = s.id
            WHERE s.client_id = ?
            GROUP BY s.id
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$clientId]);
        return array_map(
            fn($row) => Sequence::fromRow($row),
            $stmt->fetchAll()
        );
    }

    /** Single sequence — returns null if not found or belongs to wrong client. */
    public function find(int $id, int $clientId): ?Sequence
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM mia_sequences WHERE id = ? AND client_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $clientId]);
        $row = $stmt->fetch();
        return $row ? Sequence::fromRow($row) : null;
    }

    /**
     * Save (insert or update) a sequence + its steps.
     * Pass id=0 for insert (returns new id).
     *
     * @param array $steps  Each: ['delay_days' => int, 'message' => string]
     */
    public function save(
        int    $clientId,
        int    $id,
        string $name,
        string $trigger,
        array  $steps
    ): int {
        $name    = trim(substr($name, 0, 120));
        $trigger = in_array($trigger, ['manual', 'on_new', 'on_interested'], true)
            ? $trigger : 'manual';

        if ($id === 0) {
            $stmt = $this->db->prepare(
                "INSERT INTO mia_sequences (client_id, name, `trigger`) VALUES (?,?,?)"
            );
            $stmt->execute([$clientId, $name, $trigger]);
            $id = (int)$this->db->lastInsertId();
        } else {
            $stmt = $this->db->prepare(
                "UPDATE mia_sequences SET name=?, `trigger`=?, updated_at=NOW()
                 WHERE id=? AND client_id=?"
            );
            $stmt->execute([$name, $trigger, $id, $clientId]);
            // Remove all existing steps to replace them
            $this->db->prepare("DELETE FROM mia_sequence_steps WHERE sequence_id=?")
                     ->execute([$id]);
        }

        // Insert steps in order
        $stepStmt = $this->db->prepare(
            "INSERT INTO mia_sequence_steps (sequence_id, step_order, delay_days, message)
             VALUES (?,?,?,?)"
        );
        $order = 1;
        foreach ($steps as $step) {
            $delay   = max(0, (int)($step['delay_days'] ?? 1));
            $message = trim((string)($step['message'] ?? ''));
            if ($message === '') {
                continue;
            }
            $stepStmt->execute([$id, $order, $delay, $message]);
            $order++;
        }

        return $id;
    }

    /** Archive (soft-delete) a sequence owned by the client. */
    public function archive(int $id, int $clientId): void
    {
        $this->db->prepare(
            "UPDATE mia_sequences SET status='archived' WHERE id=? AND client_id=?"
        )->execute([$id, $clientId]);
    }

    /** Steps for a sequence, ordered. */
    public function steps(int $sequenceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM mia_sequence_steps WHERE sequence_id=? ORDER BY step_order ASC"
        );
        $stmt->execute([$sequenceId]);
        return array_map(fn($r) => SequenceStep::fromRow($r), $stmt->fetchAll());
    }

    // ── Enrollment ────────────────────────────────────────────────────────────

    /**
     * Enroll a lead in a sequence.
     * First step fires after step[0].delay_days from now.
     * Silently does nothing if already enrolled and active.
     */
    public function enroll(int $leadId, int $sequenceId, int $clientId): void
    {
        $steps = $this->steps($sequenceId);
        if (empty($steps)) {
            return;
        }
        $firstDelay  = $steps[0]->delay_days;
        $nextFireAt  = date('Y-m-d H:i:s', strtotime("+{$firstDelay} days"));

        // INSERT IGNORE respects the UNIQUE KEY (won't re-enroll if already active)
        $this->db->prepare("
            INSERT IGNORE INTO mia_lead_sequences
                (lead_id, sequence_id, client_id, current_step, next_fire_at, status)
            VALUES (?, ?, ?, 0, ?, 'active')
        ")->execute([$leadId, $sequenceId, $clientId, $nextFireAt]);
    }

    /** Cancel or pause a specific enrollment. */
    public function unenroll(int $leadId, int $sequenceId, int $clientId): void
    {
        $this->db->prepare("
            UPDATE mia_lead_sequences
            SET status = 'cancelled'
            WHERE lead_id=? AND sequence_id=? AND client_id=?
        ")->execute([$leadId, $sequenceId, $clientId]);
    }

    /**
     * Pause all active sequences for a lead.
     * Called from ClientBotService when the lead sends any inbound message.
     */
    public function pauseForLead(int $leadId, int $clientId): void
    {
        $this->db->prepare("
            UPDATE mia_lead_sequences
            SET status='paused'
            WHERE lead_id=? AND client_id=? AND status='active'
        ")->execute([$leadId, $clientId]);
    }

    /** Active enrollments for a specific lead. */
    public function enrollmentsForLead(int $leadId, int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT ls.*, s.name AS seq_name
            FROM mia_lead_sequences ls
            JOIN mia_sequences s ON s.id = ls.sequence_id
            WHERE ls.lead_id=? AND ls.client_id=?
            ORDER BY ls.enrolled_at DESC
        ");
        $stmt->execute([$leadId, $clientId]);
        return $stmt->fetchAll();
    }

    /** All enrollments across a client's sequences, with lead name. */
    public function allEnrollments(int $clientId, int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT ls.*, s.name AS seq_name,
                   cl.contact_name, cl.phone
            FROM mia_lead_sequences ls
            JOIN mia_sequences s ON s.id = ls.sequence_id
            JOIN mia_client_leads cl ON cl.id = ls.lead_id
            WHERE ls.client_id = ?
            ORDER BY ls.next_fire_at ASC
            LIMIT ?
        ");
        $stmt->execute([$clientId, $limit]);
        return $stmt->fetchAll();
    }

    // ── Cron: fire due steps ──────────────────────────────────────────────────

    /**
     * Find and fire all due sequence steps across ALL clients.
     * Returns a summary array for logging.
     * Called from cron_sequences.php (CLI or cron job).
     */
    public function fireDueSteps(): array
    {
        $now = date('Y-m-d H:i:s');

        // Fetch all active enrollments whose next_fire_at is in the past
        $stmt = $this->db->prepare("
            SELECT ls.*,
                   cl.phone      AS lead_phone,
                   cl.contact_name,
                   cl.client_id  AS lead_client_id
            FROM mia_lead_sequences ls
            JOIN mia_client_leads cl ON cl.id = ls.lead_id
            WHERE ls.status = 'active'
              AND ls.next_fire_at <= ?
        ");
        $stmt->execute([$now]);
        $due = $stmt->fetchAll();

        $fired  = 0;
        $errors = [];

        foreach ($due as $enrollment) {
            $nextStepOrder = $enrollment['current_step'] + 1;

            // Get the next step
            $stepStmt = $this->db->prepare("
                SELECT * FROM mia_sequence_steps
                WHERE sequence_id = ? AND step_order = ?
                LIMIT 1
            ");
            $stepStmt->execute([$enrollment['sequence_id'], $nextStepOrder]);
            $step = $stepStmt->fetch();

            if (!$step) {
                // No more steps → sequence completed
                $this->db->prepare("
                    UPDATE mia_lead_sequences SET status='completed'
                    WHERE id=?
                ")->execute([$enrollment['id']]);
                continue;
            }

            // Send via bot
            $phone   = $enrollment['lead_phone'];
            $message = $step['message'];
            $sent    = $this->sendViaBot($phone, $message);

            if ($sent) {
                $fired++;

                // Advance enrollment to next step
                $lookAheadStmt = $this->db->prepare("
                    SELECT delay_days FROM mia_sequence_steps
                    WHERE sequence_id=? AND step_order=?
                    LIMIT 1
                ");
                $lookAheadStmt->execute([$enrollment['sequence_id'], $nextStepOrder + 1]);
                $nextStep = $lookAheadStmt->fetch();

                if ($nextStep) {
                    $nextFireAt = date('Y-m-d H:i:s', strtotime("+{$nextStep['delay_days']} days"));
                    $this->db->prepare("
                        UPDATE mia_lead_sequences
                        SET current_step=?, next_fire_at=?
                        WHERE id=?
                    ")->execute([$nextStepOrder, $nextFireAt, $enrollment['id']]);
                } else {
                    // This was the last step
                    $this->db->prepare("
                        UPDATE mia_lead_sequences SET current_step=?, status='completed'
                        WHERE id=?
                    ")->execute([$nextStepOrder, $enrollment['id']]);
                }
            } else {
                $errors[] = "lead_id={$enrollment['lead_id']} phone={$phone}";
            }
        }

        return ['due' => count($due), 'fired' => $fired, 'errors' => $errors];
    }

    // ── Auto-enroll hooks ─────────────────────────────────────────────────────

    /**
     * Called when a lead is created or status changes.
     * Finds sequences with matching trigger and enrolls.
     */
    public function autoEnroll(int $leadId, int $clientId, string $trigger): void
    {
        $stmt = $this->db->prepare("
            SELECT id FROM mia_sequences
            WHERE client_id=? AND `trigger`=? AND status='active'
        ");
        $stmt->execute([$clientId, $trigger]);
        foreach ($stmt->fetchAll() as $seq) {
            $this->enroll($leadId, (int)$seq['id'], $clientId);
        }
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function sendViaBot(string $phone, string $message): bool
    {
        $payload = json_encode(['to' => $phone, 'message' => $message]);
        $ctx     = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
                'content'       => $payload,
                'timeout'       => 10,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents(self::BOT_URL, false, $ctx);
        if ($response === false) {
            return false;
        }
        $data = json_decode($response, true);
        return ($data['ok'] ?? false) || ($data['status'] ?? '') === 'sent';
    }
}
