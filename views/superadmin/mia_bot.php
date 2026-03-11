<?php
/**
 * mia/views/superadmin/mia_bot.php
 *
 * Shows the Mia sales bot WhatsApp connection status and QR code.
 * Polls /qr/mia on the bot server every 3s.
 */
$base = App::basePath();
require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">

        <!-- Status card -->
        <div class="sa-card p-4 text-center mb-3" id="statusCard">
            <div id="statusIcon" style="font-size:3rem;margin-bottom:12px;">⏳</div>
            <div id="statusTitle" style="font-size:1.1rem;font-weight:700;color:#e2e8f0;margin-bottom:6px;">
                Verificando estado…
            </div>
            <div id="statusSub" style="font-size:0.83rem;color:#64748b;">
                Conectando con el servidor del bot…
            </div>
        </div>

        <!-- QR card — shown only when QR is available -->
        <div class="sa-card p-4 text-center" id="qrCard" style="display:none;">
            <div style="font-size:0.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;">
                <i class="bi bi-qr-code me-1"></i>Escanea con WhatsApp Business
            </div>
            <img id="qrImage" src="" alt="QR Code"
                 style="width:240px;height:240px;border-radius:12px;border:3px solid rgba(99,102,241,0.4);">
            <p style="font-size:0.8rem;color:#475569;margin-top:14px;margin-bottom:0;">
                Abre WhatsApp Business → <strong style="color:#94a3b8;">Dispositivos vinculados</strong> → Vincular dispositivo
            </p>
            <div style="margin-top:12px;">
                <button onclick="forceReload()" class="btn btn-sm"
                        style="background:rgba(99,102,241,0.15);color:#818cf8;border:1px solid rgba(99,102,241,0.3);border-radius:8px;font-size:0.8rem;">
                    <i class="bi bi-arrow-clockwise me-1"></i>Actualizar QR
                </button>
            </div>
        </div>

        <!-- Phone connected card -->
        <div class="sa-card p-4 text-center" id="connectedCard" style="display:none;">
            <div style="font-size:2.5rem;margin-bottom:10px;">✅</div>
            <div style="font-weight:700;color:#4ade80;font-size:1.1rem;margin-bottom:6px;">
                Bot conectado
            </div>
            <div id="connectedPhone" style="font-size:0.85rem;color:#94a3b8;font-family:monospace;"></div>
            <p style="font-size:0.8rem;color:#475569;margin-top:12px;margin-bottom:0;">
                Mia está activa y respondiendo mensajes de WhatsApp.
            </p>
        </div>

        <div class="text-center mt-3">
            <a href="<?= $base ?>/superadmin/dashboard"
               style="font-size:0.83rem;color:#475569;text-decoration:none;">
                <i class="bi bi-arrow-left me-1"></i>Volver al dashboard
            </a>
        </div>
    </div>
</div>

<script>
const POLL_URL = '<?= $base ?>/superadmin/mia-bot-status';

let pollInterval = null;
let lastQrSrc    = null;
let lastStatus   = null;

async function poll() {
    try {
        const res  = await fetch(POLL_URL);
        const data = await res.json();
        render(data);
    } catch (e) {
        showStatus('⚠️', 'Error de conexión', 'No se pudo contactar el servidor del bot.');
    }
}

function render(data) {
    if (data.status === lastStatus && data.status !== 'qr_pending') return;
    lastStatus = data.status;

    document.getElementById('statusCard').style.display  = '';
    document.getElementById('qrCard').style.display      = 'none';
    document.getElementById('connectedCard').style.display = 'none';

    if (data.status === 'connected') {
        document.getElementById('statusCard').style.display   = 'none';
        document.getElementById('connectedCard').style.display = '';
        const p = data.phone ? '+' + data.phone : '';
        document.getElementById('connectedPhone').textContent = p;
        clearInterval(pollInterval);
        return;
    }

    if (data.status === 'qr_pending' && data.qr_image) {
        document.getElementById('statusCard').style.display = 'none';
        document.getElementById('qrCard').style.display     = '';
        document.getElementById('qrImage').src = data.qr_image;
        return;
    }

    if (data.status === 'qr_pending') {
        showStatus('📱', 'Generando QR…', 'El bot está preparando el código QR. Espera un momento…');
        return;
    }

    if (data.status === 'initializing') {
        showStatus('⏳', 'Iniciando bot…', 'El servidor del bot está arrancando. Esto puede tardar unos segundos.');
        return;
    }

    if (data.status === 'disconnected') {
        showStatus('🔴', 'Bot desconectado', 'El bot no está corriendo. Revisa PM2 en el servidor.');
        return;
    }

    showStatus('⏳', 'Conectando…', 'Estado: ' + data.status);
}

function showStatus(icon, title, sub) {
    document.getElementById('statusIcon').textContent  = icon;
    document.getElementById('statusTitle').textContent = title;
    document.getElementById('statusSub').textContent   = sub;
    document.getElementById('statusCard').style.display   = '';
    document.getElementById('qrCard').style.display       = 'none';
    document.getElementById('connectedCard').style.display = 'none';
}

function forceReload() {
    lastStatus = null;
    poll();
}

poll();
pollInterval = setInterval(poll, 3000);
</script>

<?php require __DIR__ . '/_foot.php'; ?>
