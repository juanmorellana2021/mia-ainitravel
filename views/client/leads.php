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
        <div class="d-flex gap-1 flex-wrap">
            <?php foreach ($allStatuses as $val => $label): ?>
                <a href="?status=<?= $val ?>"
                   class="btn btn-sm <?= $filter === $val ? 'btn-dark' : 'btn-outline-secondary' ?>">
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

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
                        <th>#</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Fuente</th>
                        <th>Estado</th>
                        <th>Valor estimado</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td class="text-muted">#<?= $lead->id ?></td>
                        <td class="fw-medium"><?= htmlspecialchars($lead->contact_name ?: '—') ?></td>
                        <td>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead->phone) ?>"
                               target="_blank" class="text-decoration-none text-reset">
                                <i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($lead->phone) ?>
                            </a>
                        </td>
                        <td>
                            <i class="bi <?= $lead->sourceIcon() ?> me-1"></i>
                            <?= ucfirst(htmlspecialchars($lead->source)) ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $lead->statusClass() ?> bg-opacity-10 text-<?= $lead->statusClass() ?> border border-<?= $lead->statusClass() ?> border-opacity-25">
                                <?= $lead->statusLabel() ?>
                            </span>
                        </td>
                        <td><?= $lead->value_estimate > 0 ? App::CURRENCY . ' ' . number_format($lead->value_estimate, 0) : '—' ?></td>
                        <td class="text-muted"><?= date('d/m/y H:i', strtotime($lead->created_at)) ?></td>
                        <td>
                            <a href="<?= $base ?>/dashboard/leads/<?= $lead->id ?>"
                               class="btn btn-sm btn-outline-primary" style="font-size:0.78rem;padding:3px 10px">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_foot.php'; ?>
