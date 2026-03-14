/**
 * bot_client_worker.js
 *
 * Isolated worker process for ONE subscribed client's WhatsApp session.
 * Forked by bot.js (the orchestrator). Never runs standalone.
 *
 * IPC protocol (parent <-> worker):
 *   parent → worker: { type: 'init',    clientId: '5' }
 *   parent → worker: { type: 'send',    to: '519...@c.us', message: '...' }
 *   parent → worker: { type: 'destroy' }
 *
 *   worker → parent: { type: 'status',  clientId, status: 'connected'|'qr_pending'|'disconnected', phone }
 *   worker → parent: { type: 'qr',      clientId, qrData: 'data:image/png;base64,...' }
 *
 * If this process crashes, only THIS client's session drops.
 * All other clients and Mia's own sales bot keep running.
 */

'use strict';

const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcodeImage           = require('qrcode');
const https                 = require('https');
const path                  = require('path');

const MIA_API_HOST   = 'mia.ainitravel.com';
const MIA_API_PORT   = 443;
const MIA_BOT_SECRET = 'mia-bot-secret-2026';
const AUTH_DIR       = path.join(__dirname, '.wwebjs_auth');

let clientId       = null;
let ww             = null;
let sessionReadyAt = 0;

// ── IPC: receive commands from parent ─────────────────────────────────────────
process.on('message', (msg) => {
    if (!msg || !msg.type) return;

    if (msg.type === 'init' && msg.clientId) {
        clientId = String(msg.clientId);
        console.log(`[worker:${clientId}] Received init — starting session`);
        startSession();
        return;
    }

    if (msg.type === 'send' && ww) {
        const chatId = msg.to.includes('@') ? msg.to : msg.to.replace('+', '') + '@c.us';
        ww.sendMessage(chatId, msg.message)
            .then(() => console.log(`[worker:${clientId}] OUTBOUND to ${chatId}`))
            .catch((e) => console.error(`[worker:${clientId}] OUTBOUND error: ${e.message}`));
        return;
    }

    if (msg.type === 'destroy') {
        console.log(`[worker:${clientId}] Received destroy — shutting down`);
        if (ww) {
            ww.destroy().catch(() => {}).finally(() => process.exit(0));
        } else {
            process.exit(0);
        }
    }
});

// ── WhatsApp session ──────────────────────────────────────────────────────────
function startSession() {
    ww = new Client({
        authStrategy: new LocalAuth({ clientId: 'client_' + clientId, dataPath: AUTH_DIR }),
        puppeteer: {
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox'],
        },
    });

    ww.on('qr', async (qr) => {
        console.log(`[worker:${clientId}] QR generated`);
        let qrData = null;
        try { qrData = await qrcodeImage.toDataURL(qr, { width: 300 }); } catch (_) {}
        process.send({ type: 'qr', clientId, qrData, status: 'qr_pending' });
    });

    ww.on('ready', () => {
        sessionReadyAt = Math.floor(Date.now() / 1000);
        const phone = ww.info?.wid?.user ? '+' + ww.info.wid.user : null;
        console.log(`[worker:${clientId}] ✅ Connected! Phone: ${phone}`);
        process.send({ type: 'status', clientId, status: 'connected', phone });
    });

    ww.on('disconnected', (reason) => {
        console.log(`[worker:${clientId}] ❌ Disconnected: ${reason}`);
        process.send({ type: 'status', clientId, status: 'disconnected', phone: null });
        // Exit so the parent can detect the disconnect and notify the client
        process.exit(1);
    });

    ww.on('message', async (msg) => {
        if (msg.from === 'status@broadcast' || msg.from.includes('@g.us')) return;
        if (msg.fromMe) return;

        const rawBody = msg.body?.trim() || '';
        console.log(`[worker:${clientId}] RAW from=${msg.from} type=${msg.type} body="${rawBody.substring(0, 60)}"`);

        // Skip offline backlog after reconnect
        if (sessionReadyAt > 0 && msg.timestamp && msg.timestamp < sessionReadyAt) {
            console.log(`[worker:${clientId}] Skipping offline-backlog msg from ${msg.from}`);
            return;
        }

        // ── Media handling ────────────────────────────────────────────────────
        // Facebook/Instagram ad clicks arrive as notification_template with empty body
        // The pre-filled ad text lives in msg._data.body; fall back to "Hola" so the bot always greets them
        const isAdClick = msg.type === 'notification_template';

        const isMediaMsg = msg.hasMedia && ['ptt', 'audio', 'image'].includes(msg.type);

        let messageText = isAdClick
            ? (msg._data?.body?.trim() || rawBody || 'Hola')
            : rawBody;
        let mediaData = null, mediaMime = null, mediaType = null;
        if (isMediaMsg) {
            try {
                const media = await msg.downloadMedia();
                if (media) {
                    mediaData = media.data;      // base64
                    mediaMime = media.mimetype;
                    mediaType = msg.type;
                    // For images, use the caption as text if present
                    if (!messageText && msg.type === 'image' && msg.body) messageText = msg.body;
                    if (!messageText) messageText = `[${msg.type}]`;
                }
            } catch (e) {
                console.error(`[worker:${clientId}] Media download error: ${e.message}`);
            }
        }

        if (!messageText && !mediaData && !isAdClick) {
            console.log(`[worker:${clientId}] Dropped: empty body, no media (type=${msg.type})`);
            return;
        }
        if (!messageText) messageText = `[${mediaType || 'media'}]`;

        const from = msg.from;
        console.log(`[worker:${clientId}] MSG from ${from}: ${messageText.substring(0, 80)}`);

        try {
            const reply = await callApi('/api/client-chat', {
                from,
                message:    messageText,
                client_id:  parseInt(clientId, 10),
                media_data: mediaData,
                media_mime: mediaMime,
                media_type: mediaType,
            });
            if (reply) {
                await ww.sendMessage(from, reply);
                console.log(`[worker:${clientId}] REPLY to ${from}: ${reply.substring(0, 60)}`);
            }
        } catch (e) {
            console.error(`[worker:${clientId}] API error: ${e.message}`);
            try {
                await ww.sendMessage(from, 'Un momento, estoy teniendo un pequeño problema técnico 🙏');
            } catch (_) {}
        }
    });

    ww.initialize();
}

// ── HTTPS API caller ──────────────────────────────────────────────────────────
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
                    reject(new Error('Invalid JSON: ' + data.substring(0, 100)));
                }
            });
        });
        req.on('error', reject);
        req.setTimeout(20000, () => { req.destroy(); reject(new Error('API timeout')); });
        req.write(body);
        req.end();
    });
}
