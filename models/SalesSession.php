<?php
/**
 * mia/models/SalesSession.php
 *
 * Tracks Mia's WhatsApp sales conversation state with a prospect.
 * Mirrors the same pattern as GuestBookingService sessions.
 */

declare(strict_types=1);

class SalesSession
{
    public int     $id;
    public string  $phone;
    public string  $state;           // intro, qualifying_size, qualifying_method, qualifying_pain, roi_pitch, demo, benefits, closing, captured, followup
    public ?string $business_name;
    public ?string $contact_name;
    public ?string $email;
    public ?string $business_type;
    public ?int    $room_count;
    public ?string $current_method;
    public ?string $pain_point;
    public string  $created_at;
    public string  $updated_at;

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
}
