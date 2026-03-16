<?php
/**
 * mia/views/client/dashboard.php — Main overview dashboard
 */
$base          = App::basePath();
$pageTitle     = 'Dashboard — Mia';
$pageTopTitle  = 'Dashboard';
$activeNav     = 'dashboard';

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<?php if ($welcome): ?>
<div class="alert alert-success border-0 rounded-3 mb-4" style="background:rgba(37,211,102,0.12);color:#155724">
    <i class="bi bi-rocket me-2"></i>
    <strong>¡Bienvenido/a a Mia!</strong> Tu prueba de <?= App::FREE_TRIAL_DAYS ?> días ha comenzado.
    El siguiente paso es conectar tu número de WhatsApp. <a href="<?= $base ?>/dashboard/billing" class="fw-semibold" style="color:#0a5c36">Ver plan →</a>
</div>
<?php endif; ?>

<!-- ── Stats cards ──────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="mc-stat-card">
            <div class="stat-num text-primary"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="stat-label"><i class="bi bi-people me-1"></i>Total leads</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="mc-stat-card">
            <div class="stat-num" style="color:#25d366"><?= (int)($stats['new_leads'] ?? 0) ?></div>
            <div class="stat-label"><i class="bi bi-star me-1"></i>Leads nuevos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="mc-stat-card">
            <div class="stat-num text-warning"><?= (int)($stats['won'] ?? 0) ?></div>
            <div class="stat-label"><i class="bi bi-trophy me-1"></i>Cerrados ganados</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="mc-stat-card">
            <div class="stat-num text-info"><?= $todayMessages ?></div>
            <div class="stat-label"><i class="bi bi-chat-dots me-1"></i>Mensajes hoy</div>
        </div>
    </div>
</div>

