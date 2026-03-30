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

<!-- ── Tu IA Mia ────────────────────────────────────────────────────────────── -->
<?php
$_aiTiers = [
    'starter' => [
        'name'  => 'Mia Soporte',
        'icon'  => 'bi-headset',
        'color' => '#6c757d',
        'bg'    => 'rgba(108,117,125,0.08)',
        'border'=> 'rgba(108,117,125,0.22)',
        'desc'  => 'IA de soporte 24/7 que atiende consultas, responde FAQs y habla más de 50 idiomas automáticamente.',
        'caps'  => [
            ['icon'=>'bi-robot',          'label'=>'Bot IA 24/7 en WhatsApp',             'on'=>true ],
            ['icon'=>'bi-translate',      'label'=>'50+ idiomas automáticos',              'on'=>true ],
            ['icon'=>'bi-chat-dots',      'label'=>'500 conversaciones/mes',               'on'=>true ],
            ['icon'=>'bi-gear',           'label'=>'Horario de atención configurable',     'on'=>true ],
            ['icon'=>'bi-qr-code',        'label'=>'Enlace y código QR de WhatsApp',       'on'=>true ],
            ['icon'=>'bi-person-check',   'label'=>'Traspaso humano inteligente',          'on'=>false],
            ['icon'=>'bi-person-plus',    'label'=>'Captura de leads automática',          'on'=>false],
            ['icon'=>'bi-megaphone',      'label'=>'Difusión masiva a leads',              'on'=>false],
            ['icon'=>'bi-arrow-repeat',   'label'=>'Automatizaciones y secuencias',        'on'=>false],
            ['icon'=>'bi-calendar-check', 'label'=>'Agenda de citas con recordatorios',    'on'=>false],
        ],
    ],
    'basic' => [
        'name'  => 'Mia Ventas',
        'icon'  => 'bi-graph-up-arrow',
        'color' => '#0d6efd',
        'bg'    => 'rgba(13,110,253,0.07)',
        'border'=> 'rgba(13,110,253,0.2)',
        'desc'  => 'IA de ventas que cierra ventas por WhatsApp: califica leads, convierte interesados en clientes y tiene traspaso humano disponible si el cliente lo pide.',
        'caps'  => [
            ['icon'=>'bi-robot',          'label'=>'Bot IA 24/7 en WhatsApp',              'on'=>true ],
            ['icon'=>'bi-translate',      'label'=>'50+ idiomas automáticos',               'on'=>true ],
            ['icon'=>'bi-chat-dots',      'label'=>'1,000 conversaciones/mes',              'on'=>true ],
            ['icon'=>'bi-gear',           'label'=>'Horario de atención configurable',      'on'=>true ],
            ['icon'=>'bi-qr-code',        'label'=>'Enlace y código QR de WhatsApp',        'on'=>true ],
            ['icon'=>'bi-bag-check',      'label'=>'Cierra ventas directamente por WA',     'on'=>true ],
            ['icon'=>'bi-person-plus',    'label'=>'Captura de leads (guardados en CRM)',   'on'=>true ],
            ['icon'=>'bi-person-check',   'label'=>'Traspaso humano (si el cliente lo pide)','on'=>true ],
            ['icon'=>'bi-megaphone',      'label'=>'Difusión masiva a leads',               'on'=>false],
            ['icon'=>'bi-megaphone',      'label'=>'Difusión masiva a leads',              'on'=>false],
            ['icon'=>'bi-arrow-repeat',   'label'=>'Automatizaciones y secuencias',        'on'=>false],
            ['icon'=>'bi-calendar-check', 'label'=>'Agenda de citas con recordatorios',    'on'=>false],
        ],
    ],
    'default' => [
        'name'  => 'Mia Business',
        'icon'  => 'bi-buildings',
        'color' => '#25d366',
        'bg'    => 'rgba(37,211,102,0.07)',
        'border'=> 'rgba(37,211,102,0.22)',
        'desc'  => 'Suite completa de IA para negocios: capta leads, agenda citas, envía difusiones, automatiza seguimientos y nunca descansa.',
        'caps'  => [
            ['icon'=>'bi-robot',          'label'=>'Bot IA 24/7 en WhatsApp',             'on'=>true ],
            ['icon'=>'bi-translate',      'label'=>'50+ idiomas automáticos',              'on'=>true ],
            ['icon'=>'bi-infinity',       'label'=>'Conversaciones ilimitadas',            'on'=>true ],
            ['icon'=>'bi-gear',           'label'=>'Horario de atención configurable',     'on'=>true ],
            ['icon'=>'bi-qr-code',        'label'=>'Enlace y código QR de WhatsApp',       'on'=>true ],
            ['icon'=>'bi-person-check',   'label'=>'Traspaso humano inteligente',          'on'=>true ],
            ['icon'=>'bi-person-plus',    'label'=>'Captura y cierre de leads automático',  'on'=>true ],
            ['icon'=>'bi-megaphone',      'label'=>'Difusión masiva a leads',              'on'=>true ],
            ['icon'=>'bi-arrow-repeat',   'label'=>'Automatizaciones y secuencias',        'on'=>true ],
            ['icon'=>'bi-calendar-check', 'label'=>'Agenda de citas con recordatorios',    'on'=>true ],
        ],
    ],
];

