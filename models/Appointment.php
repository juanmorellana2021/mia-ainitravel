<?php
/**
 * mia/models/Appointment.php
 *
 * Represents a single booked appointment.
 */

declare(strict_types=1);

class Appointment
{
    public int     $id;
    public int     $clientId;
    public ?int    $leadId;
    public string  $contactName;
    public string  $phone;
    public string  $startsAt;    // 'Y-m-d H:i:s'
    public string  $endsAt;      // 'Y-m-d H:i:s'
    public ?string $notes;
    public string  $status;      // pending | confirmed | cancelled | completed
    public bool    $reminderSent;
    public string  $createdAt;
    public string  $updatedAt;

    public static function fromRow(array $row): static
    {
        $a = new static();
        $a->id           = (int)$row['id'];
        $a->clientId     = (int)$row['client_id'];
        $a->leadId       = isset($row['lead_id']) ? (int)$row['lead_id'] : null;
        $a->contactName  = $row['contact_name'] ?? '';
        $a->phone        = $row['phone']        ?? '';
        $a->startsAt     = $row['starts_at']    ?? '';
        $a->endsAt       = $row['ends_at']      ?? '';
        $a->notes        = $row['notes']        ?? null;
        $a->status       = $row['status']       ?? 'pending';
        $a->reminderSent = (bool)($row['reminder_sent'] ?? false);
        $a->createdAt    = $row['created_at']   ?? '';
        $a->updatedAt    = $row['updated_at']   ?? '';
        return $a;
    }

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isConfirmed(): bool  { return $this->status === 'confirmed'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }

    /**
     * Returns a human-readable start time in the given timezone.
     * E.g. "Lunes 14 jul · 10:00 am"
     */
    public function formattedStart(string $tz = 'America/Lima'): string
    {
        try {
            $dt = new DateTimeImmutable($this->startsAt, new DateTimeZone('UTC'));
            $dt = $dt->setTimezone(new DateTimeZone($tz));
        } catch (\Exception $e) {
            return $this->startsAt;
        }
        $days = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles',
                 'Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
        $months = ['January'=>'ene','February'=>'feb','March'=>'mar','April'=>'abr',
                   'May'=>'may','June'=>'jun','July'=>'jul','August'=>'ago',
                   'September'=>'sep','October'=>'oct','November'=>'nov','December'=>'dic'];
        $dayName   = $days[$dt->format('l')]   ?? $dt->format('l');
        $monthName = $months[$dt->format('F')] ?? $dt->format('F');
        return "{$dayName} {$dt->format('j')} {$monthName} · {$dt->format('g:i')} {$dt->format('a')}";
    }
}