<!-- ── Pipeline value + quick actions ──────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="mc-stat-card d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;background:rgba(37,211,102,0.12);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#25d366">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div>
                <div class="stat-num fs-4" style="color:#25d366">
                    <?= App::CURRENCY ?> <?= number_format((float)($stats['pipeline_value'] ?? 0), 0) ?>
                </div>
                <div class="stat-label">Valor en pipeline</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mc-stat-card d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;background:rgba(13,110,253,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#0d6efd">
                <i class="bi bi-bar-chart"></i>
            </div>
            <div>
                <div class="stat-num fs-4 text-primary"><?= (int)($stats['interested'] ?? 0) ?></div>
                <div class="stat-label">Interesados activos</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <?php
        $total     = (int)($stats['total'] ?? 0);
        $won       = (int)($stats['won'] ?? 0);
        $convRate  = $total > 0 ? round(($won / $total) * 100, 1) : 0;
        ?>
        <div class="mc-stat-card d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;background:rgba(255,193,7,0.12);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#ffc107">
                <i class="bi bi-percent"></i>
            </div>
            <div>
                <div class="stat-num fs-4 text-warning"><?= $convRate ?>%</div>
                <div class="stat-label">Tasa de conversión</div>
            </div>
        </div>
    </div>
</div>

<!-- ── Monthly conversations counter ───────────────────────────────────────── -->
<?php
$convPct   = $convLimit > 0 ? min(100, (int)round(($monthConvos / $convLimit) * 100)) : 0;
$convLabel = $convLimit > 0 ? number_format($monthConvos) . ' / ' . number_format($convLimit) : number_format($monthConvos);
$convColor = $convPct >= 90 ? 'danger' : ($convPct >= 70 ? 'warning' : 'success');
$monthName = mb_strtoupper(strftime('%B', mktime(0,0,0, (int)date('m'), 1, (int)date('Y'))));
?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="mc-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="stat-num fs-3 text-<?= $convLimit > 0 ? $convColor : 'primary' ?>">
                            <?= $convLabel ?>
                        </span>
                        <?php if ($convLimit > 0): ?>
                        <span class="text-muted" style="font-size:.8rem">conversaciones del plan <?= ucfirst($client->plan) ?></span>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:.8rem">conversaciones este mes · plan <?= ucfirst($client->plan) ?> — ilimitadas</span>
                        <?php endif; ?>
                    </div>
                    <div class="stat-label"><i class="bi bi-chat-square-dots me-1"></i>Conversaciones únicas — <?= $monthName ?></div>
                </div>
                <?php if ($convLimit > 0 && $convPct >= 90): ?>
                <a href="<?= $base ?>/dashboard/billing" class="btn btn-sm btn-danger">
                    <i class="bi bi-arrow-up-circle me-1"></i>Actualizar plan
                </a>
                <?php endif; ?>
            </div>
            <?php if ($convLimit > 0): ?>
            <div class="progress mt-3" style="height:6px">
                <div class="progress-bar bg-<?= $convColor ?>" style="width:<?= $convPct ?>%"></div>
            </div>
            <div class="text-muted mt-1" style="font-size:.72rem"><?= $convPct ?>% del límite mensual utilizado</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Plan status bar ─────────────────────────────────────────────────────── -->
<?php
$_planNames = [
    'trial'           => 'Prueba gratuita',
    'starter'         => 'Starter',
    'basic'           => 'Pro',
    'pro'             => 'Business',
    'enterprise'      => 'Enterprise',
    'enterprise_duo'  => 'Enterprise Duo',
    'enterprise_chain'=> 'Enterprise Cadena',
    'enterprise_corp' => 'Enterprise Corporativo',
];
$_planFeats = [
    'trial'           => ['handoff', 'leads'],
    'starter'         => [],
    'basic'           => ['handoff'],
    'pro'             => ['handoff', 'leads'],
    'enterprise'      => ['handoff', 'leads'],
    'enterprise_duo'  => ['handoff', 'leads'],
    'enterprise_chain'=> ['handoff', 'leads'],
    'enterprise_corp' => ['handoff', 'leads'],
];
$_myFeats  = $_planFeats[$client->plan] ?? [];
$_planName = $_planNames[$client->plan] ?? ucfirst($client->plan);
?>
<div class="mc-table-card p-3 mb-4 d-flex align-items-center flex-wrap gap-3">
    <div style="min-width:150px">
        <div class="fw-bold" style="font-size:.95rem"><?= htmlspecialchars($_planName) ?></div>
        <?php if ($client->plan_status === 'trial'): ?>
            <span class="badge bg-warning text-dark mt-1" style="font-size:.7rem">
                <i class="bi bi-clock me-1"></i>Prueba &middot; <?= $client->trialDaysLeft() ?> días
            </span>
        <?php elseif ($client->plan_status === 'active'): ?>
            <span class="badge mt-1" style="background:rgba(37,211,102,0.15);color:#0a5c36;border:1px solid rgba(37,211,102,0.3);font-size:.7rem">
                <i class="bi bi-check-circle me-1"></i>Activo
            </span>
        <?php elseif ($client->plan_status === 'expired'): ?>
            <span class="badge bg-danger mt-1" style="font-size:.7rem"><i class="bi bi-x-circle me-1"></i>Prueba vencida</span>
        <?php else: ?>
            <span class="badge bg-secondary mt-1" style="font-size:.7rem"><?= htmlspecialchars($client->plan_status) ?></span>
        <?php endif; ?>
    </div>

    <div class="d-flex flex-wrap gap-2 flex-fill">
        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size:.78rem">
            <i class="bi bi-check2 me-1"></i>Bot 24/7
        </span>
        <?php if (in_array('handoff', $_myFeats)): ?>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size:.78rem">
            <i class="bi bi-check2 me-1"></i>Traspaso humano
        </span>
        <?php else: ?>
        <span class="badge bg-light text-muted border" style="font-size:.78rem">
            <i class="bi bi-lock me-1"></i>Traspaso humano
        </span>
        <?php endif; ?>
        <?php if (in_array('leads', $_myFeats)): ?>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size:.78rem">
            <i class="bi bi-check2 me-1"></i>Captura de leads
        </span>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size:.78rem">
            <i class="bi bi-check2 me-1"></i>Automatizaciones
        </span>
        <?php else: ?>
        <span class="badge bg-light text-muted border" style="font-size:.78rem">
            <i class="bi bi-lock me-1"></i>Captura de leads
        </span>
        <?php endif; ?>
        <?php if ($convLimit === 0): ?>
        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size:.78rem">
            <i class="bi bi-infinity me-1"></i>Conversaciones ilimitadas
        </span>
        <?php else: ?>
        <span class="badge bg-light text-muted border" style="font-size:.78rem">
            <i class="bi bi-chat me-1"></i><?= number_format($convLimit) ?>/mes
        </span>
        <?php endif; ?>
    </div>

    <a href="<?= $base ?>/dashboard/billing" class="btn btn-sm btn-outline-secondary" style="white-space:nowrap;font-size:.8rem">
        <?php if ($client->plan_status === 'trial' || $client->plan_status === 'expired'): ?>
            <i class="bi bi-lightning me-1"></i>Activar plan
        <?php else: ?>
            <i class="bi bi-receipt me-1"></i>Mi suscripción
        <?php endif; ?>
    </a>
</div>

<!-- ── Recent leads table ────────────────────────────────────────────────────── -->
<div class="mc-table-card mb-4">
    <div class="card-header-bar">
        <span><i class="bi bi-people me-2 text-muted"></i>Leads recientes</span>
        <a href="<?= $base ?>/dashboard/leads" class="btn btn-sm btn-outline-secondary">
            Ver todos <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <?php if (empty($recentLeads)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
            <p class="mb-1 fw-medium">Aún no tienes leads</p>
            <p class="small">Cuando tu bot de Mia empiece a recibir mensajes, aparecerán aquí.</p>
            <a href="<?= $base ?>/dashboard/billing" class="btn btn-sm mt-2" style="background:#25d366;color:#fff">
                <i class="bi bi-lightning me-1"></i>Activar Mia
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mc-table-card">
                <thead>
                    <tr>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Fuente</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLeads as $lead): ?>
                    <tr>
                        <td class="fw-medium"><?= htmlspecialchars($lead->contact_name ?: 'Sin nombre') ?></td>
                        <td><?= htmlspecialchars($lead->phone) ?></td>
                        <td>
                            <i class="bi <?= $lead->sourceIcon() ?> me-1"></i>
                            <?= ucfirst(htmlspecialchars($lead->source)) ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $lead->statusClass() ?> bg-opacity-10 text-<?= $lead->statusClass() ?> border border-<?= $lead->statusClass() ?> border-opacity-25">
                                <?= $lead->statusLabel() ?>
                            </span>
                        </td>
                        <td class="text-muted"><?= date('d/m/y', strtotime($lead->created_at)) ?></td>
                        <td>
                            <a href="<?= $base ?>/dashboard/leads/<?= $lead->id ?>"
                               class="btn btn-xs btn-outline-secondary" style="font-size:0.78rem;padding:2px 8px">
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

<!-- ── Onboarding checklist (shown when < 2 leads) ──────────────────────────── -->
<?php if ($total < 2): ?>
<div class="mc-table-card">
    <div class="card-header-bar">
        <span><i class="bi bi-list-check me-2 text-muted"></i>Pasos para activar Mia</span>
    </div>
    <div class="p-4">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="flex-shrink-0" style="width:28px;height:28px;background:<?= !empty($_SESSION['mia_client']['whatsapp_number'] ?? '') ? '#25d366' : '#e2e8f0' ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem">
                <?= !empty($_SESSION['mia_client']['whatsapp_number'] ?? '') ? '✓' : '1' ?>
            </div>
            <div>
                <div class="fw-medium">Conectar tu número de WhatsApp</div>
                <div class="text-muted small">Dinos qué número de WhatsApp quieres que Mia atienda</div>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="flex-shrink-0" style="width:28px;height:28px;background:#e2e8f0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#6c757d;font-size:0.8rem">2</div>
            <div>
                <div class="fw-medium">Configurar Mia para tu negocio</div>
                <div class="text-muted small">Nuestro equipo programa las respuestas de Mia con tu información</div>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <div class="flex-shrink-0" style="width:28px;height:28px;background:#e2e8f0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#6c757d;font-size:0.8rem">3</div>
            <div>
                <div class="fw-medium">¡Listo! Mia empieza a atender leads</div>
                <div class="text-muted small">En 48 horas tu bot estará activo cerrando negocios 24/7</div>
            </div>
        </div>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', App::WHATSAPP) ?>?text=Hola!%20Quiero%20activar%20Mia%20para%20mi%20negocio"
           class="btn mt-3 btn-sm" style="background:#25d366;color:#fff" target="_blank">
            <i class="bi bi-whatsapp me-1"></i>Hablar con soporte para activar Mia
        </a>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_foot.php'; ?>
