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

// ── Marketing Intelligence — build insights array from live data ──────────────
$insights = [];   // ['type'=>'good|warn|bad', 'icon'=>'...', 'text'=>'...', 'action'=>'...']

// 1. Traffic volume
if ($pvs === 0) {
    $insights[] = ['bad',  'bi-wifi-off',       'Sin pageviews en el periodo. Verifica que el script de analytics está instalado en el landing.', 'Revisa analytics.js en la landing page.'];
} elseif ($pvs < 50) {
    $insights[] = ['warn', 'bi-bar-chart',       "Solo {$pvs} pageviews en {$days} días — el tráfico es muy bajo.", 'Aumenta el presupuesto de ads o lanza una campaña orgánica (reels, posts).'];
} else {
    $insights[] = ['good', 'bi-bar-chart-fill',  number_format($pvs)." pageviews en {$days} días — buen nivel de tráfico.", null];
}

// 2. CTR
if ($sessions > 0 && $ctr < 1) {
    $insights[] = ['bad',  'bi-cursor',          "CTR del {$ctr}% — casi nadie que llega hace click en el CTA.", 'Prueba cambiar el texto/color del botón CTA. Muévelo más arriba en el landing (above the fold). A/B test "Empieza gratis" vs "Ver demo".'];
} elseif ($sessions > 0 && $ctr < 3) {
    $insights[] = ['warn', 'bi-cursor',          "CTR del {$ctr}% — está por debajo del 3% de referencia para SaaS.", 'Refuerza el headline del landing. Agrega 1-2 testimonios o logos de clientes cerca del CTA.'];
} elseif ($sessions > 0) {
    $insights[] = ['good', 'bi-cursor-fill',     "CTR del {$ctr}% — buen ratio de conversión en el landing.", null];
}

// 3. Bounce rate
if ($bounceRate > 70) {
    $insights[] = ['bad',  'bi-door-open',       "Tasa de rebote del {$bounceRate}% — la mayoría se va sin explorar.", 'El landing carga lento o no es relevante para el tráfico. Mejora la velocidad (PageSpeed) y asegúrate de que el mensaje del ad coincida con el headline.'];
} elseif ($bounceRate > 45) {
    $insights[] = ['warn', 'bi-door-open',       "Rebote del {$bounceRate}% — aceptable pero mejorable.", 'Agrega una sección de "Cómo funciona" o video demo en los primeros 2 scrolls.'];
}

// 4. Traffic source diversity
$fbOnly = false;
if (!empty($topReferrers)) {
    $topSource = strtolower($topReferrers[0]['ref'] ?? '');
    $topCnt    = (int)($topReferrers[0]['cnt'] ?? 0);
    $totalRef  = array_sum(array_column($topReferrers, 'cnt'));
    if ($totalRef > 0 && $topCnt / $totalRef > 0.85 && str_contains($topSource, 'facebook')) {
        $fbOnly = true;
        $insights[] = ['warn', 'bi-facebook',    "El ".round($topCnt/$totalRef*100)."% del tráfico viene solo de Facebook — dependencia de un único canal.", 'Diversifica: SEO (blog con casos de uso), Google Ads, LinkedIn para B2B, o email marketing.'];
    }
}

// 5. UTM tracking quality
$hasUtm = !empty($topUtm);
if (!$hasUtm && $pvs > 10) {
    $insights[] = ['warn', 'bi-tags',            'No hay datos UTM. No sabes qué campaña genera más leads.', 'Agrega ?utm_source=facebook&utm_medium=cpc&utm_campaign=nombre a los links de tus anuncios.'];
} elseif ($hasUtm && strlen($topUtm[0]['camp'] ?? '') > 15 && is_numeric(str_replace(' ','',$topUtm[0]['camp']??''))) {
    $insights[] = ['warn', 'bi-tags',            'Las campañas muestran IDs numéricos de Facebook en vez de nombres legibles.', 'Edita cada anuncio en Meta Ads y pon utm_campaign=nombre-legible en la URL de destino.'];
}

