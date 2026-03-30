<?php
/**
 * mia/views/client/lead_detail.php — Single lead + message thread
 */
$base         = App::basePath();
$pageTitle    = 'Lead #' . $lead->id . ' — Mia';
$pageTopTitle = 'Lead: ' . $lead->displayName();
$activeNav    = 'leads';
$pageBackUrl  = $base . '/dashboard/leads';

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<?php if (!empty($_GET['saved'])): ?>
<div class="alert alert-success small py-2 mb-3 border-0" style="background:rgba(37,211,102,0.1);color:#155724">
    <i class="bi bi-check-circle me-1"></i>Lead actualizado correctamente.
</div>
<?php elseif (!empty($_GET['added'])): ?>
<div class="alert alert-success small py-2 mb-3 border-0" style="background:rgba(37,211,102,0.1);color:#155724">
    <i class="bi bi-person-check-fill me-1"></i>Contacto agregado correctamente<?= !empty($_GET['msg_sent']) ? ' y mensaje enviado.' : '.' ?>
</div>
<?php elseif (!empty($_GET['already'])): ?>
<div class="alert alert-info small py-2 mb-3 border-0">
    <i class="bi bi-info-circle me-1"></i>Este número ya existe en tu lista de contactos.
</div>
<?php elseif (!empty($_GET['enrolled'])): ?>
<div class="alert small py-2 mb-3 border-0" style="background:rgba(37,211,102,0.1);color:#155724">
    <i class="bi bi-send-check me-1"></i>Lead inscrito en la secuencia. Los mensajes se enviarán automáticamente.
</div>
<?php elseif (!empty($_GET['unenrolled'])): ?>
<div class="alert alert-secondary small py-2 mb-3 border-0">
    <i class="bi bi-x-circle me-1"></i>Secuencia cancelada para este lead.
</div>
<?php endif; ?>

