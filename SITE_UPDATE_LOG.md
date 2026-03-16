# Mia by AiniTravel — Site Update Log
**Last updated:** March 16, 2026

---

## Summary of Deployed Features (as of March 16, 2026)

---

### Feature 1 — Business Hours
**Commit:** early history
**Files:** `services/ClientService.php`, `views/client/settings.php`
- Clients can define open/close hours per day of the week
- Bot respects hours: replies with a "closed" message outside business hours
- Stored as JSON in `mia_clients.business_hours`

---

### Feature 2 — WhatsApp QR Connect
**Files:** `controllers/SettingsController.php`, `views/client/settings.php`
- Client settings page has a "Connect WhatsApp" section
- Clicks "Conectar WhatsApp" → bot server starts session → QR appears
- JS polls `/dashboard/settings/wa-qr` every 4–20s until `status === 'connected'`
- Status stored as `bot_wa_status` on `mia_clients`

---

### Feature 3 — Appointments / Citas Scheduler
**Commit:** `3688c5c`
**New files:** `models/Availability.php`, `models/Appointment.php`, `services/AppointmentService.php`, `controllers/AppointmentController.php`, `views/client/appointments.php`
**Routes added to `index.php`:**
- `GET dashboard/appointments` — calendar view
- `GET dashboard/appointments/settings` — availability config
- `POST dashboard/appointments/settings/save`
- `POST dashboard/appointments/{id}/cancel`
- `GET api/appointments/slots`
- Clients set weekly availability; leads book slots via WhatsApp bot
- Monthly calendar UI (Bootstrap + vanilla JS) with green border on days with appointments

---

### Feature 4 — Add-on Credits (Billing)
**Files:** `models/ClientAddon.php`, `services/AddonService.php`, `controllers/BillingController.php`
**Routes:** `POST dashboard/billing/addon`, `GET dashboard/billing/addon-return`
- Clients can purchase broadcast message credits via MercadoPago
- Stored in `mia_client_addons` table
- `AddonService` deducts credits on each broadcast send

---

### Feature 5 — Follow-up Sequences
**Files:** `models/Sequence.php`, `models/SequenceStep.php`, `models/LeadSequence.php`, `services/SequenceService.php`, `controllers/SequenceController.php`, `views/client/sequences.php`, `views/client/sequence_edit.php`
**Routes:** full CRUD under `dashboard/sequences/*`
- Clients create multi-step message sequences (delay + message per step)
- Enroll/unenroll leads manually or via bot trigger
- `cron_sequences.php` runs steps on schedule

---

### Fix — Session Timeout (Superadmin 1h logout)
**Commit:** `45c427c`
**Files:** `index.php`, `views/superadmin/login.php`
- Root cause: Ubuntu system cron was purging `/var/lib/php/sessions/` after 3600s
- Fix: `session_save_path(__DIR__ . '/tmp/sessions')` to use app-local path
- Session TTL set to 8 hours (`App::SUPERADMIN_SESSION_TTL`)
- Remember Me adds 30-day cookie for client logins
- `tmp/sessions/` is excluded from system cron purge

---

### Fix — DB Audit: Missing `mia_page_events` Table + `track()` Method
**Commit:** `3688c5c`
**Files:** `controllers/ApiController.php`, `migrations/006_page_events.sql`
- Route `api/track` existed but `ApiController::track()` method was missing → 500 errors
- `mia_page_events` table was queried in analytics but never created
- Added `track()` method with inline `CREATE TABLE IF NOT EXISTS` guard
- Added `migrations/006_page_events.sql` for documentation

---

### Feature 6 — Appointment Calendar Grid Lines Fix
**Commit:** `081e5a5`
**Files:** `views/client/appointments.php`
- Day cells were white-on-white (invisible borders)
- Past days → `background: #f5f5f5`
- Future days → `background: #f8fafc`
- Days with appointments → `border: 2px solid #25d366` (green)

---