// 6. WA funnel — engagement rate
if ($waTotalConvos > 0 && $waEngageRate < 30) {
    $insights[] = ['bad',  'bi-whatsapp',        "Solo el {$waEngageRate}% de quienes abren el chat siguen la conversación.", 'El mensaje de bienvenida puede ser muy genérico. Prueba abrir con una pregunta directa: "¿Cuántos hoteles administras actualmente?"'];
} elseif ($waTotalConvos > 0 && $waEngageRate >= 30) {
    $insights[] = ['good', 'bi-whatsapp',        "Engagement del bot al {$waEngageRate}% — los prospectos muestran interes.", null];
}

// 7. WA conversion rate (leads / convos)
if ($waTotalConvos > 0 && $waConvRate < 5) {
    $insights[] = ['bad',  'bi-person-x',        "Solo el {$waConvRate}% de conversaciones termina en lead capturado.", 'El bot puede estar perdiendo prospectos antes de pedir el email. Revisa en qué etapa se abandonan más en el pipeline.'];
} elseif ($waTotalConvos > 0 && $waConvRate >= 10) {
    $insights[] = ['good', 'bi-person-check',    "Tasa de captura del {$waConvRate}% — el bot está convirtiendo bien.", null];
}

// 8. Mobile vs Desktop split
$mobilePvs  = $devPvMap['mobile']  ?? 0;
$desktopPvs = $devPvMap['desktop'] ?? 0;
if ($mobilePvs > 0 && $desktopPvs > 0) {
    $mobilePct = round($mobilePvs / ($mobilePvs + $desktopPvs) * 100);
    if ($mobilePct > 65) {
        $mobileCtr = $mobilePvs > 0 ? round(($devCtaMap['mobile'] ?? 0) / $mobilePvs * 100, 1) : 0;
        $deskCtr   = $desktopPvs > 0 ? round(($devCtaMap['desktop'] ?? 0) / $desktopPvs * 100, 1) : 0;
        if ($mobileCtr < $deskCtr * 0.5) {
            $insights[] = ['warn', 'bi-phone',   "{$mobilePct}% del tráfico es móvil pero el CTR móvil ({$mobileCtr}%) es mucho menor que desktop ({$deskCtr}%).", 'Optimiza el CTA para móvil: botón grande, full-width, sticky en el footer. Revisa velocidad en 3G.'];
        }
    }
}

// 9. No traffic at all
if ($pvs > 0 && $waTotalConvos === 0) {
    $insights[] = ['warn', 'bi-chat-square-x',   'Hay tráfico en el landing pero nadie ha iniciado conversación con el bot.', 'Revisa que el link de WhatsApp funcione. Considera agregar el chat widget directamente en el landing.'];
}

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Period selector -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div style="font-size:0.85rem;color:#94a3b8;">
        Ultimos <strong style="color:#e2e8f0"><?= $days ?> dias</strong>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php foreach ([7,14,30,60,90] as $d): ?>
        <a href="?days=<?= $d ?>" class="btn btn-sm"
           style="<?= $days == $d ? 'background:rgba(99,102,241,0.2);color:#818cf8;border:1px solid rgba(99,102,241,0.4);' : 'background:transparent;color:#94a3b8;border:1px solid rgba(255,255,255,0.1);' ?>border-radius:7px;font-size:0.78rem;padding:3px 12px;"><?= $d ?>d</a>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── Marketing Intelligence ───────────────────────────────────────────── -->
