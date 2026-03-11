<?php
/**
 * mia/views/superadmin/client_detail.php
 */
$base         = App::basePath();
$client       = $data['client'];
$subscriptions = $data['subscriptions'];
$leadStats    = $data['lead_stats'];
$msgStats     = $data['msg_stats'];

$pageTitle    = htmlspecialchars($client['business_name']) . ' — Superadmin Mia';
$pageTopTitle = htmlspecialchars($client['business_name']);
$activeNav    = 'clients';

$allPlans    = ['trial','starter','basic','pro','enterprise'];
$allStatuses = ['trial','active','expired','cancelled'];

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<?php if ($saved): ?>
<div class="alert border-0 mb-3" style="background:rgba(34,197,94,0.1);color:#166534;border-radius:10px;font-size:0.88rem;">
    <i class="bi bi-check-circle me-1"></i> Cambios guardados correctamente.
</div>
<?php endif; ?>
<?php if (($error ?? '') === 'password_short'): ?>
<div class="alert border-0 mb-3" style="background:rgba(239,68,68,0.1);color:#991b1b;border-radius:10px;font-size:0.88rem;">
    <i class="bi bi-exclamation-triangle me-1"></i> La contraseña debe tener al menos 8 caracteres.
</div>
<?php endif; ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= $base ?>/superadmin/clients" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left"></i>
    </a>
    <span class="text-muted small">Clientes /</span>
    <span class="fw-semibold"><?= htmlspecialchars($client['business_name']) ?></span>
    <span class="ms-auto badge bg-secondary bg-opacity-10 text-secondary border">#<?= $client['id'] ?></span>
</div>

