<?php
/**
 * mia/views/superadmin/dashboard.php
 */
$base         = App::basePath();
$pageTitle    = 'Dashboard — Superadmin Mia';
$pageTopTitle = 'Dashboard';
$activeNav    = 'dashboard';

$c         = $stats['clients'];
$mrr       = number_format($stats['mrr_cents'] / 100, 0);
$arr       = number_format($stats['mrr_cents'] / 100 * 12, 0);

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- KPI row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="sa-kpi-card">
            <div class="kpi-label"><i class="bi bi-people me-1"></i>Total clientes</div>
            <div class="kpi-value"><?= (int)$c['total'] ?></div>
            <div class="kpi-sub"><?= $stats['new_this_week'] ?> nuevos esta semana</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sa-kpi-card">
            <div class="kpi-label"><i class="bi bi-currency-dollar me-1"></i>MRR</div>
            <div class="kpi-value" style="color:#6366f1">S/ <?= $mrr ?></div>
            <div class="kpi-sub">ARR estimado: S/ <?= $arr ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sa-kpi-card">
            <div class="kpi-label"><i class="bi bi-chat-dots me-1"></i>Mensajes hoy</div>
            <div class="kpi-value"><?= $stats['today_messages'] ?></div>
            <div class="kpi-sub"><?= $stats['total_leads'] ?> leads totales en el sistema</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sa-kpi-card">
            <div class="kpi-label"><i class="bi bi-whatsapp me-1"></i>Bots conectados</div>
            <div class="kpi-value" style="color:#22c55e"><?= $stats['bot_connections'] ?></div>
            <div class="kpi-sub">de <?= (int)$c['total'] ?> clientes totales</div>
        </div>
    </div>
</div>

<!-- Plan breakdown + recent signups -->
<div class="row g-3 mb-4">
    <!-- Plan breakdown -->
    <div class="col-md-4">
        <div class="sa-detail-card h-100">
            <h6><i class="bi bi-pie-chart me-1"></i>Estado de planes</h6>
            <div class="d-flex flex-column gap-2">
                <?php
                $breakdown = [
                    ['label' => 'Prueba activa',  'val' => (int)$c['trials'],    'cls' => 'badge-trial'],
                    ['label' => 'Activos',         'val' => (int)$c['active'],    'cls' => 'badge-active'],
                    ['label' => 'Vencidos',        'val' => (int)$c['expired'],   'cls' => 'badge-expired'],
                    ['label' => 'Cancelados',      'val' => (int)$c['cancelled'], 'cls' => 'badge-cancelled'],
                ];
                foreach ($breakdown as $b): ?>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="badge <?= $b['cls'] ?>" style="font-size:0.8rem;padding:5px 10px;"><?= $b['label'] ?></span>
                    <span class="fw-bold"><?= $b['val'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent signups -->
    <div class="col-md-8">
        <div class="sa-table-card">
            <div class="card-header-bar">
                <span><i class="bi bi-person-plus me-2 text-muted"></i>Últimos registros</span>
                <a href="<?= $base ?>/superadmin/clients" class="btn btn-sm btn-outline-secondary" style="font-size:0.78rem">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Negocio</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Estado</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSignups as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= $base ?>/superadmin/clients/<?= $r['id'] ?>" class="text-decoration-none fw-medium text-dark">
                                    <?= htmlspecialchars($r['business_name']) ?>
                                </a>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($r['email']) ?></td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary border"><?= ucfirst($r['plan']) ?></span></td>
                            <td>
                                <?php
                                $statusCls = match($r['plan_status']) {
                                    'active'    => 'badge-active',
                                    'expired'   => 'badge-expired',
                                    'cancelled' => 'badge-cancelled',
                                    default     => 'badge-trial',
                                };
                                ?>
                                <span class="badge <?= $statusCls ?>"><?= ucfirst($r['plan_status']) ?></span>
                            </td>
                            <td class="text-muted small"><?= date('d/m/y', strtotime($r['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Mia leads feed -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="sa-table-card">
            <div class="card-header-bar">
                <span><i class="bi bi-whatsapp me-2" style="color:#22c55e"></i>Prospectos de Mia — actividad reciente</span>
                <a href="<?= $base ?>/superadmin/prospects" class="btn btn-sm btn-outline-secondary" style="font-size:0.78rem">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Teléfono</th>
                            <th>Negocio</th>
                            <th>Tipo</th>
                            <th>Etapa</th>
                            <th>Última actividad</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentProspects)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4" style="font-size:0.88rem">Aún no hay prospectos. Serán visibles aquí cuando alguien escriba a Mia.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($recentProspects as $p):
                            $stateCls = match($p['state']) {
                                'captured'          => 'badge-active',
                                'closing','demo',
                                'roi_pitch','benefits' => 'badge-trial',
                                'new','intro'       => 'bg-secondary bg-opacity-10 text-secondary border',
                                default             => 'bg-secondary bg-opacity-10 text-secondary border',
                            };
                            $stateLabel = match($p['state']) {
                                'new'               => 'Nuevo',
                                'intro'             => 'Intro',
                                'qualifying_size'   => 'Calificando',
                                'qualifying_method' => 'Método',
                                'qualifying_pain'   => 'Dolor',
                                'roi_pitch'         => 'ROI pitch',
                                'demo'              => 'Demo',
                                'benefits'          => 'Beneficios',
                                'closing'           => 'Cierre',
                                'collecting_name'   => 'Datos',
                                'collecting_email'  => 'Email',
                                'captured'          => '✅ Capturado',
                                default             => ucfirst($p['state']),
                            };
                            $ago = (time() - strtotime($p['updated_at']));
                            $agoStr = $ago < 60 ? 'hace un momento'
                                    : ($ago < 3600 ? 'hace ' . (int)($ago/60) . ' min'
                                    : ($ago < 86400 ? 'hace ' . (int)($ago/3600) . ' h'
                                    : date('d/m/y H:i', strtotime($p['updated_at']))));
                        ?>
                        <tr>
                            <td class="text-muted small font-monospace"><?= htmlspecialchars($p['phone']) ?></td>
                            <td>
                                <?php if ($p['business_name']): ?>
                                    <span class="fw-medium"><?= htmlspecialchars($p['business_name']) ?></span>
                                    <?php if ($p['contact_name']): ?>
                                        <br><span class="text-muted small"><?= htmlspecialchars($p['contact_name']) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars(ucfirst($p['business_type'] ?? '—')) ?></td>
                            <td><span class="badge <?= $stateCls ?>" style="font-size:0.75rem"><?= $stateLabel ?></span></td>
                            <td class="text-muted small"><?= $agoStr ?></td>
                            <td>
                                <?php if ($p['client_id']): ?>
                                    <a href="<?= $base ?>/superadmin/clients/<?= $p['client_id'] ?>" class="btn btn-xs btn-outline-success" style="font-size:0.72rem;padding:2px 8px">Cliente</a>
                                <?php else: ?>
                                    <a href="<?= $base ?>/superadmin/prospects/<?= $p['id'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:0.72rem;padding:2px 8px">Ver</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_foot.php'; ?>
