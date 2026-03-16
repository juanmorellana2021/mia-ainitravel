# MIA — Feature Roadmap & Engineering Plan

> **Architectural rules (non-negotiable)**
> - Every feature follows Model → Service → Controller → View + Routes in `index.php`.
> - Controllers are thin wrappers; all logic lives in Services.
> - Never call a Service or Model static method from a View file — class may not be loaded.
>   Pass all computed values from the Controller, or use inline PHP in the view.
> - All new MVC classes must be `require_once`'d in `index.php`'s autoload block.
> - All new routes go in `index.php`'s `match(true)` table.
> - Settings stored as JSON fields inside `bot_config` column on `mia_clients`.
> - DB changes = a new migration file in `migrations/` AND run on VPS.
> - VPS cron format: `0 * * * * php /var/www/html/mia.ainitravel.com/cron_X.php`
> - Plan gating: add cap to `ClientBotService::$planCaps` + inline `$_proPlans` in `_sidebar.php`.
> - Commit after every completed feature.

---

## Build Order (rationale: quick wins first, then largest scope)

| # | Feature | Plans | Complexity | Status |
|---|---------|-------|-----------|--------|
| 1 | **Business Hours Mode** | All | Low | ⬜ not started |
| 2 | **WA QR & Link Generator** | All | Low | ⬜ not started |
| 3 | **Scheduler / Citas** | Business+ | High | ⬜ not started |
| 4 | **Pipeline Kanban** | Pro+ | Medium | ⬜ not started |
| 5 | **Lead Follow-up Email Digest** | Pro+ | Low-Med | ⬜ not started |
| 6 | **Team Members / Multi-seat** | Business+ | High | ⬜ not started |
| 7 | **Post-chat Review Request** | Business+ | Medium | ⬜ not started |
| 8 | **Webhook / API Outbound** | Enterprise | Medium | ⬜ not started |

---

## Feature 1 — Business Hours Mode (All plans)

**Goal**: Bot responds differently when outside business hours. No new plan gate — free for all.

### Storage
No new DB table. Two new keys added to `bot_config` JSON:
```json
{
  "hours_enabled": true,
  "hours_config": {
    "timezone":  "America/Lima",
    "schedule": {
      "mon": { "open": "09:00", "close": "18:00", "enabled": true },
      "tue": { "open": "09:00", "close": "18:00", "enabled": true },
      "wed": { "open": "09:00", "close": "18:00", "enabled": true },
      "thu": { "open": "09:00", "close": "18:00", "enabled": true },
      "fri": { "open": "09:00", "close": "17:00", "enabled": true },
      "sat": { "open": "09:00", "close": "13:00", "enabled": false },
      "sun": { "open": "", "close": "", "enabled": false }
    },
    "closed_message": "Estamos cerrados en este momento. Nuestro horario es de lunes a viernes 9am-6pm. Te respondemos en cuanto abramos 🕐"
  }
}
```

### Files changed / created
| File | Action | Notes |
|------|--------|-------|
| `services/ClientBotService.php` | Edit | Add `isWithinHours(): bool` private method. Read `hours_enabled` + `hours_config` from `$this->cfg`. In `process()`, check `isWithinHours()` early — if closed, save inbound msg, return closed_message, save outbound msg, return. |
| `controllers/SettingsController.php` | Edit | In `save()`, also persist `hours_enabled`, `hours_config` JSON. |
| `views/client/settings.php` | Edit | Add "⏰ Horario de atención" card/section at bottom of bot config tab. Checkbox to enable, timezone dropdown, Mon–Sun rows each with checkbox + open/close time inputs, custom closed message textarea. |

### `isWithinHours()` logic
```php
private function isWithinHours(): bool
{
    if (empty($this->cfg['hours_enabled'])) return true; // off = always open
    $hc = $this->cfg['hours_config'] ?? [];
    if (empty($hc['schedule']))         return true;

    $tz  = $hc['timezone'] ?? 'America/Lima';
    $now = new DateTimeImmutable('now', new DateTimeZone($tz));
    $dow = strtolower($now->format('D')); // mon, tue, ...
    $day = $hc['schedule'][$dow] ?? null;

    if (!$day || empty($day['enabled'])) return false;
    if (empty($day['open']) || empty($day['close'])) return false;

    $open  = DateTimeImmutable::createFromFormat('H:i', $day['open'],  new DateTimeZone($tz));
    $close = DateTimeImmutable::createFromFormat('H:i', $day['close'], new DateTimeZone($tz));
    return $now >= $open && $now < $close;
}
```

