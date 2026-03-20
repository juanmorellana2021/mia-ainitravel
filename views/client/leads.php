<?php
/**
 * mia/views/client/leads.php — Lead management list
 */
$base         = App::basePath();
$pageTitle    = 'Leads — Mia';
$pageTopTitle = 'Gestión de Leads';
$activeNav    = 'leads';

$allStatuses = [
    ''            => 'Todos',
    'new'         => 'Nuevo',
    'interested'  => 'Interesado',
    'demo'        => 'Demo',
    'closed_won'  => 'Cerrado ✓',
    'closed_lost' => 'Perdido',
];

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="mc-stat-card text-center">
            <div class="stat-num text-dark"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="mc-stat-card text-center">
            <div class="stat-num text-primary"><?= (int)($stats['new_leads'] ?? 0) ?></div>
            <div class="stat-label">Nuevos</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="mc-stat-card text-center">
            <div class="stat-num text-info"><?= (int)($stats['interested'] ?? 0) ?></div>
            <div class="stat-label">Interesados</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="mc-stat-card text-center">
            <div class="stat-num text-success"><?= (int)($stats['won'] ?? 0) ?></div>
            <div class="stat-label">Ganados</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="mc-stat-card text-center">
            <div class="stat-num text-secondary"><?= (int)($stats['lost'] ?? 0) ?></div>
            <div class="stat-label">Perdidos</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="mc-stat-card text-center">
            <div class="stat-num" style="color:#25d366;font-size:1.3rem">
                <?= App::CURRENCY ?><?= number_format((float)($stats['pipeline_value'] ?? 0), 0) ?>
            </div>
            <div class="stat-label">Pipeline</div>
        </div>
    </div>
</div>

