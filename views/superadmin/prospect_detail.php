<?php
/**
 * mia/views/superadmin/prospect_detail.php
 *
 * Full detail of a single mia_sales_session prospect.
 * Shows captured data, conversation history, and "Convertir en cliente" button.
 */
$base         = App::basePath();
$session      = $data['session'];
$history      = $data['history'];
$pageTitle    = 'Prospecto — Superadmin Mia';
$pageTopTitle = 'Prospecto #' . (int)$session['id'];
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

$state = $session['state'] ?? 'new';
[$stateLabel, $stateColor] = $stateLabels[$state] ?? [$state, '#94a3b8'];
$alreadyClient = !empty($session['client_id']);

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Back link -->
<div class="mb-3">
    <a href="<?= $base ?>/superadmin/prospects"
       style="color:#94a3b8;font-size:0.85rem;text-decoration:none;">
        <i class="bi bi-arrow-left me-1"></i>Volver a Prospectos
    </a>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="alert border-0 mb-3" style="background:rgba(239,68,68,0.1);color:#fca5a5;border-radius:10px;font-size:0.88rem;">
    <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['reset'])): ?>
<div class="alert border-0 mb-3" style="background:rgba(245,158,11,0.1);color:#fcd34d;border:1px solid rgba(245,158,11,0.25);border-radius:10px;font-size:0.88rem;">
    <i class="bi bi-arrow-counterclockwise me-2"></i>
    Estado del bot reiniciado a <strong>Nuevo</strong>. El historial de conversación se ha conservado.
</div>
<?php endif; ?>

<?php if (isset($_GET['converted'], $_GET['tmp'])): ?>
<div class="alert border-0 mb-3" style="background:rgba(0,168,132,0.12);color:#6ee7b7;border:1px solid rgba(0,168,132,0.3);border-radius:10px;font-size:0.88rem;">
    <i class="bi bi-check-circle-fill me-2" style="color:#00a884"></i>
    <strong>Cliente creado.</strong> Contraseña temporal:
    <code style="background:rgba(0,0,0,0.3);padding:2px 8px;border-radius:5px;font-size:0.9rem;letter-spacing:.05em;color:#a7f3d0;margin-left:6px;"><?= htmlspecialchars($_GET['tmp']) ?></code>
    <span style="font-size:0.78rem;color:#64748b;margin-left:8px;">Compártela con el cliente para que inicie sesión.</span>
</div>
<?php endif; ?>

