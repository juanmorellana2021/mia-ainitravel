<?php
/**
 * mia/views/superadmin/prospect_detail.php
 *
 * Full detail of a single mia_sales_session prospect.
 * Shows captured data, conversation history, and "Convertir en cliente" button.
 */
$base         = App::basePath();
$session      = $data['session'];
$history      = $data['history'];
$pageTitle    = 'Prospecto — Superadmin Mia';
$pageTopTitle = 'Prospecto #' . (int)$session['id'];
$activeNav    = 'prospects';

$stateLabels = [
    'new'               => ['Nuevo',           '#6366f1'],
    'intro'             => ['Intro',            '#8b5cf6'],
    'qualifying_size'   => ['Calificando',      '#f59e0b'],
    'qualifying_method' => ['Calificando',      '#f59e0b'],
    'qualifying_pain'   => ['Calificando',      '#f59e0b'],
    'roi_pitch'         => ['Pitch ROI',        '#0ea5e9'],
    'demo'              => ['Demo',             '#0ea5e9'],
    'benefits'          => ['Beneficios',       '#06b6d4'],
    'closing'           => ['Cerrando',         '#f97316'],
    'collecting_name'   => ['Recogiendo datos', '#f97316'],
    'collecting_email'  => ['Recogiendo datos', '#f97316'],
    'captured'          => ['Capturado ✓',      '#22c55e'],
    'human_handoff'     => ['Mano humana',      '#ef4444'],
];

$state = $session['state'] ?? 'new';
[$stateLabel, $stateColor] = $stateLabels[$state] ?? [$state, '#94a3b8'];
$alreadyClient = !empty($session['client_id']);

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Back link -->
<div class="mb-3">
    <a href="<?= $base ?>/superadmin/prospects"
       style="color:#94a3b8;font-size:0.85rem;text-decoration:none;">
        <i class="bi bi-arrow-left me-1"></i>Volver a Prospectos
    </a>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="alert border-0 mb-3" style="background:rgba(239,68,68,0.1);color:#fca5a5;border-radius:10px;font-size:0.88rem;">
    <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['converted'], $_GET['tmp'])): ?>
<div class="alert border-0 mb-3" style="background:rgba(0,168,132,0.12);color:#6ee7b7;border:1px solid rgba(0,168,132,0.3);border-radius:10px;font-size:0.88rem;">
    <i class="bi bi-check-circle-fill me-2" style="color:#00a884"></i>
    <strong>Cliente creado.</strong> Contraseña temporal:
    <code style="background:rgba(0,0,0,0.3);padding:2px 8px;border-radius:5px;font-size:0.9rem;letter-spacing:.05em;color:#a7f3d0;margin-left:6px;"><?= htmlspecialchars($_GET['tmp']) ?></code>
    <span style="font-size:0.78rem;color:#64748b;margin-left:8px;">Compártela con el cliente para que inicie sesión.</span>
</div>
<?php endif; ?>

