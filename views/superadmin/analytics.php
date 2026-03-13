<?php
/**
 * mia/views/superadmin/analytics.php
 */
$base         = App::basePath();
$pageTitle    = 'Analíticas — Superadmin Mia';
$pageTopTitle = 'Analíticas';
$activeNav    = 'analytics';

$sessions  = (int)($totals['sessions']       ?? 0);
$visitors  = (int)($totals['unique_visitors'] ?? 0);
$pvs       = (int)($totals['pageviews']       ?? 0);
$ctas      = (int)($totals['cta_clicks']      ?? 0);
$avgSec    = (float)($totals['avg_seconds']   ?? 0);
$bounces   = (int)($totals['bounces']         ?? 0);
$ctr       = $sessions > 0 ? round($ctas / $sessions * 100, 1) : 0;
$bounceRate = $sessions > 0 ? round($bounces / $sessions * 100) : 0;

// Build full days arrays for chart (fill gaps)
$dailyPvMap  = [];
$dailyCtaMap = [];
foreach ($daily as $row) {
    $dailyPvMap[$row['day']]  = (int)$row['pvs'];
    $dailyCtaMap[$row['day']] = (int)$row['ctas'];
}
$waTrendMap = [];
foreach ($waTrend as $row) $waTrendMap[$row['day']] = (int)$row['cnt'];

$chartLabels  = [];
$chartPvData  = [];
$chartCtaData = [];
$chartWaData  = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[]  = date('d/m', strtotime($d));
    $chartPvData[]  = $dailyPvMap[$d]  ?? 0;
    $chartCtaData[] = $dailyCtaMap[$d] ?? 0;
    $chartWaData[]  = $waTrendMap[$d]  ?? 0;
}

// Device
$devPvMap  = [];
$devCtaMap = [];
foreach ($devices as $r) {
    $dev = $r['device'] ?: 'desktop';
    $devPvMap[$dev]  = (int)($r['pageviews']  ?? 0);
    $devCtaMap[$dev] = (int)($r['cta_clicks'] ?? 0);
}
$totalDevClicks = max(1, array_sum($devCtaMap));

// WhatsApp stats
$waTotalConvos  = (int)($waStats['total_conversations'] ?? 0);
$waEngaged      = (int)($waStats['engaged']             ?? 0);
$waLeads        = (int)($waStats['leads_captured']      ?? 0);
$waFull         = (int)($waStats['fully_captured']      ?? 0);
$waConvRate     = $waTotalConvos > 0 ? round($waLeads / $waTotalConvos * 100, 1) : 0;
$waEngageRate   = $waTotalConvos > 0 ? round($waEngaged / $waTotalConvos * 100)  : 0;

// Pipeline stages (friendly names)
$stageLabels = [
    'new'                     => 'Nuevos',
    'collecting_contact_name' => 'Dando nombre',
    'intro'                   => 'Intro',
    'qualifying_size'         => 'Calificando',
    'qualifying_method'       => 'Calificando metodo',
    'qualifying_pain'         => 'Pain point',
    'roi_pitch'               => 'ROI pitch',
    'demo'                    => 'Demo',
    'benefits'                => 'Beneficios',
    'closing'                 => 'Cierre',
    'collecting_name'         => 'Recogiendo nombre negocio',
    'collecting_email'        => 'Recogiendo email',
    'captured'                => 'Capturados',
];

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Period selector -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div style="font-size:0.85rem;color:#64748b;">
        Ultimos <strong style="color:#e2e8f0"><?= $days ?> dias</strong>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php foreach ([7,14,30,60,90] as $d): ?>
        <a href="?days=<?= $d ?>" class="btn btn-sm"
           style="<?= $days == $d ? 'background:rgba(99,102,241,0.2);color:#818cf8;border:1px solid rgba(99,102,241,0.4);' : 'background:transparent;color:#64748b;border:1px solid rgba(255,255,255,0.1);' ?>border-radius:7px;font-size:0.78rem;padding:3px 12px;"><?= $d ?>d</a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Landing KPIs -->
