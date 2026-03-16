<?php
/**
 * mia/views/client/appointments.php
 *
 * Appointment list — upcoming and past bookings.
 * Variables: $client, $upcoming (Appointment[]), $past (Appointment[]), $tz, $base
 */
$pageTitle    = $pageTitle ?? 'Citas — Mia';
$pageTopTitle = 'Citas agendadas';
$activeNav    = 'appointments';

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<?php if (isset($_GET['cancelled'])): ?>
<div class="alert border-0 rounded-3 mb-4" style="background:rgba(220,53,69,0.1);color:#842029">
    <i class="bi bi-x-circle me-2"></i>Cita cancelada correctamente.
</div>
<?php endif; ?>

<!-- Header row -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0">Citas agendadas</h5>
        <p class="text-muted small mb-0">Próximas reservas de tus clientes via WhatsApp</p>
    </div>
    <a href="<?= $base ?>/dashboard/appointments/settings" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-gear me-1"></i>Configurar disponibilidad
    </a>
</div>

<?php if (empty($upcoming) && empty($past)): ?>
<div class="mc-table-card p-5 text-center text-muted">
    <i class="bi bi-calendar-x fs-1 d-block mb-3 opacity-25"></i>
    <p class="mb-1 fw-semibold">Aún no hay citas registradas</p>
    <p class="small">Cuando un cliente agende una cita por WhatsApp, aparecerá aquí.</p>
    <a href="<?= $base ?>/dashboard/appointments/settings" class="btn btn-sm btn-success mt-2"
       style="background:#25d366;border-color:#25d366">
        <i class="bi bi-gear me-1"></i>Configurar disponibilidad
    </a>
</div>
<?php else: ?>

<!-- ── Upcoming ───────────────────────────────────────────────────────────── -->
<?php if (!empty($upcoming)): ?>
<div class="mc-table-card mb-4">
    <div class="p-3 border-bottom d-flex align-items-center gap-2">
        <span class="badge bg-success" style="background:#25d366 !important">Próximas</span>
        <span class="text-muted small"><?= count($upcoming) ?> cita<?= count($upcoming) !== 1 ? 's' : '' ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted">
                    <th>Fecha y hora</th>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Notas</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($upcoming as $appt): ?>
            <tr>
                <td class="fw-semibold small"><?= htmlspecialchars($appt->formattedStart($tz)) ?></td>
                <td><?= htmlspecialchars($appt->contactName ?: '—') ?></td>
                <td class="small text-muted">
                    <?php if ($appt->phone): ?>
                    <a href="https://wa.me/<?= htmlspecialchars(ltrim($appt->phone, '+')) ?>" target="_blank"
                       class="text-decoration-none text-success">
                        <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($appt->phone) ?>
                    </a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <?php $badge = $appt->isConfirmed() ? 'bg-success' : 'bg-warning text-dark'; ?>
                    <span class="badge <?= $badge ?>"><?= htmlspecialchars(ucfirst($appt->status)) ?></span>
                </td>
                <td class="small text-muted"><?= htmlspecialchars($appt->notes ?? '') ?></td>
                <td class="text-end">
                    <form method="POST"
                          action="<?= $base ?>/dashboard/appointments/<?= $appt->id ?>/cancel"
                          onsubmit="return confirm('¿Cancelar esta cita?')">
                        <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-x-circle me-1"></i>Cancelar
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Past ───────────────────────────────────────────────────────────────── -->
<?php if (!empty($past)): ?>
<div class="mc-table-card">
    <div class="p-3 border-bottom">
        <span class="text-muted small fw-semibold">Historial de citas</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted">
                    <th>Fecha y hora</th>
                    <th>Cliente</th>
                    <th>Estado</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (array_slice($past, 0, 30) as $appt): ?>
            <tr class="text-muted">
                <td class="small"><?= htmlspecialchars($appt->formattedStart($tz)) ?></td>
                <td class="small"><?= htmlspecialchars($appt->contactName ?: '—') ?></td>
                <td>
                    <?php
                    $badge = match($appt->status) {
                        'completed' => 'bg-secondary',
                        'cancelled' => 'bg-danger',
                        default     => 'bg-light text-muted border',
                    };
                    ?>
                    <span class="badge <?= $badge ?>"><?= htmlspecialchars(ucfirst($appt->status)) ?></span>
                </td>
                <td class="small"><?= htmlspecialchars($appt->notes ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/_foot.php'; ?>
