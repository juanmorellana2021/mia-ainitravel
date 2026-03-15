<?php
/**
 * mia/views/superadmin/prospects.php
 *
 * List of all Mia sales sessions (prospective hotel clients from WhatsApp).
 */
$base         = App::basePath();
$pageTitle    = 'Prospectos — Superadmin Mia';
$pageTopTitle = 'Prospectos';
$activeNav    = 'prospects';

$stateLabels = [
    'new'               => ['Nuevo',           '#6366f1'],
    'intro'             => ['Intro',            '#8b5cf6'],
    'qualifying_size'   => ['Calificando',      '#f59e0b'],
    'qualifying_method' => ['Calificando',      '#f59e0b'],
    'qualifying_pain'   => ['Calificando',      '#f59e0b'],
    'roi_pitch'         => ['Pitch ROI',        '#0ea5e9'],
    'demo'              => ['Demo',             '#0ea5e9'],
    'benefits'          => ['Beneficios',       '#06b6d4'],
    'closing'           => ['Cerrando',         '#f97316'],
    'collecting_name'   => ['Recogiendo datos', '#f97316'],
    'collecting_email'  => ['Recogiendo datos', '#f97316'],
    'captured'          => ['Capturado ✓',      '#22c55e'],
    'human_handoff'     => ['Mano humana',      '#ef4444'],
];

$allStates = array_merge([''], array_keys($stateLabels));

function fmtPhone(string $p): string {
    $p = preg_replace('/@.*$/', '', $p); // strip @c.us
    $p = ltrim($p, '+');
    return strlen($p) > 12 ? '+' . substr($p, 0, 7) . '…' . substr($p, -4) : '+' . $p;
}

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Search + filter bar -->
<div class="d-flex gap-2 flex-wrap mb-3 align-items-center">
    <form method="GET" action="" class="d-flex gap-2 flex-wrap flex-grow-1">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
               class="form-control form-control-sm"
               style="max-width:240px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);background:rgba(30,30,50,0.6);color:#e2e8f0;"
               placeholder="Buscar teléfono, nombre, email…">

        <select name="state" class="form-select form-select-sm"
                style="max-width:200px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);background:rgba(30,30,50,0.6);color:#e2e8f0;">
            <option value="" <?= $stateFilter === '' ? 'selected' : '' ?>>Todos los estados</option>
            <?php foreach ($stateLabels as $key => [$label, $color]): ?>
            <option value="<?= $key ?>" <?= $stateFilter === $key ? 'selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <button class="btn btn-sm btn-primary" type="submit" style="border-radius:8px;">
            <i class="bi bi-search me-1"></i>Buscar
        </button>
        <?php if ($search || $stateFilter): ?>
        <a href="<?= $base ?>/superadmin/prospects" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;border-color:rgba(255,255,255,0.15);color:#94a3b8;">
            <i class="bi bi-x-lg me-1"></i>Limpiar
        </a>
        <?php endif; ?>
    </form>

    <span class="text-muted small ms-auto"><?= count($prospects) ?> prospecto<?= count($prospects) !== 1 ? 's' : '' ?></span>
</div>

<?php if (empty($prospects)): ?>
<div class="text-center py-5" style="color:#64748b;">
    <i class="bi bi-chat-square-text" style="font-size:2.5rem;"></i>
    <p class="mt-3 mb-0">No hay prospectos<?= $search || $stateFilter ? ' que coincidan con la búsqueda' : ' todavía' ?>.</p>
    <?php if ($search || $stateFilter): ?>
    <a href="<?= $base ?>/superadmin/prospects" class="btn btn-sm btn-outline-secondary mt-3" style="border-radius:8px;">Ver todos</a>
    <?php endif; ?>
</div>
<?php else: ?>

