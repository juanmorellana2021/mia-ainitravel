# Mia by AiniDesk — Project Status Report
**Date:** March 12, 2026 | **Version:** 1.0 | **Status:** Ready for Facebook Ads Launch

---

## 1. What We Built

### Core Infrastructure
| Component | Status | Notes |
|---|---|---|
| PHP MVC app at `mia.ainitravel.com` | ✅ Live | Apache + MySQL on VPS 108.175.12.152 |
| WhatsApp bot (`/root/mia-whatsapp-bot/bot.js`) | ✅ Live | PM2 process `mia-bot`, WA Web connected |
| MySQL DB (`mia_db`, user `miauser`) | ✅ Fixed | Password was broken — fixed Mar 12 |
| HTTPS SSL | ✅ Live | Apache handles TLS for `mia.ainitravel.com` |

### Mia Sales Bot (AI Sales Agent)
| Feature | Status |
|---|---|
| Full AI-driven sales funnel (intro → qualifying → ROI pitch → demo → closing → capture) | ✅ |
| Business-type aware (hotel, agency, restaurant, retail, services) | ✅ |
| Groq LLM (fast, free-tier) | ✅ |
| Conversation history stored per phone | ✅ |
| **Multilingual: auto-detects English vs Spanish, responds in user's language** | ✅ Fixed today |
| Facebook `@lid` contact resolution (gets real phone number) | ✅ Fixed today |
| **Auto-converts prospect to client account on email capture** | ✅ Added today |

### Superadmin Panel (`/superadmin`)
| Feature | Status |
|---|---|
| Dashboard with KPIs (MRR, ARR, clients, messages, leads) | ✅ |
| Live prospects feed with last 15 conversations | ✅ |
| Slide-in chat panel (WhatsApp dark theme bubbles) | ✅ |
| Prospectos page with full list + Chat + Detalle buttons | ✅ |
| Prospect detail page with full conversation history | ✅ |
| "Convertir en cliente" button with temp password banner | ✅ |
| Clients management page | ✅ |
| Mia Bot QR connect page | ✅ |
| **Analytics page** (pageviews, sessions, CTR, devices, UTM, referrers) | ✅ Added today |

### Public Analytics Tracking
| Feature | Status |
|---|---|
| Auto-tracking pixel in `main.php` (pageview + pageleave + CTA clicks) | ✅ |
| `POST /api/track` endpoint | ✅ |
| `mia_page_events` DB table | ✅ |
| Privacy: IPs hashed daily (SHA-256 + date), not stored raw | ✅ |
| Chart.js daily bar chart in analytics dashboard | ✅ |
| UTM campaign tracking (`?utm_source=...&utm_medium=...&utm_campaign=...`) | ✅ Ready for ads |

---

## 2. Tonight: Facebook Ads Launch Checklist

### Your Ad Links — USE THESE UTM FORMATS
```
# Primary CTA (Register/Trial)
https://mia.ainitravel.com/register?utm_source=facebook&utm_medium=cpc&utm_campaign=launch_mar26

# Landing page
https://mia.ainitravel.com/?utm_source=facebook&utm_medium=cpc&utm_campaign=launch_mar26

# WhatsApp demo (click-to-chat to Mia)
https://wa.me/51XXXXXXXXX?text=hola&utm_source=facebook&utm_medium=social&utm_campaign=launch_mar26
```
> Analytics will automatically segment by `utm_source / utm_medium / utm_campaign` so you can see exactly which ad drove which lead.

### What Mia Does When a Facebook Lead Messages
1. Lead clicks "Send WhatsApp Message" on your ad → opens chat with Mia
2. Mia greets them, qualifies (business type → size → pain → ROI pitch)
3. Mia collects name, email → **auto-creates their trial account**
4. You see them appear in **Prospectos** page within seconds
5. They appear in **Analytics** as a session too

### Recommended Ad Copy Direction
- Hook: "¿Tu negocio pierde clientes porque nadie responde WhatsApp a las 2am?"
- Visual: Before/After — ignored WhatsApp vs instant Mia reply
- CTA: "Prueba gratis 7 días — sin tarjeta"

