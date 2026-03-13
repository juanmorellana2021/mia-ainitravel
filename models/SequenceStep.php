<?php
/**
 * mia/models/SequenceStep.php
 *
 * One step inside a follow-up sequence.
 * delay_days = days after the previous step was fired (or after enrollment for step 1).
 */

declare(strict_types=1);

class SequenceStep
{
    public int    $id          = 0;
    public int    $sequence_id = 0;
    public int    $step_order  = 1;
    public int    $delay_days  = 1;
    public string $message     = '';
    public string $created_at  = '';

    public static function fromRow(array $row): self
    {
        $s = new self();
        foreach ($row as $key => $val) {
            if (property_exists($s, $key)) {
                $s->$key = match ($key) {
                    'id', 'sequence_id', 'step_order', 'delay_days' => (int) $val,
                    default => (string) ($val ?? ''),
                };
            }
        }
        return $s;
    }
}
