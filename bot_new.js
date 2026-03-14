/**
 * Mia WhatsApp Bot — Multi-tenant
 *
 * Manages WhatsApp sessions for:
 *   1. Mia's own sales bot (mia-bot) → /api/chat
 *   2. Each subscribed client (client_N) → /api/client-chat with client_id
 *
 * Admin HTTP API (localhost:3001):
 *   POST /send                   — outbound message via Mia's sales bot
 *   POST /connect/:clientId      — start WA session for a client
 *   POST /disconnect/:clientId   — end WA session for a client
 *   GET  /qr/:clientId           — { status, qr_image } for settings page
 *   GET  /status/:clientId       — { status, phone } for settings page
 *
 * Setup / deploy:
 *   pm2 start bot.js --name mia-bot
 */

'use strict';

const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcodeTerminal        = require('qrcode-terminal');
const qrcodeImage           = require('qrcode');
const https                 = require('https');
const http                  = require('http');
const { fork }              = require('child_process');
const fs                    = require('fs');
const path                  = require('path');

// ── Constants ─────────────────────────────────────────────────────────────────
const MIA_BOT_SECRET = 'mia-bot-secret-2026';
const MIA_API_HOST   = 'mia.ainitravel.com';
const MIA_API_PORT   = 443;
const AUTH_DIR       = path.join(__dirname, '.wwebjs_auth');

// ── Client worker registry ──────────────────────────────────────────────────────
// Each client runs in an isolated forked process. A crash in one never affects others.
// Map<string clientId, { worker: ChildProcess|null, status: string, qrData: string|null, phone: string|null }>
const clientWorkers = new Map();

// ── Helper: build a puppeteer Client ─────────────────────────────────────────
function makeWaClient(clientId) {
    return new Client({
        authStrategy: new LocalAuth({ clientId, dataPath: AUTH_DIR }),
        puppeteer: {
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox'],
        },
    });
}

// ═════════════════════════════════════════════════════════════════════════════
// Mia's own sales bot (Juan's number)
// ═════════════════════════════════════════════════════════════════════════════
const miaClient = makeWaClient('mia-bot');

// Store Mia bot QR so superadmin can display it via /qr/mia
let miaBotQrData   = null;
let miaBotStatus   = 'initializing';
let miaBotPhone    = null;
let miaBotReadyAt  = 0; // Unix timestamp when bot last connected — used to skip offline backlog

miaClient.on('qr', async (qr) => {
    qrcodeTerminal.generate(qr, { small: true });
    console.log('[mia-bot] Scan QR to connect Mia sales bot');
    miaBotStatus = 'qr_pending';
    try {
        miaBotQrData = await qrcodeImage.toDataURL(qr, { width: 300 });
    } catch (e) {
        miaBotQrData = null;
    }
});

miaClient.on('ready', () => {
    miaBotReadyAt = Math.floor(Date.now() / 1000);
    miaBotStatus  = 'connected';
    miaBotQrData  = null;
    try { miaBotPhone = miaClient.info?.wid?.user ?? null; } catch(_) {}
    console.log('[mia-bot] ✅ Sales bot connected and ready. Phone:', miaBotPhone);
});

miaClient.on('disconnected', (reason) => {
    console.log('[mia-bot] ❌ Disconnected:', reason);
    miaBotStatus = 'disconnected';
    miaBotQrData = null;
    process.exit(1); // pm2 restarts the whole process
});