### Feature 7 — Client Onboarding Wizard + Mia Help Chat
**Commit:** `0c61ac5`
**Date:** March 16, 2026
**Files changed (10):**
| File | Change |
|------|--------|
| `migrations/007_onboarding.sql` | New: adds `onboarding_done TINYINT(1) DEFAULT 0` to `mia_clients` |
| `models/Client.php` | Added `public int $onboarding_done = 0` property |
| `services/ClientService.php` | Added `ensureOnboardingColumn()` (idempotent ALTER TABLE) + `markOnboardingDone()` |
| `services/BillingService.php` | `clientToSession()` now includes `onboarding_done` in session array |
| `controllers/AuthController.php` | Login redirects to `/dashboard/settings?onboarding=1` if `onboarding_done === 0` |
| `controllers/SettingsController.php` | Passes `$onboarding` var; added `finishOnboarding()` POST action |
| `controllers/ApiController.php` | Added `onboardingHelp()` — Groq-powered, auth-gated, rate-limited to 40/session |
| `views/client/settings.php` | 3-step progress wizard banner + finish CTA card |
| `views/client/_foot.php` | Floating green chat bubble widget (56×56px, bottom-right) |
| `index.php` | 2 new routes: `finish-onboarding` POST + `api/onboarding-help` POST |

**How it works:**
- New signups → always land on `/dashboard/settings?onboarding=1`
- Existing clients with `onboarding_done = 0` → same redirect on next login
- Wizard shows 3 steps: "Configura tu negocio" → "Conecta WhatsApp" → "¡Mia lista! 🎉"
- Step detection: step 1 if no bot config, step 2 if config but no WA, step 3 if connected
- "¡Ir al Dashboard!" button → `POST /dashboard/settings/finish-onboarding` → sets flag in DB + session → wizard and bubble disappear permanently
- Floating Mia chat bubble visible until `onboarding_done = 1`
- Chat widget stores history in `sessionStorage` (last 14 turns displayed, last 6 sent to API)
- API uses Groq `llama-3.3-70b-versatile`, max 180 tokens, temp 0.5, system-prompt scoped to setup help only

---

## Database Schema Notes

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `mia_clients` | `onboarding_done TINYINT(1)` | Added by `ensureOnboardingColumn()` or `007_onboarding.sql` |
| `mia_clients` | `bot_wa_status VARCHAR(20)` | `'connected'` \| `'disconnected'` |
| `mia_clients` | `business_hours JSON` | Per-day open/close schedule |
| `mia_page_events` | `page`, `event`, `client_id`, `created_at` | Landing page analytics pixel |
| `mia_client_addons` | `client_id`, `type`, `credits`, `created_at` | Purchased broadcast credits |
| `mia_sequences` | full sequence header | FK to `mia_clients` |
| `mia_sequence_steps` | `sequence_id`, `step_order`, `delay_hours`, `message` | Steps per sequence |
| `mia_lead_sequences` | `lead_id`, `sequence_id`, `current_step`, `next_run_at` | Active enrollments |
| `mia_appointments` | `lead_id`, `client_id`, `start_at`, `status` | Booked slots |
| `mia_availability` | `client_id`, `day_of_week`, `start_time`, `end_time` | Weekly schedule |

---

## Stack & Infrastructure

| Item | Value |
|------|-------|
| VPS | `root@108.175.12.152` |
| Deploy path | `/var/www/html/mia.ainitravel.com/` |
| Deploy method | `scp` to `/tmp/` → `ssh mv` → `service php8.2-fpm reload` |
| PHP version | 8.2 (FPM) |
| DB | MySQL, `mia_db`, user `miauser@localhost` |
| Session path | `/var/www/html/mia.ainitravel.com/tmp/sessions/` (app-local, not system-purged) |
| LLM — Sales | Groq `llama-3.3-70b-versatile` via `MiaSalesService` |
| LLM — Onboarding Help | Groq `llama-3.3-70b-versatile` via `ApiController::onboardingHelp()` |
| Billing | MercadoPago (subscriptions + add-ons) |
| WhatsApp | Custom bot server on same VPS, internal API |

---

## Commit History (key milestones)

| Commit | Description |
|--------|-------------|
| `45c427c` | fix: session path + 8h TTL to stop superadmin 1h logout |
| `8262892` | fix: PLAN_CAPS missing leads key + copy updates |
| `3688c5c` | feat: appointments calendar + DB audit fixes (page_events + track method) |
| `081e5a5` | fix: calendar grid cell backgrounds |
| `0c61ac5` | feat: client onboarding wizard + Mia help chat widget |

---

## Next Steps / Known TODOs

- Run `migrations/007_onboarding.sql` manually on VPS if `ClientService::ensureOnboardingColumn()` does not fire on first request (it fires on every `ClientService` instantiation, so it should auto-apply)
- Consider adding email notification when a new lead books an appointment
- Analytics dashboard currently shows placeholder data — hook up real queries from `mia_page_events` and lead activity tables
- Superadmin `mia_brain.php` view is static — consider wiring it to live Groq system-prompt editing
