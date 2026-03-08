<?php
/**
 * mia/views/client/lead_detail.php — Single lead + message thread
 */
$base         = App::basePath();
$pageTitle    = 'Lead #' . $lead->id . ' — Mia';
$pageTopTitle = 'Lead: ' . ($lead->contact_name ?: $lead->phone);
$activeNav    = 'leads';

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<?php if (!empty($_GET['saved'])): ?>
<div class="alert alert-success small py-2 mb-3 border-0" style="background:rgba(37,211,102,0.1);color:#155724">
    <i class="bi bi-check-circle me-1"></i>Lead actualizado correctamente.
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
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead->phone) ?>"
                       target="_blank" class="text-reset fw-medium">
                        <?= htmlspecialchars($lead->phone) ?>
                    </a>
                </p>
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
    </div>

    <!-- ── Message thread ─────────────────────────────────────────────────── -->
    <div class="col-md-8">
        <div class="mc-table-card">
            <div class="card-header-bar">
                <span><i class="bi bi-chat-dots me-2 text-muted"></i>Conversación
                    <span class="badge bg-secondary ms-1"><?= count($messages) ?></span>
                </span>
                <?php if ($messages): ?>
                    <small class="text-muted">Última: <?= date('d/m/y H:i', strtotime(end($messages)->created_at)) ?></small>
                <?php endif; ?>
            </div>

            <div class="p-3" style="max-height:65vh;overflow-y:auto;background:#f8f9fa;">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-chat fs-1 d-block mb-2 opacity-25"></i>
                        <p class="small">No hay mensajes aún en esta conversación.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                    <div class="d-flex flex-column <?= $msg->direction === 'outbound' ? 'align-items-end' : 'align-items-start' ?> mb-1">
                        <div class="msg-bubble <?= $msg->direction ?> <?= $msg->handled_by === 'human' ? 'human' : '' ?>">
                            <?= nl2br(htmlspecialchars($msg->message)) ?>
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

<?php require __DIR__ . '/_foot.php'; ?>
