<?php
/**
 * mia/views/superadmin/activity.php
 * Client activity tracking — shows login history and usage stats per client.
 */
$base         = App::basePath();
$pageTitle    = 'Actividad de Clientes — Superadmin Mia';
$pageTopTitle = 'Actividad de Clientes';
$activeNav    = 'activity';

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';

// Helper: classify a client row into an activity tier
function activityTier(?string $lastLogin): string {
    if (!$lastLogin) return 'never';
    $days = (int)floor((time() - strtotime($lastLogin)) / 86400);
    if ($days <= 7)  return 'active';
    if ($days <= 30) return 'recent';
    return 'inactive';
}

// KPI counts
$total       = count($clients);
$activeCount  = 0;
$recentCount  = 0;
$neverCount   = 0;
$inactiveCount = 0;
foreach ($clients as $c) {
    $tier = activityTier($c['last_login_at']);
    if ($tier === 'active')   $activeCount++;
    elseif ($tier === 'recent') $recentCount++;
    elseif ($tier === 'never')  $neverCount++;
    else                        $inactiveCount++;
}
?>

<!-- KPI cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:rgba(34,197,94,0.12);border-radius:12px;">
            <div class="card-body py-3 px-3">
                <div class="text-muted small mb-1">Activos (7 días)</div>
                <div class="fw-bold fs-3" style="color:#16a34a;"><?= $activeCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:rgba(234,179,8,0.12);border-radius:12px;">
            <div class="card-body py-3 px-3">
                <div class="text-muted small mb-1">Recientes (8-30 días)</div>
                <div class="fw-bold fs-3" style="color:#ca8a04;"><?= $recentCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:rgba(239,68,68,0.12);border-radius:12px;">
            <div class="card-body py-3 px-3">
                <div class="text-muted small mb-1">Inactivos (+30 días)</div>
                <div class="fw-bold fs-3" style="color:#dc2626;"><?= $inactiveCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 h-100" style="background:rgba(148,163,184,0.12);border-radius:12px;">
            <div class="card-body py-3 px-3">
                <div class="text-muted small mb-1">Nunca ingresaron</div>
                <div class="fw-bold fs-3" style="color:#64748b;"><?= $neverCount ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Activity table -->
<div class="sa-table-card">
    <div class="card-header-bar d-flex justify-content-between align-items-center">
        <span><i class="bi bi-activity me-2 text-muted"></i>Actividad por cliente (<?= $total ?>)</span>
        <span class="small text-muted">
            <span class="badge me-1" style="background:rgba(34,197,94,0.15);color:#16a34a;">● Activo</span>
            <span class="badge me-1" style="background:rgba(234,179,8,0.15);color:#ca8a04;">● Reciente</span>
            <span class="badge me-1" style="background:rgba(239,68,68,0.15);color:#dc2626;">● Inactivo</span>
            <span class="badge" style="background:rgba(148,163,184,0.15);color:#64748b;">● Nunca</span>
        </span>
    </div>

    <?php if (empty($clients)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
            <p>No hay clientes registrados.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table sa-table mb-0">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Plan</th>
                    <th>Último ingreso</th>
                    <th class="text-center">Días inactivo</th>
                    <th class="text-center">Bot WA</th>
                    <th class="text-center">Leads</th>
                    <th class="text-center">Onboarding</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clients as $c):
                $tier       = activityTier($c['last_login_at']);
                $daysAgo    = $c['last_login_at']
                            ? (int)floor((time() - strtotime($c['last_login_at'])) / 86400)
                            : null;

                switch ($tier) {
                    case 'active':   $rowStyle = 'border-left:3px solid #16a34a;'; $tierBg = 'rgba(34,197,94,0.06)';  break;
                    case 'recent':   $rowStyle = 'border-left:3px solid #ca8a04;'; $tierBg = 'rgba(234,179,8,0.06)';  break;
                    case 'inactive': $rowStyle = 'border-left:3px solid #dc2626;'; $tierBg = 'rgba(239,68,68,0.06)';  break;
                    default:         $rowStyle = 'border-left:3px solid #94a3b8;'; $tierBg = 'rgba(148,163,184,0.06)';break;
                }

                $planLabels = ['trial'=>'Prueba','active'=>'Activo','expired'=>'Vencido','cancelled'=>'Cancelado'];
                $planColors = ['trial'=>'#6366f1','active'=>'#16a34a','expired'=>'#dc2626','cancelled'=>'#94a3b8'];
                $planLabel  = $planLabels[$c['plan_status']] ?? ucfirst($c['plan_status']);
                $planColor  = $planColors[$c['plan_status']] ?? '#6366f1';

                $botConnected = ($c['bot_wa_status'] ?? '') === 'connected';
            ?>
                <tr style="background:<?= $tierBg ?>;<?= $rowStyle ?>">
                    <td>
                        <div class="fw-semibold" style="font-size:0.9rem;"><?= htmlspecialchars($c['business_name'] ?? '—') ?></div>
                        <div class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($c['contact_name'] ?? '') ?></div>
                        <div class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($c['email']) ?></div>
                    </td>
                    <td>
                        <span class="badge" style="background:<?= $planColor ?>22;color:<?= $planColor ?>;font-size:0.78rem;">
                            <?= htmlspecialchars($planLabel) ?>
                        </span>
                        <?php if ($c['plan']): ?>
                            <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($c['plan']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($c['last_login_at']): ?>
                            <div style="font-size:0.85rem;"><?= date('d/m/Y H:i', strtotime($c['last_login_at'])) ?></div>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:0.85rem;">Nunca</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($daysAgo === null): ?>
                            <span class="text-muted">—</span>
                        <?php elseif ($daysAgo === 0): ?>
                            <span style="color:#16a34a;font-size:0.85rem;font-weight:600;">Hoy</span>
                        <?php elseif ($daysAgo === 1): ?>
                            <span style="color:#16a34a;font-size:0.85rem;">Ayer</span>
                        <?php else: ?>
                            <span style="font-size:0.85rem;"><?= $daysAgo ?>d</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($botConnected): ?>
                            <i class="bi bi-whatsapp" style="color:#16a34a;font-size:1rem;" title="Conectado"></i>
                        <?php else: ?>
                            <i class="bi bi-whatsapp text-muted opacity-25" style="font-size:1rem;" title="No conectado"></i>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span style="font-size:0.9rem;font-weight:600;"><?= (int)($c['lead_count'] ?? 0) ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($c['onboarding_done']): ?>
                            <i class="bi bi-check-circle-fill" style="color:#16a34a;" title="Completado"></i>
                        <?php else: ?>
                            <i class="bi bi-circle text-muted opacity-40" title="Pendiente"></i>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= $base ?>/superadmin/clients/<?= $c['id'] ?>"
                           class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.78rem;border-radius:6px;">
                            Ver
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