miaClient.on('message', async (msg) => {
    if (msg.from === 'status@broadcast' || msg.from.includes('@g.us')) return;
    if (msg.fromMe) return;

    // Log ALL messages before any filter so nothing is invisible
    const rawBody = msg.body?.trim() || '';
    console.log(`[mia-bot] RAW from=${msg.from} type=${msg.type} body="${rawBody.substring(0, 60)}"`);

    // Skip offline backlog to avoid reply storms after reconnect
    if (miaBotReadyAt > 0 && msg.timestamp && msg.timestamp < miaBotReadyAt) {
        console.log(`[mia-bot] Skipping offline-backlog msg (ts:${msg.timestamp} < ready:${miaBotReadyAt}) from ${msg.from}`);
        return;
    }

    // Accept text AND media (voice notes, images)
    const hasMiaMedia = msg.hasMedia && ['ptt', 'audio', 'image'].includes(msg.type);

    // Facebook/Instagram ad clicks arrive as notification_template with empty body
    // — the pre-filled ad text lives in msg._data.body; fall back to "Hola" so Mia greets them
    const isAdClick = msg.type === 'notification_template';
    if (isAdClick) {
        const adText = msg._data?.body?.trim() || '';
        console.log(`[mia-bot] Ad-click from ${msg.from} — extracted text: "${adText || '(empty, using Hola)'}"`);
        // We'll set rawBody below via messageText; don't drop this message
    } else if (!rawBody && !hasMiaMedia) {
        console.log(`[mia-bot] Dropped: empty body, no media (type=${msg.type})`);
        return;
    }

    // Resolve real phone number — @lid = Facebook anonymized ID
    let from = msg.from;
    const isLid = msg.from.includes('@lid');
    if (isLid) {
        try {
            const contact = await msg.getContact();
            if (contact && contact.number) {
                from = contact.number + '@c.us';
                console.log(`[mia-bot] @lid resolved to ${from}`);
            } else {
                console.log(`[mia-bot] @lid could not resolve number, keeping ${from}`);
            }
        } catch (e) {
            console.log(`[mia-bot] @lid getContact failed: ${e.message}, keeping ${from}`);
        }
    }

    // Download media if present (voice notes, images)
    // For ad-click (notification_template), try _data.body first, then fall back to "Hola"
    let messageText = isAdClick
        ? (msg._data?.body?.trim() || rawBody || 'Hola')
        : rawBody;
    let mediaData = null, mediaMime = null, mediaType = null;
    if (hasMiaMedia) {
        try {
            const media = await msg.downloadMedia();
            if (media) {
                mediaData = media.data;
                mediaMime = media.mimetype;
                mediaType = msg.type;
                if (!messageText && msg.type === 'image' && msg.body) messageText = msg.body;
                if (!messageText) messageText = `[${msg.type}]`;
            }
        } catch (e) {
            console.error(`[mia-bot] Media download error: ${e.message}`);
        }
    }
    if (!messageText) messageText = `[${mediaType || 'media'}]`;

    console.log(`[mia-bot] MSG from ${from}: ${messageText.substring(0, 80)}`);

    try {
        const reply = await callApi('/api/chat', {
            from,
            message:    messageText,
            media_data: mediaData,
            media_mime: mediaMime,
            media_type: mediaType,
        });
        if (reply) {
            // @lid contacts (Facebook ads) must use msg.reply() — sendMessage(@lid) silently fails
            if (isLid) {
                await msg.reply(reply);
            } else {
                await miaClient.sendMessage(from, reply);
            }
            console.log(`[mia-bot] REPLY to ${from}: ${reply.substring(0, 60)}`);
        }
    } catch (e) {
        console.error('[mia-bot] API error:', e.message);
        try {
            await msg.reply('Lo siento, tuve un problema técnico. Intenta de nuevo en un momento. 🙏');
        } catch (_) {}
    }
});

miaClient.initialize();

// ═════════════════════════════════════════════════════════════════════════════
// Multi-tenant client sessions
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Start a WhatsApp session for one subscribed client as an isolated forked process.
 * A crash in this worker affects only that client — never Mia or other clients.
 */
function createClientSession(clientId) {
    const id       = String(clientId);
    const existing = clientWorkers.get(id);
    if (existing && existing.status !== 'disconnected' && existing.worker !== null) {
        console.log(`[client:${id}] Session already ${existing.status} — skipping`);
        return;
    }

    console.log(`[client:${id}] Forking isolated worker process...`);
    const state = { worker: null, status: 'qr_pending', qrData: null, phone: null };
    clientWorkers.set(id, state);

    const worker = fork(path.join(__dirname, 'bot_client_worker.js'), [], { silent: false });
    state.worker = worker;

    // Bootstrap the worker with its clientId
    worker.send({ type: 'init', clientId: id });

    // Status / QR updates arrive via IPC
    worker.on('message', (msg) => {
        if (msg.type === 'status') {
            state.status = msg.status;
            state.phone  = msg.phone || null;
            if (msg.status === 'connected') state.qrData = null;
            notifyPhpStatus(id, msg.status, msg.phone);
        }
        if (msg.type === 'qr') {
            state.status = 'qr_pending';
            state.qrData = msg.qrData;
        }
    });

    // Worker exit = disconnect (crash or clean shutdown)
    worker.on('exit', (code, signal) => {
        console.log(`[client:${id}] Worker exited (code=${code} signal=${signal})`);
        state.worker = null;
        state.status = 'disconnected';
        state.qrData = null;
        state.phone  = null;
    });

    worker.on('error', (err) => {
        console.error(`[client:${id}] Worker error: ${err.message}`);
    });
}