<?php if ($alreadyClient): ?>
<div class="alert border-0 mb-3" style="background:rgba(34,197,94,0.1);color:#86efac;border-radius:10px;font-size:0.88rem;">
    <i class="bi bi-person-check-fill me-1"></i>
    Este prospecto ya fue convertido en cliente.
    <a href="<?= $base ?>/superadmin/clients/<?= (int)$session['client_id'] ?>"
       style="color:#4ade80;font-weight:600;margin-left:6px;">Ver cliente →</a>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">

    <!-- Left column: captured info -->
    <div class="col-md-4">
        <div class="sa-card p-3 h-100">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div style="width:42px;height:42px;border-radius:50%;background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-person-lines-fill" style="color:#818cf8;font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-weight:600;color:#e2e8f0;font-size:0.95rem;">
                        <?= htmlspecialchars($session['contact_name'] ?: 'Sin nombre') ?>
                    </div>
                    <div style="font-size:0.78rem;color:#64748b;font-family:monospace;">
                        <?= htmlspecialchars($session['phone']) ?>
                    </div>
                </div>
                <span class="ms-auto" style="
                    padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;
                    background:<?= $stateColor ?>22;color:<?= $stateColor ?>;border:1px solid <?= $stateColor ?>44;">
                    <?= htmlspecialchars($stateLabel) ?>
                </span>
            </div>

            <div style="display:grid;gap:10px;">
                <?php
                $fields = [
                    ['bi-building',       'Negocio',        $session['business_name']],
                    ['bi-envelope',       'Email',          $session['email']],
                    ['bi-tags',           'Tipo',           $session['business_type']],
                    ['bi-door-open',      'Habitaciones',   $session['room_count'] ? (int)$session['room_count'] . ' hab.' : null],
                    ['bi-credit-card',    'Método actual',  $session['current_method']],
                    ['bi-emoji-frown',    'Dolor principal',$session['pain_point']],
                ];
                foreach ($fields as [$icon, $label, $value]):
                    if ($value === null || $value === '') continue;
                ?>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi <?= $icon ?>" style="color:#475569;margin-top:2px;font-size:0.9rem;min-width:16px;"></i>
                    <div>
                        <div style="font-size:0.72rem;color:#475569;text-transform:uppercase;letter-spacing:.04em;"><?= $label ?></div>
                        <div style="color:#cbd5e1;font-size:0.85rem;"><?= htmlspecialchars((string)$value) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-calendar3" style="color:#475569;margin-top:2px;font-size:0.9rem;min-width:16px;"></i>
                    <div>
                        <div style="font-size:0.72rem;color:#475569;text-transform:uppercase;letter-spacing:.04em;">Creado</div>
                        <div style="color:#94a3b8;font-size:0.83rem;">
                            <?= date('d M Y H:i', strtotime($session['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-clock-history" style="color:#475569;margin-top:2px;font-size:0.9rem;min-width:16px;"></i>
                    <div>
                        <div style="font-size:0.72rem;color:#475569;text-transform:uppercase;letter-spacing:.04em;">Última actividad</div>
                        <div style="color:#94a3b8;font-size:0.83rem;">
                            <?= date('d M Y H:i', strtotime($session['updated_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right column: convert button + conversation -->
    <div class="col-md-8">

        <!-- Convert to client card -->
        <div class="sa-card p-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <div style="font-weight:600;color:#e2e8f0;margin-bottom:3px;">
                        <?php if ($alreadyClient): ?>
                        <i class="bi bi-person-check-fill me-1" style="color:#4ade80;"></i>
                        Ya es cliente de Mia
                        <?php else: ?>
                        <i class="bi bi-person-plus-fill me-1" style="color:#818cf8;"></i>
                        Convertir en cliente
                        <?php endif; ?>
                    </div>
                    <div style="font-size:0.82rem;color:#64748b;">
                        <?php if ($alreadyClient): ?>
                        Se creó una cuenta con los datos capturados por Mia en WhatsApp.
                        <?php else: ?>
                        Crea una cuenta de cliente con los datos capturados por Mia.
                        Se generará una contraseña temporal que podrás compartir.
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ms-auto">
                    <?php if ($alreadyClient): ?>
                    <a href="<?= $base ?>/superadmin/clients/<?= (int)$session['client_id'] ?>"
                       class="btn"
                       style="background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.35);border-radius:9px;padding:8px 18px;font-size:0.85rem;font-weight:600;white-space:nowrap;">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Ver cliente
                    </a>
                    <?php else: ?>
                    <form method="POST" action="<?= $base ?>/superadmin/prospects/<?= (int)$session['id'] ?>/convert"
                          onsubmit="return confirm('¿Crear cuenta de cliente para <?= htmlspecialchars(addslashes($session['phone'])) ?>?\n\nSe generará una contraseña temporal.');">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App::csrfToken()) ?>">
                        <button type="submit" class="btn"
                                style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;border-radius:9px;padding:8px 18px;font-size:0.85rem;font-weight:600;white-space:nowrap;box-shadow:0 2px 12px rgba(99,102,241,0.4);">
                            <i class="bi bi-person-plus-fill me-1"></i>Convertir en cliente
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reset state card -->
        <?php if (!$alreadyClient && $state !== 'new'): ?>
        <div class="sa-card p-3 mb-3" style="border:1px solid rgba(245,158,11,0.2);">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <div style="font-weight:600;color:#e2e8f0;margin-bottom:3px;">
                        <i class="bi bi-arrow-counterclockwise me-1" style="color:#f59e0b;"></i>
                        Reiniciar estado del bot
                    </div>
                    <div style="font-size:0.82rem;color:#64748b;">
                        Vuelve el estado a <strong style="color:#94a3b8">Nuevo</strong> sin borrar el historial de mensajes.
                        El próximo mensaje del prospecto reinicia el flujo de Mia.
                    </div>
                </div>
                <div class="ms-auto">
                    <form method="POST" action="<?= $base ?>/superadmin/prospects/<?= (int)$session['id'] ?>/reset-state"
                          onsubmit="return confirm('¿Reiniciar el estado del bot para este prospecto?\n\nSe conserva el historial. El flujo de Mia empezará de nuevo cuando escriba.');">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App::csrfToken()) ?>">
                        <button type="submit" class="btn"
                                style="background:rgba(245,158,11,0.15);color:#fbbf24;border:1px solid rgba(245,158,11,0.35);border-radius:9px;padding:8px 18px;font-size:0.85rem;font-weight:600;white-space:nowrap;">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reiniciar estado
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Conversation history -->
        <div class="sa-card p-3">
            <div style="font-size:0.8rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                <i class="bi bi-chat-text me-1"></i>
                Conversación
                <span id="msgCount" style="font-weight:400;color:#475569;">(<?= count($history) ?> mensajes)</span>
            </div>

            <!-- Message thread -->
            <div id="msgThread" style="display:flex;flex-direction:column;gap:8px;max-height:420px;overflow-y:auto;padding-right:4px;margin-bottom:12px;">
                <?php if (empty($history)): ?>
                <div style="color:#475569;font-size:0.85rem;text-align:center;padding:20px 0;">
                    Sin mensajes registrados.
                </div>
                <?php else: ?>
                <?php foreach ($history as $msg):
                    $role    = $msg['role'] ?? 'user';
                    $content = $msg['content'] ?? '';
                    $isMia   = ($role === 'assistant');
                    $isHuman = ($role === 'human_agent');
                    $isRight = ($isMia || $isHuman);
                ?>
                <div style="display:flex;justify-content:<?= $isRight ? 'flex-end' : 'flex-start' ?>;">
                    <div style="
                        max-width:80%;padding:8px 12px;border-radius:<?= $isRight ? '12px 4px 12px 12px' : '4px 12px 12px 12px' ?>;
                        font-size:0.83rem;line-height:1.45;
                        background:<?= $isHuman ? '#1a4731' : ($isMia ? '#202c33' : '#1e293b') ?>;
                        color:#e9edef;
                        border:<?= $isHuman ? '1px solid rgba(0,168,132,0.4)' : 'none' ?>;
                    ">
                        <?php if ($isMia): ?>
                        <div style="font-size:0.7rem;color:#00a884;font-weight:600;margin-bottom:3px;">
                            <i class="bi bi-robot me-1"></i>Mia
                        </div>
                        <?php elseif ($isHuman): ?>
                        <div style="font-size:0.7rem;color:#6ee7b7;font-weight:600;margin-bottom:3px;text-align:right;">
                            <i class="bi bi-person-badge me-1"></i>Tú (agente)
                        </div>
                        <?php else: ?>
                        <div style="font-size:0.7rem;color:#8fcebd;font-weight:600;margin-bottom:3px;">
                            Prospecto
                        </div>
                        <?php endif; ?>
                        <?= nl2br(htmlspecialchars($content)) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Send message input -->
            <div id="sendStatus" style="display:none;font-size:0.78rem;padding:6px 10px;border-radius:7px;margin-bottom:8px;"></div>
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <textarea id="msgInput" rows="2"
                    placeholder="Escribe un mensaje para enviar por WhatsApp..."
                    style="flex:1;resize:none;background:#1e293b;border:1px solid rgba(99,102,241,0.3);border-radius:10px;padding:9px 12px;font-size:0.88rem;color:#e2e8f0;outline:none;font-family:inherit;line-height:1.45;"></textarea>
                <button id="msgSendBtn"
                    style="background:linear-gradient(135deg,#059669,#065f46);color:#fff;border:none;border-radius:10px;padding:10px 16px;font-size:1rem;cursor:pointer;flex-shrink:0;align-self:flex-end;box-shadow:0 2px 10px rgba(5,150,105,0.4);"
                    title="Enviar mensaje por WhatsApp">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
            <div style="font-size:0.72rem;color:#334155;margin-top:5px;">
                <i class="bi bi-info-circle me-1"></i>
                El mensaje se enviará por WhatsApp desde el número de Mia.
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    const CSRF        = <?= json_encode(App::csrfToken()) ?>;
    const BASE        = <?= json_encode($base) ?>;
    const PROSPECT_ID = <?= (int)$session['id'] ?>;

    const thread    = document.getElementById('msgThread');
    const input     = document.getElementById('msgInput');
    const sendBtn   = document.getElementById('msgSendBtn');
    const statusEl  = document.getElementById('sendStatus');
    const countEl   = document.getElementById('msgCount');

    let pollTimer = null;

    // ── Render messages ────────────────────────────────────────────────────────
    function renderMessages(msgs) {
        if (!Array.isArray(msgs) || msgs.length === 0) {
            thread.innerHTML = '<div style="color:#475569;font-size:0.85rem;text-align:center;padding:20px 0;">Sin mensajes registrados.</div>';
            if (countEl) countEl.textContent = '(0 mensajes)';
            return;
        }
        if (countEl) countEl.textContent = '(' + msgs.length + ' mensajes)';
        thread.innerHTML = msgs.map(function (m) {
            const role    = m.role || 'user';
            const content = (m.content || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
            const isMia   = role === 'assistant';
            const isHuman = role === 'human_agent';
            const isRight = isMia || isHuman;
            const justify = isRight ? 'flex-end' : 'flex-start';
            const bg      = isHuman ? '#1a4731' : (isMia ? '#202c33' : '#1e293b');
            const border  = isHuman ? '1px solid rgba(0,168,132,0.4)' : 'none';
            const radius  = isRight ? '12px 4px 12px 12px' : '4px 12px 12px 12px';
            let label = '';
            if (isMia)   label = '<div style="font-size:0.7rem;color:#00a884;font-weight:600;margin-bottom:3px;"><i class="bi bi-robot"></i> Mia</div>';
            else if (isHuman) label = '<div style="font-size:0.7rem;color:#6ee7b7;font-weight:600;margin-bottom:3px;text-align:right;"><i class="bi bi-person-badge"></i> Tú (agente)</div>';
            else label = '<div style="font-size:0.7rem;color:#8fcebd;font-weight:600;margin-bottom:3px;">Prospecto</div>';
            return '<div style="display:flex;justify-content:' + justify + ';">' +
                '<div style="max-width:80%;padding:8px 12px;border-radius:' + radius + ';font-size:0.83rem;line-height:1.45;background:' + bg + ';color:#e9edef;border:' + border + ';">' +
                label + content + '</div></div>';
        }).join('');
        thread.scrollTop = thread.scrollHeight;
    }

    // ── Load / poll messages ───────────────────────────────────────────────────
    function loadMessages() {
        fetch(BASE + '/superadmin/prospects/' + PROSPECT_ID + '/messages', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(msgs) { renderMessages(msgs); })
            .catch(function() {});
    }

    function startPolling() {
        stopPolling();
        pollTimer = setInterval(loadMessages, 5000);
    }
    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    startPolling();

    // ── Send message ───────────────────────────────────────────────────────────
    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    function sendMessage() {
        const text = input.value.trim();
        if (!text) return;
        input.value      = '';
        sendBtn.disabled = true;

        const fd = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('message', text);

        fetch(BASE + '/superadmin/prospects/' + PROSPECT_ID + '/send', {
            method: 'POST', credentials: 'same-origin', body: fd,
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            sendBtn.disabled = false;
            if (data.success) {
                loadMessages();
                if (!data.delivered) {
                    showStatus('⚠️ Mensaje guardado, pero WhatsApp no está conectado — no fue enviado.', 'warning');
                } else {
                    showStatus('✓ Enviado', 'ok');
                }
            } else {
                showStatus('❌ Error: ' + (data.error || 'desconocido'), 'error');
            }
        })
        .catch(function() {
            sendBtn.disabled = false;
            showStatus('❌ Error de red.', 'error');
        });
    }

    function showStatus(msg, type) {
        statusEl.textContent    = msg;
        statusEl.style.display  = 'block';
        statusEl.style.background = type === 'warning' ? 'rgba(245,158,11,0.15)' :
                                    type === 'ok'      ? 'rgba(5,150,105,0.15)'   :
                                                         'rgba(239,68,68,0.15)';
        statusEl.style.color = type === 'warning' ? '#fbbf24' :
                               type === 'ok'      ? '#6ee7b7'  : '#f87171';
        clearTimeout(statusEl._t);
        statusEl._t = setTimeout(function() { statusEl.style.display = 'none'; }, 4000);
    }

    // scroll thread to bottom on load
    thread.scrollTop = thread.scrollHeight;
})();
</script>

<?php require __DIR__ . '/_foot.php'; ?>
