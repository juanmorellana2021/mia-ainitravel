<?php
/**
 * mia/views/client/messages.php — WhatsApp message inbox
 */
$base         = App::basePath();
$pageTitle    = 'Mensajes — Mia';
$pageTopTitle = 'Bandeja de Mensajes';
$activeNav    = 'messages';

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Filter tabs -->
<div class="d-flex gap-2 mb-3">
    <a href="<?= $base ?>/dashboard/messages"
       class="btn btn-sm <?= !$filter ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <i class="bi bi-inbox me-1"></i>Todos
    </a>
    <a href="?handled_by=mia"
       class="btn btn-sm <?= $filter === 'mia' ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <i class="bi bi-robot me-1"></i>Respondidos por Mia
    </a>
    <a href="?handled_by=human"
       class="btn btn-sm <?= $filter === 'human' ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <i class="bi bi-person me-1"></i>Respondidos por ti
    </a>
</div>

<div class="mc-table-card">
    <div class="card-header-bar">
        <span><i class="bi bi-chat-dots me-2 text-muted"></i>Mensajes recientes</span>
        <small class="text-muted">Mostrando últimos 30</small>
    </div>

    <?php if (empty($messages)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-chat fs-1 d-block mb-2 opacity-25"></i>
            <p>No hay mensajes <?= $filter ? 'con este filtro' : 'aún' ?>.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="d-none d-md-table-cell">Dirección</th>
                        <th>Teléfono</th>
                        <th class="d-none d-md-table-cell">Contacto</th>
                        <th>Mensaje</th>
                        <th>Respondido por</th>
                        <th class="d-none d-md-table-cell">Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $idx => $msg): ?>
                    <tr>
                        <td class="d-none d-md-table-cell">
                            <?php if ($msg['direction'] === 'inbound'): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                    <i class="bi bi-arrow-down me-1"></i>Entrada
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                    <i class="bi bi-arrow-up me-1"></i>Salida
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($msg['phone']) ?>
                        </td>
                        <td class="text-muted small d-none d-md-table-cell">
                            <?= htmlspecialchars($msg['lead_name'] ?? '—') ?>
                        </td>
                        <td style="max-width:300px">
                            <span class="text-truncate d-block" style="max-width:280px" title="<?= htmlspecialchars($msg['message']) ?>">
                                <?= htmlspecialchars(mb_substr($msg['message'], 0, 80)) ?><?= mb_strlen($msg['message']) > 80 ? '…' : '' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($msg['handled_by'] === 'mia'): ?>
                                <span class="badge" style="background:rgba(37,211,102,0.12);color:#0a5c36;border:1px solid rgba(37,211,102,0.3)">
                                    <i class="bi bi-robot me-1"></i>Mia
                                </span>
                            <?php else: ?>
                                <span class="badge" style="background:rgba(13,110,253,0.1);color:#0a1f6e;border:1px solid rgba(13,110,253,0.2)">
                                    <i class="bi bi-person me-1"></i>Tú
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small d-none d-md-table-cell"><?= date('d/m/y H:i', strtotime($msg['created_at'])) ?></td>
                        <td>
                            <?php if ($msg['lead_id']): ?>
                            <button type="button"
                                    class="btn btn-sm btn-outline-success chat-open-btn"
                                    style="font-size:0.78rem;padding:3px 10px;white-space:nowrap"
                                    data-lead-id="<?= (int)$msg['lead_id'] ?>"
                                    data-lead-name="<?= htmlspecialchars($msg['lead_name'] ?? 'Lead') ?>"
                                    data-lead-phone="<?= htmlspecialchars($msg['phone']) ?>">
                                <i class="bi bi-chat-dots me-1"></i>Responder
                            </button>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary d-md-none row-expand-btn ms-1"
                                    style="font-size:0.78rem;padding:3px 8px"
                                    data-target="msg-detail-<?= $idx ?>"
                                    title="Ver más">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </td>
                    </tr>
                    <tr id="msg-detail-<?= $idx ?>" style="display:none">
                        <td colspan="7" class="py-2 px-3 bg-light border-top-0">
                            <div class="d-flex flex-wrap gap-3 small">
                                <?php if ($msg['direction'] === 'inbound'): ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><i class="bi bi-arrow-down me-1"></i>Entrada</span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="bi bi-arrow-up me-1"></i>Salida</span>
                                <?php endif; ?>
                                <?php if ($msg['lead_name'] ?? null): ?>
                                    <span><i class="bi bi-person me-1 text-muted"></i><?= htmlspecialchars($msg['lead_name']) ?></span>
                                <?php endif; ?>
                                <span class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/y H:i', strtotime($msg['created_at'])) ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ── Chat panel (same as leads page) ────────────────────────────────────── -->