<div class="row g-3">
    <!-- Edit form -->
    <div class="col-lg-7">
        <div class="sa-detail-card">
            <h6><i class="bi bi-pencil me-1"></i>Datos del cliente</h6>
            <form method="POST" action="<?= $base ?>/superadmin/clients/<?= $client['id'] ?>">
                <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Nombre del negocio</label>
                        <input type="text" name="business_name" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($client['business_name']) ?>" required style="border-radius:8px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Contacto</label>
                        <input type="text" name="contact_name" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($client['contact_name']) ?>" style="border-radius:8px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Email</label>
                        <input type="email" name="email" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($client['email']) ?>" required style="border-radius:8px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">WhatsApp número</label>
                        <input type="text" name="whatsapp_number" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($client['whatsapp_number'] ?? '') ?>" style="border-radius:8px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Plan</label>
                        <select name="plan" class="form-select form-select-sm" style="border-radius:8px;">
                            <?php foreach ($allPlans as $p): ?>
                            <option value="<?= $p ?>" <?= $client['plan'] === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Estado plan</label>
                        <select name="plan_status" class="form-select form-select-sm" style="border-radius:8px;">
                            <?php foreach ($allStatuses as $s): ?>
                            <option value="<?= $s ?>" <?= $client['plan_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Trial vence</label>
                        <input type="datetime-local" name="trial_ends_at" class="form-control form-control-sm"
                               value="<?= $client['trial_ends_at'] ? date('Y-m-d\TH:i', strtotime($client['trial_ends_at'])) : '' ?>"
                               style="border-radius:8px;">
                    </div>
                </div>

                <hr class="my-3">
                <h6 class="mb-2"><i class="bi bi-key me-1"></i>Cambiar contraseña <small class="text-muted fw-normal">(dejar vacío = no cambiar)</small></h6>
                <div class="col-md-6 mb-3">
                    <input type="password" name="new_password" class="form-control form-control-sm"
                           placeholder="Nueva contraseña (mín. 8 caracteres)" style="border-radius:8px;" autocomplete="new-password">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm" style="background:#6366f1;color:#fff;border-radius:8px;padding:7px 20px;font-weight:600;">
                        <i class="bi bi-check2 me-1"></i>Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats + actions -->
    <div class="col-lg-5 d-flex flex-column gap-3">
        <!-- Stats -->
        <div class="sa-detail-card">
            <h6><i class="bi bi-bar-chart me-1"></i>Estadísticas</h6>
            <div class="row g-2 text-center">
                <div class="col-6">
                    <div style="background:#f8fafc;border-radius:10px;padding:14px 8px;">
                        <div style="font-size:1.5rem;font-weight:700;color:#0f172a"><?= (int)($leadStats['total'] ?? 0) ?></div>
                        <div style="font-size:0.75rem;color:#64748b">Leads totales</div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:#f8fafc;border-radius:10px;padding:14px 8px;">
                        <div style="font-size:1.5rem;font-weight:700;color:#22c55e"><?= (int)($leadStats['won'] ?? 0) ?></div>
                        <div style="font-size:0.75rem;color:#64748b">Leads ganados</div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:#f8fafc;border-radius:10px;padding:14px 8px;">
                        <div style="font-size:1.5rem;font-weight:700;color:#6366f1"><?= (int)($msgStats['total'] ?? 0) ?></div>
                        <div style="font-size:0.75rem;color:#64748b">Mensajes totales</div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:#f8fafc;border-radius:10px;padding:14px 8px;">
                        <div style="font-size:1.5rem;font-weight:700;color:#f59e0b"><?= (int)($msgStats['human_handled'] ?? 0) ?></div>
                        <div style="font-size:0.75rem;color:#64748b">Por humano</div>
                    </div>
                </div>
            </div>
            <div class="mt-2 text-muted small text-center">
                Bot WhatsApp:
                <?php if ($client['bot_wa_status'] === 'connected'): ?>
                    <span style="color:#22c55e;font-weight:600"><i class="bi bi-circle-fill" style="font-size:0.55rem"></i> Conectado</span>
                <?php else: ?>
                    <span style="color:#94a3b8"><i class="bi bi-circle" style="font-size:0.55rem"></i> Desconectado</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Danger zone -->
        <div class="sa-detail-card" style="border-color:#fee2e2;">
            <h6 style="color:#dc2626;"><i class="bi bi-exclamation-triangle me-1"></i>Zona peligrosa</h6>
            <p class="text-muted small mb-3">Eliminar borra el cliente y <strong>todos sus datos</strong> (leads, mensajes, suscripciones). No se puede deshacer.</p>
            <form method="POST" action="<?= $base ?>/superadmin/clients/<?= $client['id'] ?>/delete"
                  onsubmit="return confirm('¿Seguro? Esto eliminará TODO el cliente y sus datos permanentemente.')">
                <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">
                <button type="submit" class="btn btn-sm btn-danger" style="border-radius:8px;">
                    <i class="bi bi-trash me-1"></i>Eliminar cliente
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Subscription history -->
<?php if ($subscriptions): ?>
<div class="sa-table-card mt-3">
    <div class="card-header-bar">
        <span><i class="bi bi-credit-card me-2 text-muted"></i>Historial de suscripciones</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Plan</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Período</th>
                    <th>Pagado</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscriptions as $sub): ?>
                <tr>
                    <td class="text-muted small"><?= $sub['id'] ?></td>
                    <td><?= ucfirst($sub['plan']) ?></td>
                    <td><?= $sub['currency'] ?> <?= number_format($sub['amount_cents'] / 100, 2) ?></td>
                    <td>
                        <?php
                        $sc = match($sub['status']) {
                            'active'    => 'badge-active',
                            'failed'    => 'badge-expired',
                            'cancelled' => 'badge-cancelled',
                            default     => 'badge-trial',
                        }; ?>
                        <span class="badge <?= $sc ?>"><?= $sub['status'] ?></span>
                    </td>
                    <td class="text-muted small">
                        <?= $sub['billing_period_start'] ? date('d/m/y', strtotime($sub['billing_period_start'])) : '—' ?>
                        → <?= $sub['billing_period_end'] ? date('d/m/y', strtotime($sub['billing_period_end'])) : '∞' ?>
                    </td>
                    <td class="text-muted small"><?= $sub['paid_at'] ? date('d/m/y', strtotime($sub['paid_at'])) : '—' ?></td>
                    <td class="text-muted small"><?= date('d/m/y H:i', strtotime($sub['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_foot.php'; ?>
