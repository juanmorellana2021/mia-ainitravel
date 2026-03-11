<?php
/**
 * mia/views/superadmin/clients.php
 */
$base         = App::basePath();
$pageTitle    = 'Clientes — Superadmin Mia';
$pageTopTitle = 'Clientes';
$activeNav    = 'clients';

$allStatuses = [
    ''          => 'Todos',
    'trial'     => 'Prueba',
    'active'    => 'Activos',
    'expired'   => 'Vencidos',
    'cancelled' => 'Cancelados',
];

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success border-0 mb-3" style="background:rgba(34,197,94,0.1);color:#166534;border-radius:10px;font-size:0.88rem;">
    <i class="bi bi-check-circle me-1"></i> Cliente eliminado correctamente.
</div>
<?php endif; ?>

<!-- Search + filter bar -->
<div class="d-flex gap-2 flex-wrap mb-3 align-items-center">
    <form method="GET" action="" class="d-flex gap-2 flex-wrap flex-grow-1">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
               placeholder="Buscar negocio, email, contacto..."
               class="form-control form-control-sm" style="max-width:280px;border-radius:8px;">
        <?php if ($statusFilter): ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-sm btn-dark" style="border-radius:8px;">Buscar</button>
        <?php if ($search): ?>
            <a href="?<?= $statusFilter ? 'status='.$statusFilter : '' ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">✕ Limpiar</a>
        <?php endif; ?>
    </form>
    <div class="d-flex gap-1 flex-wrap">
        <?php foreach ($allStatuses as $val => $label): ?>
            <a href="?status=<?= $val ?><?= $search ? '&q='.urlencode($search) : '' ?>"
               class="btn btn-sm <?= $statusFilter === $val ? 'btn-dark' : 'btn-outline-secondary' ?>" style="border-radius:8px;">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="sa-table-card">
    <div class="card-header-bar">
        <span><i class="bi bi-buildings me-2 text-muted"></i>Clientes (<?= count($clients) ?>)</span>
    </div>

    <?php if (empty($clients)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
            <p>No se encontraron clientes.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Negocio</th>
                        <th>Email</th>
                        <th>Plan</th>
                        <th>Estado</th>
                        <th>Bot</th>
                        <th>Leads</th>
                        <th>Msgs hoy</th>
                        <th>Registro</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $c): ?>
                    <?php
                    $statusCls = match($c['plan_status']) {
                        'active'    => 'badge-active',
                        'expired'   => 'badge-expired',
                        'cancelled' => 'badge-cancelled',
                        default     => 'badge-trial',
                    };
                    ?>
                    <tr>
                        <td class="text-muted"><?= $c['id'] ?></td>
                        <td>
                            <div class="fw-medium"><?= htmlspecialchars($c['business_name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($c['contact_name']) ?></div>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($c['email']) ?></td>
                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary border" style="font-size:0.78rem"><?= ucfirst($c['plan']) ?></span></td>
                        <td><span class="badge <?= $statusCls ?>"><?= ucfirst($c['plan_status']) ?></span></td>
                        <td>
                            <?php if ($c['bot_wa_status'] === 'connected'): ?>
                                <span style="color:#22c55e;font-weight:600;font-size:0.82rem"><i class="bi bi-circle-fill me-1" style="font-size:0.55rem"></i>Online</span>
                            <?php else: ?>
                                <span style="color:#94a3b8;font-size:0.82rem"><i class="bi bi-circle me-1" style="font-size:0.55rem"></i>Off</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$c['lead_count'] ?></td>
                        <td><?= (int)$c['today_msgs'] ?></td>
                        <td class="text-muted small"><?= date('d/m/y', strtotime($c['created_at'])) ?></td>
                        <td>
                            <a href="<?= $base ?>/superadmin/clients/<?= $c['id'] ?>"
                               class="btn btn-sm" style="background:#6366f1;color:#fff;border-radius:7px;font-size:0.78rem;padding:3px 12px">
                                <i class="bi bi-pencil me-1"></i>Editar
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