<?php if (!empty($insights)): ?>
<div class="sa-card p-4 mb-4" style="border:1px solid rgba(99,102,241,0.25);background:linear-gradient(135deg,rgba(99,102,241,0.06) 0%,rgba(15,23,42,0.0) 100%);">
    <div class="d-flex align-items-center gap-2 mb-3">
        <div style="width:32px;height:32px;border-radius:8px;background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-lightbulb" style="color:#818cf8;font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-size:0.9rem;font-weight:700;color:#e2e8f0;">Diagnóstico de marketing</div>
            <div style="font-size:0.74rem;color:#94a3b8;">Basado en los datos de los últimos <?= $days ?> días</div>
        </div>
        <div class="ms-auto d-flex gap-1 flex-wrap" style="font-size:0.7rem;">
            <?php
            $goodCnt = count(array_filter($insights, fn($i) => $i[0] === 'good'));
            $warnCnt = count(array_filter($insights, fn($i) => $i[0] === 'warn'));
            $badCnt  = count(array_filter($insights, fn($i) => $i[0] === 'bad'));
            ?>
            <?php if ($goodCnt): ?><span style="padding:2px 8px;border-radius:12px;background:rgba(34,197,94,0.12);color:#22c55e;font-weight:600;"><?= $goodCnt ?> ok</span><?php endif; ?>
            <?php if ($warnCnt): ?><span style="padding:2px 8px;border-radius:12px;background:rgba(245,158,11,0.12);color:#f59e0b;font-weight:600;"><?= $warnCnt ?> aviso</span><?php endif; ?>
            <?php if ($badCnt):  ?><span style="padding:2px 8px;border-radius:12px;background:rgba(239,68,68,0.12);color:#ef4444;font-weight:600;"><?= $badCnt ?> critico</span><?php endif; ?>
        </div>
    </div>

    <div class="row g-2">
    <?php foreach ($insights as [$type, $icon, $text, $action]): ?>
    <?php
        $colors = [
            'good' => ['#166534','#dcfce7','#bbf7d0','#15803d'],
            'warn' => ['#92400e','#fef3c7','#fde68a','#b45309'],
            'bad'  => ['#991b1b','#fee2e2','#fecaca','#dc2626'],
        ];
        [$textCol, $bg, $border, $accentCol] = $colors[$type];
        $iconColors = ['good'=>'#16a34a','warn'=>'#d97706','bad'=>'#dc2626'];
        $iconCol = $iconColors[$type];
    ?>
    <div class="col-12 col-lg-6">
        <div style="background:<?= $bg ?>;border:1px solid <?= $border ?>;border-radius:10px;padding:14px 16px;">
            <div class="d-flex gap-2">
                <i class="bi <?= $icon ?>" style="color:<?= $iconCol ?>;font-size:1.15rem;flex-shrink:0;margin-top:2px;"></i>
                <div>
                    <div style="font-size:0.92rem;color:<?= $textCol ?>;line-height:1.5;font-weight:500;"><?= $text ?></div>
                    <?php if ($action): ?>
                    <div style="font-size:0.84rem;color:<?= $accentCol ?>;margin-top:6px;font-weight:600;">
                        <i class="bi bi-arrow-right me-1"></i><?= $action ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

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
            <div class="d-flex align-items-center gap-3 mb-3" style="font-size:0.78rem;color:#94a3b8;">
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
            <div style="font-size:0.78rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;">
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
                    <span style="color:#f1f5f9;"><?= $label ?></span>
                    <span style="color:#cbd5e1;font-weight:600;"><?= number_format($count) ?> <span style="font-size:0.72rem;color:#94a3b8;">(<?= $pct ?>%)</span></span>
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
            <div style="font-size:0.78rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;">
                <i class="bi bi-diagram-3 me-1"></i>Pipeline bot
            </div>
            <?php if (empty($waPipeline)): ?>
            <div style="color:#94a3b8;font-size:0.85rem;text-align:center;padding:20px 0;">Sin datos aun.</div>
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
                    <span style="color:#cbd5e1;"><?= $cnt ?></span>
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
            <div style="font-size:0.78rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                <i class="bi bi-box-arrow-in-right me-1"></i>Fuentes de trafico
            </div>
            <?php if (empty($topReferrers)): ?>
            <div style="color:#94a3b8;font-size:0.85rem;text-align:center;padding:20px 0;">Sin datos.</div>
            <?php else: ?>
            <?php $maxRef = max(1, (int)($topReferrers[0]['cnt'] ?? 1)); ?>
            <?php foreach ($topReferrers as $r): ?>
            <div style="margin-bottom:10px;">
                <div class="d-flex justify-content-between" style="font-size:0.82rem;margin-bottom:3px;">
                    <span style="color:#f1f5f9;"><?= htmlspecialchars($r['ref']) ?></span>
                    <span style="color:#cbd5e1;"><?= number_format((int)$r['cnt']) ?></span>
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
            <div style="font-size:0.78rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">
                <i class="bi bi-file-earmark-text me-1"></i>Paginas visitadas
            </div>
            <?php if (empty($topPages)): ?>
            <div style="color:#94a3b8;font-size:0.85rem;text-align:center;padding:12px 0;">Sin datos.</div>
            <?php else: ?>
            <?php $maxPv = max(1, (int)($topPages[0]['views'] ?? 1)); ?>
            <?php foreach ($topPages as $r): ?>
            <div style="margin-bottom:8px;">
                <div class="d-flex justify-content-between" style="font-size:0.81rem;margin-bottom:2px;">
                    <span style="color:#f1f5f9;font-family:monospace;font-size:0.76rem;"><?= htmlspecialchars($r['page']?:'/') ?></span>
                    <span style="color:#cbd5e1;"><?= number_format((int)$r['views']) ?></span>
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
            <div style="font-size:0.78rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">
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
                        <span style="color:#f1f5f9;"><?= ucfirst($dev) ?></span>
                        <span style="color:#cbd5e1;"><?= $pvPct ?>% &middot; CTR <?= $devCtr ?>%</span>
                    </div>
                    <div style="height:5px;background:rgba(255,255,255,0.06);border-radius:3px;">
                        <div style="height:5px;border-radius:3px;background:<?= $devColors[$dev] ?>;width:<?= $pvPct ?>%;opacity:0.8"></div>
                    </div>
                    <div style="font-size:0.72rem;color:#94a3b8;margin-top:2px;"><?= $clicks ?> clicks / <?= $views ?> visitas</div>
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
            <div style="font-size:0.78rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                <i class="bi bi-megaphone me-1"></i>Campanas UTM
            </div>
            <?php if (empty($topUtm)): ?>
            <div style="color:#94a3b8;font-size:0.85rem;text-align:center;padding:12px 0;">
                Sin trafico con UTM. Agrega <code style="color:#818cf8">?utm_source=facebook&amp;utm_medium=cpc&amp;utm_campaign=nombre</code> a tus links de ads.
            </div>
            <?php else: ?>
            <div class="table-responsive">
            <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                <thead>
                    <tr style="color:#94a3b8;font-size:0.72rem;text-transform:uppercase;letter-spacing:.05em;">
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
                    <td style="padding:7px 8px;color:#cbd5e1;"><?= htmlspecialchars($r['med']?:'--') ?></td>
                    <td style="padding:7px 8px;color:#cbd5e1;font-size:0.78rem;" title="<?= htmlspecialchars($r['camp']) ?>"><?= htmlspecialchars($campDisplay) ?></td>
                    <td style="padding:7px 8px;text-align:right;color:#f1f5f9;"><?= number_format($utmSessions) ?></td>
                    <td style="padding:7px 8px;text-align:right;color:#22c55e;font-weight:600;"><?= $utmCtas ?></td>
                    <td style="padding:7px 8px;text-align:right;color:<?= $utmCtr>=3?'#22c55e':'#94a3b8' ?>"><?= $utmCtr ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php if (!empty($topUtm[0]) && strlen($topUtm[0]['camp'] ?? '') > 12): ?>
            <div style="margin-top:10px;font-size:0.74rem;color:#94a3b8;">
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