$_planLabels = [
    'trial'           => 'Prueba gratuita',
    'starter'         => 'Starter',
    'basic'           => 'Pro',
    'pro'             => 'Business',
    'enterprise'      => 'Enterprise',
    'enterprise_duo'  => 'Enterprise Duo',
    'enterprise_chain'=> 'Enterprise Cadena',
    'enterprise_corp' => 'Enterprise Corporativo',
];

$_tier      = match($client->plan) {
    'starter' => $_aiTiers['starter'],
    'basic'   => $_aiTiers['basic'],
    default   => $_aiTiers['default'],
};
$_planLabel = $_planLabels[$client->plan] ?? ucfirst($client->plan);
?>

<div class="mc-table-card p-4 mb-4">
    <div class="row g-3 align-items-start">

        <!-- AI identity -->
        <div class="col-md-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:54px;height:54px;border-radius:14px;background:<?= $_tier['bg'] ?>;border:1px solid <?= $_tier['border'] ?>;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:<?= $_tier['color'] ?>;flex-shrink:0">
                    <i class="bi <?= $_tier['icon'] ?>"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:1.1rem;color:<?= $_tier['color'] ?>"><?= htmlspecialchars($_tier['name']) ?></div>
                    <div class="text-muted small">Plan <?= htmlspecialchars($_planLabel) ?></div>
                </div>
            </div>
            <p class="text-muted small mb-3" style="line-height:1.55"><?= htmlspecialchars($_tier['desc']) ?></p>
            <?php if ($client->plan_status === 'trial'): ?>
                <span class="badge bg-warning text-dark" style="font-size:.74rem">
                    <i class="bi bi-clock me-1"></i>Prueba &middot; <?= $client->trialDaysLeft() ?> días restantes
                </span>
            <?php elseif ($client->plan_status === 'active'): ?>
                <span class="badge" style="background:rgba(37,211,102,0.15);color:#0a5c36;border:1px solid rgba(37,211,102,0.3);font-size:.74rem">
                    <i class="bi bi-check-circle me-1"></i>Suscripción activa
                </span>
            <?php elseif ($client->plan_status === 'expired'): ?>
                <span class="badge bg-danger" style="font-size:.74rem"><i class="bi bi-x-circle me-1"></i>Prueba vencida</span>
            <?php else: ?>
                <span class="badge bg-secondary" style="font-size:.74rem"><?= htmlspecialchars($client->plan_status) ?></span>
            <?php endif; ?>
        </div>

        <!-- Capabilities list -->
        <div class="col-md-5">
            <div class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.68rem;letter-spacing:.07em">Capacidades de tu IA</div>
            <?php foreach ($_tier['caps'] as $cap): ?>
            <div class="d-flex align-items-center gap-2 mb-1 <?= $cap['on'] ? '' : 'opacity-40' ?>">
                <?php if ($cap['on']): ?>
                    <i class="bi <?= $cap['icon'] ?> text-success" style="font-size:.9rem;width:17px;text-align:center;flex-shrink:0"></i>
                    <span class="small"><?= htmlspecialchars($cap['label']) ?></span>
                <?php else: ?>
                    <i class="bi bi-lock" style="font-size:.85rem;width:17px;text-align:center;flex-shrink:0;color:#adb5bd"></i>
                    <span class="small text-muted" style="text-decoration:line-through"><?= htmlspecialchars($cap['label']) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA -->
        <div class="col-md-3 d-flex flex-column gap-2">
            <?php if ($client->plan_status === 'trial' || $client->plan_status === 'expired'): ?>
            <a href="<?= $base ?>/dashboard/billing" class="btn fw-semibold" style="background:#25d366;color:#fff">
                <i class="bi bi-lightning me-1"></i>Activar plan
            </a>
            <?php else: ?>
            <a href="<?= $base ?>/dashboard/billing" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-receipt me-1"></i>Mi suscripción
            </a>
            <?php endif; ?>
            <?php if (in_array($client->plan, ['starter', 'basic'])): ?>
            <a href="<?= $base ?>/dashboard/billing" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-up-circle me-1"></i>Mejorar IA
            </a>
            <div class="text-muted" style="font-size:.72rem">
                <?= $client->plan === 'starter'
                    ? 'Actualiza a <strong>Mia Ventas</strong> o <strong>Mia Business</strong> para desbloquear toda la automatización.'
                    : 'Actualiza a <strong>Mia Business</strong> para difusión, secuencias y agenda de citas.' ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
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
                        <td><?php $dp = $lead->displayPhone(); echo $dp !== '' ? htmlspecialchars($dp) : '<span style="opacity:0.35">—</span>'; ?></td>
                        <td>
                            <i class="bi <?= $lead->sourceIcon() ?> me-1"></i>
                            <?= ucfirst(htmlspecialchars($lead->source)) ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $lead->statusClass() ?> bg-opacity-10 text-<?= $lead->statusClass() ?> border border-<?= $lead->statusClass() ?> border-opacity-25">
                                <?= $lead->statusLabel() ?>
                            </span>
                        </td>
                        <td class="text-muted"><?= date('d/m/y', strtotime($lead->last_activity ?? $lead->created_at)) ?></td>
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

