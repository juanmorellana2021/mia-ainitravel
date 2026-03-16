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

<?php
// Build appointment map keyed by local date (YYYY-MM-DD) for the JS calendar
$_calMap = [];
$_tzObj  = new DateTimeZone($tz);
foreach (array_merge($upcoming, $past) as $_appt) {
    try {
        $_dt   = new DateTimeImmutable($_appt->startsAt, new DateTimeZone('UTC'));
        $_dt   = $_dt->setTimezone($_tzObj);
        $_key  = $_dt->format('Y-m-d');
        $_time = $_dt->format('H:i');
    } catch (\Throwable $_) {
        continue;
    }
    $_calMap[$_key][] = [
        'id'     => $_appt->id,
        'name'   => $_appt->contactName ?: 'Cliente',
        'time'   => $_time,
        'status' => $_appt->status,
    ];
}
?>

<!-- ── Calendar ─────────────────────────────────────────────────────────── -->
<div class="mc-table-card mb-4">
    <div class="card-header-bar d-flex align-items-center justify-content-between">
        <span><i class="bi bi-calendar3 me-2 text-muted"></i>Calendario de citas</span>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary px-2 py-1" id="cal-prev" title="Mes anterior">
                <i class="bi bi-chevron-left"></i>
            </button>
            <span id="cal-month-label" class="fw-semibold small" style="min-width:130px;text-align:center"></span>
            <button class="btn btn-sm btn-outline-secondary px-2 py-1" id="cal-next" title="Mes siguiente">
                <i class="bi bi-chevron-right"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary px-2 py-1 ms-1" id="cal-today" title="Hoy">
                Hoy
            </button>
        </div>
    </div>

    <!-- Day-of-week header -->
    <div class="px-3 pt-3">
        <div class="row g-0 text-center mb-1" id="cal-dow-header">
            <?php foreach (['Lu','Ma','Mi','Ju','Vi','Sa','Do'] as $_d): ?>
            <div class="col" style="font-size:.72rem;font-weight:600;color:#6c757d;text-transform:uppercase;letter-spacing:.04em">
                <?= $_d ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="cal-grid" class="mb-2"></div>
    </div>

    <!-- Legend -->
    <div class="px-3 pb-3 d-flex align-items-center gap-3 flex-wrap" style="font-size:.75rem;color:#6c757d">
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#25d366;margin-right:4px"></span>Confirmada</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#ffc107;margin-right:4px"></span>Pendiente</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#6c757d;margin-right:4px"></span>Pasada</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#dc3545;margin-right:4px"></span>Cancelada</span>
    </div>

    <!-- Day detail panel (shown when a day is clicked) -->
    <div id="cal-detail" class="border-top px-3 py-3" style="display:none"></div>
</div>

