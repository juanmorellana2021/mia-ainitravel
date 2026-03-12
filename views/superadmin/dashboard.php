<?php
$base         = App::basePath();
$pageTitle    = 'Dashboard — Superadmin Mia';
$pageTopTitle = 'Dashboard';
$activeNav    = 'dashboard';

$c         = $stats['clients'];
$mrr       = number_format($stats['mrr_cents'] / 100, 0);
$arr       = number_format($stats['mrr_cents'] / 100 * 12, 0);

function fmtPhone(string $p): string {
    $p = preg_replace('/@.*$/', '', $p);
    $p = ltrim($p, '+');
    return strlen($p) > 12 ? '+' . substr($p, 0, 7) . '…' . substr($p, -4) : '+' . $p;
}

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- KPI row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="sa-kpi-card">
            <div class="kpi-label"><i class="bi bi-people me-1"></i>Total clientes</div>
            <div class="kpi-value"><?= (int)$c['total'] ?></div>
            <div class="kpi-sub"><?= $stats['new_this_week'] ?> nuevos esta semana</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sa-kpi-card">
            <div class="kpi-label"><i class="bi bi-currency-dollar me-1"></i>MRR</div>
            <div class="kpi-value" style="color:#6366f1">S/ <?= $mrr ?></div>
            <div class="kpi-sub">ARR estimado: S/ <?= $arr ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sa-kpi-card">
            <div class="kpi-label"><i class="bi bi-chat-dots me-1"></i>Mensajes hoy</div>
            <div class="kpi-value"><?= $stats['today_messages'] ?></div>
            <div class="kpi-sub"><?= $stats['total_leads'] ?> leads totales en el sistema</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sa-kpi-card">
            <div class="kpi-label"><i class="bi bi-whatsapp me-1"></i>Bots conectados</div>
            <div class="kpi-value" style="color:#22c55e"><?= $stats['bot_connections'] ?></div>
            <div class="kpi-sub">de <?= (int)$c['total'] ?> clientes totales</div>
        </div>
    </div>
</div>