<div class="sa-card" style="overflow:hidden;">
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="color:#e2e8f0;font-size:0.85rem;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08);color:#64748b;font-size:0.78rem;text-transform:uppercase;letter-spacing:.04em;">
                    <th class="ps-3 py-2">Teléfono</th>
                    <th class="py-2">Estado</th>
                    <th class="py-2">Negocio</th>
                    <th class="py-2">Contacto</th>
                    <th class="py-2">Email</th>
                    <th class="py-2">Hab.</th>
                    <th class="py-2">Actualizado</th>
                    <th class="pe-3 py-2 text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($prospects as $p): ?>
            <?php
                $state      = $p['state'] ?? 'new';
                [$label, $color] = $stateLabels[$state] ?? [$state, '#94a3b8'];
                $converted  = !empty($p['client_id']);
            ?>
            <tr style="border-bottom:1px solid rgba(255,255,255,0.05);cursor:pointer;"
                onclick="openChat(<?= (int)$p['id'] ?>, '<?= htmlspecialchars(addslashes($p['phone'])) ?>', '<?= htmlspecialchars(addslashes($p['business_name'] ?: '')) ?>')">
                <td class="ps-3 py-2 align-middle">
                    <span style="font-family:monospace;font-size:0.82rem;" title="<?= htmlspecialchars($p['phone']) ?>">
                        <?= htmlspecialchars(fmtPhone($p['phone'])) ?>
                    </span>
                </td>
                <td class="py-2 align-middle">
                    <span style="
                        display:inline-block;padding:2px 8px;border-radius:20px;font-size:0.75rem;font-weight:600;
                        background:<?= $color ?>22;color:<?= $color ?>;border:1px solid <?= $color ?>44;">
                        <?= htmlspecialchars($label) ?>
                    </span>
                    <?php if ($converted): ?>
                    <span style="display:inline-block;padding:2px 7px;border-radius:20px;font-size:0.72rem;font-weight:600;background:#22c55e22;color:#22c55e;border:1px solid #22c55e44;margin-left:4px;">
                        <i class="bi bi-person-check-fill"></i> Cliente
                    </span>
                    <?php endif; ?>
                </td>
                <td class="py-2 align-middle">
                    <?= $p['business_name'] ? htmlspecialchars($p['business_name']) : '<span style="color:#475569">—</span>' ?>
                </td>
                <td class="py-2 align-middle">
                    <?= $p['contact_name'] ? htmlspecialchars($p['contact_name']) : '<span style="color:#475569">—</span>' ?>
                </td>
                <td class="py-2 align-middle">
                    <?= $p['email'] ? htmlspecialchars($p['email']) : '<span style="color:#475569">—</span>' ?>
                </td>
                <td class="py-2 align-middle text-center">
                    <?= $p['room_count'] ? (int)$p['room_count'] : '<span style="color:#475569">—</span>' ?>
                </td>
                <td class="py-2 align-middle" style="color:#64748b;font-size:0.78rem;white-space:nowrap;">
                    <?= date('d M H:i', strtotime($p['updated_at'])) ?>
                </td>
                <td class="pe-3 py-2 align-middle text-end" onclick="event.stopPropagation()">
                    <button onclick="openChat(<?= (int)$p['id'] ?>, '<?= htmlspecialchars(addslashes($p['phone'])) ?>', '<?= htmlspecialchars(addslashes($p['business_name'] ?: '')) ?>')"
                       class="btn btn-sm"
                       style="background:rgba(99,102,241,0.15);color:#818cf8;border:1px solid rgba(99,102,241,0.3);border-radius:7px;padding:2px 10px;font-size:0.78rem;">
                        <i class="bi bi-chat-text me-1"></i>Chat
                    </button>
                    <a href="<?= $base ?>/superadmin/prospects/<?= (int)$p['id'] ?>"
                       class="btn btn-sm ms-1"
                       style="background:rgba(30,40,60,0.6);color:#94a3b8;border:1px solid rgba(255,255,255,0.1);border-radius:7px;padding:2px 10px;font-size:0.78rem;">
                        <i class="bi bi-eye me-1"></i>Ver
                    </a>
                    <?php if ($converted): ?>
                    <a href="<?= $base ?>/superadmin/clients/<?= (int)$p['client_id'] ?>"
                       class="btn btn-sm ms-1"
                       style="background:rgba(34,197,94,0.12);color:#4ade80;border:1px solid rgba(34,197,94,0.3);border-radius:7px;padding:2px 10px;font-size:0.78rem;">
                        <i class="bi bi-person me-1"></i>Cliente
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Conversation slide-in panel -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="chatPanel" style="width:420px;background:#0f1623;border-left:1px solid rgba(255,255,255,0.08);">
    <div class="offcanvas-header" style="border-bottom:1px solid rgba(255,255,255,0.07);padding:14px 18px;">
        <div>
            <div style="font-weight:600;color:#e2e8f0;font-size:0.95rem;" id="chatTitle">Conversación</div>
            <div style="font-size:0.78rem;color:#64748b;" id="chatSubtitle"></div>
        </div>
        <div class="ms-auto d-flex gap-2 align-items-center">
            <a id="chatCallBtn" href="#" target="_blank" title="Llamar por WhatsApp"
               style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:rgba(37,211,102,0.15);color:#25d366;border:1px solid rgba(37,211,102,0.35);font-size:1rem;text-decoration:none;">
                <i class="bi bi-telephone-fill"></i>
            </a>
            <a id="chatDetailLink" href="#" class="btn btn-sm" style="font-size:0.75rem;padding:3px 10px;background:rgba(99,102,241,0.15);color:#818cf8;border:1px solid rgba(99,102,241,0.3);border-radius:7px;">Ver detalle</a>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
    </div>
    <div class="offcanvas-body p-0">
        <div id="chatLoading" style="display:flex;align-items:center;justify-content:center;height:200px;color:#64748b;font-size:0.88rem;">
            <i class="bi bi-arrow-repeat me-2" style="animation:spin 1s linear infinite"></i>Cargando...
        </div>
        <div id="chatMessages" style="display:none;flex-direction:column;gap:8px;padding:16px;overflow-y:auto;height:calc(100vh - 210px);"></div>
        <div id="chatEmpty" style="display:none;padding:40px 20px;text-align:center;color:#475569;font-size:0.85rem;">Sin mensajes registrados aún.</div>
        <!-- Message input footer -->
        <div id="chatInputArea" style="padding:10px 14px 14px;border-top:1px solid rgba(255,255,255,0.07);">
            <div id="chatSendStatus" style="display:none;font-size:0.75rem;padding:5px 10px;border-radius:7px;margin-bottom:7px;"></div>
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <textarea id="chatMsgInput" rows="2"
                    placeholder="Escribe un mensaje para enviar por WhatsApp..."
                    style="flex:1;resize:none;background:#1e293b;border:1px solid rgba(99,102,241,0.3);border-radius:10px;padding:8px 12px;font-size:0.85rem;color:#e2e8f0;outline:none;font-family:inherit;line-height:1.45;"></textarea>
                <button id="chatSendBtn"
                    style="background:linear-gradient(135deg,#059669,#065f46);color:#fff;border:none;border-radius:10px;padding:9px 15px;font-size:1rem;cursor:pointer;flex-shrink:0;align-self:flex-end;box-shadow:0 2px 8px rgba(5,150,105,0.4);"
                    title="Enviar por WhatsApp">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
