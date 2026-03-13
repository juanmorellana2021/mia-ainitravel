<?php
/**
 * mia/models/Sequence.php
 *
 * A follow-up sequence defined by a client.
 * Contains N SequenceSteps fired in order with configurable delays.
 */

declare(strict_types=1);

class Sequence
{
    public int    $id         = 0;
    public int    $client_id  = 0;
    public string $name       = '';
    public string $trigger    = 'manual';   // manual | on_new | on_interested
    public string $status     = 'active';   // active | archived
    public string $created_at = '';
    public string $updated_at = '';

    /** @var SequenceStep[] Populated on demand */
    public array $steps = [];

    public static function fromRow(array $row): self
    {
        $s = new self();
        foreach ($row as $key => $val) {
            if (property_exists($s, $key) && $key !== 'steps') {
                $s->$key = match ($key) {
                    'id', 'client_id' => (int) $val,
                    default           => (string) ($val ?? ''),
                };
            }
        }
        return $s;
    }

    public function triggerLabel(): string
    {
        return match ($this->trigger) {
            'manual'        => 'Manual',
            'on_new'        => 'Al crear lead',
            'on_interested' => 'Al pasar a Interesado',
            default         => ucfirst($this->trigger),
        };
    }

    public function triggerBadgeClass(): string
    {
        return match ($this->trigger) {
            'manual'        => 'secondary',
            'on_new'        => 'primary',
            'on_interested' => 'success',
            default         => 'dark',
        };
    }
}