<div id="chatPanel" style="
    position:fixed; top:0; right:0; bottom:0; width:380px;
    background:#fff; box-shadow:-4px 0 24px rgba(0,0,0,0.12);
    display:flex; flex-direction:column; z-index:500;
    transform:translateX(100%); transition:transform .28s ease;">

    <div style="padding:14px 16px; background:#1a1a2e; color:#fff; display:flex; align-items:center; gap:10px; flex-shrink:0;">
        <div style="width:38px;height:38px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
            <i class="bi bi-person-fill"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div id="chatLeadName" style="font-weight:600;font-size:0.95rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
            <div id="chatLeadPhone" style="font-size:0.78rem;opacity:0.7;"></div>
        </div>
        <a id="chatCallBtn" href="#"
           style="background:none;border:none;color:#fff;font-size:1.15rem;cursor:pointer;padding:4px 6px;line-height:1;opacity:0.85;text-decoration:none;"
           target="_blank" rel="noopener"
           title="Llamar por WhatsApp">
            <i class="bi bi-telephone"></i>
        </a>
        <button id="chatCloseBtn" style="background:none;border:none;color:#fff;font-size:1.3rem;cursor:pointer;padding:4px 6px;line-height:1;opacity:0.8;" title="Cerrar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div id="chatStatus" style="font-size:0.75rem;text-align:center;padding:4px 12px;background:#f0f4f8;color:#6c757d;flex-shrink:0;display:none;"></div>

    <div id="chatMessages" style="flex:1;overflow-y:auto;padding:14px 12px;display:flex;flex-direction:column;gap:8px;background:#f0f4f8;"></div>

    <div style="padding:10px 12px;border-top:1px solid #e9ecef;background:#fff;flex-shrink:0;display:flex;gap:8px;align-items:flex-end;">
        <textarea id="chatInput" rows="2"
            placeholder="Escribe un mensaje..."
            style="flex:1;resize:none;border:1px solid #dee2e6;border-radius:10px;padding:8px 12px;font-size:0.88rem;outline:none;font-family:inherit;"></textarea>
        <button id="chatSendBtn"
            style="background:#25d366;border:none;color:#fff;border-radius:10px;padding:9px 14px;font-size:1rem;cursor:pointer;flex-shrink:0;align-self:flex-end;">
            <i class="bi bi-send-fill"></i>
        </button>
    </div>
</div>

<div id="chatOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.3);z-index:499;"></div>