<script>
(function () {
    const apptMap = <?= json_encode($_calMap, JSON_HEX_TAG) ?>;
    const today   = new Date();
    let   curYear = today.getFullYear();
    let   curMonth= today.getMonth(); // 0-based

    const MONTHS_ES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    function pad(n){ return String(n).padStart(2,'0'); }

    function statusColor(s){
        if (s === 'confirmed') return '#25d366';
        if (s === 'cancelled') return '#dc3545';
        if (s === 'completed') return '#6c757d';
        return '#ffc107'; // pending
    }

    function render(year, month) {
        document.getElementById('cal-month-label').textContent =
            MONTHS_ES[month] + ' ' + year;

        // First day of month (ISO week: Monday=0)
        const firstDay = new Date(year, month, 1);
        let startOffset = firstDay.getDay(); // Sun=0
        startOffset = (startOffset === 0) ? 6 : startOffset - 1; // shift to Mon=0

        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const todayKey = `${today.getFullYear()}-${pad(today.getMonth()+1)}-${pad(today.getDate())}`;

        let html = '';
        let col = 0;

        // Open first row
        html += '<div class="row g-1">';

        // Empty cells before day 1
        for (let i = 0; i < startOffset; i++) {
            html += '<div class="col" style="min-height:52px"></div>';
            col++;
        }

        for (let day = 1; day <= daysInMonth; day++) {
            if (col > 0 && col % 7 === 0) {
                html += '</div><div class="row g-1">';
            }

            const key = `${year}-${pad(month+1)}-${pad(day)}`;
            const appts = apptMap[key] || [];
            const isToday = key === todayKey;
            const isPast  = new Date(year, month, day) < new Date(today.getFullYear(), today.getMonth(), today.getDate());

            let dots = '';
            appts.forEach(function(a) {
                dots += `<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:${statusColor(a.status)};margin:0 1px"></span>`;
            });

            const cellBg   = isToday ? '#25d366' : (isPast ? '#f5f5f5' : '#f8fafc');
            const cellColor= isToday ? '#fff'    : (isPast && appts.length === 0 ? '#adb5bd' : '#212529');
            const cellBorder= appts.length > 0 && !isToday ? '2px solid rgba(37,211,102,0.6)' : '1px solid #e9ecef';
            const cursor   = appts.length > 0 ? 'pointer' : 'default';

            html += `<div class="col text-center py-1 rounded-2"
                          style="min-height:52px;background:${cellBg};border:${cellBorder};cursor:${cursor};transition:background .15s"
                          ${appts.length > 0 ? `onclick="showDay('${key}')"` : ''}
                          onmouseover="this.style.opacity=appts?'0.85':'1'"
                          id="cal-cell-${key}">
                        <div style="font-size:.8rem;font-weight:${isToday?'700':'500'};color:${cellColor}">${day}</div>
                        <div style="margin-top:2px">${dots}</div>
                    </div>`;
            col++;
        }

        // Fill remaining cells
        const remaining = (7 - (col % 7)) % 7;
        for (let i = 0; i < remaining; i++) {
            html += '<div class="col" style="min-height:52px"></div>';
        }
        html += '</div>';

        document.getElementById('cal-grid').innerHTML = html;
        document.getElementById('cal-detail').style.display = 'none';
    }

    window.showDay = function(key) {
        const appts = apptMap[key] || [];
        if (!appts.length) return;

        const [y, m, d] = key.split('-');
        const dateLabel = `${d}/${m}/${y}`;

        let rows = appts.map(function(a) {
            const color = statusColor(a.status);
            const statusLabel = {confirmed:'Confirmada',pending:'Pendiente',
                                 cancelled:'Cancelada',completed:'Completada'}[a.status] || a.status;
            const cancelBtn = (a.status !== 'cancelled' && a.status !== 'completed')
                ? `<form method="POST" action="<?= $base ?>/dashboard/appointments/${a.id}/cancel"
                        style="display:inline" onsubmit="return confirm('¿Cancelar esta cita?')">
                        <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size:.72rem">
                            <i class="bi bi-x-circle me-1"></i>Cancelar
                        </button>
                   </form>`
                : '';
            return `<tr>
                <td class="small fw-semibold">${a.time}</td>
                <td class="small">${a.name}</td>
                <td><span class="badge" style="background:${color};font-size:.72rem">${statusLabel}</span></td>
                <td>${cancelBtn}</td>
            </tr>`;
        }).join('');

        document.getElementById('cal-detail').innerHTML =
            `<div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-calendar-event text-success"></i>
                <span class="fw-semibold small">Citas del ${dateLabel}</span>
                <button type="button" class="btn-close ms-auto" style="font-size:.7rem" onclick="document.getElementById('cal-detail').style.display='none'"></button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.85rem">
                    <thead class="table-light"><tr>
                        <th>Hora</th><th>Cliente</th><th>Estado</th><th></th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;
        document.getElementById('cal-detail').style.display = 'block';
    };

    document.getElementById('cal-prev').addEventListener('click', function() {
        curMonth--; if (curMonth < 0) { curMonth = 11; curYear--; }
        render(curYear, curMonth);
    });
    document.getElementById('cal-next').addEventListener('click', function() {
        curMonth++; if (curMonth > 11) { curMonth = 0; curYear++; }
        render(curYear, curMonth);
    });
    document.getElementById('cal-today').addEventListener('click', function() {
        curYear = today.getFullYear(); curMonth = today.getMonth();
        render(curYear, curMonth);
    });

    // Initial render
    render(curYear, curMonth);
})();
</script>

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
