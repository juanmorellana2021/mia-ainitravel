<?php
/**
 * mia/models/ClientAddon.php
 *
 * A one-time monthly add-on purchased by a client to extend their
 * conversation capacity beyond their plan's monthly limit.
 *
 * Types:
 *   extra_500      — adds 500 conversations to this month (stackable)
 *   unlimited_month — removes the monthly cap for the current month
 *   notice         — internal marker: limit-hit email already sent this month
 *
 * month_year is always 'YYYY-MM' matching the calendar month it applies to.
 */

declare(strict_types=1);

class ClientAddon
{
    public int    $id;
    public int    $client_id;
    public string $type        = 'extra_500';   // extra_500 | unlimited_month | notice
    public string $month_year  = '';            // 'YYYY-MM'
    public string $status      = 'pending';     // pending | active
    public float  $amount_paid = 0.0;
    public ?string $mp_preference_id = null;
    public ?string $mp_payment_id    = null;
    public string $created_at  = '';

    public static function fromRow(array $row): self
    {
        $a = new self();
        foreach ($row as $key => $val) {
            if (property_exists($a, $key)) {
                $a->$key = match (true) {
                    is_null($val) && in_array($key, ['mp_preference_id', 'mp_payment_id'], true) => null,
                    in_array($key, ['id', 'client_id'], true) => (int)$val,
                    $key === 'amount_paid' => (float)$val,
                    default => (string)$val,
                };
            }
        }
        return $a;
    }

    public function label(): string
    {
        return match ($this->type) {
            'extra_500'       => '+500 conversaciones',
            'unlimited_month' => 'Ilimitado este mes',
            default           => 'Aviso',
        };
    }
}
