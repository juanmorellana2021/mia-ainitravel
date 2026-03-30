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

const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcodeImage           = require('qrcode');
const https                 = require('https');
const path                  = require('path');

const MIA_API_HOST   = 'mia-whatsapp.com';
const MIA_API_PORT   = 443;
const MIA_BOT_SECRET = 'mia-bot-secret-2026';
const AUTH_DIR       = path.join(__dirname, '.wwebjs_auth');

let clientId       = null;
let ww             = null;
let sessionReadyAt = 0;

// Dedup: track recently-seen message IDs to prevent double-processing
const recentMsgIds = new Set();
// Ad-click tracking: notification_template is always followed by a regular chat event.
// Skip notification_template, process the chat. No per-phone dedup timers needed.

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
        const raw = msg.to.replace('+', '');
        const chatId = msg.to.includes('@') ? msg.to : (raw.length >= 14 ? raw + '@lid' : raw + '@c.us');
        sendReplyWithPhotos(chatId, msg.message, null)
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
    // Remove stale Chrome singleton lock files left by a previous crash.
    // Without this, puppeteer throws "browser is already running" and the worker
    // crashes immediately, creating an infinite restart loop.
    const sessionDir = path.join(AUTH_DIR, 'session-client_' + clientId);
    for (const f of ['SingletonLock', 'SingletonSocket', 'SingletonCookie']) {
        try { require('fs').unlinkSync(path.join(sessionDir, f)); } catch (_) {}
    }
    // Also kill any stale Chrome processes still holding that user-data-dir.
    // This handles the case where the Node process was killed but Chrome kept running.
    try {
        require('child_process').execSync(
            `pkill -f "user-data-dir=${sessionDir}" 2>/dev/null || true`
        );
        // Give Chrome 1s to fully exit before launching a new instance
        require('child_process').execSync('sleep 1');
    } catch (_) {}

    ww = new Client({
        authStrategy: new LocalAuth({ clientId: 'client_' + clientId, dataPath: AUTH_DIR }),
        // Force a fresh WA Web JS download on every startup — prevents 'getLastMsgKeyForAction
        // is not a function' errors that occur when WhatsApp updates their web app and the
        // Chromium cache serves a stale version of their JS.
        webVersionCache: { type: 'none' },
        puppeteer: {
            headless: true,
            protocolTimeout: 120000, // 2 min — prevents 'Runtime.callFunctionOn timed out' on slow init
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
        },
    });

    ww.on('qr', async (qr) => {
        console.log(`[worker:${clientId}] QR generated`);
        let qrData = null;
        try { qrData = await qrcodeImage.toDataURL(qr, { width: 300 }); } catch (_) {}
        process.send({ type: 'qr', clientId, qrData, status: 'qr_pending' });
    });

    ww.on('ready', async () => {
        sessionReadyAt = Math.floor(Date.now() / 1000);
        const phone = ww.info?.wid?.user ? '+' + ww.info.wid.user : null;
        console.log(`[worker:${clientId}] ✅ Connected! Phone: ${phone}`);
        process.send({ type: 'status', clientId, status: 'connected', phone });

        // ── One-time LID → real phone migration ───────────────────────────────
        // Resolve any stored LID-format numbers (15-digit internal WA IDs) to
        // real phone numbers by asking WhatsApp directly on startup.
        // Wait a few seconds for WA to fully load the contact list
        setTimeout(async () => {
            try {
                await resolveLidPhones();
            } catch (e) {
                console.error(`[worker:${clientId}] LID migration error: ${e.message}`);
            }
        }, 2000);

        // ── One-time profile pic + name backfill ──────────────────────────────
        // Fetch profile pics and push names for existing leads that are missing them.
        setTimeout(async () => {
            try {
                await backfillLeadInfo();
            } catch (e) {
                console.error(`[worker:${clientId}] Backfill error: ${e.message}`);
            }
        }, 8000);
    });

    ww.on('disconnected', (reason) => {
        console.log(`[worker:${clientId}] ❌ Disconnected: ${reason}`);
        process.send({ type: 'status', clientId, status: 'disconnected', phone: null });
        // Exit so the parent can detect the disconnect and notify the client
        process.exit(1);
    });

    ww.on('message', async (msg) => {
        if (msg.from === 'status@broadcast' || msg.from.includes('@g.us') || msg.from.includes('@newsletter')) return;
        if (msg.fromMe) return;

        // Dedup by message ID
        const msgId = msg.id?._serialized || '';
        if (msgId && recentMsgIds.has(msgId)) {
            console.log(`[worker:${clientId}] Duplicate msgId ${msgId} — skipping`);
            return;
        }
        if (msgId) {
            recentMsgIds.add(msgId);
            setTimeout(() => recentMsgIds.delete(msgId), 300_000);
        }

        const rawBody = msg.body?.trim() || '';
        console.log(`[worker:${clientId}] RAW from=${msg.from} type=${msg.type} body="${rawBody.substring(0, 60)}"`);

        // Skip offline backlog after reconnect
        if (sessionReadyAt > 0 && msg.timestamp && msg.timestamp < sessionReadyAt) {
            console.log(`[worker:${clientId}] Skipping offline-backlog msg from ${msg.from}`);
            return;
        }

        // ── Media handling ────────────────────────────────────────────────────
        // Facebook/Instagram ad clicks arrive as notification_template followed by a chat.
        // Skip the notification_template — the real message comes right after.
        if (msg.type === 'notification_template') {
            console.log(`[worker:${clientId}] Ad-click notification_template from ${msg.from} — waiting for chat event`);
            return;
        }

        // Detect and skip bot auto-replies from other businesses
        const autoReplyPatterns = /gracias por (comunicarte|escribirnos|contactarnos)|en este momento no podemos|te responderemos a la brevedad|fuera del horario|horario de atenci[oó]n|mensaje autom[aá]tico|respuesta autom[aá]tica|tu mensaje (fue|ha sido) recibido|bienvenid[oa] a\b/i;
        if (rawBody && autoReplyPatterns.test(rawBody)) {
            console.log(`[worker:${clientId}] Auto-reply detected from ${msg.from}: "${rawBody.substring(0, 80)}" — skipping`);
            return;
        }

        const isMediaMsg = msg.hasMedia && ['ptt', 'audio', 'image'].includes(msg.type);

        let messageText = rawBody;
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

        if (!messageText && !mediaData) {
            console.log(`[worker:${clientId}] Dropped: empty body, no media (type=${msg.type})`);
            return;
        }
        if (!messageText) messageText = `[${mediaType || 'media'}]`;

        const from = msg.from;

        // -- phone and lid are always SEPARATE --
        // phone = real dialable number (empty if unresolved)
        // lid   = WhatsApp internal LID digits (empty for normal @c.us contacts)
        const isLid     = from.endsWith('@lid');
        const fromDigits = from.replace(/@.*/, '').replace(/[^0-9]/g, '');
        let phone       = isLid ? '' : fromDigits;  // @c.us: phone known immediately
        let lid         = isLid ? fromDigits : '';   // @lid:  LID known immediately
        let contactName = '';
        let profilePicUrl = '';

        if (isLid) {
            // Method 1: getContactLidAndPhone -- the only reliable LID->phone API
            try {
                const results = await ww.getContactLidAndPhone([from]);
                const pn = results?.[0]?.pn || '';
                const resolved = pn.replace(/@.*/, '').replace(/[^0-9]/g, '');
                if (resolved.length >= 7 && resolved.length <= 15) {
                    phone = resolved;
                    console.log(`[worker:${clientId}] LID resolved (method 1): ${lid} -> ${phone}`);
                }
            } catch (e) {
                console.log(`[worker:${clientId}] getContactLidAndPhone failed for ${lid}: ${e.message}`);
            }
            // Method 2: contact.number -- only valid if DIFFERENT from the LID itself
            if (!phone) {
                try {
                    const contact = await msg.getContact();
                    if (contact) {
                        contactName = contact.pushname || contact.name || '';
                        const num = (contact.number || '').replace(/[^0-9]/g, '');
                        if (num.length >= 7 && num.length <= 15 && num !== lid) {
                            phone = num;
                            console.log(`[worker:${clientId}] LID resolved (method 2): ${lid} -> ${phone}`);
                        }
                    }
                } catch (_) {}
            }
            if (!phone) {
                if (!contactName) {
                    try { const c = await msg.getContact(); if (c) contactName = c.pushname || c.name || ''; } catch (_) {}
                }
                console.log(`[worker:${clientId}] LID unresolved: ${lid} -- routing by LID only`);
            }
        } else {
            try { const contact = await msg.getContact(); if (contact) contactName = contact.pushname || contact.name || ''; } catch (_) {}
        }

        try { profilePicUrl = await ww.getProfilePicUrl(from) || ''; } catch (_) {}

        console.log(`[worker:${clientId}] MSG from ${from} phone=${phone||'(none)'} lid=${lid||'(none)'} name="${contactName}": ${messageText.substring(0, 80)}`);

        try {
            const resp = await callApi('/api/client-chat', {
                from,
                phone,           // real dialable number or empty string
                lid,             // LID digits or empty string
                message:         messageText,
                client_id:       parseInt(clientId, 10),
                contact_name:    contactName,
                profile_pic_url: profilePicUrl,
                media_data:      mediaData,
                media_mime:      mediaMime,
                media_type:      mediaType,
            });
            const reply = resp && resp.reply;
            if (reply) {
                await sendReplyWithPhotos(from, reply, msg);
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

// ── Download image as base64 (more reliable than MessageMedia.fromUrl) ────────
function downloadImageAsBase64(url) {
    return new Promise((resolve, reject) => {
        const proto = url.startsWith('https') ? https : require('http');
        proto.get(url, { rejectUnauthorized: false }, (res) => {
            if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
                // Follow redirect
                return downloadImageAsBase64(res.headers.location).then(resolve).catch(reject);
            }
            if (res.statusCode !== 200) {
                return reject(new Error(`HTTP ${res.statusCode}`));
            }
            const mime = res.headers['content-type'] || 'image/jpeg';
            const chunks = [];
            res.on('data', (c) => chunks.push(c));
            res.on('end', () => resolve({ data: Buffer.concat(chunks).toString('base64'), mimetype: mime }));
            res.on('error', reject);
        }).on('error', reject);
    });
}

// ── Reply sender: handles text + optional [FOTO:url] markers ─────────────────
// The AI can include [FOTO:https://...] for images and [ARCHIVO:https://...:filename] for documents.
// We extract those, send the text portion first (if any), then each attachment.
// @lid contacts MUST use chat.sendMessage() via Chat object — ww.sendMessage(@lid) silently fails for media.
async function sendReplyWithPhotos(to, reply, originalMsg) {
    const photoRegex   = /\[FOTO:(https?:\/\/[^\]]+)\]/gi;
    const archivoRegex = /\[ARCHIVO:(https?:\/\/[^\]]+):([^\]]+)\]/gi;
    const photoUrls  = [];
    const archivos   = [];
    let match;
    while ((match = photoRegex.exec(reply)) !== null) {
        photoUrls.push(match[1]);
    }
    while ((match = archivoRegex.exec(reply)) !== null) {
        archivos.push({ url: match[1], filename: match[2] });
    }

    const isLid = to.includes('@lid');
    console.log(`[worker:${clientId}] PHOTO: ${photoUrls.length} photos, ARCHIVO: ${archivos.length} docs, isLid=${isLid}`);

    // Text with all markers removed and trimmed
    const textPart = reply
        .replace(photoRegex, '')
        .replace(archivoRegex, '')
        .replace(/\s{2,}/g, ' ')
        .trim();

    // For @lid contacts, get the Chat object — ww.sendMessage(@lid) silently fails for media
    let chat = null;
    if (isLid) {
        try {
            chat = await ww.getChatById(to);
        } catch (e) {
            console.error(`[worker:${clientId}] PHOTO: getChatById failed: ${e.message}`);
        }
    }

    // Helper: send to the right place
    const sendMsg = async (content, opts) => {
        if (chat) {
            await chat.sendMessage(content, opts || {});
        } else if (originalMsg && isLid) {
            await originalMsg.reply(content);
        } else {
            await ww.sendMessage(to, content, opts || {});
        }
    };

    // Send text portion
    if (textPart) {
        await sendMsg(textPart);
    }

    // Send each photo as media attachment
    for (const url of photoUrls) {
        try {
            console.log(`[worker:${clientId}] PHOTO: downloading ${url}`);
            const { data, mimetype } = await downloadImageAsBase64(url);

            const media = new MessageMedia(mimetype, data, url.split('/').pop());
            await sendMsg(media);
            console.log(`[worker:${clientId}] PHOTO: sent OK`);
        } catch (e) {
            console.error(`[worker:${clientId}] PHOTO FAIL ${url}: ${e.message}`);
            // Fallback: send URL as clickable link so user can at least see the photo
            try {
                await sendMsg(`📷 Ver foto: ${url}`);
            } catch (_) {}
        }
    }

    // Send each document as a file attachment
    for (const archivo of archivos) {
        try {
            console.log(`[worker:${clientId}] ARCHIVO: downloading ${archivo.url}`);
            const response = await fetch(archivo.url);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const buffer   = await response.arrayBuffer();
            const b64      = Buffer.from(buffer).toString('base64');
            const mime     = response.headers.get('content-type') || 'application/octet-stream';
            const safeName = archivo.filename.replace(/[^\w.\-]/g, '_') || 'documento';
            const media = new MessageMedia(mime, b64, safeName);
            await sendMsg(media, { sendMediaAsDocument: true });
            console.log(`[worker:${clientId}] ARCHIVO: sent OK — ${safeName}`);
        } catch (e) {
            console.error(`[worker:${clientId}] ARCHIVO FAIL ${archivo.url}: ${e.message}`);
            try {
                await sendMsg(`📄 Ver documento: ${archivo.url}`);
            } catch (_) {}
        }
    }
}