<div class="row g-3">
    <!-- ── Lead info + update form ─────────────────────────────────────────── -->
    <div class="col-md-4">
        <div class="mc-table-card p-0">
            <div class="card-header-bar"><i class="bi bi-person-circle me-2 text-muted"></i>Información del lead</div>
            <div class="p-4">
                <p class="mb-2">
                    <i class="bi bi-telephone me-2 text-muted"></i>
                    <?php $dp = $lead->displayPhone(); ?>
                    <?php if ($dp !== ''): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $dp) ?>"
                       target="_blank" class="text-reset fw-medium">
                        <?= htmlspecialchars($dp) ?>
                    </a>
                    <?php else: ?>
                    <span class="text-muted fst-italic small">Sin número disponible</span>
                    <?php endif; ?>
                </p>
                <?php $dl = $lead->displayLid(); ?>
                <?php if ($dl !== ''): ?>
                <p class="mb-2">
                    <i class="bi bi-link-45deg me-2 text-muted"></i>
                    <span class="text-muted small" title="WhatsApp internal LID — not a phone number">LID: <?= htmlspecialchars($dl) ?></span>
                </p>
                <?php endif; ?>
                <p class="mb-2">
                    <i class="bi <?= $lead->sourceIcon() ?> me-2 text-muted"></i>
                    <?= ucfirst(htmlspecialchars($lead->source)) ?>
                </p>
                <p class="mb-3">
                    <span class="badge bg-<?= $lead->statusClass() ?> bg-opacity-10 text-<?= $lead->statusClass() ?> border border-<?= $lead->statusClass() ?> border-opacity-25">
                        <?= $lead->statusLabel() ?>
                    </span>
                </p>
                <hr>
                <form method="POST" action="<?= $base ?>/dashboard/leads/<?= $lead->id ?>">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App::csrfToken()) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-medium small">Nombre del contacto</label>
                        <input type="text" name="contact_name" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($lead->contact_name) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium small">Estado</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="new"         <?= $lead->status === 'new'         ? 'selected' : '' ?>>Nuevo</option>
                            <option value="interested"  <?= $lead->status === 'interested'  ? 'selected' : '' ?>>Interesado</option>
                            <option value="demo"        <?= $lead->status === 'demo'        ? 'selected' : '' ?>>Demo</option>
                            <option value="closed_won"  <?= $lead->status === 'closed_won'  ? 'selected' : '' ?>>Cerrado ✓</option>
                            <option value="closed_lost" <?= $lead->status === 'closed_lost' ? 'selected' : '' ?>>Perdido</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium small">Valor estimado (<?= App::CURRENCY ?>)</label>
                        <input type="number" name="value_estimate" class="form-control form-control-sm"
                               step="0.01" min="0"
                               value="<?= htmlspecialchars((string)$lead->value_estimate) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium small">Notas</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="4"><?= htmlspecialchars($lead->notes ?? '') ?></textarea>
                    </div>

                    <!-- ── Tipo de contacto ──────────────────────────────── -->
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Tipo de contacto</label>
                        <?php
                        $ctypes = [
                            'lead'      => ['label' => 'Lead',      'icon' => 'bi-person-check-fill',  'bg' => '#0d6efd', 'txt' => '#fff'],
                            'friend'    => ['label' => 'Amigo/a',   'icon' => 'bi-emoji-smile-fill',   'bg' => '#198754', 'txt' => '#fff'],
                            'staff'     => ['label' => 'Staff',     'icon' => 'bi-person-badge-fill',  'bg' => '#0dcaf0', 'txt' => '#000'],
                            'proveedor' => ['label' => 'Proveedor', 'icon' => 'bi-truck',              'bg' => '#ffc107', 'txt' => '#000'],
                            'ignored'   => ['label' => 'Ignorar',   'icon' => 'bi-slash-circle-fill',  'bg' => '#dc3545', 'txt' => '#fff'],
                        ];
                        $currentCt = $lead->contact_type ?? 'lead';
                        ?>
                        <div class="d-flex flex-wrap gap-2" id="ctypeGroup">
                        <?php foreach ($ctypes as $val => $ct): ?>
                            <?php $isActive = ($currentCt === $val); ?>
                            <label class="ctype-btn d-flex align-items-center gap-1 px-3 py-1 rounded-pill small fw-medium"
                                   style="cursor:pointer;border:2px solid <?= $ct['bg'] ?>;background:<?= $isActive ? $ct['bg'] : '#fff' ?>;color:<?= $isActive ? $ct['txt'] : $ct['bg'] ?>;transition:all .15s"
                                   data-bg="<?= $ct['bg'] ?>" data-txt="<?= $ct['txt'] ?>">
                                <input type="radio" name="contact_type" value="<?= $val ?>" <?= $isActive ? 'checked' : '' ?> hidden>
                                <i class="bi <?= $ct['icon'] ?>"></i><?= $ct['label'] ?>
                            </label>
                        <?php endforeach; ?>
                        </div>
                        <small class="text-muted d-block mt-1">
                            <b>Lead</b> = bot ventas &middot;
                            <b>Amigo/a</b> = chat casual sin ventas &middot;
                            <b>Staff</b> = responde preguntas internas del negocio &middot;
                            <b>Proveedor</b> = asistente profesional &middot;
                            <b>Ignorar</b> = Mia no responde a este número
                        </small>
                        <script>
                        document.querySelectorAll('#ctypeGroup .ctype-btn').forEach(function(lbl) {
                            lbl.addEventListener('click', function() {
                                document.querySelectorAll('#ctypeGroup .ctype-btn').forEach(function(l) {
                                    l.style.background = '#fff';
                                    l.style.color = l.dataset.bg;
                                });
                                this.style.background = this.dataset.bg;
                                this.style.color = this.dataset.txt;
                                this.querySelector('input[type=radio]').checked = true;
                            });
                        });
                        </script>
                    </div>
                    <!-- ─────────────────────────────────────────────────── -->

                    <button type="submit" class="btn btn-sm w-100 fw-medium" style="background:#25d366;color:#fff">
                        <i class="bi bi-save me-1"></i>Guardar cambios
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-3">
            <a href="<?= $base ?>/dashboard/leads" class="btn btn-sm btn-outline-secondary w-100">
                <i class="bi bi-arrow-left me-1"></i>Volver a leads
            </a>
        </div>

        <!-- ── Sequences ─────────────────────────────────────────────────── -->
        <?php if (!empty($sequences)): ?>
        <div class="mc-table-card p-0 mt-3">
            <div class="card-header-bar"><i class="bi bi-send-check me-2 text-muted"></i>Automatizaciones</div>
            <div class="p-3">

                <?php if (!empty($leadEnrollments)): ?>
                <div class="mb-3">
                    <?php foreach ($leadEnrollments as $e): ?>
                    <div class="d-flex align-items-center justify-content-between mb-2 p-2 rounded"
                         style="background:#f8f9fa;font-size:.82rem">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($e['seq_name']) ?></div>
                            <span class="badge text-bg-<?= LeadSequence::fromRow($e)->statusClass() ?>" style="font-size:.68rem">
                                <?= LeadSequence::fromRow($e)->statusLabel() ?>
                            </span>
                            <?php if ($e['status'] === 'active'): ?>
                            <div class="text-muted" style="font-size:.74rem;margin-top:2px">
                                Próximo: <?= date('d M, H:i', strtotime($e['next_fire_at'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($e['status'] === 'active' || $e['status'] === 'paused'): ?>
                        <form method="POST"
                              action="<?= $base ?>/dashboard/sequences/<?= (int)$e['sequence_id'] ?>/unenroll/<?= $lead->id ?>">
                            <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">
                            <button class="btn btn-sm btn-link text-danger p-0" title="Cancelar secuencia"
                                    onclick="return confirm('¿Cancelar esta secuencia para este lead?')">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php
                $enrolledIds = array_column($leadEnrollments, 'sequence_id');
                $available   = array_filter($sequences, fn($s) => !in_array($s->id, $enrolledIds) && $s->status !== 'archived');
                ?>
                <?php if (!empty($available)): ?>
                <form method="POST" action="#" id="enrollForm">
                    <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">
                    <div class="d-flex gap-2">
                        <select name="seq_id" id="enrollSelect" class="form-select form-select-sm" style="font-size:.82rem">
                            <option value="">— elegir secuencia —</option>
                            <?php foreach ($available as $s): ?>
                            <option value="<?= $s->id ?>"><?= htmlspecialchars($s->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm fw-medium" style="background:#25d366;color:#fff;white-space:nowrap">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </form>
                <script>
                document.getElementById('enrollForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const id = document.getElementById('enrollSelect').value;
                    if (!id) return;
                    this.action = <?= json_encode($base) ?> + '/dashboard/sequences/' + id + '/enroll/<?= $lead->id ?>';
                    this.submit();
                });
                </script>
                <?php else: ?>
                <p class="text-muted small mb-0">
                    <?= empty($leadEnrollments) ? 'No hay secuencias disponibles.' : 'Lead ya inscrito en todas las secuencias.' ?>
                </p>
                <?php endif; ?>

            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Message thread ─────────────────────────────────────────────────── -->
    <div class="col-md-8">
        <div class="mc-table-card">
            <div class="card-header-bar">
                <span><i class="bi bi-chat-dots me-2 text-muted"></i>Conversación
                    <span class="badge bg-secondary ms-1"><?= count($messages) ?></span>
                </span>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($messages): ?>
                        <small class="text-muted">Última: <?= date('d/m/y H:i', strtotime(end($messages)->created_at)) ?></small>
                    <?php endif; ?>
                    <?php if ($messages): ?>
                    <button id="translateBtn" type="button"
                            class="btn btn-sm btn-outline-secondary"
                            style="font-size:0.75rem;padding:3px 10px">
                        <i class="bi bi-translate me-1"></i>Traducir
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-3" style="max-height:65vh;overflow-y:auto;background:#f8f9fa;">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-chat fs-1 d-block mb-2 opacity-25"></i>
                        <p class="small">No hay mensajes aún en esta conversación.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $idx => $msg): ?>
                    <div class="d-flex flex-column <?= $msg->direction === 'outbound' ? 'align-items-end' : 'align-items-start' ?> mb-1">
                        <div class="msg-bubble <?= $msg->direction ?> <?= $msg->handled_by === 'human' ? 'human' : '' ?>" data-msgidx="<?= $idx ?>">
                            <?php
                                $safe = nl2br(htmlspecialchars($msg->message));
                                // Render [FOTO:url] markers as inline images
                                $safe = preg_replace(
                                    '/\[FOTO:(https?:\/\/[^\]]+)\]/i',
                                    '<a href="$1" target="_blank"><img src="$1" class="chat-photo mt-1 rounded" style="max-width:200px;max-height:200px;display:block;" alt="foto"></a>',
                                    $safe
                                );
                                echo $safe;
                            ?>
                        </div>
                        <div class="msg-time">
                            <?= date('H:i', strtotime($msg->created_at)) ?>
                            <?php if ($msg->direction === 'outbound'): ?>
                                · <?= $msg->handled_by === 'human' ? 'Tú' : 'Mia' ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const btn = document.getElementById('translateBtn');
    if (!btn) return;

    let translated = false;
    let originals = [];

    btn.addEventListener('click', async function () {
        if (translated) {
            // Restore originals
            document.querySelectorAll('.msg-bubble[data-msgidx]').forEach(function (el) {
                const i = parseInt(el.dataset.msgidx, 10);
                if (originals[i] !== undefined) el.innerHTML = originals[i];
            });
            translated = false;
            btn.innerHTML = '<i class="bi bi-translate me-1"></i>Traducir';
            btn.classList.replace('btn-secondary', 'btn-outline-secondary');
            return;
        }

        // Collect plain texts from each bubble
        const bubbles = document.querySelectorAll('.msg-bubble[data-msgidx]');
        const texts = [];
        originals = [];
        bubbles.forEach(function (el) {
            originals[parseInt(el.dataset.msgidx, 10)] = el.innerHTML;
            texts.push(el.innerText.trim());
        });

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Traduciendo...';

        try {
            const res = await fetch('<?= htmlspecialchars($base) ?>/dashboard/leads/<?= (int)$lead->id ?>/translate', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= App::csrfToken() ?>'},
                body: JSON.stringify({texts: texts, _csrf: '<?= App::csrfToken() ?>'})
            });
            const data = await res.json();
            if (data.translations && data.translations.length) {
                bubbles.forEach(function (el) {
                    const i = parseInt(el.dataset.msgidx, 10);
                    if (data.translations[i] !== undefined) {
                        el.textContent = data.translations[i];
                    }
                });
                translated = true;
                btn.innerHTML = '<i class="bi bi-translate me-1"></i>Ver original';
                btn.classList.replace('btn-outline-secondary', 'btn-secondary');
            } else {
                btn.innerHTML = '<i class="bi bi-translate me-1"></i>Traducir';
            }
        } catch (e) {
            console.error('translate error', e);
            btn.innerHTML = '<i class="bi bi-translate me-1"></i>Traducir';
        }
        btn.disabled = false;
    });
}());
</script>

<?php require __DIR__ . '/_foot.php'; ?>
