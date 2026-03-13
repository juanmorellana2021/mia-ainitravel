<?php
/**
 * mia/models/LeadSequence.php
 *
 * Tracks a single lead's enrollment in one follow-up sequence.
 * current_step = how many steps have already fired (0 = none yet).
 */

declare(strict_types=1);

class LeadSequence
{
    public int    $id           = 0;
    public int    $lead_id      = 0;
    public int    $sequence_id  = 0;
    public int    $client_id    = 0;
    public int    $current_step = 0;
    public string $next_fire_at = '';
    public string $status       = 'active';  // active | paused | completed | cancelled
    public string $enrolled_at  = '';

    public static function fromRow(array $row): self
    {
        $ls = new self();
        foreach ($row as $key => $val) {
            if (property_exists($ls, $key)) {
                $ls->$key = match ($key) {
                    'id', 'lead_id', 'sequence_id', 'client_id', 'current_step' => (int) $val,
                    default => (string) ($val ?? ''),
                };
            }
        }
        return $ls;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active'    => 'Activa',
            'paused'    => 'Pausada',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            default     => ucfirst($this->status),
        };
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            'active'    => 'success',
            'paused'    => 'warning',
            'completed' => 'primary',
            'cancelled' => 'secondary',
            default     => 'dark',
        };
    }
}
