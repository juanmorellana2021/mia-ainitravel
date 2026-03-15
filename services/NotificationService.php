<?php
/**
 * mia/services/NotificationService.php
 *
 * Sends email alerts using PHPMailer + IONOS SMTP (same config as the PMS).
 */

declare(strict_types=1);

// PHPMailer (shared vendor on the VPS)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

class NotificationService
{
    private PDO $db;

    // IONOS SMTP — same credentials used by all apps on this VPS
    private const SMTP_HOST = 'smtp.ionos.com';
    private const SMTP_PORT = 587;
    private const SMTP_USER = 'support@ainitravel.com';
    private const SMTP_PASS = 'FpF5vBZ5!t$6LFp';
    private const FROM_EMAIL = 'support@ainitravel.com';
    private const FROM_NAME  = 'Mia by AiniTravel';

    public function __construct()
    {
        $this->db = Database::get();
    }

    private function mailer(): PHPMailer
    {
        require_once '/var/www/html/manage/vendor/phpmailer/phpmailer/src/Exception.php';
        require_once '/var/www/html/manage/vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once '/var/www/html/manage/vendor/phpmailer/phpmailer/src/SMTP.php';

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = self::SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = self::SMTP_USER;
        $mail->Password   = self::SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = self::SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(self::FROM_EMAIL, self::FROM_NAME);
        return $mail;
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

        try {
            $mail = $this->mailer();
            $mail->addAddress($toEmail);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->send();
            error_log("[Mia] Welcome email sent to {$toEmail}");
        } catch (\Throwable $e) {
            error_log("[Mia] Welcome email FAILED to {$toEmail}: " . $e->getMessage());
        }
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

        foreach ($clients as $client) {
            $to = !empty($client['notify_email'])
                ? $client['notify_email']
                : $client['email'];
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) continue;
            try {
                $mail = $this->mailer();
                $mail->addAddress($to);
                $mail->Subject = $subject;
                $mail->isHTML(true);
                $mail->Body = $html;
                $mail->send();
                error_log("[Mia] Lead capture notification sent to {$to}");
            } catch (\Throwable $e) {
                error_log("[Mia] Lead notification FAILED to {$to}: " . $e->getMessage());
            }
        }
    }

    /**
     * Email a client when their WhatsApp bot disconnects.
     * Called by ApiController::clientStatus() on every disconnection event.
     */
    public function sendDisconnectAlert(int $clientId): void
    {
        $stmt = $this->db->prepare(
            "SELECT email, contact_name, business_name FROM mia_clients WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$clientId]);
        $client = $stmt->fetch();

        if (!$client || !filter_var($client['email'] ?? '', FILTER_VALIDATE_EMAIL)) return;

        $name     = htmlspecialchars($client['contact_name']  ?: 'equipo');
        $bizName  = htmlspecialchars($client['business_name'] ?: 'tu negocio');
        $loginUrl = App::URL . '/login';
        $time     = date('d/m/Y H:i');
        $subject  = "⚠️ Tu bot de WhatsApp se desconectó — {$bizName}";

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f0f4f8;margin:0;padding:20px}
  .card{background:#fff;border-radius:12px;max-width:520px;margin:0 auto;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
  .hd{background:#1a1a2e;color:#fff;padding:28px 24px;text-align:center}
  .hd h2{margin:8px 0 4px;color:#f59e0b;font-size:20px}
  .bd{padding:24px}
  .btn{display:inline-block;background:#25d366;color:#fff!important;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:700;margin-top:20px}
  .ft{background:#f8f9fa;padding:14px 24px;text-align:center;font-size:12px;color:#adb5bd}
</style>
</head>
<body>
<div class="card">
  <div class="hd">
    <div style="font-size:2.4rem">⚠️</div>
    <h2>Bot desconectado</h2>
    <p style="margin:0;color:#ccc;font-size:13px">{$time}</p>
  </div>
  <div class="bd">
    <p>Hola <strong>{$name}</strong>,</p>
    <p>Tu bot de WhatsApp para <strong>{$bizName}</strong> se desconectó y <strong>ya no responde mensajes</strong>.</p>
    <p>Para reconectarlo, ingresa a tu panel y escanea el código QR en <em>Configuración → WhatsApp</em>:</p>
    <div style="text-align:center">
      <a href="{$loginUrl}" class="btn">Reconectar ahora →</a>
    </div>
    <p style="margin-top:20px;color:#6c757d;font-size:13px">
      Esto ocurre si el teléfono se desconectó de internet, WhatsApp fue cerrado, o se desvincló el dispositivo.
    </p>
  </div>
  <div class="ft">Mia by AiniDesk &middot; mia.ainitravel.com</div>
</div>
</body>
</html>
HTML;

        try {
            $mail = $this->mailer();
            $mail->addAddress($client['email']);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->send();
            error_log("[Mia] Disconnect alert sent to {$client['email']} (client {$clientId})");
        } catch (\Throwable $e) {
            error_log("[Mia] Disconnect alert FAILED for client {$clientId}: " . $e->getMessage());
        }
    }
}
