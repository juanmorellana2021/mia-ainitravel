<?php
/**
 * mia/views/client/appointment_settings.php
 *
 * Configure availability: slot duration, buffer, days/times.
 * Variables: $client, $avail (Availability|null), $saved, $base
 */
$pageTitle    = $pageTitle ?? 'Config. Citas — Mia';
$pageTopTitle = 'Configurar disponibilidad';
$activeNav    = 'appointments';

$slotMin = $avail?->slotMinutes   ?? 60;
$bufMin  = $avail?->bufferMinutes ?? 0;
$maxDays = $avail?->maxDaysAhead  ?? 14;
$tz      = $avail?->timezone      ?? 'America/Lima';
$sch     = $avail?->schedule      ?? [];

$defaultSch = [
    'mon' => ['enabled'=>true, 'open'=>'09:00','close'=>'18:00'],
    'tue' => ['enabled'=>true, 'open'=>'09:00','close'=>'18:00'],
    'wed' => ['enabled'=>true, 'open'=>'09:00','close'=>'18:00'],
    'thu' => ['enabled'=>true, 'open'=>'09:00','close'=>'18:00'],
    'fri' => ['enabled'=>true, 'open'=>'09:00','close'=>'17:00'],
    'sat' => ['enabled'=>false,'open'=>'09:00','close'=>'13:00'],
    'sun' => ['enabled'=>false,'open'=>'','close'=>''],
];
$sch = array_merge($defaultSch, $sch);

$days = [
    'mon'=>'Lunes','tue'=>'Martes','wed'=>'Miércoles',
    'thu'=>'Jueves','fri'=>'Viernes','sat'=>'Sábado','sun'=>'Domingo',
];

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<?php if ($saved): ?>
<div class="alert border-0 rounded-3 mb-4" style="background:rgba(37,211,102,0.12);color:#155724">
    <i class="bi bi-check-circle me-2"></i><strong>Disponibilidad guardada.</strong>
</div>
<?php endif; ?>

<div class="mb-4 d-flex align-items-center gap-3">
    <a href="<?= $base ?>/dashboard/appointments" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver a citas
    </a>
    <div>
        <h5 class="fw-bold mb-0">Configurar disponibilidad</h5>
        <p class="text-muted small mb-0">Define cuándo puedes atender citas</p>
    </div>
</div>

<form method="POST" action="<?= $base ?>/dashboard/appointments/settings/save">
    <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">

    <div class="row g-4">

        <!-- ── Slot settings ──────────────────────────────────────────────── -->
        <div class="col-md-6">
            <div class="mc-table-card p-4 h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-stopwatch me-2 text-primary"></i>Duración de citas</h6>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Duración por cita</label>
                    <select name="slot_minutes" class="form-select">
                        <?php foreach ([15,30,45,60,90,120] as $m): ?>
                        <option value="<?= $m ?>" <?= $slotMin === $m ? 'selected' : '' ?>>
                            <?= $m ?> minutos
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Tiempo de descanso entre citas</label>
                    <select name="buffer_minutes" class="form-select">
                        <?php foreach ([0,10,15,30] as $m): ?>
                        <option value="<?= $m ?>" <?= $bufMin === $m ? 'selected' : '' ?>>
                            <?= $m === 0 ? 'Sin descanso' : "{$m} minutos" ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-semibold text-muted">Máximo de días de anticipación</label>
                    <select name="max_days_ahead" class="form-select">
                        <?php foreach ([7,14,30,60,90] as $d): ?>
                        <option value="<?= $d ?>" <?= $maxDays === $d ? 'selected' : '' ?>>
                            <?= $d ?> días
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Los clientes solo pueden agendar dentro de este rango.</div>
                </div>
            </div>
        </div>

        <!-- ── Timezone ───────────────────────────────────────────────────── -->
        <div class="col-md-6">
            <div class="mc-table-card p-4 h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-globe me-2 text-info"></i>Zona horaria y horario</h6>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Zona horaria</label>
                    <select name="timezone" class="form-select">
                        <?php
                        $tzGroups = [
                            'América del Sur'  => ['America/Lima','America/Bogota','America/Santiago','America/Buenos_Aires','America/La_Paz','America/Caracas','America/Guayaquil'],
                            'América Central'  => ['America/Mexico_City','America/Guatemala','America/Costa_Rica','America/Panama'],
                            'América del Norte'=> ['America/New_York','America/Chicago','America/Denver','America/Los_Angeles'],
                            'Europa'           => ['Europe/Madrid','Europe/London','Europe/Paris'],
                        ];
                        foreach ($tzGroups as $grpLabel => $tzList):
                        ?>
                        <optgroup label="<?= htmlspecialchars($grpLabel) ?>">
                            <?php foreach ($tzList as $tzOpt): ?>
                            <option value="<?= $tzOpt ?>" <?= $tz === $tzOpt ? 'selected' : '' ?>><?= $tzOpt ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <h6 class="fw-semibold small text-muted mb-2 mt-3">Días y horarios disponibles</h6>
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-muted small">
                            <th style="width:110px">Día</th>
                            <th style="width:55px" class="text-center">Activo</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($days as $slug => $label):
                        $d = $sch[$slug];
                    ?>
                    <tr>
                        <td class="small fw-semibold"><?= $label ?></td>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input appt-day-toggle"
                                   name="schedule_<?= $slug ?>_enabled" value="1"
                                   data-day="<?= $slug ?>"
                                   <?= !empty($d['enabled']) ? 'checked' : '' ?>>
                        </td>
                        <td>
                            <input type="time" name="schedule_<?= $slug ?>_open"
                                   class="form-control form-control-sm" style="max-width:110px"
                                   value="<?= htmlspecialchars($d['open'] ?? '09:00') ?>"
                                   <?= empty($d['enabled']) ? 'disabled' : '' ?>>
                        </td>
                        <td>
                            <input type="time" name="schedule_<?= $slug ?>_close"
                                   class="form-control form-control-sm" style="max-width:110px"
                                   value="<?= htmlspecialchars($d['close'] ?? '18:00') ?>"
                                   <?= empty($d['enabled']) ? 'disabled' : '' ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Save ──────────────────────────────────────────────────────── -->
        <div class="col-12">
            <button type="submit" class="btn btn-success px-4"
                    style="background:#25d366;border-color:#25d366">
                <i class="bi bi-check-circle me-2"></i>Guardar disponibilidad
            </button>
        </div>

    </div>
</form>

<script>
document.querySelectorAll('.appt-day-toggle').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var row    = this.closest('tr');
        var inputs = row.querySelectorAll('input[type=time]');
        inputs.forEach(function(inp) { inp.disabled = !cb.checked; });
    });
});
</script>

<?php require __DIR__ . '/_foot.php'; ?>
