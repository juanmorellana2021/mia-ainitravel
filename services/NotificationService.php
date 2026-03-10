<?php
/**
 * mia/services/NotificationService.php
 *
 * Sends email alerts to clients when Mia captures a lead.
 * Queries all active clients with notify_on_capture = 1 and emails them.
 */

declare(strict_types=1);

class NotificationService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get();
    }

    /**
     * Called when MiaSalesService transitions a lead to "captured".
     * $session: the full session array (business_name, contact_name, phone, email, etc.)
     */
    public function notifyLeadCaptured(array $session): void
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM mia_clients
             WHERE notify_on_capture = 1 AND plan_status IN ('trial', 'active')"
        );
        $stmt->execute();
        $clients = $stmt->fetchAll();

        if (empty($clients)) return;

        $bizName = htmlspecialchars($session['business_name'] ?? 'Sin nombre');
        $contact = htmlspecialchars($session['contact_name']  ?? '—');
        $phone   = htmlspecialchars($session['phone']         ?? '—');
        $email   = htmlspecialchars($session['email']         ?? '—');
        $bizType = htmlspecialchars($session['business_type'] ?? '—');
        $rooms   = htmlspecialchars((string)($session['room_count'] ?? '—'));
        $time    = date('d/m/Y H:i');

        $subject = "🎉 Nuevo lead capturado — {$bizName}";

        $dashUrl = App::URL . '/dashboard/leads';
        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  body  { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; background:#f0f4f8; margin:0; padding:20px; }
  .card { background:#fff; border-radius:12px; max-width:520px; margin:0 auto; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08); }
  .hd   { background:#1a1a2e; color:#fff; padding:28px 24px; text-align:center; }
  .hd h2{ margin:8px 0 4px; color:#25d366; font-size:20px; }
  .bd   { padding:24px; }
  .cell { padding:10px 14px; background:#f8f9fa; border-radius:8px; }
  .lbl  { font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#6c757d; margin-bottom:3px; }
  .val  { font-weight:600; font-size:15px; color:#212529; }
  .btn  { display:inline-block; background:#25d366; color:#fff !important; padding:12px 32px; border-radius:8px; text-decoration:none; font-weight:700; margin-top:20px; }
  .ft   { background:#f8f9fa; padding:14px 24px; text-align:center; font-size:12px; color:#adb5bd; }
</style>
</head>
<body>
<div class="card">
  <div class="hd">
    <div style="font-size:2.4rem">🎉</div>
    <h2>Nuevo lead capturado</h2>
    <p style="margin:0;color:#ccc;font-size:13px">{$time}</p>
  </div>
  <div class="bd">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td class="cell"><div class="lbl">Negocio</div><div class="val">{$bizName}</div></td>
        <td width="12"></td>
        <td class="cell"><div class="lbl">Tipo</div><div class="val">{$bizType}</div></td>
      </tr>
      <tr><td colspan="3" height="10"></td></tr>
      <tr>
        <td class="cell"><div class="lbl">Contacto</div><div class="val">{$contact}</div></td>
        <td width="12"></td>
        <td class="cell"><div class="lbl">Habitaciones</div><div class="val">{$rooms}</div></td>
      </tr>
      <tr><td colspan="3" height="10"></td></tr>
      <tr>
        <td class="cell"><div class="lbl">WhatsApp</div><div class="val">+{$phone}</div></td>
        <td width="12"></td>
        <td class="cell"><div class="lbl">Email</div><div class="val">{$email}</div></td>
      </tr>
    </table>
    <div style="text-align:center">
      <a href="{$dashUrl}" class="btn">Ver en el dashboard →</a>
    </div>
  </div>
  <div class="ft">Mia by AiniTravel &middot; Alertas automáticas</div>
</div>
</body>
</html>
HTML;

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Mia Alerts <noreply@ainitravel.com>\r\n";
        $headers .= "X-Mailer: PHP/" . PHP_VERSION . "\r\n";

        foreach ($clients as $client) {
            $to = !empty($client['notify_email'])
                ? $client['notify_email']
                : $client['email'];

            if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                @mail($to, $subject, $html, $headers);
                error_log("[Mia] Lead capture notification sent to {$to}");
            }
        }
    }
}
