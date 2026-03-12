<?php
/**
 * mia/views/superadmin/analytics.php
 * Landing page analytics — sessions, pageviews, top pages, referrers, UTM, devices.
 */
$base         = App::basePath();
$pageTitle    = 'Analíticas — Superadmin Mia';
$pageTopTitle = 'Analíticas de la landing';
$activeNav    = 'analytics';

$sessions  = (int)($totals['sessions']       ?? 0);
$visitors  = (int)($totals['unique_visitors'] ?? 0);
$pvs       = (int)($totals['pageviews']       ?? 0);
$ctas      = (int)($totals['cta_clicks']      ?? 0);
$avgSec    = (float)($totals['avg_seconds']   ?? 0);
$ctr       = $sessions > 0 ? round($ctas / $sessions * 100, 1) : 0;

// Build a full days array for the chart (fill gaps with 0)
$dailyMap = [];
foreach ($daily as $row) $dailyMap[$row['day']] = (int)$row['cnt'];
$chartLabels = [];
$chartData   = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d/m', strtotime($d));
    $chartData[]   = $dailyMap[$d] ?? 0;
}

// Device totals
$devMap = [];
foreach ($devices as $r) $devMap[$r['device']] = (int)$r['cnt'];
$totalDev = max(1, array_sum($devMap));

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Period selector -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div style="font-size:0.85rem;color:#64748b;">
        Mostrando últimos <strong style="color:#e2e8f0"><?= $days ?> días</strong>
    </div>
    <div class="d-flex gap-2">
        <?php foreach ([7,14,30,60,90] as $d): ?>
        <a href="?days=<?= $d ?>"
           class="btn btn-sm <?= $days == $d ? '' : 'btn-outline-secondary' ?>"
           style="<?= $days == $d
               ? 'background:rgba(99,102,241,0.2);color:#818cf8;border:1px solid rgba(99,102,241,0.4);'
               : 'background:transparent;color:#64748b;border:1px solid rgba(255,255,255,0.1);'
           ?>border-radius:7px;font-size:0.78rem;padding:3px 12px;"><?= $d ?>d</a>
        <?php endforeach; ?>
    </div>
</div>