<?php if (!empty($qrSetup) && $client->bot_wa_status !== 'connected'): ?>
<!-- ── QR Setup Modal (first login from chat onboarding) ──────────────── -->
<div class="modal fade" id="qrSetupModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;padding:28px 28px 18px;">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>¡Tu Mia ya está configurada!</h5>
                    <p class="mb-0 opacity-75" style="font-size:0.88rem">Solo falta conectar tu WhatsApp — toma 1 minuto.</p>
                </div>
            </div>
            <div class="modal-body text-center px-4 py-4">
                <div id="qrStep1">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:64px;height:64px;background:rgba(37,211,102,0.12);">
                            <i class="bi bi-whatsapp" style="font-size:2rem;color:#25d366;"></i>
                        </div>
                        <p class="text-muted mb-3" style="font-size:0.9rem">
                            Conecta el WhatsApp de tu negocio para que Mia empiece a atender clientes automáticamente.
                        </p>
                    </div>
                    <button type="button" class="btn btn-lg px-5" id="qrConnectBtn"
                            style="background:#25d366;border-color:#25d366;color:#fff;border-radius:12px;">
                        <i class="bi bi-qr-code-scan me-2"></i>Conectar WhatsApp
                    </button>
                    <div class="mt-3">
                        <a href="<?= $base ?>/dashboard" class="text-muted small text-decoration-none">
                            Hacer esto después →
                        </a>
                    </div>
                </div>
                <div id="qrStep2" class="d-none">
                    <div class="text-center p-3 border rounded-3 bg-light mb-3" id="qrBox">
                        <div class="spinner-border spinner-border-sm text-success mb-2" role="status"></div>
                        <div class="text-muted small">Generando código QR...</div>
                    </div>
                    <div class="text-start bg-light rounded-3 p-3" style="font-size:0.85rem;">
                        <div class="fw-semibold mb-2"><i class="bi bi-phone me-1"></i>En tu teléfono:</div>
                        <ol class="mb-0 ps-3">
                            <li>Abre <strong>WhatsApp</strong></li>
                            <li>Ve a <strong>Dispositivos vinculados</strong></li>
                            <li>Toca <strong>Vincular dispositivo</strong></li>
                            <li>Escanea el código QR de arriba</li>
                        </ol>
                    </div>
                </div>
                <div id="qrStep3" class="d-none">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:64px;height:64px;background:rgba(37,211,102,0.12);">
                        <i class="bi bi-check-lg" style="font-size:2.5rem;color:#25d366;"></i>
                    </div>
                    <h5 class="fw-bold text-success">¡WhatsApp conectado!</h5>
                    <p class="text-muted">Mia ya está atendiendo a tus clientes 24/7.</p>
                    <button type="button" class="btn btn-success px-4" onclick="location.href='<?= $base ?>/dashboard'">
                        <i class="bi bi-rocket me-1"></i>Ir al Dashboard
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    const BASE = <?= json_encode($base) ?>;
    const modal = new bootstrap.Modal(document.getElementById('qrSetupModal'));
    modal.show();

    const step1 = document.getElementById('qrStep1');
    const step2 = document.getElementById('qrStep2');
    const step3 = document.getElementById('qrStep3');
    const qrBox = document.getElementById('qrBox');
    let pollTimer = null;

    document.getElementById('qrConnectBtn').addEventListener('click', function(){
        step1.classList.add('d-none');
        step2.classList.remove('d-none');
        fetch(BASE + '/dashboard/settings/wa-connect', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: '_csrf=' + encodeURIComponent(<?= json_encode(App::csrfToken()) ?>)
        }).then(r => r.json()).then(data => {
            if (data.ok) pollQR();
            else qrBox.innerHTML = '<div class="text-danger small">Error al conectar. Intenta desde Configuración.</div>';
        }).catch(() => {
            qrBox.innerHTML = '<div class="text-danger small">Error de conexión.</div>';
        });
    });

    function pollQR(){
        pollTimer = setInterval(()=>{
            fetch(BASE + '/dashboard/settings/wa-qr', {credentials:'same-origin'})
            .then(r=>r.json())
            .then(d=>{
                if(d.status==='connected'){
                    clearInterval(pollTimer);
                    step2.classList.add('d-none');
                    step3.classList.remove('d-none');
                    // Mark onboarding done
                    fetch(BASE + '/dashboard/settings/finish-onboarding', {
                        method:'POST', credentials:'same-origin',
                        headers:{'Content-Type':'application/x-www-form-urlencoded'},
                        body:'_csrf=' + encodeURIComponent(<?= json_encode(App::csrfToken()) ?>)
                    });
                } else if(d.qr_image){
                    qrBox.innerHTML = '<img src="'+d.qr_image+'" style="width:260px;height:260px;" alt="QR">';
                }
            }).catch(()=>{});
        }, 2500);
    }
})();
</script>
<?php endif; ?>

