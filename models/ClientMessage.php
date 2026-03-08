<?php
/**
 * mia/models/ClientMessage.php
 *
 * A single WhatsApp message in a client's conversation thread.
 */

declare(strict_types=1);

class ClientMessage
{
    public int    $id;
    public int    $client_id;
    public ?int   $lead_id    = null;
    public string $phone      = '';
    public string $direction  = 'inbound';  // inbound | outbound
    public string $message    = '';
    public string $handled_by = 'mia';      // mia | human
    public string $created_at = '';

    public static function fromRow(array $row): self
    {
        $m = new self();
        foreach ($row as $key => $val) {
            if (property_exists($m, $key)) {
                $m->$key = $val;
            }
        }
        return $m;
    }
}