### Integration in `process()`
Place after the monthly conversation limit check, before building the Groq prompt:
```php
if (!$this->isWithinHours()) {
    $closedMsg = $this->cfg['hours_config']['closed_message']
        ?? 'Estamos cerrados por el momento. Te respondemos en nuestro próximo horario de atención.';
    $leadService->saveMessage($this->client->id, $leadId, $guestPhone, $closedMsg, 'outbound', 'bot');
    return ['reply' => $closedMsg];
}
```

### Settings UI wireframe (settings.php addition)
```
╔── ⏰ Horario de atención ─────────────────────────────────────────╗
║ [✓] Activar modo horario de atención                              ║
║                                                                   ║
║  Zona horaria: [America/Lima ▼]                                   ║
║                                                                   ║
║  Día       Activo   Apertura   Cierre                             ║
║  Lunes     [✓]      [09:00]    [18:00]                            ║
║  Martes    [✓]      [09:00]    [18:00]                            ║
║  ...                                                              ║
║  Domingo   [ ]      --         --                                 ║
║                                                                   ║
║  Mensaje fuera de horario:                                        ║
║  [textarea — max 300 chars]                                       ║
╚───────────────────────────────────────────────────────────────────╝
```

### No routes needed — saved via existing `POST /dashboard/settings/save`.
### No new MVC classes needed.

---

## Feature 2 — WhatsApp QR & Link Generator (All plans)

**Goal**: Client can generate a scannable QR code and a direct `wa.me` link using their connected WhatsApp number. Simple, zero external APIs.

### Files changed / created
| File | Action | Notes |
|------|--------|-------|
| `controllers/SettingsController.php` | Edit | Add `waLink(): void` method. Reads `$client->whatsapp_number`, strips `+`, builds `wa.me` URL and pre-fill text, outputs JSON with `{link, qr_url}`. |
| `views/client/settings.php` | Edit | Add "📲 Tu enlace de WhatsApp" card to the WhatsApp status section. Show QR image (via Google Charts API: `https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl={url}`) + copyable link + `wa.me` deep link. |

### Route addition in `index.php`
```php
$uri === 'dashboard/settings/wa-link' && $method === 'GET'
    => (new SettingsController())->waLink(),
```

### `waLink()` controller method
```php
public function waLink(): void
{
    $client = $this->requireAuth();
    if (!$client->whatsapp_number) {
        http_response_code(400);
        echo json_encode(['error' => 'WhatsApp no conectado']);
        return;
    }
    $num     = ltrim($client->whatsapp_number, '+');
    $text    = urlencode('Hola! Me interesa saber más sobre ' . $client->business_name);
    $link    = "https://wa.me/{$num}?text={$text}";
    $qrUrl   = "https://chart.googleapis.com/chart?cht=qr&chs=250x250&chl=" . urlencode($link);
    header('Content-Type: application/json');
    echo json_encode(['link' => $link, 'qr_url' => $qrUrl]);
}
```

### Settings UI addition
Display in the existing "Conectar WhatsApp" card when `bot_wa_status === 'connected'`.
Add below the "Probar enlace" button:
```
╔── 📲 Tu enlace de WhatsApp ───────────────────────────────────────╗
║  [QR code image 200x200]                                          ║
║                                                                   ║
║  Enlace directo:                                                  ║
║  https://wa.me/51999888777?text=Hola!...  [📋 Copiar]            ║
║                                                                   ║
║  [⬇ Descargar QR]   [↗ Abrir en nueva pestaña]                   ║
╚───────────────────────────────────────────────────────────────────╝
```

---

## Feature 3 — Scheduler / Citas (Business+)