/**
 * Gracefully stop a client's worker process.
 */
async function destroyClientSession(clientId) {
    const id    = String(clientId);
    const state = clientWorkers.get(id);
    if (!state?.worker) {
        if (state) state.status = 'disconnected';
        return;
    }
    try { state.worker.send({ type: 'destroy' }); } catch (_) {}
    // Force kill after 5 s if it hasn't exited gracefully
    setTimeout(() => {
        if (state.worker) {
            try { state.worker.kill(); } catch (_) {}
            state.worker = null;
            state.status = 'disconnected';
        }
    }, 5000);
}

// ── On startup: reconnect any clients with saved LocalAuth sessions ───────────
(function autoInitSavedSessions() {
    if (!fs.existsSync(AUTH_DIR)) return;
    for (const entry of fs.readdirSync(AUTH_DIR)) {
        // LocalAuth stores sessions as  session-{clientId}/
        if (entry.startsWith('session-client_')) {
            const clientId = entry.replace('session-client_', '');
            if (/^\d+$/.test(clientId)) {
                console.log(`[startup] Reconnecting saved session for client ${clientId}`);
                createClientSession(clientId);
            }
        }
    }
})();

// ═════════════════════════════════════════════════════════════════════════════
// PHP status callback
// ═════════════════════════════════════════════════════════════════════════════
function notifyPhpStatus(clientId, status, phone) {
    const body = JSON.stringify({
        client_id: parseInt(String(clientId), 10),
        status,
        phone: phone || null,
    });
    return new Promise((resolve) => {
        const opts = {
            hostname:           MIA_API_HOST,
            port:               MIA_API_PORT,
            path:               '/api/client-status',
            method:             'POST',
            rejectUnauthorized: false,
            headers: {
                'Content-Type':   'application/json',
                'Content-Length': Buffer.byteLength(body),
                'X-Mia-Bot-Key':  MIA_BOT_SECRET,
            },
        };
        const req = https.request(opts, (res) => { res.resume(); resolve(); });
        req.on('error', (e) => { console.error('[notifyPhp] error:', e.message); resolve(); });
        req.setTimeout(8000, () => { req.destroy(); resolve(); });
        req.write(body);
        req.end();
    });
}

// ═════════════════════════════════════════════════════════════════════════════
// Generic Mia API caller
// ═════════════════════════════════════════════════════════════════════════════
function callApi(apiPath, payload) {
    return new Promise((resolve, reject) => {
        const body = JSON.stringify(payload);
        const opts = {
            hostname:           MIA_API_HOST,
            port:               MIA_API_PORT,
            path:               apiPath,
            method:             'POST',
            rejectUnauthorized: false,
            headers: {
                'Content-Type':   'application/json',
                'Content-Length': Buffer.byteLength(body),
                'X-Mia-Bot-Key':  MIA_BOT_SECRET,
            },
        };
        const req = https.request(opts, (res) => {
            let data = '';
            res.on('data', (c) => data += c);
            res.on('end', () => {
                try {
                    const json = JSON.parse(data);
                    if (json.success && json.reply) resolve(json.reply);
                    else reject(new Error(json.error || 'No reply from API'));
                } catch (e) {
                    reject(new Error('Invalid JSON from API: ' + data.substring(0, 100)));
                }
            });
        });
        req.on('error', reject);
        req.setTimeout(15000, () => { req.destroy(); reject(new Error('API timeout')); });
        req.write(body);
        req.end();
    });
}