<div style="font-size:0.72rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">
    <i class="bi bi-globe me-1"></i>Landing Page
</div>
<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['bi-eye',         'Pageviews',         number_format($pvs),      null],
        ['bi-people',      'Sesiones',           number_format($sessions), null],
        ['bi-person',      'Visitantes unicos',  number_format($visitors), null],
        ['bi-cursor-fill', 'Clicks CTA',         number_format($ctas),    '#22c55e'],
        ['bi-percent',     'CTR',                $ctr.'%',                 $ctr>=3?'#22c55e':($ctr>=1?'#f59e0b':'#ef4444')],
        ['bi-skip-forward','Tasa rebote',         $bounceRate.'%',         $bounceRate<40?'#22c55e':($bounceRate<70?'#f59e0b':'#ef4444')],
        ['bi-clock',       'Tiempo promedio',    ($avgSec>0?$avgSec.'s':'--'), null],
    ];
    foreach ($kpis as [$icon,$label,$value,$color]):
    ?>
    <div class="col-6 col-sm-4 col-xl-3">
        <div class="sa-kpi-card">
            <div class="kpi-label"><i class="bi <?= $icon ?> me-1"></i><?= $label ?></div>
            <div class="kpi-value" style="font-size:1.55rem;<?= $color?"color:{$color}":'' ?>"><?= $value ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- WhatsApp Bot KPIs -->
<div style="font-size:0.72rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;margin-top:8px;">
    <i class="bi bi-whatsapp me-1" style="color:#25d366"></i>Bot de Ventas WhatsApp
</div>
<div class="row g-3 mb-4">
    <?php
    $waKpis = [
        ['bi-chat-dots',      'Conversaciones',   number_format($waTotalConvos), null],
        ['bi-chat-text',      'Enganchados',       $waEngaged.' ('.$waEngageRate.'%)', null],
        ['bi-person-check',   'Leads capturados',  number_format($waLeads),      '#22c55e'],
        ['bi-trophy',         'Completados',        number_format($waFull),       '#6366f1'],
        ['bi-arrow-up-right', 'Tasa conversion',    $waConvRate.'%',             $waConvRate>=10?'#22c55e':($waConvRate>=5?'#f59e0b':'#ef4444')],
    ];
    foreach ($waKpis as [$icon,$label,$value,$color]):
    ?>
    <div class="col-6 col-sm-4 col-xl-3">
        <div class="sa-kpi-card" style="border-left:3px solid rgba(37,211,102,0.3)">
            <div class="kpi-label"><i class="bi <?= $icon ?> me-1"></i><?= $label ?></div>
            <div class="kpi-value" style="font-size:1.55rem;<?= $color?"color:{$color}":'' ?>"><?= $value ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Dual chart -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="sa-card p-3">
            <div class="d-flex align-items-center gap-3 mb-3" style="font-size:0.78rem;color:#64748b;">
                <span><span style="display:inline-block;width:12px;height:3px;background:#6366f1;border-radius:2px;vertical-align:middle;margin-right:4px"></span>Pageviews</span>
                <span><span style="display:inline-block;width:12px;height:3px;background:#22c55e;border-radius:2px;vertical-align:middle;margin-right:4px"></span>Clicks CTA</span>
                <span><span style="display:inline-block;width:12px;height:3px;background:#25d366;border-radius:2px;vertical-align:middle;margin-right:4px"></span>Convos WA</span>
            </div>
            <canvas id="pvChart" height="70"></canvas>
        </div>
    </div>
</div>

