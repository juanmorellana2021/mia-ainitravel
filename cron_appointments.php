<?php
/**
 * mia/cron_appointments.php
 *
 * Sends 24-hour appointment reminders via WhatsApp + email.
 * Run every hour via cron on the VPS:
 *
 *   0 * * * * php /var/www/html/mia-whatsapp.com/cron_appointments.php >> /var/log/mia_appointments.log 2>&1
 *
 * Can also be triggered manually:
 *   php /var/www/html/mia-whatsapp.com/cron_appointments.php
 */

declare(strict_types=1);

// Only allow CLI or localhost invocations
$isCli   = (PHP_SAPI === 'cli');
$isLocal = isset($_SERVER['REMOTE_ADDR'])
    && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'], true);

if (!$isCli && !$isLocal) {
    http_response_code(403);
    exit('Forbidden');
}

// Bootstrap — minimal (no session, no controllers)
require_once __DIR__ . '/config/App.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Client.php';
require_once __DIR__ . '/models/Appointment.php';
require_once __DIR__ . '/models/Availability.php';
require_once __DIR__ . '/services/AppointmentService.php';
require_once __DIR__ . '/services/NotificationService.php';

$start   = microtime(true);
$svc     = new AppointmentService();
$due     = $svc->getDueReminders();
$fired   = 0;
$errors  = [];

foreach ($due as $row) {
    $appt = Appointment::fromRow($row);

    // ── WhatsApp reminder via bot admin API ───────────────────────────────
    try {
        $tz        = json_decode($row['bot_config'] ?? '{}', true)['hours_config']['timezone'] ?? 'America/Lima';
        $timeStr   = $appt->formattedStart($tz);
        $bizName   = htmlspecialchars($row['business_name']);
        $waMsg     = "🗓 Recordatorio de cita: tienes una cita con *{$bizName}* el {$timeStr}. ¡Te esperamos!";

        if ($appt->phone) {
            $phoneWa = ltrim($appt->phone, '+');
            $payload = json_encode([
                'clientId' => $appt->clientId,
                'phone'    => "{$phoneWa}@c.us",
                'message'  => $waMsg,
            ]);
            $ch = curl_init('http://127.0.0.1:3001/send-client');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_RETURNTRANSFER => true,
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
    } catch (\Throwable $e) {
        $errors[] = "WA reminder #{$appt->id}: " . $e->getMessage();
    }

    // ── Email reminder to business owner ─────────────────────────────────
    try {
        $ownerEmail = trim($row['notify_email'] ?: $row['owner_email']);
        if ($ownerEmail && filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            $tz       = json_decode($row['bot_config'] ?? '{}', true)['hours_config']['timezone'] ?? 'America/Lima';
            $timeStr  = $appt->formattedStart($tz);
            $bizName  = $row['business_name'];

            $subject = "📅 Cita mañana: {$appt->contactName} — {$timeStr}";
            $body    = "<p>Hola,</p>"
                     . "<p>Este es un recordatorio de la siguiente cita agendada para <strong>{$bizName}</strong>:</p>"
                     . "<table style='border-collapse:collapse;font-family:sans-serif;font-size:14px'>"
                     . "<tr><td style='padding:6px 12px;color:#888'>Cliente:</td><td style='padding:6px 12px'><strong>" . htmlspecialchars($appt->contactName) . "</strong></td></tr>"
                     . "<tr><td style='padding:6px 12px;color:#888'>Teléfono:</td><td style='padding:6px 12px'>" . htmlspecialchars($appt->phone) . "</td></tr>"
                     . "<tr><td style='padding:6px 12px;color:#888'>Fecha/hora:</td><td style='padding:6px 12px'><strong>{$timeStr}</strong></td></tr>"
                     . (!empty($appt->notes) ? "<tr><td style='padding:6px 12px;color:#888'>Notas:</td><td style='padding:6px 12px'>" . htmlspecialchars($appt->notes) . "</td></tr>" : '')
                     . "</table>"
                     . "<p style='color:#888;font-size:12px;margin-top:24px'>Mia — Asistente de WhatsApp</p>";

            (new NotificationService())->sendRaw($ownerEmail, $subject, $body);
        }
    } catch (\Throwable $e) {
        $errors[] = "Email reminder #{$appt->id}: " . $e->getMessage();
    }

    $svc->markReminderSent($appt->id);
    $fired++;
}

$elapsed = round((microtime(true) - $start) * 1000);
$ts      = date('Y-m-d H:i:s');
echo "[{$ts}] cron_appointments: due=" . count($due) . " fired={$fired} errors=" . count($errors) . " ({$elapsed}ms)\n";

foreach ($errors as $err) {
    echo "  ERROR: {$err}\n";
}