</style>

<script>
const BASE = '<?= $base ?>';
const CHAT_CSRF = '<?= App::csrfToken() ?>';
let _chatProspectId   = null;
let _chatProspectPhone = null;

function openChat(id, phone, bizName) {
    _chatProspectId    = id;
    _chatProspectPhone = phone;
    document.getElementById('chatLoading').style.display = 'flex';
    document.getElementById('chatMessages').style.display = 'none';
    document.getElementById('chatEmpty').style.display = 'none';
    document.getElementById('chatMessages').innerHTML = '';
    document.getElementById('chatTitle').textContent = bizName || phone;
    document.getElementById('chatSubtitle').textContent = bizName ? phone : '';
    document.getElementById('chatDetailLink').href = BASE + '/superadmin/prospects/' + id;
    // WhatsApp call link — strip non-digits and open wa.me
    const digits = phone.replace(/[^0-9]/g, '');
    document.getElementById('chatCallBtn').href = 'https://wa.me/' + digits;
    // Reset input area
    document.getElementById('chatMsgInput').value = '';
    document.getElementById('chatSendStatus').style.display = 'none';
    bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('chatPanel')).show();
    fetch(BASE + '/superadmin/prospects/' + id + '/chat')
        .then(r => r.json())
        .then(data => {
            document.getElementById('chatLoading').style.display = 'none';
            const history = data.history || [];
            if (!history.length) { document.getElementById('chatEmpty').style.display = 'block'; return; }
            const wrap = document.getElementById('chatMessages');
            wrap.style.display = 'flex';
            history.forEach(msg => {
                const isMia = msg.role === 'assistant';
                const outer = document.createElement('div');
                outer.style.cssText = 'display:flex;justify-content:' + (isMia ? 'flex-start' : 'flex-end');
                const bubble = document.createElement('div');
                bubble.style.cssText = 'max-width:85%;padding:8px 12px;font-size:0.83rem;line-height:1.45;' + (isMia
                    ? 'background:#202c33;color:#e9edef;border:none;border-radius:4px 12px 12px 12px;'
                    : 'background:#005c4b;color:#e9edef;border:none;border-radius:12px 4px 12px 12px;');
                bubble.innerHTML = '<div style="font-size:0.68rem;font-weight:600;margin-bottom:3px;color:' + (isMia ? '#00a884' : '#8fcebd') + '">'  
                    + (isMia ? '<i class="bi bi-robot me-1"></i>Mia' : 'Prospecto') + '</div>'
                    + escapeHtml(msg.content || '').replace(/\n/g, '<br>');
                outer.appendChild(bubble);
                wrap.appendChild(outer);
            });
            wrap.scrollTop = wrap.scrollHeight;
        })
        .catch(() => { document.getElementById('chatLoading').innerHTML = '<span style="color:#f87171">Error al cargar.</span>'; });
}

function escapeHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Send message ─────────────────────────────────────────────────────────────
(function () {
    const sendBtn  = document.getElementById('chatSendBtn');
    const input    = document.getElementById('chatMsgInput');
    const statusEl = document.getElementById('chatSendStatus');

    function showChatStatus(msg, type) {
        statusEl.textContent = msg;
        statusEl.style.display = 'block';
        statusEl.style.background = type === 'warning' ? 'rgba(245,158,11,0.15)'
                                  : type === 'ok'      ? 'rgba(5,150,105,0.15)'
                                  :                      'rgba(239,68,68,0.15)';
        statusEl.style.color = type === 'warning' ? '#fbbf24'
                             : type === 'ok'      ? '#6ee7b7' : '#f87171';
        clearTimeout(statusEl._t);
        statusEl._t = setTimeout(() => { statusEl.style.display = 'none'; }, 4000);
    }

    function sendChatMessage() {
        const text = input.value.trim();
        if (!text || !_chatProspectId) return;
        input.value = '';
        sendBtn.disabled = true;
        const fd = new FormData();
        fd.append('csrf_token', CHAT_CSRF);
        fd.append('message', text);
        fetch(BASE + '/superadmin/prospects/' + _chatProspectId + '/send', {
            method: 'POST', credentials: 'same-origin', body: fd,
        })
        .then(r => r.json())
        .then(data => {
            sendBtn.disabled = false;
            if (data.success) {
                if (!data.delivered) {
                    showChatStatus('⚠️ Guardado, WhatsApp no conectado — no enviado.', 'warning');
                } else {
                    showChatStatus('✓ Enviado', 'ok');
                }
            } else {
                showChatStatus('❌ Error: ' + (data.error || 'desconocido'), 'error');
            }
        })
        .catch(() => { sendBtn.disabled = false; showChatStatus('❌ Error de red.', 'error'); });
    }

    sendBtn.addEventListener('click', sendChatMessage);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChatMessage(); }
    });
})();
</script>

<?php require __DIR__ . '/_foot.php'; ?>