<?php if ($alreadyClient): ?>
<div class="alert border-0 mb-3" style="background:rgba(34,197,94,0.1);color:#86efac;border-radius:10px;font-size:0.88rem;">
    <i class="bi bi-person-check-fill me-1"></i>
    Este prospecto ya fue convertido en cliente.
    <a href="<?= $base ?>/superadmin/clients/<?= (int)$session['client_id'] ?>"
       style="color:#4ade80;font-weight:600;margin-left:6px;">Ver cliente →</a>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">

    <!-- Left column: captured info -->
    <div class="col-md-4">
        <div class="sa-card p-3 h-100">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div style="width:42px;height:42px;border-radius:50%;background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-person-lines-fill" style="color:#818cf8;font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-weight:600;color:#e2e8f0;font-size:0.95rem;">
                        <?= htmlspecialchars($session['contact_name'] ?: 'Sin nombre') ?>
                    </div>
                    <div style="font-size:0.78rem;color:#64748b;font-family:monospace;">
                        <?= htmlspecialchars($session['phone']) ?>
                    </div>
                </div>
                <span class="ms-auto" style="
                    padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;
                    background:<?= $stateColor ?>22;color:<?= $stateColor ?>;border:1px solid <?= $stateColor ?>44;">
                    <?= htmlspecialchars($stateLabel) ?>
                </span>
            </div>

            <div style="display:grid;gap:10px;">
                <?php
                $fields = [
                    ['bi-building',       'Negocio',        $session['business_name']],
                    ['bi-envelope',       'Email',          $session['email']],
                    ['bi-tags',           'Tipo',           $session['business_type']],
                    ['bi-door-open',      'Habitaciones',   $session['room_count'] ? (int)$session['room_count'] . ' hab.' : null],
                    ['bi-credit-card',    'Método actual',  $session['current_method']],
                    ['bi-emoji-frown',    'Dolor principal',$session['pain_point']],
                ];
                foreach ($fields as [$icon, $label, $value]):
                    if ($value === null || $value === '') continue;
                ?>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi <?= $icon ?>" style="color:#475569;margin-top:2px;font-size:0.9rem;min-width:16px;"></i>
                    <div>
                        <div style="font-size:0.72rem;color:#475569;text-transform:uppercase;letter-spacing:.04em;"><?= $label ?></div>
                        <div style="color:#cbd5e1;font-size:0.85rem;"><?= htmlspecialchars((string)$value) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-calendar3" style="color:#475569;margin-top:2px;font-size:0.9rem;min-width:16px;"></i>
                    <div>
                        <div style="font-size:0.72rem;color:#475569;text-transform:uppercase;letter-spacing:.04em;">Creado</div>
                        <div style="color:#94a3b8;font-size:0.83rem;">
                            <?= date('d M Y H:i', strtotime($session['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-clock-history" style="color:#475569;margin-top:2px;font-size:0.9rem;min-width:16px;"></i>
                    <div>
                        <div style="font-size:0.72rem;color:#475569;text-transform:uppercase;letter-spacing:.04em;">Última actividad</div>
                        <div style="color:#94a3b8;font-size:0.83rem;">
                            <?= date('d M Y H:i', strtotime($session['updated_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right column: convert button + conversation -->
    <div class="col-md-8">

        <!-- Convert to client card -->
        <div class="sa-card p-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <div style="font-weight:600;color:#e2e8f0;margin-bottom:3px;">
                        <?php if ($alreadyClient): ?>
                        <i class="bi bi-person-check-fill me-1" style="color:#4ade80;"></i>
                        Ya es cliente de Mia
                        <?php else: ?>
                        <i class="bi bi-person-plus-fill me-1" style="color:#818cf8;"></i>
                        Convertir en cliente
                        <?php endif; ?>
                    </div>
                    <div style="font-size:0.82rem;color:#64748b;">
                        <?php if ($alreadyClient): ?>
                        Se creó una cuenta con los datos capturados por Mia en WhatsApp.
                        <?php else: ?>
                        Crea una cuenta de cliente con los datos capturados por Mia.
                        Se generará una contraseña temporal que podrás compartir.
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ms-auto">
                    <?php if ($alreadyClient): ?>
                    <a href="<?= $base ?>/superadmin/clients/<?= (int)$session['client_id'] ?>"
                       class="btn"
                       style="background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.35);border-radius:9px;padding:8px 18px;font-size:0.85rem;font-weight:600;white-space:nowrap;">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Ver cliente
                    </a>
                    <?php else: ?>
                    <form method="POST" action="<?= $base ?>/superadmin/prospects/<?= (int)$session['id'] ?>/convert"
                          onsubmit="return confirm('¿Crear cuenta de cliente para <?= htmlspecialchars(addslashes($session['phone'])) ?>?\n\nSe generará una contraseña temporal.');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(App::csrfToken()) ?>">
                        <button type="submit" class="btn"
                                style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;border-radius:9px;padding:8px 18px;font-size:0.85rem;font-weight:600;white-space:nowrap;box-shadow:0 2px 12px rgba(99,102,241,0.4);">
                            <i class="bi bi-person-plus-fill me-1"></i>Convertir en cliente
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Conversation history -->
        <div class="sa-card p-3">
            <div style="font-size:0.8rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                <i class="bi bi-chat-text me-1"></i>
                Historial de conversación (<?= count($history) ?> mensajes)
            </div>

            <?php if (empty($history)): ?>
            <div style="color:#475569;font-size:0.85rem;text-align:center;padding:20px 0;">
                Sin mensajes registrados.
            </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:8px;max-height:460px;overflow-y:auto;padding-right:4px;">
                <?php foreach ($history as $msg):
                    $role    = $msg['role'] ?? 'user';
                    $content = $msg['content'] ?? '';
                    $isMia   = ($role === 'assistant');
                ?>
                <div style="display:flex;justify-content:<?= $isMia ? 'flex-start' : 'flex-end' ?>;">
                    <div style="
                        max-width:80%;padding:8px 12px;border-radius:<?= $isMia ? '4px 12px 12px 12px' : '12px 4px 12px 12px' ?>;
                        font-size:0.83rem;line-height:1.45;
                        background:<?= $isMia ? '#202c33' : '#005c4b' ?>;
                        color:#e9edef;
                        border:none;
                    ">
                        <?php if ($isMia): ?>
                        <div style="font-size:0.7rem;color:#00a884;font-weight:600;margin-bottom:3px;">
                            <i class="bi bi-robot me-1"></i>Mia
                        </div>
                        <?php else: ?>
                        <div style="font-size:0.7rem;color:#8fcebd;font-weight:600;margin-bottom:3px;text-align:right;">
                            Prospecto
                        </div>
                        <?php endif; ?>
                        <?= nl2br(htmlspecialchars($content)) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require __DIR__ . '/_foot.php'; ?>
