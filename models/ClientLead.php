<?php
/**
 * mia/models/ClientLead.php
 *
 * A lead (prospect) that a client's Mia bot has been managing.
 */

declare(strict_types=1);

class ClientLead
{
    public int    $id;
    public int    $client_id;
    public string $contact_name   = '';
    public string $phone          = '';
    public string $source         = 'whatsapp';  // facebook, whatsapp, instagram, website
    public string $status         = 'new';       // new, interested, demo, closed_won, closed_lost
    public string $contact_type   = 'lead';      // lead | friend | staff | proveedor | ignored
    public float  $value_estimate = 0.0;
    public ?string $notes         = null;
    public string $created_at     = '';
    public string $updated_at     = '';

    // Joined from last message query (not DB columns)
    public ?string $last_message_text       = null;
    public ?string $last_message_at         = null;
    public ?string $last_message_direction  = null;
    public ?string $last_message_handled_by = null;
    public ?string $profile_pic             = null;

    public static function fromRow(array $row): self
    {
        $l = new self();
        foreach ($row as $key => $val) {
            if (property_exists($l, $key)) {
                $l->$key = match (true) {
                    is_null($val) && (new \ReflectionProperty(self::class, $key))->getType()?->allowsNull() => $val,
                    in_array($key, ['id', 'client_id'], true) => (int) $val,
                    $key === 'value_estimate' => (float) $val,
                    default => $val,
                };
            }
        }
        return $l;
    }

    public function contactTypeLabel(): string
    {
        return match ($this->contact_type) {
            'friend'    => 'Amigo/a',
            'staff'     => 'Staff',
            'proveedor' => 'Proveedor',
            'ignored'   => 'Ignorar',
            default     => 'Lead',
        };
    }

    public function contactTypeBadgeClass(): string
    {
        return match ($this->contact_type) {
            'friend'    => 'success',
            'staff'     => 'info',
            'proveedor' => 'warning',
            'ignored'   => 'danger',
            default     => 'primary',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'new'         => 'Nuevo',
            'interested'  => 'Interesado',
            'demo'        => 'Demo',
            'closed_won'  => 'Cerrado ✓',
            'closed_lost' => 'Perdido',
            default       => ucfirst($this->status),
        };
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            'new'         => 'primary',
            'interested'  => 'info',
            'demo'        => 'warning',
            'closed_won'  => 'success',
            'closed_lost' => 'secondary',
            default       => 'dark',
        };
    }

    public function sourceIcon(): string
    {
        return match ($this->source) {
            'facebook'  => 'bi-facebook',
            'instagram' => 'bi-instagram',
            'website'   => 'bi-globe',
            default     => 'bi-whatsapp',
        };
    }

    /**
     * Best available display name for this lead.
     * Falls back to formatted phone when no real name is known.
     */
    public function displayName(): string
    {
        $name = trim($this->contact_name);
        if ($name !== '' && $name !== 'Sin nombre') {
            return $name;
        }
        // Format phone as +XXXXXXXXXXX (groups of 3 after country prefix)
        $p = preg_replace('/\D/', '', $this->phone);
        return $p !== '' ? '+' . $p : 'Sin nombre';
    }

    /**
     * Single character for the avatar circle.
     */
    public function displayInitial(): string
    {
        $name = trim($this->contact_name);
        if ($name !== '' && $name !== 'Sin nombre') {
            return mb_strtoupper(mb_substr($name, 0, 1));
        }
        return '#';
    }
}