---

## 3. Known Issues / What to Improve

### High Priority (Soon)
| Issue | Impact | Fix |
|---|---|---|
| **Mia's closing message still in Spanish even when prospect writes English** | Medium | Fixed today (language detection now per-message) — needs real-world test |
| **Account holder dashboard language** — clients who speak English see the panel in Spanish | Medium | Plan: add `locale` column to `mia_clients`, serve translated views |
| `mia_page_events` only tracks public pages — not logged-in client sessions | Low | Add optional client session tracking behind auth |
| No email sent to new auto-converted clients with their login credentials | High | `AiniTravelEmailSender.php` exists — wire it into `convertToClient()` |

### Medium Priority (Next Sprint)
| Feature | Notes |
|---|---|
| **Email auto-send on conversion** | When Mia collects email and auto-converts, the new client should receive a welcome email with their temp password and login link |
| **Mia follow-up scheduler** | After 24h with no reply from a prospect, Mia sends a soft follow-up. Re-engages cold leads automatically |
| **WhatsApp opt-in confirmation** | Some countries require explicit consent before messaging. Add a consent step |
| **Pricing page A/B test** | Test S/399 vs S/349 first-month discount for Facebook ad traffic |
| **Mobile responsiveness audit** | Landing page and client panel on mobile – verify on real devices |

### Low Priority (Backlog)
| Feature | Notes |
|---|---|
| Google Ads / TikTok pixel integration | Currently only first-party; add GA4 or Meta Pixel if needed |
| Broadcast / bulk message scheduler | UI exists in client panel but not heavily tested |
| Multi-language client dashboard | Detect locale from browser, serve translated views |
| SMS fallback for non-WhatsApp users | AWS SNS setup guide exists in BACKUP_GUIDE.md |
| Client onboarding flow | Currently manual (48h setup). Automate: self-serve bot config wizard |

---

## 4. Architecture Summary

```
Internet
   │
   ├── mia.ainitravel.com (Apache + PHP 8.2)
   │   ├── Landing / Pricing / Features pages
   │   ├── /superadmin/*  ← you manage everything here
   │   ├── /dashboard/*   ← client control panel
   │   └── /api/*         ← bot API + tracking pixel
   │
   ├── WhatsApp bot (Node.js, PM2 `mia-bot`)
   │   └── Calls /api/chat → MiaSalesService → Groq AI
   │
   └── MySQL (mia_db)
       ├── mia_sales_sessions    ← prospect conversations
       ├── mia_clients           ← paying clients
       ├── mia_subscriptions     ← billing
       ├── mia_client_messages   ← client bot conversations
       ├── mia_client_leads      ← guest leads for each client
       ├── mia_page_events       ← analytics tracking (new)
       └── mia_broadcast_logs    ← bulk messages
```

---

## 5. Revenue Model
| Plan | Price | Notes |
|---|---|---|
| Básico | S/399/mo | Up to 200 conversations |
| Pro | S/699/mo | Unlimited + full panel |
| Enterprise | S/1,199/mo | Multi-number + custom integrations |
| Setup fee | S/500 | One-time, covers full configuration |
| Trial | 7 days free | No credit card required |

**Current MRR:** S/0 (pre-launch) → Target after first Facebook ads campaign: S/1,200+ (3 Básico clients)

---

## 6. What's Working Well
- Mia's sales script is genuinely good — the objection handling and challenger-sale framework are production quality
- The superadmin panel gives you full visibility without needing to touch the DB
- Analytics tracking is zero-config — it just works for every visitor
- The bot auto-converts prospects to clients — no manual step needed in ideal flow

## 7. What to Watch After Launch
1. **Drop-off point** — which funnel state do prospects abandon most? (visible in Prospectos page)
2. **CTR on landing page** — analytics will show % of visitors clicking Register
3. **Time to capture** — how many messages does it take Mia to get an email? Optimize if >8
4. **Language mix** — what % of Facebook leads write in English vs Spanish?
