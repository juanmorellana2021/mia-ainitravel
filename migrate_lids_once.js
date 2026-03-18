/**
 * migrate_lids_once.js
 *
 * One-shot script: resolves all LID-format phone numbers stored in the DB
 * to real phone numbers by asking WhatsApp directly.
 *
 * Usage:
 *   pm2 stop mia-bot
 *   node migrate_lids_once.js
 *   pm2 start mia-bot
 */

'use strict';

const { Client, LocalAuth } = require('whatsapp-web.js');
const https = require('https');
const path  = require('path');

const CLIENT_ID      = '1';                     // Samaywasi's client ID
const MIA_API_HOST   = 'mia-whatsapp.com';
const MIA_API_PORT   = 443;
const MIA_BOT_SECRET = 'mia-bot-secret-2026';
const AUTH_DIR       = path.join(__dirname, '.wwebjs_auth');

// ── API caller ────────────────────────────────────────────────────────────────
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
            let raw = '';
            res.on('data', d => raw += d);
            res.on('end', () => {
                try { resolve(JSON.parse(raw)); }
                catch (e) { reject(new Error('Bad JSON: ' + raw.substring(0, 200))); }
            });
        });
        req.on('error', reject);
        req.write(body);
        req.end();
    });
}

// ── Migration logic ───────────────────────────────────────────────────────────
async function migrate(ww) {
    console.log('[migrate] Querying stored LID phone numbers...');
    let resp;
    try {
        resp = await callApi('/api/resolve-lids', { client_id: parseInt(CLIENT_ID, 10) });
    } catch (e) {
        console.error('[migrate] resolve-lids API failed:', e.message);
        return;
    }

    if (!resp || !resp.success) {
        console.error('[migrate] API error:', resp && resp.error);
        return;
    }

    if (!Array.isArray(resp.lids) || resp.lids.length === 0) {
        console.log('[migrate] Nothing to resolve — all phone numbers are already real numbers!');
        return;
    }

    console.log(`[migrate] Found ${resp.lids.length} LID number(s) to resolve:`, resp.lids);

    const resolved = [];
    for (const lid of resp.lids) {
        try {
            const contact = await ww.getContactById(lid + '@lid');
            if (contact && contact.number) {
                resolved.push({ lid, phone: contact.number });
                console.log(`[migrate]   ${lid}  →  ${contact.number}`);
            } else {
                console.log(`[migrate]   ${lid}  →  (no number returned)`);
            }
        } catch (e) {
            console.log(`[migrate]   ${lid}  →  error: ${e.message}`);
        }
        // 300ms gap between WA lookups
        await new Promise(r => setTimeout(r, 300));
    }

    if (resolved.length === 0) {
        console.log('[migrate] Could not resolve any LIDs — contacts may no longer be in WA.');
        return;
    }

    console.log(`\n[migrate] Applying ${resolved.length} resolution(s) to database...`);
    try {
        const result = await callApi('/api/apply-lid-resolutions', {
            client_id: parseInt(CLIENT_ID, 10),
            resolved,
        });
        if (result && result.success) {
            console.log(`[migrate] ✅ Done! Updated ${result.updated ?? resolved.length} records.`);
        } else {
            console.error('[migrate] API returned error:', result && result.error);
        }
    } catch (e) {
        console.error('[migrate] apply-lid-resolutions failed:', e.message);
    }
}

// ── WhatsApp client ───────────────────────────────────────────────────────────
const ww = new Client({
    authStrategy: new LocalAuth({ clientId: 'client_' + CLIENT_ID, dataPath: AUTH_DIR }),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    },
});

ww.on('qr', () => {
    console.error('[migrate] ❌ QR code required — session is not authenticated.');
    console.error('[migrate]    Start the bot normally first so it can scan QR, then run this script.');
    process.exit(1);
});

ww.on('auth_failure', (msg) => {
    console.error('[migrate] ❌ Auth failure:', msg);
    process.exit(1);
});

ww.on('ready', async () => {
    console.log('[migrate] ✅ WhatsApp connected.\n');
    try {
        await migrate(ww);
    } catch (e) {
        console.error('[migrate] Unexpected error:', e.message);
    }
    console.log('\n[migrate] Closing connection...');
    await ww.destroy().catch(() => {});
    process.exit(0);
});

console.log('[migrate] Initializing WhatsApp client (reusing saved session)...');
ww.initialize();
