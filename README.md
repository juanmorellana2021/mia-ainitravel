# Mia — AI WhatsApp Sales Bot

**Mia** is an AI-powered WhatsApp sales bot for AiniDesk, a B2B SaaS that sells AI receptionist software to hotels and small hospitality businesses.

When a potential customer messages the Mia WhatsApp number, Mia:
1. Receives the message via a Node.js bot (whatsapp-web.js)
2. Passes it to a PHP AI service (MiaSalesService)
3. Runs a 12-state sales conversation funnel
4. Qualifies the lead and captures their name + email
5. Hands off to the human sales team

---

## Project Structure

```
mia-ainitravel/
├── config/
│   ├── App.php              # App constants: branding, pricing, Stripe keys
│   └── Database.php         # MySQL PDO connection singleton
├── controllers/
│   ├── ApiController.php    # POST /api/chat — WhatsApp bot endpoint
│   ├── AuthController.php   # Client register/login/logout
│   ├── BillingController.php# Stripe subscription & webhook
│   ├── BroadcastController.php # Bulk WhatsApp messaging
│   ├── DashboardController.php # Client dashboard
│   ├── LeadController.php   # Admin panel: view/manage leads
│   ├── PageController.php   # Public pages (landing, pricing, demo, features)
│   └── SettingsController.php  # Client account settings
├── models/
│   ├── Client.php           # Business customer
│   ├── ClientLead.php       # Lead associated with a client
│   ├── ClientMessage.php    # Individual WhatsApp message
│   ├── Lead.php             # Sales lead captured by Mia
│   ├── SalesSession.php     # Active WhatsApp conversation state
│   └── Subscription.php     # Billing record
├── services/
│   ├── BillingService.php   # Stripe integration
│   ├── BroadcastService.php # Bulk message scheduling
│   ├── ClientLeadService.php# Lead-to-client association
│   ├── ClientService.php    # Client account management
│   ├── LeadService.php      # Lead CRUD & queries
│   ├── MiaSalesService.php  # ⭐ Core AI sales bot (state machine + LLM calls)
│   └── NotificationService.php # Email/alert notifications
├── views/
│   ├── admin/               # Admin panel (leads overview, lead detail)
│   ├── auth/                # Client login & registration
│   ├── client/              # Authenticated client dashboard
│   ├── layouts/             # Shared HTML layout
│   └── pages/               # Public pages (landing, pricing, demo, features, 404)
├── assets/
│   ├── css/                 # Bootstrap 5 + custom Mia styles
│   ├── js/                  # Bootstrap JS + demo simulator
│   └── fonts/               # Bootstrap Icons font files
├── sql/
│   ├── create_mia_db.sql    # Database & user setup
│   ├── mia_client_tables.sql# Client, leads, messages, subscriptions tables
│   └── add_settings_broadcast.sql # Broadcast & settings schema
├── index.php                # Front controller (MVC router)
├── .htaccess                # Apache mod_rewrite → routes all requests to index.php
├── MIA_STATUS_REPORT.txt    # Detailed architecture & deployment notes
└── NEXT_STEPS.txt           # Roadmap & pending tasks
```

---

## Architecture

```
[WhatsApp] ←→ [bot.js (Node.js / PM2)] ←→ [MiaSalesService.php]
                                                    ↓
                                         [ai_gateway.php (shared)]
                                              /            \
                                   [Ollama (3s timeout)]  [Groq API fallback]
```

- **WhatsApp Bot**: Node.js using `whatsapp-web.js`, managed by PM2 (`mia-bot`)
- **PHP Service**: MVC app served at `mia.ainitravel.com`
- **AI Models**: Primary — Ollama (`qwen2.5:7b`); Fallback — Groq (`llama-3.3-70b-versatile`)

---

## Sales Conversation Flow (12 States)

```
new → intro → qualifying_size → qualifying_method → qualifying_pain
    → roi_pitch → demo → benefits → closing
    → collecting_name → collecting_email → captured
```

Sales frameworks built into the system prompt: Challenger Sale, SPIN Selling, Loss Aversion, micro-commitments, Feel/Felt/Found objection handling, Choice Close, Social Proof, and Future Pacing.

---

## Setup

### Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB
- Apache with `mod_rewrite` enabled

### Database
```bash
mysql -u root -p < sql/create_mia_db.sql
mysql -u miauser -p mia_db < sql/mia_client_tables.sql
mysql -u miauser -p mia_db < sql/add_settings_broadcast.sql
```

### Configuration
- Edit `config/App.php` to set your branding, WhatsApp number, pricing, and Stripe keys.
- Edit `config/Database.php` to set your database credentials.

### Deploy to VPS
```bash
# PHP app
scp -r ./* root@YOUR_VPS:/var/www/html/mia.ainitravel.com/

# Restart bot after bot.js changes
ssh root@YOUR_VPS "pm2 restart mia-bot"
```

---

## Public Routes

| Route | Description |
|-------|-------------|
| `GET /` | Landing page |
| `GET /pricing` | Pricing plans |
| `GET /features` | Feature list |
| `GET /demo` | Interactive demo |
| `POST /api/chat` | WhatsApp bot endpoint (localhost only) |
| `GET /login` | Client login |
| `GET /register` | Client registration |
| `GET /dashboard` | Client dashboard |
| `GET /admin` | Admin panel |

---

## Pricing (PEN — Peruvian Soles)

| Plan | Price |
|------|-------|
| Basic | S/ 399/mo |
| Pro | S/ 699/mo |
| Enterprise | S/ 1,199/mo |

7-day free trial included. Setup fee: S/ 1,000.

---

## Tech Stack

- **Backend**: PHP 8 (MVC, no framework)
- **Database**: MySQL (PDO)
- **Frontend**: Bootstrap 5, Bootstrap Icons
- **AI**: Ollama (self-hosted) + Groq API
- **Payments**: Stripe
- **WhatsApp**: whatsapp-web.js (Node.js)
- **Process Manager**: PM2
- **Web Server**: Apache + mod_rewrite