// ═════════════════════════════════════════════════════════════════════════════
// Admin HTTP server  (localhost:3001)
// ═════════════════════════════════════════════════════════════════════════════
const adminServer = http.createServer((req, res) => {
    const url    = req.url   || '';
    const method = req.method || '';

    // POST /send — send message via Mia's sales bot
    if (method === 'POST' && url === '/send') {
        readBody(req, (raw) => {
            try {
                const { to, message } = JSON.parse(raw);
                if (!to || !message) return respond(res, 400, { error: 'to and message required' });
                const chatId = to.includes('@') ? to : to.replace('+', '') + '@c.us';
                miaClient.sendMessage(chatId, message)
                    .then(() => {
                        console.log(`[admin] OUTBOUND to ${chatId}: ${message.substring(0, 60)}`);
                        respond(res, 200, { success: true, to: chatId });
                    })
                    .catch((e) => respond(res, 500, { error: e.message }));
            } catch (_) {
                respond(res, 400, { error: 'Invalid JSON' });
            }
        });
        return;
    }

    // POST /send-client — send outbound message via a specific client's bot
    if (method === 'POST' && url === '/send-client') {
        readBody(req, (raw) => {
            try {
                const { client_id, to, message } = JSON.parse(raw);
                if (!client_id || !to || !message) return respond(res, 400, { error: 'client_id, to and message required' });
                const id    = String(client_id);
                const state = clientWorkers.get(id);
                if (!state?.worker || state.status !== 'connected') return respond(res, 503, { error: 'client not connected' });
                const chatId = to.includes('@') ? to : to.replace('+', '') + '@c.us';
                state.worker.send({ type: 'send', to: chatId, message });
                console.log(`[client:${id}] OUTBOUND(human) queued to ${chatId}: ${message.substring(0, 60)}`);
                respond(res, 200, { success: true, to: chatId });
            } catch (_) {
                respond(res, 400, { error: 'Invalid JSON' });
            }
        });
        return;
    }

    // POST /connect/:clientId
    const connectMatch = url.match(/^\/connect\/(\d+)$/);
    if (method === 'POST' && connectMatch) {
        const clientId = connectMatch[1];
        createClientSession(clientId);
        respond(res, 200, { success: true, clientId });
        return;
    }

    // POST /disconnect/:clientId
    const disconnectMatch = url.match(/^\/disconnect\/(\d+)$/);
    if (method === 'POST' && disconnectMatch) {
        destroyClientSession(disconnectMatch[1])
            .then(() => respond(res, 200, { success: true }));
        return;
    }

    // GET /qr/mia  — QR for the Mia sales bot itself
    if (method === 'GET' && url === '/qr/mia') {
        return respond(res, 200, {
            status:   miaBotStatus,
            qr_image: miaBotQrData,
            phone:    miaBotPhone,
        });
    }

    // GET /qr/:clientId
    const qrMatch = url.match(/^\/qr\/(\d+)$/);
    if (method === 'GET' && qrMatch) {
        const clientId = qrMatch[1];
        const s        = clientWorkers.get(clientId);
        if (!s)                    return respond(res, 200, { status: 'disconnected', qr_image: null });
        if (s.status === 'connected') return respond(res, 200, { status: 'connected',    qr_image: null, phone: s.phone });
        if (s.qrData)              return respond(res, 200, { status: 'qr_pending',    qr_image: s.qrData });
        respond(res, 200, { status: s.status, qr_image: null });
        return;
    }

    // GET /status/:clientId
    const statusMatch = url.match(/^\/status\/(\d+)$/);
    if (method === 'GET' && statusMatch) {
        const s = clientWorkers.get(statusMatch[1]);
        if (!s) return respond(res, 200, { status: 'disconnected', phone: null });
        respond(res, 200, { status: s.status, phone: s.phone });
        return;
    }

    respond(res, 404, { error: 'Not found' });
});

adminServer.listen(3001, '127.0.0.1', () => {
    console.log('📡 Admin server listening on 127.0.0.1:3001');
});

// ═════════════════════════════════════════════════════════════════════════════
// Utilities
// ═════════════════════════════════════════════════════════════════════════════
function readBody(req, cb) {
    let body = '';
    req.on('data', (c) => body += c);
    req.on('end', () => cb(body));
}

function respond(res, code, obj) {
    res.writeHead(code, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify(obj));
}
