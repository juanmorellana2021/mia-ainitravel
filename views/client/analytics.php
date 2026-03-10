<?php
/**
 * mia/views/client/analytics.php — Leads & messages analytics dashboard
 */
$base         = App::basePath();
$pageTitle    = 'Analíticas — Mia';
$pageTopTitle = 'Analíticas';
$activeNav    = 'analytics';

// Prepare chart data
$weekLabels  = array_column($data['leads_per_week'],   'label');
$weekCounts  = array_column($data['leads_per_week'],   'cnt');
$dayLabels   = array_column($data['messages_per_day'], 'label');
$dayCounts   = array_column($data['messages_per_day'], 'cnt');

// Status breakdown colours
$statusMap = [
    'new'         => ['label' => 'Nuevos',         'color' => '#25d366'],
    'interested'  => ['label' => 'Interesados',    'color' => '#0d6efd'],
    'demo'        => ['label' => 'Demo',           'color' => '#fd7e14'],
    'closed_won'  => ['label' => 'Cerrados ✓',     'color' => '#198754'],
    'closed_lost' => ['label' => 'Perdidos',       'color' => '#dc3545'],
];
$sLabels = $sData = $sColors = [];
foreach ($data['status_breakdown'] as $row) {
    $key = $row['status'];
    $sLabels[] = $statusMap[$key]['label'] ?? $key;
    $sData[]   = (int)$row['cnt'];
    $sColors[] = $statusMap[$key]['color'] ?? '#adb5bd';
}

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- ── Stat cards ──────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="mc-stat-card">
            <div class="stat-num text-primary"><?= $data['total_leads'] ?></div>
            <div class="stat-label"><i class="bi bi-people me-1"></i>Total leads</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="mc-stat-card">
            <div class="stat-num" style="color:#25d366"><?= $data['won_leads'] ?></div>
            <div class="stat-label"><i class="bi bi-trophy me-1"></i>Cerrados ganados</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="mc-stat-card">
            <div class="stat-num text-warning"><?= $data['conv_rate'] ?>%</div>
            <div class="stat-label"><i class="bi bi-graph-up me-1"></i>Tasa de conversión</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="mc-stat-card">
            <div class="stat-num text-info"><?= $data['avg_msgs'] ?></div>
            <div class="stat-label"><i class="bi bi-chat-dots me-1"></i>Msgs por lead</div>
        </div>
    </div>
</div>

<!-- ── Charts row 1 ────────────────────────────────────────────────────────── -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="mc-table-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart me-2 text-primary"></i>Leads por semana (últimas 8 semanas)</h6>
            <canvas id="chartWeekly" height="90"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mc-table-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart me-2 text-warning"></i>Estado de leads</h6>
            <canvas id="chartStatus" height="200"></canvas>
        </div>
    </div>
</div>

<!-- ── Charts row 2 ────────────────────────────────────────────────────────── -->
<div class="row g-4">
    <div class="col-12">
        <div class="mc-table-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-chat-dots me-2" style="color:#25d366"></i>Mensajes por día (últimos 14 días)</h6>
            <canvas id="chartMessages" height="60"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const wkLabels  = <?= json_encode(array_values($weekLabels))  ?>;
const wkData    = <?= json_encode(array_map('intval', array_values($weekCounts))) ?>;
const dayLabels = <?= json_encode(array_values($dayLabels))   ?>;
const dayData   = <?= json_encode(array_map('intval', array_values($dayCounts)))  ?>;
const stLabels  = <?= json_encode($sLabels) ?>;
const stData    = <?= json_encode($sData)   ?>;
const stColors  = <?= json_encode($sColors) ?>;

// Defaults
Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
Chart.defaults.color = '#6c757d';

// Weekly leads bar
new Chart(document.getElementById('chartWeekly'), {
    type: 'bar',
    data: {
        labels: wkLabels.length ? wkLabels : ['Sin datos'],
        datasets: [{
            label: 'Leads',
            data: wkLabels.length ? wkData : [0],
            backgroundColor: 'rgba(37,211,102,0.7)',
            borderRadius: 6,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Status doughnut
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: stLabels.length ? stLabels : ['Sin datos'],
        datasets: [{
            data:            stLabels.length ? stData   : [1],
            backgroundColor: stLabels.length ? stColors : ['#e9ecef'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } }
        },
        cutout: '65%'
    }
});

// Daily messages line
new Chart(document.getElementById('chartMessages'), {
    type: 'line',
    data: {
        labels: dayLabels.length ? dayLabels : ['Sin datos'],
        datasets: [{
            label: 'Mensajes',
            data: dayLabels.length ? dayData : [0],
            borderColor: '#25d366',
            backgroundColor: 'rgba(37,211,102,0.08)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#25d366',
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,.05)' } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php require __DIR__ . '/_foot.php'; ?>
