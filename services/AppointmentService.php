<?php
/**
 * mia/services/AppointmentService.php
 *
 * Business logic for the Scheduler / Citas feature.
 * Handles availability config, slot calculation, booking, and cron reminders.
 */

declare(strict_types=1);

class AppointmentService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::get();
        $this->ensureTables();
    }

    // ── Availability ──────────────────────────────────────────────────────────

    public function getAvailability(int $clientId): ?Availability
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mia_availability WHERE client_id = ? LIMIT 1');
        $stmt->execute([$clientId]);
        $row = $stmt->fetch();
        return $row ? Availability::fromRow($row) : null;
    }

    public function saveAvailability(int $clientId, array $data): void
    {
        $allowedDays = ['mon','tue','wed','thu','fri','sat','sun'];
        $allowedTz   = timezone_identifiers_list();
        $tz = in_array($data['timezone'] ?? '', $allowedTz) ? $data['timezone'] : 'America/Lima';

        $schedule = [];
        foreach ($allowedDays as $d) {
            $schedule[$d] = [
                'enabled' => !empty($data["schedule_{$d}_enabled"]),
                'open'    => preg_replace('/[^0-9:]/', '', $data["schedule_{$d}_open"]  ?? '09:00'),
                'close'   => preg_replace('/[^0-9:]/', '', $data["schedule_{$d}_close"] ?? '18:00'),
            ];
        }

        $slot   = max(15, min(480, (int)($data['slot_minutes']   ?? 60)));
        $buffer = max(0,  min(120, (int)($data['buffer_minutes'] ?? 0)));
        $maxd   = max(1,  min(90,  (int)($data['max_days_ahead'] ?? 14)));

        $existing = $this->getAvailability($clientId);
        if ($existing) {
            $stmt = $this->pdo->prepare(
                'UPDATE mia_availability
                 SET slot_minutes=?, buffer_minutes=?, max_days_ahead=?, timezone=?, schedule=?, updated_at=NOW()
                 WHERE client_id=?'
            );
            $stmt->execute([$slot, $buffer, $maxd, $tz, json_encode($schedule, JSON_UNESCAPED_UNICODE), $clientId]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO mia_availability (client_id, slot_minutes, buffer_minutes, max_days_ahead, timezone, schedule)
                 VALUES (?,?,?,?,?,?)'
            );
            $stmt->execute([$clientId, $slot, $buffer, $maxd, $tz, json_encode($schedule, JSON_UNESCAPED_UNICODE)]);
        }
    }

    // ── Slot calculation ──────────────────────────────────────────────────────

    /**
     * Returns array of available time slot strings ("HH:MM") for a given date.
     * Excludes already-booked slots.
     */
    public function findSlots(int $clientId, string $date): array
    {
        $avail = $this->getAvailability($clientId);
        if (!$avail) return [];

        try {
            $tz = new DateTimeZone($avail->timezone);
            $dt = new DateTimeImmutable($date, $tz);
        } catch (\Exception $e) {
            return [];
        }

        // Check if date is within allowed range
        $today   = new DateTimeImmutable('today', $tz);
        $maxDate = $today->modify("+{$avail->maxDaysAhead} days");
        if ($dt < $today || $dt > $maxDate) return [];

        $dow = strtolower($dt->format('D')); // mon, tue, ...
        $day = $avail->schedule[$dow] ?? null;
        if (!$day || empty($day['enabled'])) return [];
        if (empty($day['open']) || empty($day['close'])) return [];

        // Build all theoretical slots for the day
        $openDt  = new DateTimeImmutable("{$date} {$day['open']}",  $tz);
        $closeDt = new DateTimeImmutable("{$date} {$day['close']}", $tz);
        $step    = ($avail->slotMinutes + $avail->bufferMinutes) * 60;

        $theoretical = [];
        $cursor      = $openDt;
        while ($cursor->getTimestamp() + $avail->slotMinutes * 60 <= $closeDt->getTimestamp()) {
            $theoretical[] = $cursor->format('H:i');
            $cursor = $cursor->modify("+{$step} seconds");
        }

        if (empty($theoretical)) return [];

        // Fetch booked/pending slots on this date
        $stmt = $this->pdo->prepare(
            "SELECT starts_at FROM mia_appointments
             WHERE client_id = ? AND DATE(starts_at) = ? AND status IN ('pending','confirmed')"
        );
        $stmt->execute([$clientId, $date]);
        $booked = array_column($stmt->fetchAll(), 'starts_at');
        $bookedTimes = array_map(fn($s) => substr($s, 11, 5), $booked); // extract HH:MM

        return array_values(array_diff($theoretical, $bookedTimes));
    }

    // ── Booking ───────────────────────────────────────────────────────────────

    /**
     * Book a slot. Throws RuntimeException on conflict or invalid slot.
     *
     * $data keys: date (Y-m-d), time (H:i), contact_name, phone, notes, lead_id (optional)
     */
    public function book(int $clientId, array $data): Appointment
    {
        $date = $data['date'] ?? '';
        $time = $data['time'] ?? '';

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
            throw new \RuntimeException('Fecha u hora inválida.');
        }

        $avail = $this->getAvailability($clientId);
        if (!$avail) throw new \RuntimeException('El negocio no tiene horario configurado.');

        // Verify slot is actually available
        $available = $this->findSlots($clientId, $date);
        if (!in_array($time, $available, true)) {
            throw new \RuntimeException('El horario seleccionado ya no está disponible.');
        }

        $tz       = new DateTimeZone($avail->timezone);
        $startsAt = new DateTimeImmutable("{$date} {$time}", $tz);
        $endsAt   = $startsAt->modify("+{$avail->slotMinutes} minutes");

        $stmt = $this->pdo->prepare(
            'INSERT INTO mia_appointments
                (client_id, lead_id, contact_name, phone, starts_at, ends_at, notes, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $clientId,
            isset($data['lead_id']) ? (int)$data['lead_id'] : null,
            substr(trim($data['contact_name'] ?? ''), 0, 120),
            substr(trim($data['phone']        ?? ''), 0, 30),
            $startsAt->format('Y-m-d H:i:s'),
            $endsAt->format('Y-m-d H:i:s'),
            substr(trim($data['notes'] ?? ''), 0, 1000) ?: null,
            'confirmed',
        ]);

        return $this->getById((int)$this->pdo->lastInsertId(), $clientId);
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public function cancel(int $appointmentId, int $clientId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE mia_appointments SET status='cancelled', updated_at=NOW()
             WHERE id=? AND client_id=? AND status IN ('pending','confirmed')"
        );
        $stmt->execute([$appointmentId, $clientId]);
    }

    public function getById(int $id, int $clientId): ?Appointment
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM mia_appointments WHERE id=? AND client_id=? LIMIT 1'
        );
        $stmt->execute([$id, $clientId]);
        $row = $stmt->fetch();
        return $row ? Appointment::fromRow($row) : null;
    }

    /**
     * @param string $filter  'upcoming' | 'past' | '' (all)
     * @return Appointment[]
     */
    public function getByClient(int $clientId, string $filter = ''): array
    {
        $where = 'WHERE client_id = ?';
        $params = [$clientId];

        if ($filter === 'upcoming') {
            $where  .= " AND starts_at >= NOW() AND status NOT IN ('cancelled')";
        } elseif ($filter === 'past') {
            $where  .= " AND starts_at < NOW()";
        }

        $order = $filter === 'past' ? 'DESC' : 'ASC';
        $stmt = $this->pdo->prepare(
            "SELECT * FROM mia_appointments {$where} ORDER BY starts_at {$order} LIMIT 200"
        );
        $stmt->execute($params);
        return array_map([Appointment::class, 'fromRow'], $stmt->fetchAll());
    }

    // ── Cron: reminders ───────────────────────────────────────────────────────

    /**
     * Returns appointments that start in the next 24–25 hours and haven't had a reminder sent.
     * Called by cron_appointments.php.
     */
    public function getDueReminders(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, c.business_name, c.email AS owner_email, c.notify_email, c.bot_config
             FROM mia_appointments a
             JOIN mia_clients c ON c.id = a.client_id
             WHERE a.reminder_sent = 0
               AND a.status IN ('pending','confirmed')
               AND a.starts_at BETWEEN DATE_ADD(NOW(), INTERVAL 23 HOUR)
                                   AND DATE_ADD(NOW(), INTERVAL 25 HOUR)"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markReminderSent(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE mia_appointments SET reminder_sent=1, updated_at=NOW() WHERE id=?'
        );
        $stmt->execute([$id]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function ensureTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `mia_availability` (
                `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `client_id`      INT UNSIGNED NOT NULL,
                `slot_minutes`   SMALLINT     NOT NULL DEFAULT 60,
                `buffer_minutes` SMALLINT     NOT NULL DEFAULT 0,
                `max_days_ahead` TINYINT      NOT NULL DEFAULT 14,
                `timezone`       VARCHAR(64)  NOT NULL DEFAULT 'America/Lima',
                `schedule`       JSON         NOT NULL,
                `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_client` (`client_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `mia_appointments` (
                `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `client_id`     INT UNSIGNED NOT NULL,
                `lead_id`       INT UNSIGNED NULL,
                `contact_name`  VARCHAR(120) NOT NULL DEFAULT '',
                `phone`         VARCHAR(30)  NOT NULL DEFAULT '',
                `starts_at`     DATETIME     NOT NULL,
                `ends_at`       DATETIME     NOT NULL,
                `notes`         TEXT,
                `status`        ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
                `reminder_sent` TINYINT(1)   NOT NULL DEFAULT 0,
                `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_client_starts` (`client_id`, `starts_at`),
                KEY `idx_reminder`      (`reminder_sent`, `starts_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}
