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
    /**
     * Send welcome email to a newly created client with their login credentials.
     */
    public function sendWelcomeEmail(string $toEmail, string $contactName, string $bizName, string $tempPassword): void
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return;

        $loginUrl    = App::URL . '/login';
        $name        = htmlspecialchars($contactName ?: 'Bienvenido/a');
        $biz         = htmlspecialchars($bizName ?: 'tu negocio');
        $safeEmail   = htmlspecialchars($toEmail);
        $safePass    = htmlspecialchars($tempPassword);

        $subject = '🎉 Tu cuenta Mia está lista — aquí están tus accesos';
        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  body  { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; background:#f0f4f8; margin:0; padding:20px; }
  .card { background:#fff; border-radius:12px; max-width:520px; margin:0 auto; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08); }
  .hd   { background:#1a1a2e; color:#fff; padding:28px 24px; text-align:center; }
  .hd h2{ margin:8px 0 4px; color:#25d366; font-size:22px; }
  .bd   { padding:28px 24px; }
  .cred { background:#f8f9fa; border:1px solid #e9ecef; border-radius:10px; padding:18px 20px; margin:18px 0; }
  .lbl  { font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#6c757d; margin-bottom:4px; }
  .val  { font-weight:700; font-size:17px; color:#212529; letter-spacing:.5px; }
  .btn  { display:inline-block; background:#25d366; color:#fff !important; padding:14px 36px; border-radius:8px; text-decoration:none; font-weight:700; font-size:15px; }
  .ft   { background:#f8f9fa; padding:14px 24px; text-align:center; font-size:12px; color:#adb5bd; }
  .note { font-size:13px; color:#6c757d; margin-top:16px; line-height:1.5; }
</style>
</head>
<body>
<div class="card">
  <div class="hd">
    <div style="font-size:2.4rem">🎉</div>
    <h2>¡Tu cuenta está lista!</h2>
    <p style="margin:4px 0 0;color:#adb5bd;font-size:13px">Mia by AiniDesk</p>
  </div>
  <div class="bd">
    <p style="color:#212529;font-size:15px">Hola <strong>{$name}</strong>,</p>
    <p style="color:#495057;font-size:14px;line-height:1.6">
      Tu cuenta para <strong>{$biz}</strong> fue creada exitosamente. Aquí están tus datos de acceso:
    </p>
    <div class="cred">
      <div class="lbl">Email / Usuario</div>
      <div class="val" style="font-size:15px">{$safeEmail}</div>
    </div>
    <div class="cred">
      <div class="lbl">Contraseña temporal</div>
      <div class="val" style="font-size:20px;letter-spacing:2px">{$safePass}</div>
    </div>
    <div style="text-align:center;margin-top:22px">
      <a href="{$loginUrl}" class="btn">Ingresar al panel →</a>
    </div>
    <p class="note">
      ⚠️ Por seguridad, te recomendamos cambiar tu contraseña después del primer ingreso.<br>
      El equipo de AiniDesk te contactará en las próximas horas para configurar Mia para tu negocio.
    </p>
  </div>
  <div class="ft">Mia by AiniTravel &middot; Si no creaste esta cuenta, ignora este email.</div>
</div>
</body>
</html>
HTML;

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Mia by AiniDesk <noreply@ainitravel.com>\r\n";
        $headers .= "X-Mailer: PHP/" . PHP_VERSION . "\r\n";

        @mail($toEmail, $subject, $html, $headers);
        error_log("[Mia] Welcome email sent to {$toEmail}");
    }

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
