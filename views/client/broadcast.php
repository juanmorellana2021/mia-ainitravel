<?php
/**
 * mia/views/client/broadcast.php — Send a message to all captured leads
 */
$base         = App::basePath();
$pageTitle    = 'Difusión — Mia';
$pageTopTitle = 'Difusión a leads';
$activeNav    = 'broadcast';

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Result alerts -->
<?php if (isset($_GET['sent'])): ?>
<div class="alert border-0 rounded-3 mb-4" style="background:rgba(37,211,102,0.12);color:#155724">
    <i class="bi bi-check-circle me-2"></i>
    <strong>¡Envío completado!</strong>
    <?= (int)$_GET['sent'] ?> enviados
    <?php if ((int)($_GET['failed'] ?? 0) > 0): ?>
        &mdash; <span class="text-danger"><?= (int)$_GET['failed'] ?> fallidos</span>
    <?php endif; ?>
</div>
<?php elseif (isset($_GET['error'])): ?>
<div class="alert alert-warning border-0 mb-4">
    <i class="bi bi-exclamation-circle me-2"></i>El mensaje no puede estar vacío.
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- ── Compose ──────────────────────────────────────────────────────────── -->
    <div class="col-md-7">
        <div class="mc-table-card p-4">
            <h6 class="fw-bold mb-1"><i class="bi bi-megaphone me-2" style="color:#25d366"></i>Nuevo envío</h6>
            <p class="text-muted small mb-3">
                Se enviará a <strong><?= count($leads) ?> leads</strong> que tienen número de WhatsApp.
            </p>

            <!-- Warning -->
            <div class="alert border-0 small mb-3" style="background:rgba(255,193,7,0.12);color:#856404">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Límite de 50 leads por envío.</strong>
                WhatsApp puede considerar spam los mensajes masivos no solicitados.
                Usa esto para leads que ya interactuaron con Mia.
            </div>

            <form method="POST" action="<?= $base ?>/dashboard/broadcast/send"
                  onsubmit="return confirmSend(<?= count($leads) ?>)">
                <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Mensaje</label>
                    <textarea name="message" class="form-control" rows="5"
                              placeholder="Ej: Hola! Soy Mia de AiniDesk 👋 Quería saber si tienes alguna pregunta sobre tu prueba gratuita..."
                              maxlength="1000" required></textarea>
                    <div class="form-text text-end"><span id="charCount">0</span> / 1000</div>
                </div>

                <?php if (count($leads) === 0): ?>
                    <div class="alert alert-info border-0 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        No hay leads con número de WhatsApp aún. Cuando Mia capture leads, aparecerán aquí.
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-success px-4"
                        style="background:#25d366;border-color:#25d366"
                        <?= count($leads) === 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-send me-2"></i>Enviar a <?= count($leads) ?> leads
                </button>
            </form>
        </div>
    </div>

    <!-- ── Lead list preview ────────────────────────────────────────────────── -->
    <div class="col-md-5">
        <div class="mc-table-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-people me-2 text-primary"></i>Destinatarios</h6>
            <?php if (empty($leads)): ?>
                <p class="text-muted small">Sin leads aún.</p>
            <?php else: ?>
                <div style="max-height:320px;overflow-y:auto">
                    <table class="table table-sm mb-0" style="font-size:13px">
                        <thead><tr>
                            <th>Nombre</th>
                            <th>WhatsApp</th>
                            <th>Estado</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td><?= htmlspecialchars($lead['contact_name'] ?: '—') ?></td>
                                <td class="text-muted">+<?= htmlspecialchars($lead['phone']) ?></td>
                                <td>
                                    <span class="badge"
                                          style="background:rgba(37,211,102,.15);color:#0a5c36;font-size:11px">
                                        <?= htmlspecialchars($lead['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── History ──────────────────────────────────────────────────────────── -->
    <div class="col-12">
        <div class="mc-table-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-muted"></i>Historial de envíos</h6>
            <?php if (empty($history)): ?>
                <p class="text-muted small">Ningún envío todavía.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" style="font-size:13px">
                        <thead><tr>
                            <th>Fecha</th>
                            <th>Mensaje</th>
                            <th class="text-center">Enviados</th>
                            <th class="text-center">Fallidos</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($history as $log): ?>
                            <tr>
                                <td class="text-muted text-nowrap">
                                    <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                </td>
                                <td style="max-width:320px">
                                    <span class="d-block text-truncate">
                                        <?= htmlspecialchars($log['message']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge" style="background:rgba(37,211,102,.15);color:#0a5c36">
                                        <?= (int)$log['total_sent'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$log['total_failed'] > 0): ?>
                                        <span class="badge bg-danger"><?= (int)$log['total_failed'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
const ta = document.querySelector('textarea[name="message"]');
const cc = document.getElementById('charCount');
if (ta && cc) {
    ta.addEventListener('input', () => cc.textContent = ta.value.length);
}

function confirmSend(count) {
    if (count === 0) return false;
    return confirm('¿Enviar este mensaje a ' + count + ' leads por WhatsApp?');
}
</script>

<?php require __DIR__ . '/_foot.php'; ?>
