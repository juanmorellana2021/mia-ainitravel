<?php
/**
 * mia/models/Subscription.php
 *
 * A billing/payment record for a client subscription.
 */

declare(strict_types=1);

class Subscription
{
    public int    $id;
    public int    $client_id;
    public string $plan                   = '';
    public int    $amount_cents           = 0;
    public string $currency               = 'PEN';
    public string $status                 = 'pending';  // pending, active, failed, cancelled
    public ?string $mp_preapproval_id  = null;
    public ?string $mp_payer_email     = null;
    public ?string $mp_init_point      = null;
    public ?string $billing_period_start  = null;
    public ?string $billing_period_end    = null;
    public ?string $paid_at               = null;
    public string $created_at             = '';

    public static function fromRow(array $row): self
    {
        $s = new self();
        foreach ($row as $key => $val) {
            if (property_exists($s, $key)) {
                $s->$key = $val;
            }
        }
        return $s;
    }

    public function formattedAmount(): string
    {
        return $this->currency . ' ' . number_format($this->amount_cents / 100, 2);
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            'active'    => 'success',
            'pending'   => 'warning',
            'failed'    => 'danger',
            'cancelled' => 'secondary',
            default     => 'dark',
        };
    }
}