<!-- KPI row -->
<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['bi-eye',         'Pageviews',       number_format($pvs),       null],
        ['bi-people',      'Sesiones',        number_format($sessions),  null],
        ['bi-person',      'Visitantes únicos',number_format($visitors), null],
        ['bi-cursor-fill', 'Clicks CTA',      number_format($ctas),      null],
        ['bi-percent',     'CTR (CTA/sesión)', $ctr . '%',               null],
        ['bi-clock',       'Tiempo promedio',  ($avgSec > 0 ? $avgSec . 's' : '—'), null],
    ];
    foreach ($kpis as [$icon, $label, $value, $_]):
    ?>
    <div class="col-6 col-xl-2">
        <div class="sa-kpi-card">
            <div class="kpi-label"><i class="bi <?= $icon ?> me-1"></i><?= $label ?></div>
            <div class="kpi-value" style="font-size:1.6rem"><?= $value ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <!-- Daily pageviews chart -->
    <div class="col-12">
        <div class="sa-card p-3">
            <div style="font-size:0.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;">
                <i class="bi bi-graph-up me-1"></i>Pageviews diarios
            </div>
            <canvas id="pvChart" height="80"></canvas>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">

    <!-- Top pages -->
    <div class="col-md-6">
        <div class="sa-card p-3 h-100">
            <div style="font-size:0.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                <i class="bi bi-file-earmark-text me-1"></i>Páginas más visitadas
            </div>
            <?php if (empty($topPages)): ?>
            <div style="color:#475569;font-size:0.85rem;text-align:center;padding:20px 0;">Sin datos aún.</div>
            <?php else: ?>
            <?php $maxPv = max(1, (int)($topPages[0]['views'] ?? 1)); ?>
            <?php foreach ($topPages as $r): ?>
            <div style="margin-bottom:10px;">
                <div class="d-flex justify-content-between" style="font-size:0.82rem;margin-bottom:3px;">
                    <span style="color:#cbd5e1;font-family:monospace;font-size:0.78rem;"><?= htmlspecialchars($r['page'] ?: '/') ?></span>
                    <span style="color:#94a3b8;"><?= number_format((int)$r['views']) ?></span>
                </div>
                <div style="height:4px;background:rgba(255,255,255,0.06);border-radius:2px;">
                    <div style="height:4px;border-radius:2px;background:linear-gradient(90deg,#6366f1,#8b5cf6);width:<?= round((int)$r['views'] / $maxPv * 100) ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top referrers -->
    <div class="col-md-6">
        <div class="sa-card p-3 h-100">
            <div style="font-size:0.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                <i class="bi bi-box-arrow-in-right me-1"></i>Fuentes de tráfico (referrer)
            </div>
            <?php if (empty($topReferrers)): ?>
            <div style="color:#475569;font-size:0.85rem;text-align:center;padding:20px 0;">Sin datos aún.</div>
            <?php else: ?>
            <?php $maxRef = max(1, (int)($topReferrers[0]['cnt'] ?? 1)); ?>
            <?php foreach ($topReferrers as $r): ?>
            <div style="margin-bottom:10px;">
                <div class="d-flex justify-content-between" style="font-size:0.82rem;margin-bottom:3px;">
                    <span style="color:#cbd5e1;font-size:0.78rem;max-width:75%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($r['ref']) ?>">
                        <?= htmlspecialchars($r['ref']) ?>
                    </span>
                    <span style="color:#94a3b8;"><?= number_format((int)$r['cnt']) ?></span>
                </div>
                <div style="height:4px;background:rgba(255,255,255,0.06);border-radius:2px;">
                    <div style="height:4px;border-radius:2px;background:linear-gradient(90deg,#0ea5e9,#06b6d4);width:<?= round((int)$r['cnt'] / $maxRef * 100) ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="row g-3 mb-4">

    <!-- UTM campaigns -->
    <div class="col-md-8">
        <div class="sa-card p-3">
            <div style="font-size:0.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                <i class="bi bi-megaphone me-1"></i>Campañas UTM
            </div>
            <?php if (empty($topUtm)): ?>
            <div style="color:#475569;font-size:0.85rem;text-align:center;padding:20px 0;">
                Sin tráfico con UTM aún. Añade <code style="color:#818cf8">?utm_source=facebook&amp;utm_medium=cpc&amp;utm_campaign=nombre</code> a tus enlaces.
            </div>
            <?php else: ?>
            <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                <thead>
                    <tr style="color:#475569;font-size:0.72rem;text-transform:uppercase;letter-spacing:.05em;">
                        <th style="padding:4px 8px;text-align:left;">Fuente</th>
                        <th style="padding:4px 8px;text-align:left;">Medio</th>
                        <th style="padding:4px 8px;text-align:left;">Campaña</th>
                        <th style="padding:4px 8px;text-align:right;">Sesiones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($topUtm as $r): ?>
                <tr style="border-top:1px solid rgba(255,255,255,0.05);">
                    <td style="padding:7px 8px;color:#e2e8f0;"><?= htmlspecialchars($r['src']) ?></td>
                    <td style="padding:7px 8px;color:#94a3b8;"><?= htmlspecialchars($r['med']) ?></td>
                    <td style="padding:7px 8px;color:#94a3b8;font-size:0.78rem;"><?= htmlspecialchars($r['camp']) ?></td>
                    <td style="padding:7px 8px;text-align:right;color:#cbd5e1;"><?= number_format((int)$r['cnt']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Device breakdown -->
    <div class="col-md-4">
        <div class="sa-card p-3">
            <div style="font-size:0.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                <i class="bi bi-phone me-1"></i>Dispositivos
            </div>
            <?php
            $devIcons  = ['desktop'=>'bi-display', 'mobile'=>'bi-phone', 'tablet'=>'bi-tablet'];
            $devColors = ['desktop'=>'#6366f1', 'mobile'=>'#22c55e', 'tablet'=>'#f59e0b'];
            foreach (['desktop','mobile','tablet'] as $dev):
                $cnt = $devMap[$dev] ?? 0;
                $pct = $totalDev > 0 ? round($cnt / $totalDev * 100) : 0;
            ?>
            <div style="margin-bottom:14px;">
                <div class="d-flex justify-content-between align-items-center" style="margin-bottom:5px;">
                    <span style="font-size:0.85rem;color:#cbd5e1;">
                        <i class="bi <?= $devIcons[$dev] ?> me-1" style="color:<?= $devColors[$dev] ?>"></i>
                        <?= ucfirst($dev) ?>
                    </span>
                    <span style="font-size:0.82rem;color:#94a3b8;"><?= $pct ?>% <span style="color:#475569;font-size:0.75rem;">(<?= $cnt ?>)</span></span>
                </div>
                <div style="height:6px;background:rgba(255,255,255,0.06);border-radius:3px;">
                    <div style="height:6px;border-radius:3px;background:<?= $devColors[$dev] ?>;width:<?= $pct ?>%;opacity:0.8"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode($chartLabels) ?>;
const data   = <?= json_encode($chartData) ?>;
new Chart(document.getElementById('pvChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Pageviews',
            data: data,
            backgroundColor: 'rgba(99,102,241,0.4)',
            borderColor: '#6366f1',
            borderWidth: 1,
            borderRadius: 3,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color:'#64748b', font:{size:10} }, grid: { color:'rgba(255,255,255,0.04)' } },
            y: { ticks: { color:'#64748b', font:{size:10} }, grid: { color:'rgba(255,255,255,0.05)' }, beginAtZero:true }
        }
    }
});
</script>

<?php require __DIR__ . '/_foot.php'; ?>
