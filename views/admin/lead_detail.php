<?php
/**
 * mia/views/admin/lead_detail.php — Single lead detail + status update
 */

$base = App::basePath();
$pageTitle = htmlspecialchars($lead->business_name ?: 'Lead #' . $lead->id) . ' — Mia Admin';

$statusLabels = [
    'new'        => ['Nuevo', 'primary'],
    'contacted'  => ['Contactado', 'info'],
    'demo_done'  => ['Demo Hecha', 'warning'],
    'trial'      => ['En Prueba', 'success'],
    'converted'  => ['Convertido', 'success'],
    'lost'       => ['Perdido', 'secondary'],
];
[$sLabel, $sColor] = $statusLabels[$lead->status] ?? ['?', 'secondary'];

$painLabels = [
    'after_hours'      => 'Pierde reservas de noche',
    'slow_replies'     => 'Tarda mucho en responder',
    'no_confirm'       => 'Clientes no confirman',
    'high_commissions' => 'Comisiones altas de OTAs',
    'general'          => 'General',
];

$methodLabels = [
    'manual'   => 'Llamadas / WhatsApp manual',
    'web_form' => 'Formulario web',
    'otas'     => 'OTAs (Booking, Airbnb...)',
    'mixed'    => 'Mezcla de todo',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap-icons.min.css') ?>">
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= $base ?>/admin">
            <i class="bi bi-whatsapp text-success me-2"></i>Mia Admin
        </a>
        <a href="<?= $base ?>/admin/leads" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>
</nav>

<div class="container py-4">
    <div class="row g-4">
        <!-- Lead info -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><?= htmlspecialchars($lead->business_name ?: 'Sin nombre') ?></h5>
                    <span class="badge bg-<?= $sColor ?> fs-6"><?= $sLabel ?></span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label text-muted small">Contacto</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($lead->contact_name ?: '-') ?></p>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-muted small">Teléfono</label>
                            <p class="fw-bold mb-0">
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead->phone) ?>" class="text-success text-decoration-none" target="_blank">
                                    <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($lead->phone) ?>
                                </a>
                            </p>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-muted small">Email</label>
                            <p class="mb-0"><?= htmlspecialchars($lead->email ?: '-') ?></p>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-muted small">Tipo de negocio</label>
                            <p class="mb-0"><?= htmlspecialchars(ucfirst($lead->business_type)) ?></p>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-muted small">Habitaciones</label>
                            <p class="mb-0"><?= (int)$lead->room_count ?: '-' ?></p>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-muted small">Método actual</label>
                            <p class="mb-0"><?= htmlspecialchars($methodLabels[$lead->current_method] ?? $lead->current_method) ?></p>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-muted small">Pain point</label>
                            <p class="mb-0"><?= htmlspecialchars($painLabels[$lead->pain_point] ?? $lead->pain_point) ?></p>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-muted small">Fuente</label>
                            <p class="mb-0"><span class="badge bg-light text-dark"><?= htmlspecialchars($lead->source) ?></span></p>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small">Registrado</label>
                            <p class="mb-0"><?= date('d/m/Y H:i', strtotime($lead->created_at)) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <?php if (!empty($lead->notes)): ?>
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white fw-bold">Notas</div>
                <div class="card-body">
                    <pre class="mb-0 small" style="white-space:pre-wrap"><?= htmlspecialchars($lead->notes) ?></pre>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Actualizar estado</div>
                <div class="card-body">
                    <form method="POST" action="<?= $base ?>/admin/leads/<?= $lead->id ?>">
                        <div class="mb-3">
                            <label class="form-label small">Nuevo estado</label>
                            <select name="status" class="form-select">
                                <?php foreach ($statusLabels as $key => [$label, $c]): ?>
                                    <option value="<?= $key ?>" <?= $lead->status === $key ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Agregar nota</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Ej: Le envié demo por WhatsApp, quedó interesado..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle me-2"></i>Guardar cambios
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white fw-bold">Acciones rápidas</div>
                <div class="card-body d-grid gap-2">
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead->phone) ?>" class="btn btn-outline-success" target="_blank">
                        <i class="bi bi-whatsapp me-2"></i>Enviar WhatsApp
                    </a>
                    <?php if ($lead->email): ?>
                    <a href="mailto:<?= htmlspecialchars($lead->email) ?>" class="btn btn-outline-primary">
                        <i class="bi bi-envelope me-2"></i>Enviar Email
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= App::asset('js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