// ── Profile pic + name backfill ──────────────────────────────────────────────
// Runs once on startup. Fetches profile pics and push names for all existing
// leads that are missing them in the DB.
async function backfillLeadInfo() {
    console.log(`[worker:${clientId}] Backfill: fetching leads needing info...`);
    let resp;
    try {
        resp = await callApi('/api/leads-needing-backfill', { client_id: parseInt(clientId, 10) });
    } catch (e) {
        console.error(`[worker:${clientId}] Backfill: API failed: ${e.message}`);
        return;
    }
    if (!resp || !Array.isArray(resp.leads) || resp.leads.length === 0) {
        console.log(`[worker:${clientId}] Backfill: nothing to update`);
        return;
    }
    console.log(`[worker:${clientId}] Backfill: checking ${resp.leads.length} leads...`);
    const updates = [];
    for (const lead of resp.leads) {
        const phone = lead.phone;
        if (!phone) continue;
        try {
            const chatId = phone + '@c.us';
            const contact = await ww.getContactById(chatId);
            const name = contact ? (contact.pushname || contact.name || '') : '';
            let picUrl = '';
            try { picUrl = await ww.getProfilePicUrl(chatId) || ''; } catch (_) {}
            if (name || picUrl) {
                updates.push({ lead_id: lead.id, contact_name: name, profile_pic_url: picUrl });
                console.log(`[worker:${clientId}] Backfill: ${phone} → name="${name}" pic=${picUrl ? 'yes' : 'no'}`);
            }
        } catch (_) {}
        // Small delay to avoid hammering WhatsApp
        await new Promise(r => setTimeout(r, 500));
    }
    if (updates.length > 0) {
        try {
            await callApi('/api/apply-lead-backfill', { client_id: parseInt(clientId, 10), updates });
            console.log(`[worker:${clientId}] Backfill: applied ${updates.length} updates`);
        } catch (e) {
            console.error(`[worker:${clientId}] Backfill: apply failed: ${e.message}`);
        }
    } else {
        console.log(`[worker:${clientId}] Backfill: no contacts resolved`);
    }
}