<!-- ── Recent leads live refresh ──────────────────────────────────────────── -->
<?php if (!empty($recentLeads)): ?>
<script>
(function(){
    const BASE = <?= json_encode(rtrim(App::basePath(), '/')) ?>;
    const tbody = document.querySelector('.mc-table-card .table tbody');
    if (!tbody) return;

    function refreshLeads(){
        fetch(BASE + '/dashboard/recent-leads', {credentials:'same-origin'})
        .then(r => r.json())
        .then(data => {
            if (!data.ok || !Array.isArray(data.leads)) return;
            const rows = data.leads.map(l => {
                const phone = l.phone
                    ? `<td>${escHtml(l.phone)}</td>`
                    : `<td><span style="opacity:0.35">—</span></td>`;
                return `<tr>
                    <td class="fw-medium">${escHtml(l.name)}</td>
                    ${phone}
                    <td><i class="bi ${escHtml(l.sourceIcon)} me-1"></i>${escHtml(l.source)}</td>
                    <td><span class="badge bg-${escHtml(l.statusClass)} bg-opacity-10 text-${escHtml(l.statusClass)} border border-${escHtml(l.statusClass)} border-opacity-25">${escHtml(l.statusLabel)}</span></td>
                    <td class="text-muted">${escHtml(l.last_activity)}</td>
                    <td><a href="${BASE}/dashboard/leads/${l.id}" class="btn btn-xs btn-outline-secondary" style="font-size:0.78rem;padding:2px 8px">Ver</a></td>
                </tr>`;
            });
            tbody.innerHTML = rows.join('');
        }).catch(()=>{});
    }

    function escHtml(str){
        return String(str ?? '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    setInterval(refreshLeads, 30000);
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/_foot.php'; ?>