**Goal**: Clients can define their available slots. Guests can book appointments via WhatsApp bot. System sends reminders via WhatsApp + email.

**Plan gate**: `Business` (= pro, enterprise, enterprise_duo, enterprise_chain, enterprise_corp, trial)

### DB Schema — 2 new tables

```sql
-- migrations/004_scheduler.sql

-- Availability config per client
CREATE TABLE IF NOT EXISTS `mia_availability` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `client_id`     INT UNSIGNED NOT NULL,
    `slot_minutes`  SMALLINT     NOT NULL DEFAULT 60,   -- e.g. 30, 60, 90
    `buffer_minutes` SMALLINT   NOT NULL DEFAULT 0,     -- gap between slots
    `max_days_ahead` TINYINT    NOT NULL DEFAULT 14,    -- how far ahead can someone book
    `schedule`      JSON         NOT NULL,              -- same shape as hours_config schedule
    `timezone`      VARCHAR(64)  NOT NULL DEFAULT 'America/Lima',
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Individual appointments
CREATE TABLE IF NOT EXISTS `mia_appointments` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `client_id`     INT UNSIGNED NOT NULL,
    `lead_id`       INT UNSIGNED,
    `contact_name`  VARCHAR(120) NOT NULL DEFAULT '',
    `phone`         VARCHAR(30)  NOT NULL,
    `starts_at`     DATETIME     NOT NULL,
    `ends_at`       DATETIME     NOT NULL,
    `notes`         TEXT,
    `status`        ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    `reminder_sent` TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_starts` (`client_id`, `starts_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### New MVC files

| File | Role |
|------|------|
| `models/Availability.php` | `fromRow()` factory. Helpers: `hasDay(string $dow): bool`. |
| `models/Appointment.php` | `fromRow()` factory. Helpers: `isPending()`, `isConfirmed()`, `isCancelled()`, `formattedTime(string $tz): string`. |
| `services/AppointmentService.php` | All DB + business logic (see API below). |
| `controllers/AppointmentController.php` | Thin HTTP handlers. |
| `views/client/appointments.php` | List + quick cancel. Today / upcoming split. |
| `cron_appointments.php` | Sends 24h-before reminders via WhatsApp + email. |

### `AppointmentService` public API
```php
getAvailability(int $clientId): ?Availability
saveAvailability(int $clientId, array $data): void
findSlots(int $clientId, string $date): array   // returns ['09:00','10:00', ...]
book(int $clientId, array $data): Appointment   // throws on conflict
cancel(int $appointmentId, int $clientId): void
getByClient(int $clientId, string $status = ''): array  // 'upcoming', 'past', ''
getById(int $id, int $clientId): ?Appointment
getDueReminders(): array  // for cron — appointments starting in ~24h with reminder_sent=0
markReminderSent(int $id): void
```

### `AppointmentController` routes + methods

| Method | Route | HTTP | Role |
|--------|-------|------|------|
| `index()` | `dashboard/appointments` | GET | List upcoming + past appointments |
| `cancel(int $id)` | `dashboard/appointments/{id}/cancel` | POST | Cancel an appointment |
| `settings()` | `dashboard/appointments/settings` | GET | Availability config form |
| `saveSettings()` | `dashboard/appointments/settings/save` | POST | Save availability |
| `slots()` | `api/appointments/slots` | GET | AJAX: ?client_id=&date= → JSON array of free slots |

### Routes to add in `index.php`
```php
// ── Appointments ──────────────────────────────────────────────────────────────
$uri === 'dashboard/appointments' && $method === 'GET'
    => (new AppointmentController())->index(),

$uri === 'dashboard/appointments/settings' && $method === 'GET'
    => (new AppointmentController())->settings(),

$uri === 'dashboard/appointments/settings/save' && $method === 'POST'
    => (new AppointmentController())->saveSettings(),

preg_match('#^dashboard/appointments/(\d+)/cancel$#', $uri, $m) && $method === 'POST'
    => (new AppointmentController())->cancel((int)$m[1]),

$uri === 'api/appointments/slots' && $method === 'GET'
    => (new AppointmentController())->slots(),
```

### Bot integration (`ClientBotService`)

The bot detects booking intent from context and calls `AppointmentService::findSlots()`.
Add `canAppointments` property, gated to Business+ in `$planCaps`.

Add to `buildSystemPrompt()` when `$canAppointments` is true and client has availability configured:
```
CITAS:
- Si el cliente quiere agendar una cita/reunión/visita, detecta esta intención.
- Responde SOLO con: [BOOK_INTENT] seguido de texto amigable pidiendo fecha preferida.
- Cuando te digan la fecha, responde SOLO con: [BOOK_DATE:YYYY-MM-DD]
- Cuando confirmen un horario, responde SOLO con: [BOOK_SLOT:YYYY-MM-DD HH:MM]
```

In `process()`, parse bot reply for these sentinel tags before sending to guest:
```php
if (str_contains($reply, '[BOOK_DATE:')) {
    preg_match('/\[BOOK_DATE:(\d{4}-\d{2}-\d{2})\]/', $reply, $bm);
    $date = $bm[1] ?? null;
    if ($date) {
        $slots = (new AppointmentService())->findSlots($this->client->id, $date);
        $slotList = implode(', ', $slots) ?: 'No hay horarios disponibles para ese día.';
        $reply = "Los horarios disponibles para {$date} son: {$slotList}. ¿Cuál prefieres?";
    }
}
if (str_contains($reply, '[BOOK_SLOT:')) {
    preg_match('/\[BOOK_SLOT:(\d{4}-\d{2}-\d{2} \d{2}:\d{2})\]/', $reply, $bm);
    // book(), notify owner, confirm to guest
}
```

### `cron_appointments.php` — reminder cron
```php
<?php
// mia/cron_appointments.php — send 24h appointment reminders
if (php_sapi_name() !== 'cli') exit;

require_once __DIR__ . '/config/App.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Client.php';
require_once __DIR__ . '/models/Appointment.php';
require_once __DIR__ . '/services/AppointmentService.php';
require_once __DIR__ . '/services/NotificationService.php';

$apptSvc = new AppointmentService();
$due     = $apptSvc->getDueReminders();

foreach ($due as $appt) {
    // WhatsApp reminder via bot API
    // Email reminder via NotificationService
    $apptSvc->markReminderSent($appt->id);
    echo "[cron_appointments] Reminder sent for appointment #{$appt->id}\n";
}
```

VPS cron: `0 * * * * php /var/www/html/mia.ainitravel.com/cron_appointments.php >> /var/log/mia_appointments.log 2>&1`

### Sidebar entry
```php
// In _sidebar.php, with plan guard (same pattern as broadcast):
$_apptPlans = ['trial', 'pro', 'enterprise', 'enterprise_duo', 'enterprise_chain', 'enterprise_corp'];
$canAppt    = in_array($_plan, $_apptPlans);
```

### Settings tab
Add "📅 Citas" tab to `settings.php`:
- Slot duration selector (30 / 45 / 60 / 90 min)
- Buffer time (0 / 10 / 15 / 30 min)
- Max days ahead (7 / 14 / 30)
- Timezone (same list as Business Hours)
- Weekly schedule (same day/open/close UI as Business Hours)
- Save via `POST /dashboard/appointments/settings/save`

---

## Feature 4 — Pipeline Kanban (Pro+)

**Goal**: Lead list becomes a drag-and-drop kanban board with columns by `status`. No new DB tables, uses existing `status` field on `mia_client_leads`.

**Plan gate**: Pro+ (same `$_proPlans` array)

### Columns (maps to existing `status` values)
```
Nuevo → Interesado → Propuesta enviada → Negociación → Cerrado ganado → Cerrado perdido
new   → interested → proposal_sent     → negotiating → closed_won    → closed_lost
```

### Files changed / created
| File | Action |
|------|--------|
| `views/client/leads.php` | Add a toggle button "Vista tabla / Vista kanban". Kanban board rendered client-side via JS using data already available in the page (or via new AJAX endpoint). Bootstrap card grid with horizontal scroll. |
| `controllers/DashboardController.php` | `leadsKanban()` method returns JSON array of leads grouped by status — used for AJAX refresh. |

### Route addition
```php
$uri === 'dashboard/leads/kanban' && $method === 'GET'
    => (new DashboardController())->leadsKanban(),
```

### Drag-drop
Use native HTML5 drag-drop API (zero extra dependencies):
- Each card has `draggable="true"` + `data-lead-id` + `data-status`
- Drop zone columns listen for `dragover` + `drop`
- On drop: `fetch('/dashboard/leads/{id}', { method:'POST', body: FormData({status: newCol}) })`
- Existing `DashboardController::leadUpdate()` already handles that POST

### Plan gate display
For Starter/Basic: show leads table as usual, kanban toggle button is grayed with "Pro" badge.
No redirect needed — just disable the toggle in the view.

---

## Feature 5 — Lead Follow-up Email Digest (Pro+)

**Goal**: Every day, Mia emails the business owner a digest of all leads that have not been contacted in N days.

**Plan gate**: Pro+

### Storage
One new key in `bot_config`:
```json
{
  "digest_enabled": true,
  "digest_days_inactive": 3
}
```

### Files changed / created
| File | Action |
|------|--------|
| `cron_digest.php` | New cron script. Loops all Pro+ clients with `digest_enabled`. Queries leads with `updated_at < NOW() - INTERVAL X DAY` and status not closed. Calls `NotificationService::sendDigest()`. |
| `services/NotificationService.php` | Add `sendDigest(Client $client, array $leads): void`. HTML email table with lead name, phone, status, last message date. |
| `views/client/settings.php` | Add "📬 Resumen diario" toggle + inactive days selector to Notifications tab. |
| `controllers/SettingsController.php` | Persist `digest_enabled` + `digest_days_inactive` in `save()`. |

### VPS cron
`0 8 * * * php /var/www/html/mia.ainitravel.com/cron_digest.php >> /var/log/mia_digest.log 2>&1`

---

## Feature 6 — Team Members / Multi-seat (Business+)

**Goal**: Client owner can invite staff members who can log in and see the dashboard (no billing access, no settings).

**Plan gate**: Business+ (= pro, enterprise*)

### DB Schema
```sql
-- migrations/005_team_members.sql
CREATE TABLE IF NOT EXISTS `mia_client_users` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `client_id`  INT UNSIGNED NOT NULL,
    `email`      VARCHAR(180) NOT NULL,
    `name`       VARCHAR(120) NOT NULL,
    `role`       ENUM('owner','agent') NOT NULL DEFAULT 'agent',
    `password_hash` VARCHAR(255) NOT NULL,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `invited_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` DATETIME,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`client_id`, `email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Files changed / created
| File | Action |
|------|--------|
| `models/ClientUser.php` | `fromRow()` factory. `isAgent(): bool`, `isOwner(): bool`. |
| `services/TeamService.php` | `invite()`, `activate()`, `deactivate()`, `getByClient()`, `findByEmail()`, `authenticate()`. |
| `controllers/TeamController.php` | `index()` / `invite()` / `remove()`. |
| `views/client/team.php` | List of members + invite form (email + name). |
| `config/App.php` | Max team size per plan constant array. |
| `controllers/AuthController.php` | Extended login to check `mia_client_users` if main `mia_clients` lookup fails. |

### Routes
```php
$uri === 'dashboard/team' && $method === 'GET'
    => (new TeamController())->index(),

$uri === 'dashboard/team/invite' && $method === 'POST'
    => (new TeamController())->invite(),

preg_match('#^dashboard/team/(\d+)/remove$#', $uri, $m) && $method === 'POST'
    => (new TeamController())->remove((int)$m[1]),
```

### Max seats per plan
```php
// App.php
const TEAM_SEATS = [
    'trial'            => 2,
    'starter'          => 1,
    'basic'            => 1,
    'pro'              => 5,
    'enterprise'       => 10,
    'enterprise_duo'   => 20,
    'enterprise_chain' => 50,
    'enterprise_corp'  => 200,
];
```

---

## Feature 7 — Post-chat Review Request (Business+)

**Goal**: When a lead's status is changed to `closed_won`, the bot automatically sends a "¿Nos dejarías una reseña?" message.

**Plan gate**: Business+

### Storage
```json
{
  "review_enabled": true,
  "review_url":     "https://g.page/r/...",
  "review_message": "¡Gracias por elegirnos! Si tienes un momento, nos ayudaría mucho con una reseña: {url}"
}
```

### Files changed / created
| File | Action |
|------|--------|
| `controllers/DashboardController.php` | In `leadUpdate()`: after saving status change, if new status is `closed_won` and `review_enabled`, fire WhatsApp message via bot API. |
| `views/client/settings.php` | Add "⭐ Solicitar reseña" toggle + review URL input + custom message textarea. |
| `controllers/SettingsController.php` | Persist `review_enabled`, `review_url`, `review_message`. |

### Bot send logic (in `leadUpdate()`)
```php
if ($newStatus === 'closed_won') {
    $cfg = json_decode($client->bot_config ?? '{}', true) ?: [];
    if (!empty($cfg['review_enabled']) && !empty($cfg['review_url'])) {
        $msg = str_replace('{url}', $cfg['review_url'],
            $cfg['review_message'] ?? '¡Gracias! Déjanos tu reseña: {url}');
        // Send via bot API: POST http://127.0.0.1:3001/send-client
        $payload = json_encode([
            'clientId' => $client->id,
            'phone'    => $lead->phone . '@c.us',
            'message'  => $msg,
        ]);
        // ... curl POST to bot API
    }
}
```

---

## Feature 8 — Webhook / API Outbound (Enterprise)

**Goal**: Enterprise clients can configure a webhook URL. Mia fires a POST request to it on specific events (lead created, status changed, appointment booked).

**Plan gate**: Enterprise only

### DB Schema
```sql
-- migrations/006_webhooks.sql
CREATE TABLE IF NOT EXISTS `mia_webhooks` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `client_id`  INT UNSIGNED NOT NULL,
    `url`        VARCHAR(500) NOT NULL,
    `events`     JSON         NOT NULL,  -- ["lead.created","lead.status_changed","appointment.booked"]
    `secret`     VARCHAR(64)  NOT NULL,  -- HMAC-SHA256 signing secret
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Files changed / created
| File | Action |
|------|--------|
| `models/Webhook.php` | `fromRow()` factory. |
| `services/WebhookService.php` | `getByClient()`, `save()`, `delete()`, `fire(int $clientId, string $event, array $payload)`. `fire()` sends signed POST (HMAC-SHA256 `X-Mia-Signature` header). |
| `controllers/WebhookController.php` | `index()` / `save()` / `delete()`. |
| `views/client/webhooks.php` | List configured webhooks + add form. Show event checkboxes + secret display. |

### `fire()` method — SSRF guard
Only fire to `https://` URLs. Never allow internal IPs (`127.`, `10.`, `192.168.`, `::1`):
```php
public function fire(int $clientId, string $event, array $payload): void
{
    $hooks = $this->getByClient($clientId);
    foreach ($hooks as $hook) {
        if (!$hook->is_active) continue;
        $events = json_decode($hook->events, true) ?: [];
        if (!in_array($event, $events, true)) continue;

        $url = $hook->url;
        // SSRF guard
        $host = parse_url($url, PHP_URL_HOST);
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                continue; // block private/reserved IPs
            }
        }
        if (!str_starts_with($url, 'https://')) continue;

        $body = json_encode($payload);
        $sig  = hash_hmac('sha256', $body, $hook->secret);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                "X-Mia-Signature: sha256={$sig}",
                "X-Mia-Event: {$event}",
            ],
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
```

### Routes
```php
$uri === 'dashboard/webhooks' && $method === 'GET'
    => (new WebhookController())->index(),

$uri === 'dashboard/webhooks/save' && $method === 'POST'
    => (new WebhookController())->save(),

preg_match('#^dashboard/webhooks/(\d+)/delete$#', $uri, $m) && $method === 'POST'
    => (new WebhookController())->delete((int)$m[1]),
```

### Fire points
Trigger `WebhookService::fire()` in:
- `ClientLeadService::create()` → event `lead.created`
- `DashboardController::leadUpdate()` (status change) → event `lead.status_changed`
- `AppointmentService::book()` → event `appointment.booked`

---

## Plan Cap Summary (after all features)

Update `ClientBotService::$planCaps` array to include new caps:

```php
$planCaps = [
    'trial'            => ['handoff', 'leads', 'broadcast', 'sequences', 'appointments', 'kanban', 'digest', 'team', 'review'],
    'starter'          => [],
    'basic'            => ['handoff'],
    'pro'              => ['handoff', 'leads', 'broadcast', 'sequences', 'kanban', 'digest'],
    'enterprise'       => ['handoff', 'leads', 'broadcast', 'sequences', 'appointments', 'kanban', 'digest', 'team', 'review', 'webhooks'],
    'enterprise_duo'   => ['handoff', 'leads', 'broadcast', 'sequences', 'appointments', 'kanban', 'digest', 'team', 'review', 'webhooks'],
    'enterprise_chain' => ['handoff', 'leads', 'broadcast', 'sequences', 'appointments', 'kanban', 'digest', 'team', 'review', 'webhooks'],
    'enterprise_corp'  => ['handoff', 'leads', 'broadcast', 'sequences', 'appointments', 'kanban', 'digest', 'team', 'review', 'webhooks'],
];
```

Sidebar inline `$_proPlans` arrays by feature:
```php
// Broadcast + Sequences (already done)
$_proPlans = ['trial', 'pro', 'enterprise', 'enterprise_duo', 'enterprise_chain', 'enterprise_corp'];

// Appointments
$_apptPlans = ['trial', 'pro', 'enterprise', 'enterprise_duo', 'enterprise_chain', 'enterprise_corp'];

// Kanban (same as pro+)
$_kanbanPlans = ['trial', 'pro', 'enterprise', 'enterprise_duo', 'enterprise_chain', 'enterprise_corp'];

// Webhooks (enterprise only)
$_webhookPlans = ['enterprise', 'enterprise_duo', 'enterprise_chain', 'enterprise_corp'];

// Team members
$_teamPlans = ['trial', 'pro', 'enterprise', 'enterprise_duo', 'enterprise_chain', 'enterprise_corp'];
```

---

## `index.php` autoload additions (cumulative)

Add to the `require_once` block at the top of `index.php` as each feature is built:

```php
// Feature 3 — Scheduler
require_once __DIR__ . '/models/Availability.php';
require_once __DIR__ . '/models/Appointment.php';
require_once __DIR__ . '/services/AppointmentService.php';
require_once __DIR__ . '/controllers/AppointmentController.php';

// Feature 6 — Team
require_once __DIR__ . '/models/ClientUser.php';
require_once __DIR__ . '/services/TeamService.php';
require_once __DIR__ . '/controllers/TeamController.php';

// Feature 8 — Webhooks
require_once __DIR__ . '/models/Webhook.php';
require_once __DIR__ . '/services/WebhookService.php';
require_once __DIR__ . '/controllers/WebhookController.php';
```

---

## Commit Strategy

Each feature gets **one commit per milestone**:
1. DB migration file created
2. Model(s) + Service created
3. Controller created, routes added to `index.php`, autoload updated
4. View(s) created, sidebar updated
5. Tests done, plan cap added to `ClientBotService`

Commit message format:
```
feat(appointments): add scheduler model + service + migration [3/5]
feat(appointments): add controller + routes [3/5]
feat(appointments): add views + sidebar entry [3/5]
```

---

## Security Checklist (per feature)

- [ ] All user inputs validated/sanitized before DB queries (use `htmlspecialchars()` in views, `?` placeholders in PDO)
- [ ] All controllers call `requireAuth()` first
- [ ] CSRF tokens on all mutating forms (`App::csrfToken()`)
- [ ] Webhook URLs validated against SSRF blocklist (private IPs, non-https)
- [ ] Appointment slot endpoint (`/api/appointments/slots`) rate-limited or requires auth token
- [ ] Team invite links expire after 24h and use signed tokens
- [ ] All plan gates enforced server-side (controller level), never trust client

---

*Last updated: 2026-06*
