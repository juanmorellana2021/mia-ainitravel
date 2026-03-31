<?php
/**
 * mia/views/client/leads.php — Lead management list
 */
$base         = App::basePath();
$pageTitle    = 'Leads — Mia';
$pageTopTitle = 'Gestión de Leads';
$activeNav    = 'leads';

$allStatuses = [
    ''            => 'Todos',
    'new'         => 'Nuevo',
    'interested'  => 'Interesado',
    'demo'        => 'Demo',
    'closed_won'  => 'Cerrado ✓',
    'closed_lost' => 'Perdido',
];

$statusEmoji = [
    'new'         => '🆕',
    'interested'  => '🔥',
    'demo'        => '🎯',
    'closed_won'  => '💰',
    'closed_lost' => '🔴',
];

$avatarColors = ['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc948','#b07aa1','#ff9da7','#9c755f','#bab0ac'];

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<style>
/* ── Filter pills ───────────────────────────────────────── */
.lead-filter-pills { display:flex; gap:6px; overflow-x:auto; padding-bottom:4px; -webkit-overflow-scrolling:touch; scrollbar-width:none; flex-wrap:wrap; }
.lead-filter-pills::-webkit-scrollbar { display:none; }
.lead-filter-pills a {
    white-space:nowrap; border-radius:20px; padding:5px 16px; font-size:0.82rem; font-weight:500;
    text-decoration:none; border:1px solid #dee2e6; color:#555; background:#fff; transition:all .2s;
}
.lead-filter-pills a.active { background:#1a1a2e; color:#fff; border-color:#1a1a2e; }

/* ── Cards grid ─────────────────────────────────────────── */
.leads-grid {
    display:grid;
    grid-template-columns: 1fr;
    gap:10px;
}
@media(min-width:600px) {
    .leads-grid { grid-template-columns: repeat(2, 1fr); }
}
@media(min-width:992px) {
    .leads-grid { grid-template-columns: repeat(3, 1fr); }
}
@media(min-width:1400px) {
    .leads-grid { grid-template-columns: repeat(4, 1fr); }
}

.lead-card {
    background:#fff; border-radius:12px; padding:14px 16px;
    box-shadow:0 1px 4px rgba(0,0,0,0.06); display:flex; gap:12px; align-items:flex-start;
    cursor:pointer; transition:box-shadow .2s, transform .15s; text-decoration:none; color:inherit;
    height:100%;
}
.lead-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.1); transform:translateY(-1px); }
.lead-avatar {
    width:46px; height:46px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:1.15rem; color:#fff; flex-shrink:0;
}
.lead-card-body { flex:1; min-width:0; }
.lead-card-top { display:flex; justify-content:space-between; align-items:center; }
.lead-card-name { font-weight:600; font-size:0.93rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:65%; }
.lead-card-time { font-size:0.72rem; color:#adb5bd; white-space:nowrap; display:flex; align-items:center; gap:3px; }
.lead-card-msg { font-size:0.82rem; color:#718096; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin:3px 0 6px; }
.lead-card-badges { display:flex; gap:5px; flex-wrap:wrap; align-items:center; }
.lead-card-badges .badge { font-size:0.68rem; font-weight:500; padding:3px 7px; border-radius:10px; }
.lead-mia-indicator { font-size:0.72rem; color:#25d366; margin-top:5px; display:flex; align-items:center; gap:4px; }
.lead-card-detail-btn {
    margin-left:auto; width:28px; height:28px; border-radius:50%; display:inline-flex;
    align-items:center; justify-content:center; background:#f0f4f8; color:#718096;
    font-size:0.82rem; text-decoration:none; transition:background .2s; flex-shrink:0;
}
.lead-card-detail-btn:hover { background:#e2e8f0; color:#1a1a2e; }

/* ── Stats scroll (mobile) ──────────────────────────────── */
.lead-stats-scroll { display:flex; gap:10px; overflow-x:auto; padding-bottom:4px; -webkit-overflow-scrolling:touch; scrollbar-width:none; }
.lead-stats-scroll::-webkit-scrollbar { display:none; }
.lead-stats-scroll .mc-stat-card { min-width:130px; flex-shrink:0; padding:14px 16px; }

/* ── FAB (mobile only) ──────────────────────────────────── */
.lead-fab {
    position:fixed; bottom:24px; right:24px; width:56px; height:56px; border-radius:50%;
    background:#25d366; color:#fff; border:none; font-size:1.5rem; display:flex;
    align-items:center; justify-content:center; box-shadow:0 4px 16px rgba(37,211,102,0.4);
    z-index:400; cursor:pointer; transition:transform .2s;
}
.lead-fab:hover { transform:scale(1.1); }

/* ── Responsive helpers ─────────────────────────────────── */
.lead-mobile-only { display:block; }
.lead-desktop-only { display:none; }
@media(min-width:768px) {
    .lead-mobile-only { display:none !important; }
    .lead-desktop-only { display:flex !important; }
    .lead-fab { display:none !important; }
}
</style>

<!-- ══ Stats row ══════════════════════════════════════════════════════════ -->
<!-- Mobile: horizontal scroll -->
<div class="lead-mobile-only mb-3">
    <div class="lead-stats-scroll">
        <div class="mc-stat-card text-center">
            <div class="stat-num text-info" style="font-size:1.5rem"><?= (int)($stats['interested'] ?? 0) ?></div>
            <div class="stat-label">Interesados</div>
        </div>
        <div class="mc-stat-card text-center">
            <div class="stat-num text-success" style="font-size:1.5rem"><?= (int)($stats['won'] ?? 0) ?></div>
            <div class="stat-label">Ganados</div>
        </div>
        <div class="mc-stat-card text-center">
            <div class="stat-num text-secondary" style="font-size:1.5rem"><?= (int)($stats['lost'] ?? 0) ?></div>
            <div class="stat-label">Perdidos</div>
        </div>
        <div class="mc-stat-card text-center">
            <div class="stat-num" style="color:#25d366;font-size:1.2rem"><?= App::CURRENCY ?><?= number_format((float)($stats['pipeline_value'] ?? 0), 0) ?></div>
            <div class="stat-label">Pipeline</div>
        </div>
    </div>
</div>

<!-- Desktop: 6-column row -->
<div class="row g-3 mb-4 lead-desktop-only">
    <div class="col-md-2">
        <div class="mc-stat-card text-center">
            <div class="stat-num text-dark"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="mc-stat-card text-center">
            <div class="stat-num text-primary"><?= (int)($stats['new_leads'] ?? 0) ?></div>
            <div class="stat-label">Nuevos</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="mc-stat-card text-center">
            <div class="stat-num text-info"><?= (int)($stats['interested'] ?? 0) ?></div>
            <div class="stat-label">Interesados</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="mc-stat-card text-center">
            <div class="stat-num text-success"><?= (int)($stats['won'] ?? 0) ?></div>
            <div class="stat-label">Ganados</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="mc-stat-card text-center">
            <div class="stat-num text-secondary"><?= (int)($stats['lost'] ?? 0) ?></div>
            <div class="stat-label">Perdidos</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="mc-stat-card text-center">
            <div class="stat-num" style="color:#25d366;font-size:1.3rem">
                <?= App::CURRENCY ?><?= number_format((float)($stats['pipeline_value'] ?? 0), 0) ?>
            </div>
            <div class="stat-label">Pipeline</div>
        </div>
    </div>
</div>

<!-- ══ Toolbar: filter pills + Add button ════════════════════════════════ -->
<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
    <div class="lead-filter-pills flex-grow-1">
        <?php foreach ($allStatuses as $val => $label): ?>
            <a href="?status=<?= $val ?>" class="<?= $filter === $val ? 'active' : '' ?>">
                <?= htmlspecialchars($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="lead-desktop-only flex-shrink-0" style="display:inline-flex!important;gap:6px;align-items:center;">
        <!-- View toggle -->
        <div class="btn-group btn-group-sm" id="viewToggle" role="group" title="Cambiar vista">
            <button type="button" class="btn btn-outline-secondary" id="btnViewCards" title="Vista tarjetas">
                <i class="bi bi-grid-3x3-gap-fill"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btnViewTable" title="Vista tabla">
                <i class="bi bi-table"></i>
            </button>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="modal" data-bs-target="#importCsvModal"
                style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
            <i class="bi bi-upload"></i> Importar CSV
        </button>
        <button type="button" class="btn btn-success btn-sm"
                data-bs-toggle="modal" data-bs-target="#addContactModal"
                style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
            <i class="bi bi-person-plus-fill"></i> Agregar Contacto
        </button>
    </div>
</div>

<?php if (!empty($_SESSION['lead_add_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <?= htmlspecialchars($_SESSION['lead_add_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ══ Unified Lead Cards Grid ═══════════════════════════════════════════ -->
<?php if (empty($leads)): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
        <p>No hay leads<?= $filter ? ' con este estado' : '' ?>.</p>
    </div>
<?php else: ?>
    <!-- TABLE VIEW -->
    <div id="leadsTableView" style="display:none;overflow-x:auto;">
        <table class="table table-hover align-middle bg-white rounded shadow-sm" style="font-size:0.88rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:40px"></th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>LID</th>
                    <th>Estado</th>
                    <th>Fuente</th>
                    <th>Último mensaje</th>
                    <th>Fecha</th>
                    <th style="width:40px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($leads as $i => $lead):
                $initial  = $lead->displayInitial();
                $color    = $avatarColors[$lead->id % count($avatarColors)];
                $emoji    = $statusEmoji[$lead->status] ?? '';
                $picFile  = $lead->profile_pic ? dirname(__DIR__, 2) . '/' . $lead->profile_pic : null;
                $timeFmt2 = $lead->last_message_at ? date('d/m/y', strtotime($lead->last_message_at)) : date('d/m/y', strtotime($lead->created_at));
                $msgPreview2 = $lead->last_message_text ? mb_strimwidth(strip_tags($lead->last_message_text), 0, 45, '…') : '';
                $sourceLabels2 = ['whatsapp'=>'WhatsApp','facebook'=>'Facebook','instagram'=>'Instagram','website'=>'Website','qr'=>'QR','manual'=>'Manual','import'=>'Import'];
            ?>
            <tr style="cursor:pointer"
                data-lead-id="<?= $lead->id ?>"
                data-lead-name="<?= htmlspecialchars($lead->displayName()) ?>"
                data-lead-phone="<?= htmlspecialchars($lead->displayPhone()) ?>"
                data-lead-paused="<?= ($lead->bot_paused_until && strtotime($lead->bot_paused_until) > time()) ? '1' : '0' ?>"
                data-lead-pic="<?= ($picFile && file_exists($picFile)) ? htmlspecialchars($base . '/' . $lead->profile_pic) : '' ?>">
                <td>
                    <div class="lead-avatar" style="width:34px;height:34px;font-size:0.85rem;background:<?= ($picFile && file_exists($picFile)) ? '#e8e8e8' : $color ?>">
                        <?php if ($picFile && file_exists($picFile)): ?>
                            <img src="<?= htmlspecialchars($base . '/' . $lead->profile_pic) ?>?v=<?= filemtime($picFile) ?>" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;display:block;">
                        <?php else: ?>
                            <?= $initial ?>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="fw-semibold"><?= htmlspecialchars($lead->displayName()) ?></td>
                <?php $dp2 = $lead->displayPhone(); ?>
                <td class="text-muted"><?= $dp2 !== '' ? '+' . htmlspecialchars($dp2) : '<span style="opacity:0.35">—</span>' ?></td>
                <?php $dl2 = $lead->displayLid(); ?>
                <td class="text-muted" style="font-size:0.75rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= $dl2 !== '' ? '<span title="' . htmlspecialchars($dl2) . '">🔗 ' . htmlspecialchars($dl2) . '</span>' : '<span style="opacity:0.35">—</span>' ?></td>
                <td><span class="badge bg-<?= $lead->statusClass() ?> bg-opacity-10 text-<?= $lead->statusClass() ?> border border-<?= $lead->statusClass() ?> border-opacity-25"><?= $emoji ?> <?= $lead->statusLabel() ?></span></td>
                <td class="text-muted" style="font-size:0.8rem"><?= $sourceLabels2[$lead->source] ?? ucfirst($lead->source) ?></td>
                <td class="text-muted" style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($msgPreview2) ?></td>
                <td class="text-muted" style="white-space:nowrap;font-size:0.8rem"><?= $timeFmt2 ?></td>
                <td><a href="<?= $base ?>/dashboard/leads/<?= $lead->id ?>" class="btn btn-sm btn-light" onclick="event.stopPropagation()" title="Ver detalle"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- CARD VIEW -->
    <div id="leadsCardsView">
    <div class="leads-grid">
        <?php foreach ($leads as $i => $lead):
            $initial = $lead->displayInitial();
            $color   = $avatarColors[$lead->id % count($avatarColors)];
            $emoji   = $statusEmoji[$lead->status] ?? '';
            $timeFmt = '';
            $miaIndicator = '';

            if ($lead->last_message_at) {
                $diff = time() - strtotime($lead->last_message_at);
                if ($diff < 60)         $timeFmt = 'ahora';
                elseif ($diff < 3600)   $timeFmt = floor($diff / 60) . ' min';
                elseif ($diff < 86400)  $timeFmt = floor($diff / 3600) . ' h';
                else                    $timeFmt = date('d/m', strtotime($lead->last_message_at));

                if ($lead->last_message_direction === 'outbound' && $lead->last_message_handled_by === 'mia') {
                    if ($diff < 60)         $miaLabel = 'ahora';
                    elseif ($diff < 3600)   $miaLabel = 'hace ' . floor($diff / 60) . ' min';
                    elseif ($diff < 86400)  $miaLabel = 'hace ' . floor($diff / 3600) . ' h';
                    else                    $miaLabel = date('d/m', strtotime($lead->last_message_at));
                    $miaIndicator = '⚡ Mia respondió ' . $miaLabel;
                }
            } else {
                $timeFmt = date('d/m', strtotime($lead->created_at));
            }

            $msgPreview = '';
            if ($lead->last_message_text) {
                $msgPreview = mb_strimwidth(strip_tags($lead->last_message_text), 0, 55, '…');
                if ($lead->last_message_direction === 'outbound') {
                    $msgPreview = ($lead->last_message_handled_by === 'mia' ? '🤖 ' : '👤 ') . $msgPreview;
                }
            }

            $sourceLabels = ['whatsapp' => 'WhatsApp', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'website' => 'Website', 'qr' => 'QR Code'];
            $picFile = $lead->profile_pic ? dirname(__DIR__, 2) . '/' . $lead->profile_pic : null;
        ?>
        <div class="lead-card"
             data-lead-id="<?= $lead->id ?>"
             data-lead-name="<?= htmlspecialchars($lead->displayName()) ?>"
             data-lead-phone="<?= htmlspecialchars($lead->displayPhone()) ?>"
             data-lead-paused="<?= ($lead->bot_paused_until && strtotime($lead->bot_paused_until) > time()) ? '1' : '0' ?>"
             data-lead-pic="<?= ($picFile && file_exists($picFile)) ? htmlspecialchars($base . '/' . $lead->profile_pic) : '' ?>">
            <div class="lead-avatar" style="background:<?= ($picFile && file_exists($picFile)) ? '#e8e8e8' : $color ?>">
                <?php if ($picFile && file_exists($picFile)): ?>
                    <img src="<?= htmlspecialchars($base . '/' . $lead->profile_pic) ?>?v=<?= filemtime($picFile) ?>"
                         alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;display:block;">
                <?php else: ?>
                    <?= $initial ?>
                <?php endif; ?>
            </div>
            <div class="lead-card-body">
                <div class="lead-card-top">
                    <div class="lead-card-name"><?= htmlspecialchars($lead->displayName()) ?></div>
                    <div class="lead-card-time">
                        <?php if ($lead->last_message_direction === 'outbound'): ?>
                            <i class="bi bi-check2-all" style="color:#53bdeb;font-size:0.8rem"></i>
                        <?php endif; ?>
                        <?= $timeFmt ?>
                    </div>
                </div>
                <?php if ($msgPreview): ?>
                    <div class="lead-card-msg"><?= htmlspecialchars($msgPreview) ?></div>
                <?php else: ?>
                    <div class="lead-card-msg" style="font-style:italic;color:#c0cadb">Sin mensajes aún</div>
                <?php endif; ?>
                <?php
                    $cardPhone = $lead->displayPhone();
                    $cardLid   = $lead->displayLid();
                ?>
                <?php if ($cardPhone !== '' || $cardLid !== ''): ?>
                <div style="font-size:0.72rem;color:#9ca3af;margin-bottom:4px;display:flex;gap:10px;flex-wrap:wrap">
                    <?php if ($cardPhone !== ''): ?>
                        <span title="Teléfono real"><i class="bi bi-telephone" style="font-size:0.65rem"></i> +<?= htmlspecialchars($cardPhone) ?></span>
                    <?php endif; ?>
                    <?php if ($cardLid !== ''): ?>
                        <span title="WhatsApp LID (ID interno)"><i class="bi bi-link-45deg" style="font-size:0.65rem"></i> <?= htmlspecialchars($cardLid) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="lead-card-badges">
                    <span class="badge bg-<?= $lead->statusClass() ?> bg-opacity-10 text-<?= $lead->statusClass() ?> border border-<?= $lead->statusClass() ?> border-opacity-25">
                        <?= $emoji ?> <?= $lead->statusLabel() ?>
                    </span>
                    <span class="badge bg-light text-muted border" style="font-size:0.68rem">
                        <i class="bi <?= $lead->sourceIcon() ?> me-1" style="font-size:0.65rem"></i><?= $sourceLabels[$lead->source] ?? ucfirst($lead->source) ?>
                    </span>
                    <?php if ($lead->contact_type !== 'lead'): ?>
                    <span class="badge bg-<?= $lead->contactTypeBadgeClass() ?> bg-opacity-10 text-<?= $lead->contactTypeBadgeClass() ?> border border-<?= $lead->contactTypeBadgeClass() ?> border-opacity-25" style="font-size:0.68rem">
                        <?= $lead->contactTypeLabel() ?>
                    </span>
                    <?php endif; ?>
                    <a href="<?= $base ?>/dashboard/leads/<?= $lead->id ?>"
                       class="lead-card-detail-btn" onclick="event.stopPropagation()" title="Ver detalle">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
                <?php if ($miaIndicator): ?>
                    <div class="lead-mia-indicator"><?= $miaIndicator ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    </div><!-- /leadsCardsView -->
<?php endif; ?>

<!-- FAB: Add contact (mobile) -->
<button type="button" class="lead-fab lead-mobile-only"
        data-bs-toggle="modal" data-bs-target="#addContactModal"
        title="Agregar Contacto">
    <i class="bi bi-plus-lg"></i>
</button>

<!-- ── Chat panel ──────────────────────────────────────────────────────────── -->
<div id="chatPanel" style="
    position:fixed; top:0; right:0; bottom:0; width:380px;
    background:#fff; box-shadow:-4px 0 24px rgba(0,0,0,0.12);
    display:flex; flex-direction:column; z-index:500;
    transform:translateX(100%); transition:transform .28s ease; max-width:100vw;">

    <!-- Header -->
    <div style="padding:14px 16px; background:#1a1a2e; color:#fff; display:flex; align-items:center; gap:10px; flex-shrink:0;">
        <div id="chatHeaderAvatar" style="width:38px;height:38px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;font-size:1.1rem;overflow:hidden;flex-shrink:0;">
            <i class="bi bi-person-fill"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div id="chatLeadName" style="font-weight:600;font-size:0.95rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
            <div id="chatLeadPhone" style="font-size:0.78rem;opacity:0.7;"></div>
        </div>
        <a id="chatCallBtn" href="#"
           style="background:none;border:none;color:#fff;font-size:1.15rem;cursor:pointer;padding:4px 6px;line-height:1;opacity:0.85;text-decoration:none;"
           target="_blank" rel="noopener"
           title="Llamar por WhatsApp">
            <i class="bi bi-telephone"></i>
        </a>
        <button id="chatTranslateBtn" title="Traducir conversaci&#243;n"
                style="background:none;border:none;color:#fff;font-size:1rem;cursor:pointer;padding:4px 6px;line-height:1;opacity:0.8;">
            <i class="bi bi-translate"></i>
        </button>
        <button id="chatCloseBtn" style="background:none;border:none;color:#fff;font-size:1.3rem;cursor:pointer;padding:4px 6px;line-height:1;opacity:0.8;" title="Cerrar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Bot paused banner -->
    <div id="botPausedBanner" style="display:none;background:#fff3cd;color:#856404;font-size:0.8rem;padding:7px 14px;border-bottom:1px solid #ffc107;flex-shrink:0;display:none;align-items:center;justify-content:space-between;gap:8px;">
        <span><i class="bi bi-pause-circle-fill"></i> Bot pausado &mdash; respondiendo manualmente</span>
        <button id="resumeBotBtn" style="background:#ffc107;border:none;border-radius:6px;padding:3px 10px;font-size:0.78rem;cursor:pointer;color:#212529;font-weight:600;">Reactivar bot</button>
    </div>

    <!-- Status bar -->
    <div id="chatStatus" style="font-size:0.75rem;text-align:center;padding:4px 12px;background:#f0f4f8;color:#6c757d;flex-shrink:0;display:none;"></div>

    <!-- Messages -->
    <div id="chatMessages" style="flex:1;overflow-y:auto;padding:14px 12px;display:flex;flex-direction:column;gap:8px;background:#f0f4f8;"></div>

    <!-- Photo picker panel -->
    <div id="photoPickerPanel" style="display:none;padding:10px 12px;border-top:1px solid #e9ecef;background:#fafafa;max-height:220px;overflow-y:auto;">
        <div id="photoPickerGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:8px;"></div>
    </div>

    <!-- Docs picker panel -->
    <div id="docPickerPanel" style="display:none;padding:10px 12px;border-top:1px solid #e9ecef;background:#fafafa;max-height:220px;overflow-y:auto;">
        <div id="docPickerList" style="display:flex;flex-direction:column;gap:6px;"></div>
    </div>

    <!-- Input area -->
    <div style="padding:10px 12px;border-top:1px solid #e9ecef;background:#fff;flex-shrink:0;display:flex;gap-8;gap:8px;align-items:flex-end;">
        <button id="chatPhotoBtn" title="Enviar foto del catálogo"
            style="background:#f0f4f8;border:1px solid #dee2e6;color:#495057;border-radius:10px;padding:9px 11px;font-size:1rem;cursor:pointer;flex-shrink:0;align-self:flex-end;">
            <i class="bi bi-image"></i>
        </button>
        <button id="chatDocBtn" title="Enviar documento"
            style="background:#f0f4f8;border:1px solid #dee2e6;color:#495057;border-radius:10px;padding:9px 11px;font-size:1rem;cursor:pointer;flex-shrink:0;align-self:flex-end;">
            <i class="bi bi-file-earmark-text"></i>
        </button>
        <textarea id="chatInput" rows="2"
            placeholder="Escribe un mensaje..."
            style="flex:1;resize:none;border:1px solid #dee2e6;border-radius:10px;padding:8px 12px;font-size:0.88rem;outline:none;font-family:inherit;"></textarea>
        <button id="chatSendBtn"
            style="background:#25d366;border:none;color:#fff;border-radius:10px;padding:9px 14px;font-size:1rem;cursor:pointer;flex-shrink:0;align-self:flex-end;">
            <i class="bi bi-send-fill"></i>
        </button>
    </div>
</div>

<!-- Overlay -->
<div id="chatOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.3);z-index:499;"></div>

<script>
(function () {
    const CSRF  = <?= json_encode(App::csrfToken()) ?>;
    const BASE  = <?= json_encode($base) ?>;

    const panel    = document.getElementById('chatPanel');
    const overlay  = document.getElementById('chatOverlay');
    const msgBox   = document.getElementById('chatMessages');
    const input    = document.getElementById('chatInput');
    const sendBtn  = document.getElementById('chatSendBtn');
    const statusEl = document.getElementById('chatStatus');
    const pauseBanner = document.getElementById('botPausedBanner');

    let currentLeadId = null;
    let botIsPaused   = false;
    let pollTimer     = null;
    let lastMsgId     = 0;
    let currentMsgs   = [];
    let translatedState     = false;
    let translatedOriginals = [];

    // ── Photo picker ──────────────────────────────────────────────────────────
    const photoBtn    = document.getElementById('chatPhotoBtn');
    const pickerPanel = document.getElementById('photoPickerPanel');
    const pickerGrid  = document.getElementById('photoPickerGrid');
    let galleryCache  = null;

    photoBtn.addEventListener('click', function () {
        const open = pickerPanel.style.display !== 'none';
        pickerPanel.style.display = open ? 'none' : 'block';
        if (!open && galleryCache === null) loadGallery();
    });

    function loadGallery() {
        pickerGrid.innerHTML = '<span style="font-size:0.8rem;color:#888;">Cargando...</span>';
        fetch(BASE + '/dashboard/leads/gallery', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                galleryCache = data.photos || [];
                renderGallery();
            })
            .catch(() => {
                pickerGrid.innerHTML = '<span style="font-size:0.8rem;color:#c00;">Error al cargar fotos</span>';
            });
    }

    function renderGallery() {
        if (!galleryCache || !galleryCache.length) {
            pickerGrid.innerHTML = '<span style="font-size:0.8rem;color:#888;">Sin fotos en el catálogo</span>';
            return;
        }
        pickerGrid.innerHTML = galleryCache.map(function (p, i) {
            const label = p.price ? escHtml(p.name) + ' — ' + escHtml(p.price) : escHtml(p.name);
            return '<div data-idx="' + i + '" title="' + label + '" style="cursor:pointer;border:2px solid transparent;border-radius:8px;overflow:hidden;" class="gallery-thumb">'
                 + '<img src="' + escHtml(p.url) + '" style="width:100%;aspect-ratio:1;object-fit:cover;display:block;">'
                 + '<div style="font-size:0.65rem;padding:2px 3px;background:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + label + '</div>'
                 + '</div>';
        }).join('');

        pickerGrid.querySelectorAll('.gallery-thumb').forEach(function (el) {
            el.addEventListener('click', function () {
                const idx   = parseInt(this.dataset.idx);
                const photo = galleryCache[idx];
                if (!photo || !currentLeadId) return;
                sendPhoto(photo);
                pickerPanel.style.display = 'none';
            });
        });
    }

    function sendPhoto(photo) {
        const label = photo.price ? photo.name + ' — ' + photo.price : photo.name;
        const text  = label + '\n[FOTO:' + photo.url + ']';

        const fd = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('message', text);

        fetch(BASE + '/dashboard/leads/' + currentLeadId + '/send', {
            method: 'POST', credentials: 'same-origin', body: fd,
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                loadMessages(true);
                if (!data.delivered) showStatus('⚠️ Guardado, pero WhatsApp no está conectado.', 'warning');
            } else {
                showStatus('❌ Error: ' + (data.error || 'desconocido'), 'danger');
            }
        })
        .catch(() => showStatus('❌ Error de red al enviar foto', 'danger'));
    }

    // ── Docs picker ───────────────────────────────────────────────────────────
    const docBtn        = document.getElementById('chatDocBtn');
    const docPickerPanel= document.getElementById('docPickerPanel');
    const docPickerList = document.getElementById('docPickerList');
    let docsCache = null;

    const DOC_ICONS = { pdf:'bi-file-earmark-pdf', docx:'bi-file-earmark-word', xlsx:'bi-file-earmark-excel', pptx:'bi-file-earmark-ppt' };
    const DOC_COLORS= { pdf:'#e53e3e', docx:'#2b6cb0', xlsx:'#276749', pptx:'#c05621' };

    docBtn.addEventListener('click', function () {
        // Close photo picker if open
        pickerPanel.style.display = 'none';
        const open = docPickerPanel.style.display !== 'none';
        docPickerPanel.style.display = open ? 'none' : 'block';
        if (!open && docsCache === null) loadDocs();
    });

    function loadDocs() {
        docPickerList.innerHTML = '<span style="font-size:0.8rem;color:#888;">Cargando...</span>';
        fetch(BASE + '/dashboard/leads/docs', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => { docsCache = data.docs || []; renderDocs(); })
            .catch(() => { docPickerList.innerHTML = '<span style="font-size:0.8rem;color:#c00;">Error al cargar documentos</span>'; });
    }

    function renderDocs() {
        if (!docsCache || !docsCache.length) {
            docPickerList.innerHTML = '<span style="font-size:0.8rem;color:#888;">Sin documentos en la biblioteca. <a href="' + BASE + '/dashboard/documents" target="_blank">Subir</a></span>';
            return;
        }
        docPickerList.innerHTML = docsCache.map(function(d, i) {
            const icon  = DOC_ICONS[d.file_type]  || 'bi-file-earmark';
            const color = DOC_COLORS[d.file_type] || '#718096';
            return '<div data-idx="' + i + '" class="doc-pick-item" style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:8px;cursor:pointer;border:1px solid #e9ecef;background:#fff;">'
                 + '<i class="bi ' + icon + '" style="font-size:1.3rem;color:' + color + ';flex-shrink:0;"></i>'
                 + '<div style="min-width:0;flex:1;">'
                 + '<div style="font-size:0.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escHtml(d.name) + '</div>'
                 + (d.description ? '<div style="font-size:0.7rem;color:#888;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escHtml(d.description) + '</div>' : '')
                 + '</div>'
                 + '<span style="font-size:0.68rem;color:#aaa;flex-shrink:0;">' + escHtml(d.file_size) + '</span>'
                 + '</div>';
        }).join('');

        docPickerList.querySelectorAll('.doc-pick-item').forEach(function(el) {
            el.addEventListener('mouseenter', () => el.style.background = '#f0f7ff');
            el.addEventListener('mouseleave', () => el.style.background = '#fff');
            el.addEventListener('click', function() {
                const doc = docsCache[parseInt(this.dataset.idx)];
                if (!doc || !currentLeadId) return;
                sendDoc(doc);
                docPickerPanel.style.display = 'none';
            });
        });
    }

    function sendDoc(doc) {
        // Send as [ARCHIVO:url:filename] so bot worker delivers it as a native file attachment
        const origName = doc.name.replace(/[^\w\s.\-]/g, '') + '.' + doc.file_type;
        const text = doc.name + '\n[ARCHIVO:' + doc.url + ':' + origName + ']';
        const fd = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('message', text);
        fetch(BASE + '/dashboard/leads/' + currentLeadId + '/send', {
            method: 'POST', credentials: 'same-origin', body: fd,
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                loadMessages(true);
                if (!data.delivered) showStatus('⚠️ Guardado, pero WhatsApp no está conectado.', 'warning');
            } else {
                showStatus('❌ Error: ' + (data.error || 'desconocido'), 'danger');
            }
        })
        .catch(() => showStatus('❌ Error de red al enviar documento', 'danger'));
    }

    // Also close the photo picker when doc picker opens (hook)
    photoBtn.addEventListener('click', function () {
        docPickerPanel.style.display = 'none';
    }, true);

    // ── Translate button ──────────────────────────────────────────────────────
    document.getElementById('chatTranslateBtn').addEventListener('click', async function () {
        if (!currentLeadId || !currentMsgs.length) return;
        const tBtn = this;

        if (translatedState) {
            // Restore originals
            document.querySelectorAll('#chatMessages [data-msgidx]').forEach(function (el) {
                const i = parseInt(el.dataset.msgidx, 10);
                if (translatedOriginals[i] !== undefined) el.innerHTML = translatedOriginals[i];
            });
            translatedState = false;
            tBtn.style.opacity = '0.8';
            tBtn.title = 'Traducir conversaci\u00f3n';
            return;
        }

        const bubbles = document.querySelectorAll('#chatMessages [data-msgidx]');
        if (!bubbles.length) return;
        translatedOriginals = [];
        const texts = [];
        bubbles.forEach(function (el) {
            const i = parseInt(el.dataset.msgidx, 10);
            translatedOriginals[i] = el.innerHTML;
            texts.push(el.innerText.trim());
        });

        tBtn.style.opacity = '0.4';
        tBtn.disabled = true;
        showStatus('Traduciendo...', 'info');

        try {
            const res = await fetch(BASE + '/dashboard/leads/' + currentLeadId + '/translate', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ texts: texts, _csrf: CSRF }),
            });
            const data = await res.json();
            if (data.translations && data.translations.length) {
                bubbles.forEach(function (el) {
                    const i = parseInt(el.dataset.msgidx, 10);
                    if (data.translations[i] !== undefined) {
                        el.textContent = data.translations[i];
                    }
                });
                if (data.changed) {
                    translatedState = true;
                    tBtn.style.opacity = '1';
                    tBtn.title = 'Ver original';
                    showStatus('\u2705 Traduci\u00f3n lista \u2014 clic en \uD83C\uDF10 para ver el original', 'success');
                } else {
                    showStatus('\u26A0\uFE0F Groq no pudo traducir este contenido', 'warning');
                    tBtn.style.opacity = '0.8';
                }
            } else {
                tBtn.style.opacity = '0.8';
                showStatus('\u26A0\uFE0F No se pudo traducir: ' + (data.error || 'sin respuesta'), 'warning');
            }
        } catch (e) {
            tBtn.style.opacity = '0.8';
            showStatus('\u274C Error de conexi\u00f3n al traducir', 'danger');
        }
        tBtn.disabled = false;
    });

    // ── Open panel ────────────────────────────────────────────────────────────
    document.querySelectorAll('.chat-open-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            openChat(this.dataset.leadId, this.dataset.leadName, this.dataset.leadPhone, this.dataset.leadPic || '');
        });
    });

    // ── Mobile card click → open chat ─────────────────────────────────────────
    document.querySelectorAll('.lead-card').forEach(card => {
        card.addEventListener('click', function () {
            openChat(this.dataset.leadId, this.dataset.leadName, this.dataset.leadPhone, this.dataset.leadPic || '');
        });
    });

    function openChat(id, name, phone, pic) {
        currentLeadId = id;
        lastMsgId     = 0;
        pickerPanel.style.display = 'none';
        // gallery is per-client (not per-lead), so keep cache across leads
        document.getElementById('chatLeadName').textContent  = name;
        document.getElementById('chatLeadPhone').textContent = phone;
        const avatarEl = document.getElementById('chatHeaderAvatar');
        if (pic) {
            avatarEl.innerHTML = '<img src="' + pic + '" alt="" style="width:100%;height:100%;object-fit:cover;">';
            avatarEl.style.background = '#e8e8e8';
        } else {
            avatarEl.innerHTML = '<i class="bi bi-person-fill"></i>';
            avatarEl.style.background = '#25d366';
        }
        const callBtn = document.getElementById('chatCallBtn');
        if (callBtn) callBtn.href = phone ? 'https://wa.me/' + phone.replace(/\D/g,'') : '#';
        // Bot pause state — read from data attribute of the card/row that was clicked
        const el = document.querySelector('[data-lead-id="' + id + '"]');
        botIsPaused = el ? el.dataset.leadPaused === '1' : false;
        updatePauseBanner();
        msgBox.innerHTML = '<div style="text-align:center;color:#adb5bd;font-size:0.8rem;padding:20px 0">Cargando...</div>';
        statusEl.style.display = 'none';
        panel.style.transform  = 'translateX(0)';
        overlay.style.display  = 'block';
        document.body.style.overflow = 'hidden';
        loadMessages(true);
        startPolling();
    }

    // ── Close panel ───────────────────────────────────────────────────────────
    document.getElementById('chatCloseBtn').addEventListener('click', closeChat);
    overlay.addEventListener('click', closeChat);

    function closeChat() {
        panel.style.transform = 'translateX(100%)';
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        stopPolling();
        currentLeadId = null;
        botIsPaused   = false;
        translatedState = false;
        translatedOriginals = [];
        pickerPanel.style.display = 'none';
        const tBtn = document.getElementById('chatTranslateBtn');
        if (tBtn) { tBtn.style.opacity = '0.8'; tBtn.title = 'Traducir conversaci\u00f3n'; }
    }

    // ── Bot pause helpers ─────────────────────────────────────────────────────
    function updatePauseBanner() {
        pauseBanner.style.display = botIsPaused ? 'flex' : 'none';
    }

    function updateLeadCardPauseState(id, paused) {
        document.querySelectorAll('[data-lead-id="' + id + '"]').forEach(function(el) {
            el.dataset.leadPaused = paused ? '1' : '0';
        });
    }

    document.getElementById('resumeBotBtn').addEventListener('click', function() {
        if (!currentLeadId) return;
        const fd = new FormData();
        fd.append('_csrf', CSRF);
        fetch(BASE + '/dashboard/leads/' + currentLeadId + '/resume-bot', {
            method: 'POST', credentials: 'same-origin', body: fd,
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                botIsPaused = false;
                updatePauseBanner();
                updateLeadCardPauseState(currentLeadId, false);
                showStatus('\u2705 Bot reactivado', 'success');
            }
        })
        .catch(() => {});
    });

    // ── Load messages ─────────────────────────────────────────────────────────
    function loadMessages(scrollToBottom) {
        if (!currentLeadId) return;
        fetch(BASE + '/dashboard/leads/' + currentLeadId + '/messages', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(msgs => {
                if (!Array.isArray(msgs)) return;
                // Don't disrupt an active translation
                if (!translatedState) {
                    renderMessages(msgs);
                    if (scrollToBottom) msgBox.scrollTop = msgBox.scrollHeight;
                    else if (msgs.length && msgs[msgs.length - 1].id > lastMsgId) {
                        msgBox.scrollTop = msgBox.scrollHeight;
                    }
                }
                if (msgs.length) lastMsgId = msgs[msgs.length - 1].id;
            })
            .catch(() => {});
    }

    function renderMessages(msgs) {
        if (!msgs.length) {
            msgBox.innerHTML = '<div style="text-align:center;color:#adb5bd;font-size:0.82rem;padding:30px 0">Sin mensajes aún</div>';
            return;
        }
        currentMsgs = msgs;
        msgBox.innerHTML = msgs.map((m, idx) => {
            const isOut  = m.direction === 'outbound';
            const isHuman = m.handled_by === 'human';
            const bg     = isOut ? (isHuman ? '#25d366' : '#e9ecef') : '#fff';
            const color  = isOut && isHuman ? '#fff' : '#212529';
            const align  = isOut ? 'flex-end' : 'flex-start';
            const time   = m.created_at ? (function(s){ var d = new Date(s.replace(' ','T')+'Z'); return d.toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit',hour12:false,timeZone:'America/Lima'}); })(m.created_at) : '';
            const label  = isOut && isHuman ? '👤 tú' : (isOut ? '🤖 Mia' : '');
            return `<div style="display:flex;flex-direction:column;align-items:${align};max-width:88%;">
                ${label ? `<span style="font-size:0.68rem;color:#adb5bd;margin-bottom:2px;${isOut?'text-align:right':''}">${label}</span>` : ''}
                <div data-msgidx="${idx}" style="background:${bg};color:${color};border-radius:${isOut?'16px 16px 4px 16px':'16px 16px 16px 4px'};padding:8px 12px;font-size:0.88rem;box-shadow:0 1px 3px rgba(0,0,0,0.07);word-break:break-word;">
                    ${renderPhotos(escHtml(m.message))}
                </div>
                <span style="font-size:0.68rem;color:#adb5bd;margin-top:2px;">${time}</span>
            </div>`;
        }).join('');
    }

    function renderPhotos(html) {
        return html.replace(/\[FOTO:(https?:\/\/[^\]]+)\]/gi, function(_, url) {
            return '<a href="' + url + '" target="_blank" rel="noopener"><img src="' + url + '" style="max-width:200px;max-height:200px;display:block;border-radius:8px;margin:4px 0;" alt="foto"></a>';
        });
    }

    // ── Send message ──────────────────────────────────────────────────────────
    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    function sendMessage() {
        const text = input.value.trim();
        if (!text || !currentLeadId) return;
        input.value   = '';
        sendBtn.disabled = true;

        const fd = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('message', text);

        fetch(BASE + '/dashboard/leads/' + currentLeadId + '/send', {
            method: 'POST', credentials: 'same-origin', body: fd,
        })
        .then(r => r.json())
        .then(data => {
            sendBtn.disabled = false;
            if (data.success) {
                loadMessages(true);
                // Mark bot as paused in UI
                botIsPaused = true;
                updatePauseBanner();
                updateLeadCardPauseState(currentLeadId, true);
                if (!data.delivered) showStatus('⚠️ Guardado, pero WhatsApp no está conectado — el mensaje no fue enviado.', 'warning');
            } else {
                showStatus('❌ Error: ' + (data.error || 'desconocido'), 'danger');
            }
        })
        .catch(() => { sendBtn.disabled = false; });
    }

    // ── Polling ───────────────────────────────────────────────────────────────
    function startPolling() {
        stopPolling();
        pollTimer = setInterval(() => loadMessages(false), 4000);
    }
    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function showStatus(msg, type) {
        statusEl.textContent  = msg;
        statusEl.style.display = 'block';
        if (type === 'warning') {
            statusEl.style.background = '#fff3cd'; statusEl.style.color = '#856404';
        } else if (type === 'success') {
            statusEl.style.background = '#d1e7dd'; statusEl.style.color = '#0f5132';
        } else if (type === 'info') {
            statusEl.style.background = '#cff4fc'; statusEl.style.color = '#055160';
        } else {
            statusEl.style.background = '#f8d7da'; statusEl.style.color = '#842029';
        }
        if (type !== 'info') setTimeout(() => { statusEl.style.display = 'none'; }, 5000);
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>

<!-- ── Add Contact Modal ───────────────────────────────────────────────────── -->
<div class="modal fade" id="addContactModal" tabindex="-1" aria-labelledby="addContactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?= $base ?>/dashboard/leads/add">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App::csrfToken()) ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="addContactModalLabel">
                        <i class="bi bi-person-plus-fill text-success me-2"></i>Agregar Contacto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Número de WhatsApp <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-whatsapp text-success"></i></span>
                            <input type="tel" name="phone" class="form-control"
                                   placeholder="Ej: 5491155667788 (con código de país)"
                                   required autocomplete="off">
                        </div>
                        <div class="form-text">Incluye el código de país sin el +. Ejemplo: <strong>5491155667788</strong> para Argentina.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del contacto</label>
                        <input type="text" name="contact_name" class="form-control"
                               placeholder="Nombre (opcional)" maxlength="120" autocomplete="off">
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Mensaje inicial <span class="text-muted fw-normal">(opcional)</span></label>
                        <textarea name="initial_message" class="form-control" rows="3"
                                  placeholder="Escribe el mensaje que Mia enviará al contacto al agregarlo…"
                                  maxlength="1000"></textarea>
                        <div class="form-text">Si lo dejas vacío el contacto se agrega sin mensaje.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-person-check-fill me-1"></i>Agregar<?php if(true): ?> y Enviar<?php endif; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Re-open modal with errors if session flag set on redirect back
<?php if (!empty($_SESSION['lead_add_error'])): ?>
document.addEventListener('DOMContentLoaded', function(){
    var m = document.getElementById('addContactModal');
    if (m) new bootstrap.Modal(m).show();
});
<?php unset($_SESSION['lead_add_error']); endif; ?>
</script>

<script>
// ── View toggle (cards / table) ───────────────────────────────────────────
(function(){
    var PREF_KEY   = 'mia_leads_view';
    var cardsView  = document.getElementById('leadsCardsView');
    var tableView  = document.getElementById('leadsTableView');
    var btnCards   = document.getElementById('btnViewCards');
    var btnTable   = document.getElementById('btnViewTable');
    if (!cardsView || !tableView || !btnCards || !btnTable) return;

    function setView(mode) {
        if (mode === 'table') {
            cardsView.style.display = 'none';
            tableView.style.display = 'block';
            btnTable.classList.add('active');
            btnCards.classList.remove('active');
        } else {
            tableView.style.display = 'none';
            cardsView.style.display = 'block';
            btnCards.classList.add('active');
            btnTable.classList.remove('active');
        }
        localStorage.setItem(PREF_KEY, mode);
    }

    btnCards.addEventListener('click', function(){ setView('cards'); });
    btnTable.addEventListener('click', function(){ setView('table'); });

    // Rows in table view open chat panel (same as cards)
    tableView.querySelectorAll('tr[data-lead-id]').forEach(function(row){
        row.addEventListener('click', function(){
            var card = document.querySelector('.lead-card[data-lead-id="' + row.dataset.leadId + '"]');
            if (card) { card.click(); } else {
                window.location = '<?= $base ?>/dashboard/leads/' + row.dataset.leadId;
            }
        });
    });

    // Restore saved preference
    setView(localStorage.getItem(PREF_KEY) || 'cards');
})();
</script>

<!-- ── Import CSV Modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="importCsvModal" tabindex="-1" aria-labelledby="importCsvModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importCsvModalLabel">
                    <i class="bi bi-upload text-primary me-2"></i>Importar Contactos CSV
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Sube un archivo <strong>.csv</strong> con una columna de teléfono y (opcional) una de nombre:<br>
                    <code>5491155667788, Juan Pérez</code><br>
                    La primera fila puede ser encabezado — se detecta automáticamente.
                </p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Archivo CSV</label>
                    <input type="file" id="importCsvFile" class="form-control" accept=".csv,text/csv">
                </div>
                <div id="importCsvResult" class="d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" id="importCsvBtn" class="btn btn-primary">
                    <i class="bi bi-upload me-1"></i>Importar
                </button>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    document.getElementById('importCsvBtn').addEventListener('click', function(){
        var fileInput = document.getElementById('importCsvFile');
        var resultEl  = document.getElementById('importCsvResult');
        if (!fileInput.files.length) {
            resultEl.className = 'alert alert-warning';
            resultEl.textContent = 'Selecciona un archivo CSV primero.';
            return;
        }
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Importando…';
        resultEl.className = 'd-none';

        var fd = new FormData();
        fd.append('_csrf', '<?= htmlspecialchars(App::csrfToken()) ?>');
        fd.append('csv_file', fileInput.files[0]);

        fetch('<?= $base ?>/dashboard/leads/import', { method:'POST', body:fd })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-upload me-1"></i>Importar';
                if (data.error) {
                    resultEl.className = 'alert alert-danger';
                    resultEl.textContent = data.error;
                    return;
                }
                var html = '<strong>' + data.imported + ' contactos importados</strong>';
                if (data.skipped)  html += ', ' + data.skipped + ' ya existían (omitidos)';
                if (data.errors && data.errors.length) {
                    html += '<ul class="mt-2 mb-0">' + data.errors.map(e => '<li>' + escHtml(e) + '</li>').join('') + '</ul>';
                }
                resultEl.className = 'alert ' + (data.imported > 0 ? 'alert-success' : 'alert-warning');
                resultEl.innerHTML = html;
                if (data.imported > 0) setTimeout(() => location.reload(), 2500);
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-upload me-1"></i>Importar';
                resultEl.className = 'alert alert-danger';
                resultEl.textContent = 'Error de red. Intenta de nuevo.';
            });
    });

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>

<?php require __DIR__ . '/_foot.php'; ?>
