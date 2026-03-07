<?php
/**
 * mia/views/admin/leads.php — Lead management dashboard
 */

$base = App::basePath();
$pageTitle = 'Leads — Mia Admin';

$statusLabels = [
    'new'        => ['Nuevo', 'primary'],
    'contacted'  => ['Contactado', 'info'],
    'demo_done'  => ['Demo Hecha', 'warning'],
    'trial'      => ['En Prueba', 'success'],
    'converted'  => ['Convertido', 'success'],
    'lost'       => ['Perdido', 'secondary'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/mia.css') ?>">
</head>
<body>

<!-- Admin navbar -->
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= $base ?>/admin">
            <i class="bi bi-whatsapp text-success me-2"></i>Mia Admin
        </a>
        <div class="d-flex align-items-center">
            <a href="<?= $base ?>/" class="btn btn-sm btn-outline-light me-2"><i class="bi bi-globe me-1"></i>Sitio</a>
            <a href="<?= $base ?>/admin/logout" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Salir</a>
        </div>
    </div>
</nav>

<div class="container-fluid py-4">

    <!-- Stats cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-primary"><?= (int)($stats['total'] ?? 0) ?></div>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-info"><?= (int)($stats['new_leads'] ?? 0) ?></div>
                    <small class="text-muted">Nuevos</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-warning"><?= (int)($stats['demo_done'] ?? 0) ?></div>
                    <small class="text-muted">Demo</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-success"><?= (int)($stats['on_trial'] ?? 0) ?></div>
                    <small class="text-muted">Prueba</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-success"><?= (int)($stats['converted'] ?? 0) ?></div>
                    <small class="text-muted">Convertidos</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-secondary"><?= (int)($stats['lost'] ?? 0) ?></div>
                    <small class="text-muted">Perdidos</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter pills -->
    <div class="mb-3">
        <a href="<?= $base ?>/admin/leads" class="btn btn-sm <?= $filter === '' ? 'btn-dark' : 'btn-outline-dark' ?> me-1">Todos</a>
        <?php foreach ($statusLabels as $key => [$label, $color]): ?>
            <a href="<?= $base ?>/admin/leads?status=<?= $key ?>" class="btn btn-sm <?= $filter === $key ? "btn-$color" : "btn-outline-$color" ?> me-1"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Leads table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Negocio</th>
                        <th>Contacto</th>
                        <th class="d-none d-md-table-cell">Teléfono</th>
                        <th class="d-none d-lg-table-cell">Tipo</th>
                        <th>Estado</th>
                        <th class="d-none d-md-table-cell">Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($leads)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No hay leads aún. ¡Lanza tu primera campaña de Facebook!</td></tr>
                <?php else: ?>
                    <?php foreach ($leads as $lead): ?>
                        <?php [$sLabel, $sColor] = $statusLabels[$lead->status] ?? ['?', 'secondary']; ?>
                        <tr>
                            <td class="text-muted"><?= $lead->id ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($lead->business_name ?: '-') ?></td>
                            <td><?= htmlspecialchars($lead->contact_name ?: '-') ?></td>
                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($lead->phone) ?></td>
                            <td class="d-none d-lg-table-cell"><span class="badge bg-light text-dark"><?= htmlspecialchars($lead->business_type) ?></span></td>
                            <td><span class="badge bg-<?= $sColor ?>"><?= $sLabel ?></span></td>
                            <td class="d-none d-md-table-cell small text-muted"><?= date('d/m/Y', strtotime($lead->created_at)) ?></td>
                            <td>
                                <a href="<?= $base ?>/admin/leads/<?= $lead->id ?>" class="btn btn-sm btn-outline-dark">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?= App::asset('js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
