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
const fs                    = require('fs');
const path                  = require('path');

// ── Constants ─────────────────────────────────────────────────────────────────
const MIA_BOT_SECRET = 'mia-bot-secret-2026';
const MIA_API_HOST   = 'mia.ainitravel.com';
const MIA_API_PORT   = 443;
const AUTH_DIR       = path.join(__dirname, '.wwebjs_auth');

// ── Client session registry ───────────────────────────────────────────────────
// Map<string clientId, { client: Client|null, status: string, qrData: string|null, phone: string|null }>
const clientSessions = new Map();

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
    console.log('[mia-bot] ✅ Sales bot connected and ready');
    miaBotStatus = 'connected';
    miaBotQrData = null;
    try { miaClient.info && (miaBotPhone = miaClient.info.wid.user); } catch(_) {}
});

miaClient.on('disconnected', (reason) => {
    console.log('[mia-bot] ❌ Disconnected:', reason);
    miaBotStatus = 'disconnected';
    miaBotQrData = null;
    process.exit(1); // pm2 restarts the whole process
});

miaClient.on('message', async (msg) => {
    if (msg.from === 'status@broadcast' || msg.from.includes('@g.us')) return;
    if (msg.fromMe || msg.type !== 'chat' || !msg.body?.trim()) return;

    const from    = msg.from;
    const message = msg.body;
    console.log(`[mia-bot] MSG from ${from}: ${message.substring(0, 80)}`);

    try {
        const reply = await callApi('/api/chat', { from, message });
        if (reply) {
            await miaClient.sendMessage(from, reply);
            console.log(`[mia-bot] REPLY to ${from}: ${reply.substring(0, 60)}`);
        }
    } catch (e) {
        console.error('[mia-bot] API error:', e.message);
        await miaClient.sendMessage(from, 'Lo siento, tuve un problema técnico. Intenta de nuevo en un momento. 🙏');
    }
});

miaClient.initialize();

// ═════════════════════════════════════════════════════════════════════════════
// Multi-tenant client sessions
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Start (or reconnect) a WhatsApp session for one subscribed client.
 * Does nothing if the session is already active.
 */
function createClientSession(clientId) {
    const id = String(clientId);

    const existing = clientSessions.get(id);
    if (existing && existing.status !== 'disconnected') {
        console.log(`[client:${id}] Session already ${existing.status} — skipping`);
        return;
    }

    console.log(`[client:${id}] Initializing WhatsApp session...`);
    const session = { client: null, status: 'qr_pending', qrData: null, phone: null };
    clientSessions.set(id, session);

    const ww = makeWaClient('client_' + id);
    session.client = ww;

    ww.on('qr', async (qr) => {
        console.log(`[client:${id}] QR generated`);
        session.status = 'qr_pending';
        session.qrData = null;
        try {
            session.qrData = await qrcodeImage.toDataURL(qr, { width: 300 });
        } catch (e) {
            console.error(`[client:${id}] QR image error:`, e.message);
        }
    });

    ww.on('ready', async () => {
        try {
            const phone = ww.info?.wid?.user ? '+' + ww.info.wid.user : null;
            session.status = 'connected';
            session.qrData = null;
            session.phone  = phone;
            console.log(`[client:${id}] ✅ Connected! Phone: ${phone}`);
            await notifyPhpStatus(id, 'connected', phone);
        } catch (e) {
            console.error(`[client:${id}] ready handler error:`, e.message);
        }
    });

    ww.on('disconnected', async (reason) => {
        console.log(`[client:${id}] ❌ Disconnected: ${reason}`);
        session.status = 'disconnected';
        session.qrData = null;
        session.phone  = null;
        session.client = null;
        await notifyPhpStatus(id, 'disconnected', null);
    });

    ww.on('message', async (msg) => {
        if (msg.from === 'status@broadcast' || msg.from.includes('@g.us')) return;
        if (msg.fromMe || msg.type !== 'chat' || !msg.body?.trim()) return;

        const from    = msg.from;
        const message = msg.body;
        console.log(`[client:${id}] MSG from ${from}: ${message.substring(0, 80)}`);

        try {
            const reply = await callApi('/api/client-chat', {
                from,
                message,
                client_id: parseInt(id, 10),
            });
            if (reply) {
                await ww.sendMessage(from, reply);
                console.log(`[client:${id}] REPLY to ${from}: ${reply.substring(0, 60)}`);
            }
        } catch (e) {
            console.error(`[client:${id}] API error:`, e.message);
            await ww.sendMessage(from, 'Un momento, estoy teniendo un pequeño problema técnico 🙏');
        }
    });

    ww.initialize();
}

/**
 * Gracefully disconnect and clean up a client session.
 */
async function destroyClientSession(clientId) {
    const id      = String(clientId);
    const session = clientSessions.get(id);
    if (!session?.client) {
        if (session) session.status = 'disconnected';
        return;
    }
    try { await session.client.destroy(); } catch (_) { /* ignore */ }
    session.status = 'disconnected';
    session.client = null;
    session.qrData = null;
    session.phone  = null;
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
                const id      = String(client_id);
                const session = clientSessions.get(id);
                if (!session?.client) return respond(res, 503, { error: 'client not connected' });
                const chatId = to.includes('@') ? to : to.replace('+', '') + '@c.us';
                session.client.sendMessage(chatId, message)
                    .then(() => {
                        console.log(`[client:${id}] OUTBOUND(human) to ${chatId}: ${message.substring(0, 60)}`);
                        respond(res, 200, { success: true, to: chatId });
                    })
                    .catch((e) => respond(res, 500, { error: e.message }));
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
        const s        = clientSessions.get(clientId);
        if (!s)                    return respond(res, 200, { status: 'disconnected', qr_image: null });
        if (s.status === 'connected') return respond(res, 200, { status: 'connected',    qr_image: null, phone: s.phone });
        if (s.qrData)              return respond(res, 200, { status: 'qr_pending',    qr_image: s.qrData });
        respond(res, 200, { status: s.status, qr_image: null });
        return;
    }

    // GET /status/:clientId
    const statusMatch = url.match(/^\/status\/(\d+)$/);
    if (method === 'GET' && statusMatch) {
        const s = clientSessions.get(statusMatch[1]);
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
