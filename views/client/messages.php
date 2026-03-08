<?php
/**
 * mia/views/client/messages.php — WhatsApp message inbox
 */
$base         = App::basePath();
$pageTitle    = 'Mensajes — Mia';
$pageTopTitle = 'Bandeja de Mensajes';
$activeNav    = 'messages';

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Filter tabs -->
<div class="d-flex gap-2 mb-3">
    <a href="<?= $base ?>/dashboard/messages"
       class="btn btn-sm <?= !$filter ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <i class="bi bi-inbox me-1"></i>Todos
    </a>
    <a href="?handled_by=mia"
       class="btn btn-sm <?= $filter === 'mia' ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <i class="bi bi-robot me-1"></i>Respondidos por Mia
    </a>
    <a href="?handled_by=human"
       class="btn btn-sm <?= $filter === 'human' ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <i class="bi bi-person me-1"></i>Respondidos por ti
    </a>
</div>

<div class="mc-table-card">
    <div class="card-header-bar">
        <span><i class="bi bi-chat-dots me-2 text-muted"></i>Mensajes recientes</span>
        <small class="text-muted">Mostrando últimos 30</small>
    </div>

    <?php if (empty($messages)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-chat fs-1 d-block mb-2 opacity-25"></i>
            <p>No hay mensajes <?= $filter ? 'con este filtro' : 'aún' ?>.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Contacto</th>
                        <th>Mensaje</th>
                        <th>Respondido por</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                    <tr>
                        <td>
                            <?php if ($msg['direction'] === 'inbound'): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                    <i class="bi bi-arrow-down me-1"></i>Entrada
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                    <i class="bi bi-arrow-up me-1"></i>Salida
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $msg['phone']) ?>"
                               target="_blank" class="text-reset text-decoration-none">
                                <i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($msg['phone']) ?>
                            </a>
                        </td>
                        <td class="text-muted small">
                            <?= htmlspecialchars($msg['lead_name'] ?? '—') ?>
                            <?php if ($msg['lead_id']): ?>
                                <a href="<?= $base ?>/dashboard/leads/<?= (int)$msg['lead_id'] ?>"
                                   class="ms-1 text-decoration-none" style="color:#25d366">
                                    <i class="bi bi-box-arrow-up-right" style="font-size:0.75rem"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:300px">
                            <span class="text-truncate d-block" style="max-width:280px" title="<?= htmlspecialchars($msg['message']) ?>">
                                <?= htmlspecialchars(mb_substr($msg['message'], 0, 80)) ?><?= mb_strlen($msg['message']) > 80 ? '…' : '' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($msg['handled_by'] === 'mia'): ?>
                                <span class="badge" style="background:rgba(37,211,102,0.12);color:#0a5c36;border:1px solid rgba(37,211,102,0.3)">
                                    <i class="bi bi-robot me-1"></i>Mia
                                </span>
                            <?php else: ?>
                                <span class="badge" style="background:rgba(13,110,253,0.1);color:#0a1f6e;border:1px solid rgba(13,110,253,0.2)">
                                    <i class="bi bi-person me-1"></i>Tú
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= date('d/m/y H:i', strtotime($msg['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_foot.php'; ?>