// ── LID → real phone resolver ─────────────────────────────────────────────────
// Runs once on startup. Finds all leads/messages with LID-format phone numbers
// (15-digit internal WA IDs) and resolves them to real phone numbers via WA.
async function resolveLidPhones() {
    console.log(`[worker:${clientId}] LID resolver: querying unresolved LIDs...`);
    let resp;
    try {
        resp = await callApi('/api/resolve-lids', { client_id: parseInt(clientId, 10) });
    } catch (e) {
        console.error(`[worker:${clientId}] LID resolver: API failed: ${e.message}`);
        return;
    }
    if (!resp || !Array.isArray(resp.lids) || resp.lids.length === 0) {
        console.log(`[worker:${clientId}] LID resolver: nothing to resolve`);
        return;
    }
    console.log(`[worker:${clientId}] LID resolver: trying ${resp.lids.length} LIDs...`);
    const resolved = [];
    for (const lid of resp.lids) {
        try {
            // IMPORTANT: contact.number for @lid contacts returns the LID digits back.
            // Only getContactLidAndPhone().pn gives the real phone number.
            const results = await ww.getContactLidAndPhone([lid + '@lid']);
            const pn = results?.[0]?.pn || '';
            const phone = pn.replace(/@.*/, '').replace(/[^0-9]/g, '');
            if (phone.length >= 7 && phone.length <= 15 && phone !== lid) {
                resolved.push({ lid, phone });
                console.log(`[worker:${clientId}] LID resolved: ${lid} -> ${phone}`);
            } else {
                console.log(`[worker:${clientId}] LID unresolvable: ${lid} (pn="${pn}")`);
            }
        } catch (e) {
            console.log(`[worker:${clientId}] LID resolver error for ${lid}: ${e.message}`);
        }
        await new Promise(r => setTimeout(r, 400));
    }
    if (resolved.length > 0) {
        try {
            await callApi('/api/apply-lid-resolutions', { client_id: parseInt(clientId, 10), resolved });
            console.log(`[worker:${clientId}] LID resolver: applied ${resolved.length} updates`);
        } catch (e) {
            console.error(`[worker:${clientId}] LID resolver: apply failed: ${e.message}`);
        }
    } else {
        console.log(`[worker:${clientId}] LID resolver: 0/${resp.lids.length} resolved -- WhatsApp has not mapped these yet`);
    }
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
                    if (!json.success) reject(new Error(json.error || 'API error'));
                    else resolve(json);   // return full JSON so callers can read any field
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
