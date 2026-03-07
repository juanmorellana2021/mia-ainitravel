<?php
/**
 * mia/models/Lead.php
 *
 * Represents a sales lead captured by Mia's WhatsApp conversation.
 */

declare(strict_types=1);

class Lead
{
    public int     $id;
    public string  $business_name;
    public string  $contact_name;
    public string  $phone;
    public string  $email;
    public string  $business_type;   // hotel, agency, hostel, restaurant
    public int     $room_count;
    public string  $current_method;  // manual, web_form, otas, mixed
    public string  $pain_point;      // after_hours, slow_replies, no_confirm, high_commissions
    public string  $status;          // new, contacted, demo_done, trial, converted, lost
    public string  $plan_interest;   // basic, pro, enterprise
    public ?string $notes;
    public string  $source;          // facebook, whatsapp, website, referral
    public string  $created_at;
    public string  $updated_at;

    /**
     * Create all from DB row.
     */
    public static function fromRow(array $row): self
    {
        $l = new self();
        foreach ($row as $key => $val) {
            if (property_exists($l, $key)) {
                $l->$key = $val ?? '';
            }
        }
        return $l;
    }
}
