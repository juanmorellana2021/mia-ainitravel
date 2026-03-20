<?php
/**
 * mia/models/LeadMemory.php
 *
 * A single remembered fact about a contact, extracted by AI or added manually.
 * Keyed by phone number — the persistent identifier in WhatsApp.
 */

declare(strict_types=1);

class LeadMemory
{
    public int    $id;
    public int    $client_id;
    public string $phone      = '';
    public string $fact       = '';
    public string $source     = 'ai';
    public string $created_at = '';

    public static function fromRow(array $row): self
    {
        $m = new self();
        foreach ($row as $key => $val) {
            if (property_exists($m, $key)) {
                $m->$key = in_array($key, ['id', 'client_id'], true)
                    ? (int) $val
                    : $val;
            }
        }
        return $m;
    }
}
