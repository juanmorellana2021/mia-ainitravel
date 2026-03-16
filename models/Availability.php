<?php
/**
 * mia/models/Availability.php
 *
 * Represents a client's booking availability configuration.
 */

declare(strict_types=1);

class Availability
{
    public int    $id;
    public int    $clientId;
    public int    $slotMinutes;
    public int    $bufferMinutes;
    public int    $maxDaysAhead;
    public string $timezone;
    public array  $schedule;      // keyed by 'mon'..'sun'
    public string $createdAt;
    public string $updatedAt;

    public static function fromRow(array $row): static
    {
        $a = new static();
        $a->id            = (int)$row['id'];
        $a->clientId      = (int)$row['client_id'];
        $a->slotMinutes   = (int)$row['slot_minutes'];
        $a->bufferMinutes = (int)$row['buffer_minutes'];
        $a->maxDaysAhead  = (int)$row['max_days_ahead'];
        $a->timezone      = $row['timezone'] ?? 'America/Lima';
        $a->schedule      = json_decode($row['schedule'] ?? '{}', true) ?: [];
        $a->createdAt     = $row['created_at'] ?? '';
        $a->updatedAt     = $row['updated_at'] ?? '';
        return $a;
    }

    /** Returns true if the given lowercase 3-letter day code (mon, tue…) is enabled. */
    public function hasDay(string $dow): bool
    {
        return !empty($this->schedule[$dow]['enabled']);
    }
}