<script>
(function () {
    const CSRF = <?= json_encode(App::csrfToken()) ?>;
    const BASE = <?= json_encode($base) ?>;

    const panel    = document.getElementById('chatPanel');
    const overlay  = document.getElementById('chatOverlay');
    const msgBox   = document.getElementById('chatMessages');
    const input    = document.getElementById('chatInput');
    const sendBtn  = document.getElementById('chatSendBtn');
    const statusEl = document.getElementById('chatStatus');

    let currentLeadId = null;
    let pollTimer     = null;
    let lastMsgId     = 0;

    document.querySelectorAll('.chat-open-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            openChat(this.dataset.leadId, this.dataset.leadName, this.dataset.leadPhone);
        });
    });

    function openChat(id, name, phone) {
        currentLeadId = id;
        lastMsgId     = 0;
        document.getElementById('chatLeadName').textContent  = name;
        document.getElementById('chatLeadPhone').textContent = phone;
        const callBtn = document.getElementById('chatCallBtn');
        if (callBtn) callBtn.href = phone ? 'https://wa.me/' + phone.replace(/\D/g,'') : '#';
        msgBox.innerHTML = '<div style="text-align:center;color:#adb5bd;font-size:0.8rem;padding:20px 0">Cargando...</div>';
        statusEl.style.display = 'none';
        panel.style.transform  = 'translateX(0)';
        overlay.style.display  = 'block';
        document.body.style.overflow = 'hidden';
        loadMessages(true);
        startPolling();
    }

    document.getElementById('chatCloseBtn').addEventListener('click', closeChat);
    overlay.addEventListener('click', closeChat);

    function closeChat() {
        panel.style.transform = 'translateX(100%)';
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        stopPolling();
        currentLeadId = null;
    }

    function loadMessages(scrollToBottom) {
        if (!currentLeadId) return;
        fetch(BASE + '/dashboard/leads/' + currentLeadId + '/messages', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(msgs => {
                if (!Array.isArray(msgs)) return;
                renderMessages(msgs);
                if (scrollToBottom || (msgs.length && msgs[msgs.length - 1].id > lastMsgId)) {
                    msgBox.scrollTop = msgBox.scrollHeight;
                }
                if (msgs.length) lastMsgId = msgs[msgs.length - 1].id;
            })
            .catch(() => {});
    }

    function renderMessages(msgs) {
        if (!msgs.length) {
            msgBox.innerHTML = '<div style="text-align:center;color:#adb5bd;font-size:0.82rem;padding:30px 0">Sin mensajes aún</div>';
            return;
        }
        msgBox.innerHTML = msgs.map(m => {
            const isOut   = m.direction === 'outbound';
            const isHuman = m.handled_by === 'human';
            const bg      = isOut ? (isHuman ? '#25d366' : '#e9ecef') : '#fff';
            const color   = isOut && isHuman ? '#fff' : '#212529';
            const align   = isOut ? 'flex-end' : 'flex-start';
            const time    = m.created_at ? m.created_at.slice(11, 16) : '';
            const label   = isOut && isHuman ? '👤 tú' : (isOut ? '🤖 Mia' : '');
            return `<div style="display:flex;flex-direction:column;align-items:${align};max-width:88%;">
                ${label ? `<span style="font-size:0.68rem;color:#adb5bd;margin-bottom:2px;${isOut?'text-align:right':''}">${label}</span>` : ''}
                <div style="background:${bg};color:${color};border-radius:${isOut?'16px 16px 4px 16px':'16px 16px 16px 4px'};padding:8px 12px;font-size:0.88rem;box-shadow:0 1px 3px rgba(0,0,0,0.07);word-break:break-word;">
                    ${escHtml(m.message)}
                </div>
                <span style="font-size:0.68rem;color:#adb5bd;margin-top:2px;">${time}</span>
            </div>`;
        }).join('');
    }

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    function sendMessage() {
        const text = input.value.trim();
        if (!text || !currentLeadId) return;
        input.value = '';
        sendBtn.disabled = true;
        const fd = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('message', text);
        fetch(BASE + '/dashboard/leads/' + currentLeadId + '/send', {
            method: 'POST', credentials: 'same-origin', body: fd,
        })
        .then(r => r.json())
        .then(data => {
            sendBtn.disabled = false;
            if (data.success) {
                loadMessages(true);
                if (!data.delivered) showStatus('⚠️ Guardado, pero WhatsApp no está conectado.', 'warning');
            } else {
                showStatus('❌ Error: ' + (data.error || 'desconocido'), 'danger');
            }
        })
        .catch(() => { sendBtn.disabled = false; });
    }

    function startPolling() { stopPolling(); pollTimer = setInterval(() => loadMessages(false), 4000); }
    function stopPolling()  { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

    function showStatus(msg, type) {
        statusEl.textContent   = msg;
        statusEl.style.display = 'block';
        statusEl.style.background = type === 'warning' ? '#fff3cd' : '#f8d7da';
        statusEl.style.color      = type === 'warning' ? '#856404' : '#842029';
        setTimeout(() => { statusEl.style.display = 'none'; }, 5000);
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>

<script>
// Expandable detail rows (mobile)
document.querySelectorAll('.row-expand-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var row = document.getElementById(this.dataset.target);
        var icon = this.querySelector('i');
        if (!row) return;
        if (row.style.display === 'none' || row.style.display === ''){
            row.style.display = 'table-row';
            icon.className = 'bi bi-chevron-up';
        } else {
            row.style.display = 'none';
            icon.className = 'bi bi-chevron-down';
        }
    });
});
</script>

<?php require __DIR__ . '/_foot.php'; ?>
