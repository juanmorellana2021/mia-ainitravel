<?php
/**
 * mia/models/Client.php
 *
 * Represents a Mia business client (one of our paying customers).
 */

declare(strict_types=1);

class Client
{
    public int    $id;
    public string $business_name;
    public string $contact_name;
    public string $email;
    public string $password_hash;
    public string $phone           = '';
    public string $business_type   = 'other';
    public string $whatsapp_number = '';
    public string $plan            = 'trial';
    public string $plan_status     = 'trial';
    public ?string $trial_ends_at  = null;
    public string $created_at      = '';
    public string $updated_at      = '';

    // ── Notification settings ──────────────────────────────────────────────
    public ?string $notify_email          = null;
    public int     $notify_on_capture     = 1;
    public int     $notify_daily_summary  = 0;

    public static function fromRow(array $row): self
    {
        $c = new self();
        foreach ($row as $key => $val) {
            if (property_exists($c, $key)) {
                $c->$key = $val;
            }
        }
        return $c;
    }

    public function trialDaysLeft(): int
    {
        if (!$this->trial_ends_at) return 0;
        return max(0, (int) ceil((strtotime($this->trial_ends_at) - time()) / 86400));
    }

    public function isActive(): bool
    {
        return in_array($this->plan_status, ['trial', 'active'], true);
    }

    public function planLabel(): string
    {
        return match ($this->plan) {
            'basic'      => 'Básico',
            'pro'        => 'Pro',
            'enterprise' => 'Enterprise',
            default      => 'Prueba Gratuita',
        };
    }
}