<!-- Plan breakdown + recent signups -->
<div class="row g-3 mb-4">
    <!-- Plan breakdown -->
    <div class="col-md-4">
        <div class="sa-detail-card h-100">
            <h6><i class="bi bi-pie-chart me-1"></i>Estado de planes</h6>
            <div class="d-flex flex-column gap-2">
                <?php
                $breakdown = [
                    ['label' => 'Prueba activa',  'val' => (int)$c['trials'],    'cls' => 'badge-trial'],
                    ['label' => 'Activos',         'val' => (int)$c['active'],    'cls' => 'badge-active'],
                    ['label' => 'Vencidos',        'val' => (int)$c['expired'],   'cls' => 'badge-expired'],
                    ['label' => 'Cancelados',      'val' => (int)$c['cancelled'], 'cls' => 'badge-cancelled'],
                ];
                foreach ($breakdown as $b): ?>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="badge <?= $b['cls'] ?>" style="font-size:0.8rem;padding:5px 10px;"><?= $b['label'] ?></span>
                    <span class="fw-bold"><?= $b['val'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent signups -->
    <div class="col-md-8">
        <div class="sa-table-card">
            <div class="card-header-bar">
                <span><i class="bi bi-person-plus me-2 text-muted"></i>Últimos registros</span>
                <a href="<?= $base ?>/superadmin/clients" class="btn btn-sm btn-outline-secondary" style="font-size:0.78rem">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Negocio</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Estado</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSignups as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= $base ?>/superadmin/clients/<?= $r['id'] ?>" class="text-decoration-none fw-medium text-dark">
                                    <?= htmlspecialchars($r['business_name']) ?>
                                </a>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($r['email']) ?></td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary border"><?= ucfirst($r['plan']) ?></span></td>
                            <td>
                                <?php
                                $statusCls = match($r['plan_status']) {
                                    'active'    => 'badge-active',
                                    'expired'   => 'badge-expired',
                                    'cancelled' => 'badge-cancelled',
                                    default     => 'badge-trial',
                                };
                                ?>
                                <span class="badge <?= $statusCls ?>"><?= ucfirst($r['plan_status']) ?></span>
                            </td>
                            <td class="text-muted small"><?= date('d/m/y', strtotime($r['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Mia leads feed -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="sa-table-card">
            <div class="card-header-bar">
                <span><i class="bi bi-whatsapp me-2" style="color:#22c55e"></i>Prospectos de Mia &mdash; actividad reciente</span>
                <a href="<?= $base ?>/superadmin/prospects" class="btn btn-sm btn-outline-secondary" style="font-size:0.78rem">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tel&eacute;fono</th>
                            <th>Negocio</th>
                            <th>Tipo</th>
                            <th>Etapa</th>
                            <th>&Uacute;ltima actividad</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentProspects)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4" style="font-size:0.88rem">A&uacute;n no hay prospectos. Ser&aacute;n visibles aqu&iacute; cuando alguien escriba a Mia.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($recentProspects as $p):
                            $stateCls = match($p['state']) {
                                'captured'                            => 'badge-active',
                                'closing','demo','roi_pitch','benefits' => 'badge-trial',
                                default                               => 'bg-secondary bg-opacity-10 text-secondary border',
                            };
                            $stateLabel = match($p['state']) {
                                'new'               => 'Nuevo',
                                'intro'             => 'Intro',
                                'qualifying_size','qualifying_method','qualifying_pain' => 'Calificando',
                                'roi_pitch'         => 'ROI pitch',
                                'demo'              => 'Demo',
                                'benefits'          => 'Beneficios',
                                'closing'           => 'Cierre',
                                'collecting_name','collecting_email' => 'Datos',
                                'captured'          => '&#10003; Capturado',
                                default             => ucfirst($p['state']),
                            };
                            $ago = (time() - strtotime($p['updated_at']));
                            $agoStr = $ago < 60 ? 'hace un momento'
                                    : ($ago < 3600 ? 'hace ' . (int)($ago/60) . ' min'
                                    : ($ago < 86400 ? 'hace ' . (int)($ago/3600) . ' h'
                                    : date('d/m/y H:i', strtotime($p['updated_at']))));
                        ?>
                        <tr style="cursor:pointer" onclick="openChat(<?= (int)$p['id'] ?>, '<?= htmlspecialchars(addslashes($p['phone'])) ?>', '<?= htmlspecialchars(addslashes($p['business_name'] ?: '')) ?>')">
                            <td class="text-muted small font-monospace" title="<?= htmlspecialchars($p['phone']) ?>"><?= htmlspecialchars(fmtPhone($p['phone'])) ?></td>
                            <td>
                                <?php if ($p['business_name']): ?>
                                    <span class="fw-medium"><?= htmlspecialchars($p['business_name']) ?></span>
                                    <?php if ($p['contact_name']): ?><br><span class="text-muted small"><?= htmlspecialchars($p['contact_name']) ?></span><?php endif; ?>
                                <?php else: ?><span class="text-muted small">&mdash;</span><?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= $p['business_type'] ? htmlspecialchars(ucfirst($p['business_type'])) : '<span style="color:#475569">—</span>' ?></td>
                            <td><span class="badge <?= $stateCls ?>" style="font-size:0.75rem"><?= $stateLabel ?></span></td>
                            <td class="text-muted small"><?= $agoStr ?></td>
                            <td onclick="event.stopPropagation()">
                                <?php if ($p['client_id']): ?>
                                    <a href="<?= $base ?>/superadmin/clients/<?= $p['client_id'] ?>" class="btn btn-xs btn-outline-success" style="font-size:0.72rem;padding:2px 8px">Cliente</a>
                                <?php else: ?>
                                    <a href="<?= $base ?>/superadmin/prospects/<?= $p['id'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:0.72rem;padding:2px 8px">Detalle</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Conversation slide-in panel -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="chatPanel" style="width:420px;background:#0f1623;border-left:1px solid rgba(255,255,255,0.08);">
    <div class="offcanvas-header" style="border-bottom:1px solid rgba(255,255,255,0.07);padding:14px 18px;">
        <div>
            <div style="font-weight:600;color:#e2e8f0;font-size:0.95rem;" id="chatTitle">Conversaci&oacute;n</div>
            <div style="font-size:0.78rem;color:#64748b;" id="chatSubtitle"></div>
        </div>
        <div class="ms-auto d-flex gap-2 align-items-center">
            <a id="chatDetailLink" href="#" class="btn btn-sm" style="font-size:0.75rem;padding:3px 10px;background:rgba(99,102,241,0.15);color:#818cf8;border:1px solid rgba(99,102,241,0.3);border-radius:7px;">Ver detalle</a>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
    </div>
    <div class="offcanvas-body p-0">
        <div id="chatLoading" style="display:flex;align-items:center;justify-content:center;height:200px;color:#64748b;font-size:0.88rem;">
            <i class="bi bi-arrow-repeat me-2" style="animation:spin 1s linear infinite"></i>Cargando...
        </div>
        <div id="chatMessages" style="display:none;flex-direction:column;gap:8px;padding:16px;overflow-y:auto;height:calc(100vh - 130px);"></div>
        <div id="chatEmpty" style="display:none;padding:40px 20px;text-align:center;color:#475569;font-size:0.85rem;">Sin mensajes registrados a&uacute;n.</div>
    </div>
</div>

<style>
@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
.chat-bubble { max-width:85%;padding:8px 12px;border-radius:12px;font-size:0.83rem;line-height:1.45; }
.chat-bubble .role-label { font-size:0.68rem;font-weight:600;margin-bottom:3px; }
</style>

<script>
const BASE = '<?= $base ?>';

function openChat(id, phone, bizName) {
    // Reset state
    document.getElementById('chatLoading').style.display = 'flex';
    document.getElementById('chatMessages').style.display = 'none';
    document.getElementById('chatEmpty').style.display = 'none';
    document.getElementById('chatMessages').innerHTML = '';
    document.getElementById('chatTitle').textContent = bizName || phone;
    document.getElementById('chatSubtitle').textContent = bizName ? phone : '';
    document.getElementById('chatDetailLink').href = BASE + '/superadmin/prospects/' + id;

    // Open panel
    new bootstrap.Offcanvas(document.getElementById('chatPanel')).show();

    // Fetch conversation
    fetch(BASE + '/superadmin/prospects/' + id + '/chat')
        .then(r => r.json())
        .then(data => {
            document.getElementById('chatLoading').style.display = 'none';
            const history = data.history || [];
            if (!history.length) {
                document.getElementById('chatEmpty').style.display = 'block';
                return;
            }
            const wrap = document.getElementById('chatMessages');
            wrap.style.display = 'flex';
            history.forEach(msg => {
                const isMia = msg.role === 'assistant';
                const outer = document.createElement('div');
                outer.style.cssText = 'display:flex;justify-content:' + (isMia ? 'flex-start' : 'flex-end');
                const bubble = document.createElement('div');
                bubble.className = 'chat-bubble';
                bubble.style.cssText = isMia
                    ? 'background:#202c33;color:#e9edef;border:none;border-radius:4px 12px 12px 12px;'
                    : 'background:#005c4b;color:#e9edef;border:none;border-radius:12px 4px 12px 12px;';
                bubble.innerHTML = '<div class="role-label" style="color:' + (isMia ? '#00a884' : '#8fcebd') + '">'  +
                    (isMia ? '<i class="bi bi-robot me-1"></i>Mia' : 'Prospecto') +
                    '</div>' + escapeHtml(msg.content || '').replace(/\n/g, '<br>');
                outer.appendChild(bubble);
                wrap.appendChild(outer);
            });
            wrap.scrollTop = wrap.scrollHeight;
        })
        .catch(() => {
            document.getElementById('chatLoading').innerHTML = '<span style="color:#f87171">Error al cargar la conversación.</span>';
        });
}

function escapeHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php require __DIR__ . '/_foot.php'; ?>