<!-- Funnel + Pipeline -->
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="sa-card p-3 h-100">
            <div style="font-size:0.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;">
                <i class="bi bi-funnel me-1"></i>Embudo landing a lead
            </div>
            <?php
            $pageMap = [];
            foreach ($topPages as $r) $pageMap[$r['page']] = (int)$r['views'];
            $funnelSteps = [
                ['/', 'Visitaron homepage',    $pageMap['/'] ?? 0,        '#6366f1'],
                ['/pricing','Vieron precios',  $pageMap['/pricing'] ?? 0, '#8b5cf6'],
                ['/features','Vieron features',$pageMap['/features'] ?? 0,'#a78bfa'],
                ['cta','Click CTA',            $ctas,                     '#22c55e'],
                ['wa','Mensajearon WA',         $waTotalConvos,            '#25d366'],
                ['lead','Lead capturado',       $waLeads,                  '#10b981'],
            ];
            $funnelMax = max(1, $funnelSteps[0][2]);
            foreach ($funnelSteps as [$key,$label,$count,$color]):
                $pct = $count > 0 ? round($count / $funnelMax * 100) : 0;
            ?>
            <div style="margin-bottom:12px;">
                <div class="d-flex justify-content-between" style="font-size:0.82rem;margin-bottom:4px;">
                    <span style="color:#cbd5e1;"><?= $label ?></span>
                    <span style="color:#94a3b8;font-weight:600;"><?= number_format($count) ?> <span style="font-size:0.72rem;color:#475569;">(<?= $pct ?>%)</span></span>
                </div>
                <div style="height:8px;background:rgba(255,255,255,0.05);border-radius:4px;">
                    <div style="height:8px;border-radius:4px;background:<?= $color ?>;width:<?= $pct ?>%;opacity:0.85"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-md-7">
        <div class="sa-card p-3 h-100">
            <div style="font-size:0.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;">
                <i class="bi bi-diagram-3 me-1"></i>Pipeline bot
            </div>
            <?php if (empty($waPipeline)): ?>
            <div style="color:#475569;font-size:0.85rem;text-align:center;padding:20px 0;">Sin datos aun.</div>
            <?php else: ?>
            <?php $maxPipe = max(1, (int)($waPipeline[0]['cnt'] ?? 1)); ?>
            <?php foreach ($waPipeline as $r):
                $stageName = $stageLabels[$r['state']] ?? ucfirst(str_replace('_',' ',$r['state']));
                $cnt = (int)$r['cnt'];
                $pct = round($cnt / $maxPipe * 100);
                $isCapture = $r['state'] === 'captured';
            ?>
            <div style="margin-bottom:9px;">
                <div class="d-flex justify-content-between" style="font-size:0.81rem;margin-bottom:3px;">
                    <span style="color:<?= $isCapture?'#22c55e':'#cbd5e1' ?>;font-weight:<?= $isCapture?700:400 ?>;"><?= htmlspecialchars($stageName) ?></span>
                    <span style="color:#94a3b8;"><?= $cnt ?></span>
                </div>
                <div style="height:5px;background:rgba(255,255,255,0.05);border-radius:3px;">
                    <div style="height:5px;border-radius:3px;background:<?= $isCapture?'#22c55e':'#6366f1' ?>;width:<?= $pct ?>%;opacity:0.8"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Referrers + Pages + Devices -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="sa-card p-3 h-100">
            <div style="font-size:0.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                <i class="bi bi-box-arrow-in-right me-1"></i>Fuentes de trafico
            </div>
            <?php if (empty($topReferrers)): ?>
            <div style="color:#475569;font-size:0.85rem;text-align:center;padding:20px 0;">Sin datos.</div>
            <?php else: ?>
            <?php $maxRef = max(1, (int)($topReferrers[0]['cnt'] ?? 1)); ?>
            <?php foreach ($topReferrers as $r): ?>
            <div style="margin-bottom:10px;">
                <div class="d-flex justify-content-between" style="font-size:0.82rem;margin-bottom:3px;">
                    <span style="color:#cbd5e1;"><?= htmlspecialchars($r['ref']) ?></span>
                    <span style="color:#94a3b8;"><?= number_format((int)$r['cnt']) ?></span>
                </div>
                <div style="height:4px;background:rgba(255,255,255,0.06);border-radius:2px;">
                    <div style="height:4px;border-radius:2px;background:linear-gradient(90deg,#0ea5e9,#06b6d4);width:<?= round((int)$r['cnt']/$maxRef*100) ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sa-card p-3 h-100">
            <div style="font-size:0.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">
                <i class="bi bi-file-earmark-text me-1"></i>Paginas visitadas
            </div>
            <?php if (empty($topPages)): ?>
            <div style="color:#475569;font-size:0.85rem;text-align:center;padding:12px 0;">Sin datos.</div>
            <?php else: ?>
            <?php $maxPv = max(1, (int)($topPages[0]['views'] ?? 1)); ?>
            <?php foreach ($topPages as $r): ?>
            <div style="margin-bottom:8px;">
                <div class="d-flex justify-content-between" style="font-size:0.81rem;margin-bottom:2px;">
                    <span style="color:#cbd5e1;font-family:monospace;font-size:0.76rem;"><?= htmlspecialchars($r['page']?:'/') ?></span>
                    <span style="color:#94a3b8;"><?= number_format((int)$r['views']) ?></span>
                </div>
                <div style="height:4px;background:rgba(255,255,255,0.06);border-radius:2px;">
                    <div style="height:4px;border-radius:2px;background:linear-gradient(90deg,#6366f1,#8b5cf6);width:<?= round((int)$r['views']/$maxPv*100) ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sa-card p-3 h-100">
            <div style="font-size:0.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">
                <i class="bi bi-phone me-1"></i>Dispositivos
            </div>
            <?php
            $devIcons  = ['desktop'=>'bi-display','mobile'=>'bi-phone','tablet'=>'bi-tablet'];
            $devColors = ['desktop'=>'#6366f1','mobile'=>'#22c55e','tablet'=>'#f59e0b'];
            $totalDevPvs = max(1, array_sum($devPvMap));
            foreach (['mobile','desktop','tablet'] as $dev):
                $clicks = $devCtaMap[$dev] ?? 0;
                $views  = $devPvMap[$dev]  ?? 0;
                if (!$views) continue;
                $pvPct  = round($views / $totalDevPvs * 100);
                $devCtr = $views > 0 ? round($clicks / $views * 100, 1) : 0;
            ?>
            <div class="d-flex align-items-center gap-3 mb-3" style="font-size:0.82rem;">
                <i class="bi <?= $devIcons[$dev] ?>" style="color:<?= $devColors[$dev] ?>;min-width:16px"></i>
                <div style="flex:1">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="color:#cbd5e1;"><?= ucfirst($dev) ?></span>
                        <span style="color:#94a3b8;"><?= $pvPct ?>% &middot; CTR <?= $devCtr ?>%</span>
                    </div>
                    <div style="height:5px;background:rgba(255,255,255,0.06);border-radius:3px;">
                        <div style="height:5px;border-radius:3px;background:<?= $devColors[$dev] ?>;width:<?= $pvPct ?>%;opacity:0.8"></div>
                    </div>
                    <div style="font-size:0.72rem;color:#475569;margin-top:2px;"><?= $clicks ?> clicks / <?= $views ?> visitas</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- UTM Campaigns -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="sa-card p-3">
            <div style="font-size:0.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                <i class="bi bi-megaphone me-1"></i>Campanas UTM
            </div>
            <?php if (empty($topUtm)): ?>
            <div style="color:#475569;font-size:0.85rem;text-align:center;padding:12px 0;">
                Sin trafico con UTM. Agrega <code style="color:#818cf8">?utm_source=facebook&amp;utm_medium=cpc&amp;utm_campaign=nombre</code> a tus links de ads.
            </div>
            <?php else: ?>
            <div class="table-responsive">
            <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                <thead>
                    <tr style="color:#475569;font-size:0.72rem;text-transform:uppercase;letter-spacing:.05em;">
                        <th style="padding:4px 8px;text-align:left;">Fuente</th>
                        <th style="padding:4px 8px;text-align:left;">Medio</th>
                        <th style="padding:4px 8px;text-align:left;">Campana</th>
                        <th style="padding:4px 8px;text-align:right;">Sesiones</th>
                        <th style="padding:4px 8px;text-align:right;">Clicks CTA</th>
                        <th style="padding:4px 8px;text-align:right;">CTR</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($topUtm as $r):
                    $utmSessions = (int)$r['sessions'];
                    $utmCtas     = (int)$r['cta_clicks'];
                    $utmCtr      = $utmSessions > 0 ? round($utmCtas / $utmSessions * 100, 1) : 0;
                    $campDisplay = strlen($r['camp']) > 22 ? 'ID:'.substr($r['camp'],-8) : $r['camp'];
                ?>
                <tr style="border-top:1px solid rgba(255,255,255,0.05);">
                    <td style="padding:7px 8px;color:#e2e8f0;"><?= htmlspecialchars($r['src']) ?></td>
                    <td style="padding:7px 8px;color:#94a3b8;"><?= htmlspecialchars($r['med']?:'--') ?></td>
                    <td style="padding:7px 8px;color:#94a3b8;font-size:0.78rem;" title="<?= htmlspecialchars($r['camp']) ?>"><?= htmlspecialchars($campDisplay) ?></td>
                    <td style="padding:7px 8px;text-align:right;color:#cbd5e1;"><?= number_format($utmSessions) ?></td>
                    <td style="padding:7px 8px;text-align:right;color:#22c55e;font-weight:600;"><?= $utmCtas ?></td>
                    <td style="padding:7px 8px;text-align:right;color:<?= $utmCtr>=3?'#22c55e':'#94a3b8' ?>"><?= $utmCtr ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php if (!empty($topUtm[0]) && strlen($topUtm[0]['camp'] ?? '') > 12): ?>
            <div style="margin-top:10px;font-size:0.74rem;color:#475569;">
                <i class="bi bi-info-circle me-1"></i>Campanas muestran ID de Facebook. Para nombres legibles, usa <code style="color:#818cf8">utm_campaign=nombre</code> en tus anuncios.
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labels   = <?= json_encode($chartLabels) ?>;
const pvData   = <?= json_encode($chartPvData) ?>;
const ctaData  = <?= json_encode($chartCtaData) ?>;
const waData   = <?= json_encode($chartWaData) ?>;
new Chart(document.getElementById('pvChart'), {
    data: {
        labels,
        datasets: [
            { type:'bar',  label:'Pageviews', data:pvData,  backgroundColor:'rgba(99,102,241,0.35)', borderColor:'#6366f1', borderWidth:1, borderRadius:3, yAxisID:'y' },
            { type:'line', label:'CTA clicks', data:ctaData, borderColor:'#22c55e', backgroundColor:'rgba(34,197,94,0.1)', borderWidth:2, pointRadius:4, pointBackgroundColor:'#22c55e', tension:0.3, fill:true, yAxisID:'y2' },
            { type:'line', label:'Convos WA', data:waData,  borderColor:'#25d366', borderDash:[4,3], borderWidth:2, pointRadius:3, pointBackgroundColor:'#25d366', tension:0.3, fill:false, yAxisID:'y2' },
        ]
    },
    options: {
        responsive:true,
        interaction:{ mode:'index', intersect:false },
        plugins:{
            legend:{display:false},
            tooltip:{ backgroundColor:'rgba(15,23,42,0.95)', titleColor:'#e2e8f0', bodyColor:'#94a3b8', borderColor:'rgba(255,255,255,0.08)', borderWidth:1 }
        },
        scales:{
            x: { ticks:{color:'#64748b',font:{size:10}}, grid:{color:'rgba(255,255,255,0.04)'} },
            y: { ticks:{color:'#64748b',font:{size:10}}, grid:{color:'rgba(255,255,255,0.05)'}, beginAtZero:true, position:'left' },
            y2:{ ticks:{color:'#64748b',font:{size:10}}, grid:{display:false}, beginAtZero:true, position:'right' },
        }
    }
});
</script>

<?php require __DIR__ . '/_foot.php'; ?>
