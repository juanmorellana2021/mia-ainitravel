# Mia Lead Memory System

## What It Does

Mia now **remembers facts about each contact** across conversations. When someone writes to a client's WhatsApp, Mia recalls what she learned from previous chats — their name, preferences, what they asked about, budget, dates, etc.

Think of it like a good salesperson who keeps mental notes about regular customers.

## How It Works

### The Flow (per message)

```
1. Guest sends WhatsApp message
2. ClientBotService.process() runs
3. ┌─ Load memories for this phone → getMemoryBlock()
   │  Returns stored facts from mia_lead_memories
   │
4. ├─ Inject into system prompt as "MEMORIA DEL CONTACTO" block
   │  Mia sees: "Se llama María", "Busca habitación doble", etc.
   │
5. ├─ Groq generates reply (with memory context)
   │
6. └─ After reply → extractAndStore()
      Sends the exchange to Groq with an "extractor" prompt
      Groq extracts new facts → saved to DB
```

### Key Design Decisions

- **Phone number is the key**, not lead ID. Why? Phone numbers are permanent in WhatsApp. Lead IDs can change if a contact gets deleted and recreated. The phone number `51930293197` is always the same person.

- **Two Groq calls per message** (when there are new facts to extract):
  1. The normal reply call (existing)
  2. A lightweight extraction call (max_tokens=100, temperature=0.3)
  
  When there's nothing new to extract (greetings, "ok", "thanks"), the extractor returns "NINGUNO" and no DB write happens.

- **Max 20 facts per contact**. Oldest are pruned when exceeded. This prevents the system prompt from getting too long over time.

- **Max 3 facts per exchange**. Prevents the extractor from over-generating.

- **Non-blocking**: If memory extraction fails, the reply still goes through. Memory is wrapped in try/catch.

## Architecture (MVC)

```
models/
  LeadMemory.php          ← Data model (id, client_id, phone, fact, source, created_at)

services/
  LeadMemoryService.php   ← Business logic (extract, store, load, prune)
  ClientBotService.php    ← Integration point (calls memory service)

migrations/
  005_lead_memories.sql   ← Database table
```

### Database Table: `mia_lead_memories`

| Column     | Type         | Description                        |
|------------|--------------|------------------------------------|
| id         | INT PK       | Auto-increment                     |
| client_id  | INT          | Which Mia client owns this memory  |
| phone      | VARCHAR(50)  | Contact's phone (WhatsApp format)  |
| fact       | VARCHAR(500) | A single remembered fact           |
| source     | ENUM         | 'ai' (extracted) or 'manual'       |
| created_at | TIMESTAMP    | When the fact was learned           |

**Indexes**: `(client_id, phone)` for fast lookups, `(phone)` for cross-client queries.

### LeadMemoryService Methods

| Method | Purpose |
|--------|---------|
| `getMemoryBlock(clientId, phone)` | Returns formatted string for system prompt injection |
| `extractAndStore(clientId, phone, userMsg, botReply)` | Extracts facts from exchange and saves them |
| `pruneOldFacts(clientId, phone)` | Keeps max 20 facts, removes oldest |

### System Prompt Injection

The memory block is injected between the photos block and the intentions block:

```
...business info, services, pricing, hours, FAQs...

FOTOS DEL NEGOCIO:
...

MEMORIA DEL CONTACTO (lo que sabes de conversaciones anteriores):
- Se llama María
- Busca habitación doble para el 15 de abril
- Viaja con 2 niños pequeños
- Prefiere check-in tardío (después de las 8pm)
Usa esta información de forma natural...

INTENCIONES — TÚ LAS DETECTAS:
...
```

### Token Budget

| Condition | max_tokens |
|-----------|-----------|
| Normal (no photos, no memories) | 80 |
| Has memories but no photos | 120 |
| Has photos | 250 |

## Example

**First conversation (no memories yet):**
```
Guest: Hola, tienen habitaciones disponibles para el 15 de abril?
Mia: ¡Hola! Sí, tenemos disponibilidad. ¿Para cuántas personas sería? 😊
```
→ Extractor saves: `"Busca habitación para el 15 de abril"`

**Second message:**
```
Guest: Somos 2 adultos y 2 niños. Soy María.
Mia: ¡Perfecto María! Tenemos habitaciones familiares ideales para ustedes.
```
→ Extractor saves: `"Se llama María"`, `"2 adultos y 2 niños"`

**A week later, María writes again:**
```
Guest: Hola, sigo interesada en la habitación
Mia: ¡Hola María! Claro, la habitación familiar para el 15 de abril sigue disponible para tu familia. ¿Deseas confirmar la reserva?
```
→ Mia remembered her name, the date, and the family size without asking again.

## Cost Impact

- **Groq API**: ~1 extra call per message (the extraction). At Groq's free tier / low cost, this is negligible.
- **DB**: Small table, ~500 bytes per fact. 20 facts × 1000 contacts = ~10MB. Trivial.
- **Latency**: Extraction happens AFTER the reply is sent, so the user sees no delay.

## Files Changed

| File | Change |
|------|--------|
| `models/LeadMemory.php` | **NEW** — Data model |
| `services/LeadMemoryService.php` | **NEW** — Memory extraction, storage, retrieval |
| `migrations/005_lead_memories.sql` | **NEW** — Database table |
| `services/ClientBotService.php` | Modified — Injects memory into prompt, calls extractAndStore after reply |
| `index.php` | Modified — Added require_once for new model and service |
