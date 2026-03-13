<?php
/**
 * mia/views/client/sequences.php — Follow-up Automation list
 */
$base = App::basePath();
require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Alerts -->
<?php if (isset($_GET['saved'])): ?>
<div class="alert border-0 rounded-3 mb-4" style="background:rgba(37,211,102,0.12);color:#155724">
    <i class="bi bi-check-circle me-2"></i><strong>Automatización guardada.</strong>
</div>
<?php elseif (isset($_GET['archived'])): ?>
<div class="alert alert-secondary border-0 mb-4">
    <i class="bi bi-archive me-2"></i>Secuencia archivada.
</div>
<?php endif; ?>

<!-- ── Header ─────────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-0">Automatizaciones de seguimiento</h5>
        <p class="text-muted small mb-0">
            Envía mensajes automáticos a leads en intervalos programados.
            <span class="badge bg-warning text-dark ms-1">Pro</span>
        </p>
    </div>
    <a href="<?= $base ?>/dashboard/sequences/new" class="btn btn-success btn-sm px-3"
       style="background:#25d366;border-color:#25d366">
        <i class="bi bi-plus-lg me-1"></i>Nueva secuencia
    </a>
</div>

<!-- ── Sequence cards ─────────────────────────────────────────────────────── -->
<?php if (empty($sequences)): ?>
<div class="mc-table-card p-5 text-center text-muted mb-4">
    <i class="bi bi-send-check" style="font-size:2.4rem;opacity:.35"></i>
    <p class="mt-3 mb-0">Aún no tienes automatizaciones.<br>
    <a href="<?= $base ?>/dashboard/sequences/new" class="fw-semibold" style="color:#25d366">Crea tu primera secuencia →</a></p>
</div>
<?php else: ?>
<div class="row g-3 mb-4">
<?php foreach ($sequences as $seq): ?>
    <div class="col-md-6 col-lg-4">
        <div class="mc-table-card p-4 h-100 d-flex flex-column">
            <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                    <div class="fw-semibold mb-1"><?= htmlspecialchars($seq->name) ?></div>
                    <span class="badge text-bg-<?= $seq->triggerBadgeClass() ?> me-1" style="font-size:.7rem">
                        <i class="bi bi-lightning me-1"></i><?= $seq->triggerLabel() ?>
                    </span>
                    <?php if ($seq->status === 'archived'): ?>
                    <span class="badge bg-secondary" style="font-size:.7rem">Archivada</span>
                    <?php endif; ?>
                </div>
                <span class="badge bg-light text-dark border" style="font-size:.75rem">
                    <?= (int)($seq->steps === [] ? 0 : count($seq->steps)) ?><?= isset($seq->step_count) ? (int)$seq->step_count : 0 ?> pasos
                </span>
            </div>

            <div class="text-muted small mb-3">
                Creada <?= date('d M Y', strtotime($seq->created_at)) ?>
            </div>

            <div class="mt-auto d-flex gap-2">
                <a href="<?= $base ?>/dashboard/sequences/<?= $seq->id ?>"
                   class="btn btn-sm btn-outline-secondary flex-fill">
                    <i class="bi bi-pencil me-1"></i>Editar
                </a>
                <?php if ($seq->status !== 'archived'): ?>
                <form method="POST"
                      action="<?= $base ?>/dashboard/sequences/<?= $seq->id ?>/archive"
                      onsubmit="return confirm('¿Archivar esta secuencia? Los leads activos se detendrán.')">
                    <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Archivar">
                        <i class="bi bi-archive"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Active enrollments ─────────────────────────────────────────────────── -->
<div class="mc-table-card mb-4">
    <div class="card-header-bar">
        <span><i class="bi bi-person-lines-fill me-2 text-muted"></i>Leads en secuencias activas</span>
        <span class="text-muted small"><?= count($enrollments) ?> enrollment<?= count($enrollments) !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($enrollments)): ?>
    <div class="p-4 text-center text-muted small">
        <i class="bi bi-inbox me-1"></i>Ningún lead está inscrito en una secuencia aún.
        Ve a un lead y usa el botón <strong>Inscribir en secuencia</strong>.
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.88rem">
            <thead class="table-light">
                <tr>
                    <th>Lead</th>
                    <th>Secuencia</th>
                    <th>Próximo envío</th>
                    <th>Paso</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($enrollments as $e): ?>
                <?php
                $isPast = strtotime($e['next_fire_at']) < time();
                $dateLabel = $e['status'] === 'completed'
                    ? '—'
                    : date('d M, H:i', strtotime($e['next_fire_at']));
                ?>
                <tr>
                    <td>
                        <a href="<?= $base ?>/dashboard/leads/<?= (int)$e['lead_id'] ?>"
                           class="fw-semibold text-decoration-none">
                            <?= htmlspecialchars($e['contact_name'] ?: 'Lead #' . $e['lead_id']) ?>
                        </a>
                        <div class="text-muted" style="font-size:.77rem">+<?= htmlspecialchars($e['phone']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($e['seq_name']) ?></td>
                    <td class="<?= $isPast && $e['status'] === 'active' ? 'text-danger fw-semibold' : 'text-muted' ?>">
                        <?= $dateLabel ?>
                    </td>
                    <td class="text-muted">Paso <?= (int)$e['current_step'] + 1 ?></td>
                    <td>
                        <span class="badge text-bg-<?= LeadSequence::fromRow($e)->statusClass() ?>"
                              style="font-size:.72rem">
                            <?= LeadSequence::fromRow($e)->statusLabel() ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($e['status'] === 'active' || $e['status'] === 'paused'): ?>
                        <form method="POST"
                              action="<?= $base ?>/dashboard/sequences/<?= (int)$e['sequence_id'] ?>/unenroll/<?= (int)$e['lead_id'] ?>">
                            <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">
                            <button class="btn btn-sm btn-link text-danger p-0" title="Cancelar"
                                    onclick="return confirm('¿Cancelar esta secuencia para este lead?')">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── How it works ──────────────────────────────────────────────────────── -->
<div class="mc-table-card p-4 mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>¿Cómo funciona?</h6>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="d-flex gap-2">
                <div class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                     style="width:28px;height:28px;min-width:28px;font-size:.8rem">1</div>
                <div>
                    <div class="fw-semibold small">Crea una secuencia</div>
                    <div class="text-muted" style="font-size:.78rem">Define pasos con mensajes y días de espera entre cada uno.</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex gap-2">
                <div class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                     style="width:28px;height:28px;min-width:28px;font-size:.8rem">2</div>
                <div>
                    <div class="fw-semibold small">Inscribe leads</div>
                    <div class="text-muted" style="font-size:.78rem">Manualmente desde el perfil de un lead o automáticamente por trigger.</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex gap-2">
                <div class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                     style="width:28px;height:28px;min-width:28px;font-size:.8rem">3</div>
                <div>
                    <div class="fw-semibold small">Mia envía sola</div>
                    <div class="text-muted" style="font-size:.78rem">El sistema envía cada mensaje a la hora exacta. Si el lead responde, la secuencia se pausa.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_foot.php'; ?>
