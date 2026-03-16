<?php
/**
 * mia/views/client/billing.php — Subscription management & payments
 */
$base         = App::basePath();
$pageTitle    = 'Suscripción — Mia';
$pageTopTitle = 'Suscripción y Pagos';
$activeNav    = 'billing';

$currentPlan = $client->plan;
$planStatus  = $client->plan_status;

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Alerts -->
<?php if (isset($_GET['payment']) && $_GET['payment'] === 'success'): ?>
<div class="alert alert-success border-0 mb-4" style="background:rgba(37,211,102,0.12);color:#155724">
    <i class="bi bi-check-circle me-2"></i><strong>¡Pago exitoso!</strong> Tu suscripción ha sido activada.
</div>
<?php elseif (isset($_GET['payment']) && $_GET['payment'] === 'cancelled'): ?>
<div class="alert alert-warning border-0 mb-4">
    <i class="bi bi-info-circle me-2"></i>El pago fue cancelado. Puedes intentarlo de nuevo cuando quieras.
</div>
<?php elseif (isset($_GET['cancelled'])): ?>
<div class="alert alert-info border-0 mb-4">
    <i class="bi bi-info-circle me-2"></i>Tu suscripción fue cancelada. Seguirás teniendo acceso hasta el fin del período.
</div>
<?php elseif (!empty($_GET['error'])): ?>
<div class="alert alert-danger border-0 mb-4">
    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- ── Current plan card ──────────────────────────────────────────────── -->
    <div class="col-md-5">
        <div class="mc-table-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning me-2 text-warning"></i>Tu plan actual</h6>

            <?php if ($planStatus === 'trial'): ?>
                <div class="text-center py-3 mb-3" style="background:#f8f9fa;border-radius:10px">
                    <div style="font-size:2.5rem;font-weight:800;color:#25d366">
                        <?= $client->trialDaysLeft() ?>
                    </div>
                    <div class="text-muted small">días de prueba restantes</div>
                </div>
                <p class="text-muted small mb-3">
                    Estás en tu período de prueba gratuita de <?= App::FREE_TRIAL_DAYS ?> días.
                    Activa un plan para continuar después.
                </p>
            <?php elseif ($planStatus === 'expired'): ?>
                <div class="text-center py-3 mb-3" style="background:rgba(220,53,69,0.08);border-radius:10px;border:1px solid rgba(220,53,69,0.25)">
                    <div style="font-size:2rem;color:#dc3545"><i class="bi bi-clock-history"></i></div>
                    <div class="fw-bold mt-1" style="color:#dc3545">Prueba vencida</div>
                </div>
                <p class="text-muted small mb-3">
                    Tu período de prueba gratuita ha finalizado. Elige un plan para seguir usando Mia.
                </p>
            <?php elseif ($planStatus === 'active'): ?>
                <div class="text-center py-3 mb-3" style="background:rgba(37,211,102,0.08);border-radius:10px;border:1px solid rgba(37,211,102,0.2)">
                    <div style="font-size:1.3rem;font-weight:700;color:#25d366">
                        Plan <?= htmlspecialchars(ucfirst($currentPlan)) ?>
                    </div>
                    <div class="badge" style="background:rgba(37,211,102,0.2);color:#0a5c36;border:1px solid rgba(37,211,102,0.3)">
                        <i class="bi bi-check-circle me-1"></i>Activo
                    </div>
                </div>
                <?php if ($activeSub): ?>
                <p class="small text-muted mb-3">
                    <i class="bi bi-calendar me-1"></i>
                    Próxima renovación:
                    <?= $activeSub->billing_period_end ? date('d/m/Y', strtotime($activeSub->billing_period_end)) : '—' ?>
                </p>
                <form method="POST" action="<?= $base ?>/dashboard/billing/cancel"
                      onsubmit="return confirm('¿Estás seguro que quieres cancelar tu suscripción?')">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App::csrfToken()) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                        <i class="bi bi-x-circle me-1"></i>Cancelar suscripción
                    </button>
                </form>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Tu suscripción está <?= htmlspecialchars($planStatus) ?>. Reactívala eligiendo un plan.
                </div>
            <?php endif; ?>
        </div>

        <!-- Payment history -->
        <?php if (!empty($history)): ?>
        <div class="mc-table-card mt-4">
            <div class="card-header-bar"><i class="bi bi-receipt me-2 text-muted"></i>Historial de pagos</div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $sub): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucfirst($sub->plan)) ?></td>
                            <td><?= htmlspecialchars($sub->formattedAmount()) ?></td>
                            <td>
                                <span class="badge bg-<?= $sub->statusClass() ?> bg-opacity-10 text-<?= $sub->statusClass() ?> border border-<?= $sub->statusClass() ?> border-opacity-25" style="font-size:0.75rem">
                                    <?= htmlspecialchars(ucfirst($sub->status)) ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= date('d/m/y', strtotime($sub->created_at)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Plan selection ─────────────────────────────────────────────────── -->
    <div class="col-md-7">
        <h6 class="fw-bold mb-3"><i class="bi bi-grid me-2 text-muted"></i>Elige tu plan</h6>

        <?php
        $mpConfigured = !str_starts_with(App::MP_ACCESS_TOKEN, 'PLACEHOLDER');
        $features = [
            'starter'    => ['Módulo Soporte 24/7', 'Hasta 500 conversaciones/mes', 'Respuestas automáticas por WhatsApp', '1 usuario incluido', 'Multilingüe (50+ idiomas)'],
            'basic'      => ['Módulo Soporte 24/7', 'Hasta 1,000 conversaciones/mes', 'Respuestas automáticas por WhatsApp', 'Traspaso humano inteligente', 'Multilingüe (ES · EN · PT · FR + 50 más)'],
            'pro'        => ['Todo lo del Soporte Básico', 'Conversaciones ilimitadas', 'Módulo Ventas (captura y calificación de leads)', 'Asientos adicionales de equipo disponibles', 'Soporte prioritario 24/7'],
            'enterprise'      => ['Todo lo del Pro', 'Módulo Gestión de negocio completo', 'Múltiples números WhatsApp', 'Integraciones (API, webhooks, Zapier/Make)', 'Account manager dedicado + SLA 99.9%'],
            'enterprise_duo'  => ['Todo lo del Enterprise', '2 números WhatsApp activos', 'Portal unificado para 2 negocios', 'Reportes combinados', 'Soporte prioritario'],
            'enterprise_chain'=> ['Todo lo del Enterprise Duo', 'Hasta 5 números / sedes', 'Panel multi-sucursal', 'API access + webhooks', 'Account manager dedicado'],
            'enterprise_corp' => ['Todo lo del Enterprise Cadena', 'Números ilimitados', 'Integraciones avanzadas (Zapier · Make · REST)', 'SLA 99.9% garantizado', 'Soporte técnico 24/7 dedicado'],
        ];
        ?>

        <?php foreach ($plans as $planKey => $plan): ?>
        <?php
        $isCurrent  = $currentPlan === $planKey && $planStatus === 'active';
        $amountYear = $plan['price'] * 12;
        ?>
        <div class="mc-table-card p-4 mb-3 <?= $planKey === 'pro' ? 'border-2' : '' ?>"
             style="<?= $planKey === 'pro' ? 'border:2px solid #25d366 !important;position:relative;' : '' ?>">
            <?php if ($planKey === 'pro'): ?>
                <div style="position:absolute;top:-12px;left:20px;background:#25d366;color:#fff;font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:0.05em">
                    MÁS POPULAR
                </div>
            <?php endif; ?>

            <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($plan['label']) ?></h6>
                    <div class="mt-1">
                        <span style="font-size:1.8rem;font-weight:800;color:#1a202c">
                            <?= App::CURRENCY ?> <?= number_format($plan['price'], 0) ?>
                        </span>
                        <span class="text-muted small">/mes</span>
                    </div>
                    <div class="text-muted small">
                        o <?= App::CURRENCY ?> <?= number_format($amountYear, 0) ?>/año
                    </div>
                </div>
                <?php if ($isCurrent): ?>
                    <span class="badge" style="background:rgba(37,211,102,0.15);color:#0a5c36;border:1px solid rgba(37,211,102,0.3)">
                        <i class="bi bi-check-circle me-1"></i>Plan actual
                    </span>
                <?php endif; ?>
            </div>

            <ul class="list-unstyled small text-muted mb-3">
                <?php foreach ($features[$planKey] ?? [] as $feat): ?>
                <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i><?= htmlspecialchars($feat) ?></li>
                <?php endforeach; ?>
            </ul>

            <?php if (!$isCurrent): ?>
                <?php if ($mpConfigured): ?>
                <form method="POST" action="<?= $base ?>/dashboard/billing/subscribe">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App::csrfToken()) ?>">
                    <input type="hidden" name="plan" value="<?= $planKey ?>">
                    <button type="submit" class="btn w-100 fw-medium"
                            style="<?= $planKey === 'pro' ? 'background:#25d366;color:#fff;' : 'background:#1a1a2e;color:#fff;' ?>">
                        <i class="bi bi-credit-card me-2"></i>
                        <?= $planStatus === 'active' ? 'Cambiar a ' . $plan['label'] : 'Suscribirse — ' . $plan['label'] ?>
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert-info small py-2 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Pagos no configurados aún.
                    <br><a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', App::WHATSAPP) ?>?text=Quiero%20suscribirme%20al%20plan%20<?= urlencode($plan['label']) ?>"
                           class="fw-medium" style="color:#0d6efd" target="_blank">
                        Escríbenos por WhatsApp para pago manual →
                    </a>
                </div>
                <?php endif; ?>
            <?php else: ?>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-success"></i>
                <span class="text-success fw-medium small">Suscrito a este plan</span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <p class="text-muted small text-center mt-2">
            <i class="bi bi-lock me-1"></i>Pago seguro vía Mercado Pago. Cancela en cualquier momento.
        </p>
    </div>
</div>

<?php require __DIR__ . '/_foot.php'; ?>