<!-- Filter bar -->
<div class="mc-table-card">
    <div class="card-header-bar flex-wrap gap-2">
        <span><i class="bi bi-people me-2 text-muted"></i>Leads (<?= count($leads) ?>)</span>
        <div class="d-flex gap-1 flex-wrap align-items-center">
            <?php foreach ($allStatuses as $val => $label): ?>
                <a href="?status=<?= $val ?>"
                   class="btn btn-sm <?= $filter === $val ? 'btn-dark' : 'btn-outline-secondary' ?>">
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
            <button type="button" class="btn btn-sm btn-success ms-2"
                    data-bs-toggle="modal" data-bs-target="#addContactModal"
                    style="gap:4px;display:inline-flex;align-items:center">
                <i class="bi bi-person-plus-fill me-1"></i> Agregar Contacto
            </button>
        </div>
    </div>

    <?php if (!empty($_SESSION['lead_add_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3 mb-0" role="alert">
            <?= htmlspecialchars($_SESSION['lead_add_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['lead_add_error']); ?>
    <?php endif; ?>

    <?php if (empty($leads)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
            <p>No hay leads<?= $filter ? ' con este estado' : '' ?>.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="d-none d-md-table-cell">#</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th class="d-none d-md-table-cell">Fuente</th>
                        <th>Estado</th>
                        <th class="d-none d-md-table-cell">Valor estimado</th>
                        <th class="d-none d-md-table-cell">Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td class="text-muted d-none d-md-table-cell">#<?= $lead->id ?></td>
                        <td class="fw-medium"><?= htmlspecialchars($lead->contact_name ?: '—') ?></td>
                        <td>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead->phone) ?>"
                               target="_blank" class="text-decoration-none text-reset">
                                <i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($lead->phone) ?>
                            </a>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <i class="bi <?= $lead->sourceIcon() ?> me-1"></i>
                            <?= ucfirst(htmlspecialchars($lead->source)) ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $lead->statusClass() ?> bg-opacity-10 text-<?= $lead->statusClass() ?> border border-<?= $lead->statusClass() ?> border-opacity-25">
                                <?= $lead->statusLabel() ?>
                            </span>
                        </td>
                        <td class="d-none d-md-table-cell"><?= $lead->value_estimate > 0 ? App::CURRENCY . ' ' . number_format($lead->value_estimate, 0) : '—' ?></td>
                        <td class="text-muted d-none d-md-table-cell"><?= date('d/m/y H:i', strtotime($lead->created_at)) ?></td>
                        <td class="d-flex gap-1">
                            <a href="<?= $base ?>/dashboard/leads/<?= $lead->id ?>"
                               class="btn btn-sm btn-outline-primary" style="font-size:0.78rem;padding:3px 10px">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-outline-success chat-open-btn"
                                    style="font-size:0.78rem;padding:3px 10px"
                                    data-lead-id="<?= $lead->id ?>"
                                    data-lead-name="<?= htmlspecialchars($lead->contact_name ?: 'Sin nombre') ?>"
                                    data-lead-phone="<?= htmlspecialchars($lead->phone) ?>"
                                    title="Chatear">
                                <i class="bi bi-chat-dots"></i>
                            </button>
                            <?php if ($lead->phone): ?>
                            <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/\D/','',$lead->phone)) ?>"
                               class="btn btn-sm btn-outline-secondary"
                               style="font-size:0.78rem;padding:3px 10px"
                               target="_blank" rel="noopener"
                               title="Llamar por WhatsApp +<?= htmlspecialchars($lead->phone) ?>">
                                <i class="bi bi-telephone"></i>
                            </a>
                            <?php endif; ?>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary d-md-none row-expand-btn"
                                    style="font-size:0.78rem;padding:3px 8px"
                                    data-target="lead-detail-<?= $lead->id ?>"
                                    title="Ver más">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </td>
                    </tr>
                    <tr id="lead-detail-<?= $lead->id ?>" style="display:none">
                        <td colspan="8" class="py-2 px-3 bg-light border-top-0">
                            <div class="d-flex flex-wrap gap-3 small">
                                <span class="text-muted"><strong class="text-dark">#<?= $lead->id ?></strong></span>
                                <span><i class="bi <?= $lead->sourceIcon() ?> me-1 text-muted"></i><?= ucfirst(htmlspecialchars($lead->source)) ?></span>
                                <span><strong>Valor:</strong> <?= $lead->value_estimate > 0 ? App::CURRENCY . ' ' . number_format($lead->value_estimate, 0) : '—' ?></span>
                                <span class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/y H:i', strtotime($lead->created_at)) ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ── Chat panel ──────────────────────────────────────────────────────────── -->
<div id="chatPanel" style="
    position:fixed; top:0; right:0; bottom:0; width:380px;
    background:#fff; box-shadow:-4px 0 24px rgba(0,0,0,0.12);
    display:flex; flex-direction:column; z-index:500;
    transform:translateX(100%); transition:transform .28s ease;">

    <!-- Header -->
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

    <!-- Status bar -->
    <div id="chatStatus" style="font-size:0.75rem;text-align:center;padding:4px 12px;background:#f0f4f8;color:#6c757d;flex-shrink:0;display:none;"></div>

    <!-- Messages -->
    <div id="chatMessages" style="flex:1;overflow-y:auto;padding:14px 12px;display:flex;flex-direction:column;gap:8px;background:#f0f4f8;"></div>

    <!-- Input area -->
    <div style="padding:10px 12px;border-top:1px solid #e9ecef;background:#fff;flex-shrink:0;display:flex;gap-8;gap:8px;align-items:flex-end;">
        <textarea id="chatInput" rows="2"
            placeholder="Escribe un mensaje..."
            style="flex:1;resize:none;border:1px solid #dee2e6;border-radius:10px;padding:8px 12px;font-size:0.88rem;outline:none;font-family:inherit;"></textarea>
        <button id="chatSendBtn"
            style="background:#25d366;border:none;color:#fff;border-radius:10px;padding:9px 14px;font-size:1rem;cursor:pointer;flex-shrink:0;align-self:flex-end;">
            <i class="bi bi-send-fill"></i>
        </button>
    </div>
</div>

<!-- Overlay -->
<div id="chatOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.3);z-index:499;"></div>

<script>
(function () {
    const CSRF  = <?= json_encode(App::csrfToken()) ?>;
    const BASE  = <?= json_encode($base) ?>;

    const panel    = document.getElementById('chatPanel');
    const overlay  = document.getElementById('chatOverlay');
    const msgBox   = document.getElementById('chatMessages');
    const input    = document.getElementById('chatInput');
    const sendBtn  = document.getElementById('chatSendBtn');
    const statusEl = document.getElementById('chatStatus');

    let currentLeadId = null;
    let pollTimer     = null;
    let lastMsgId     = 0;

    // ── Open panel ────────────────────────────────────────────────────────────
    document.querySelectorAll('.chat-open-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id    = this.dataset.leadId;
            const name  = this.dataset.leadName;
            const phone = this.dataset.leadPhone;
            openChat(id, name, phone);
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

    // ── Close panel ───────────────────────────────────────────────────────────
    document.getElementById('chatCloseBtn').addEventListener('click', closeChat);
    overlay.addEventListener('click', closeChat);

    function closeChat() {
        panel.style.transform = 'translateX(100%)';
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        stopPolling();
        currentLeadId = null;
    }

    // ── Load messages ─────────────────────────────────────────────────────────
    function loadMessages(scrollToBottom) {
        if (!currentLeadId) return;
        fetch(BASE + '/dashboard/leads/' + currentLeadId + '/messages', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(msgs => {
                if (!Array.isArray(msgs)) return;
                renderMessages(msgs);
                if (scrollToBottom) msgBox.scrollTop = msgBox.scrollHeight;
                else if (msgs.length && msgs[msgs.length - 1].id > lastMsgId) {
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
            const isOut  = m.direction === 'outbound';
            const isHuman = m.handled_by === 'human';
            const bg     = isOut ? (isHuman ? '#25d366' : '#e9ecef') : '#fff';
            const color  = isOut && isHuman ? '#fff' : '#212529';
            const align  = isOut ? 'flex-end' : 'flex-start';
            const time   = m.created_at ? (function(s){ var d = new Date(s.replace(' ','T')+'Z'); return d.toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit',hour12:false,timeZone:'America/Lima'}); })(m.created_at) : '';
            const label  = isOut && isHuman ? '👤 tú' : (isOut ? '🤖 Mia' : '');
            return `<div style="display:flex;flex-direction:column;align-items:${align};max-width:88%;">
                ${label ? `<span style="font-size:0.68rem;color:#adb5bd;margin-bottom:2px;${isOut?'text-align:right':''}">${label}</span>` : ''}
                <div style="background:${bg};color:${color};border-radius:${isOut?'16px 16px 4px 16px':'16px 16px 16px 4px'};padding:8px 12px;font-size:0.88rem;box-shadow:0 1px 3px rgba(0,0,0,0.07);word-break:break-word;">
                    ${renderPhotos(escHtml(m.message))}
                </div>
                <span style="font-size:0.68rem;color:#adb5bd;margin-top:2px;">${time}</span>
            </div>`;
        }).join('');
    }

    function renderPhotos(html) {
        return html.replace(/\[FOTO:(https?:\/\/[^\]]+)\]/gi, function(_, url) {
            return '<a href="' + url + '" target="_blank" rel="noopener"><img src="' + url + '" style="max-width:200px;max-height:200px;display:block;border-radius:8px;margin:4px 0;" alt="foto"></a>';
        });
    }

    // ── Send message ──────────────────────────────────────────────────────────
    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    function sendMessage() {
        const text = input.value.trim();
        if (!text || !currentLeadId) return;
        input.value   = '';
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
                if (!data.delivered) showStatus('⚠️ Guardado, pero WhatsApp no está conectado — el mensaje no fue enviado.', 'warning');
            } else {
                showStatus('❌ Error: ' + (data.error || 'desconocido'), 'danger');
            }
        })
        .catch(() => { sendBtn.disabled = false; });
    }

    // ── Polling ───────────────────────────────────────────────────────────────
    function startPolling() {
        stopPolling();
        pollTimer = setInterval(() => loadMessages(false), 4000);
    }
    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function showStatus(msg, type) {
        statusEl.textContent  = msg;
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

<!-- ── Add Contact Modal ───────────────────────────────────────────────────── -->
<div class="modal fade" id="addContactModal" tabindex="-1" aria-labelledby="addContactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?= $base ?>/dashboard/leads/add">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App::csrfToken()) ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="addContactModalLabel">
                        <i class="bi bi-person-plus-fill text-success me-2"></i>Agregar Contacto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Número de WhatsApp <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-whatsapp text-success"></i></span>
                            <input type="tel" name="phone" class="form-control"
                                   placeholder="Ej: 5491155667788 (con código de país)"
                                   required autocomplete="off">
                        </div>
                        <div class="form-text">Incluye el código de país sin el +. Ejemplo: <strong>5491155667788</strong> para Argentina.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del contacto</label>
                        <input type="text" name="contact_name" class="form-control"
                               placeholder="Nombre (opcional)" maxlength="120" autocomplete="off">
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Mensaje inicial <span class="text-muted fw-normal">(opcional)</span></label>
                        <textarea name="initial_message" class="form-control" rows="3"
                                  placeholder="Escribe el mensaje que Mia enviará al contacto al agregarlo…"
                                  maxlength="1000"></textarea>
                        <div class="form-text">Si lo dejas vacío el contacto se agrega sin mensaje.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-person-check-fill me-1"></i>Agregar<?php if(true): ?> y Enviar<?php endif; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Re-open modal with errors if session flag set on redirect back
<?php if (!empty($_SESSION['lead_add_error'])): ?>
document.addEventListener('DOMContentLoaded', function(){
    var m = document.getElementById('addContactModal');
    if (m) new bootstrap.Modal(m).show();
});
<?php endif; ?>
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
