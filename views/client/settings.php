<?php
/**
 * mia/views/client/settings.php — Account & notification settings
 */
$base         = App::basePath();
$pageTitle    = 'Configuración — Mia';
$pageTopTitle = 'Configuración';
$activeNav    = 'settings';

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<?php if ($saved): ?>
<div class="alert border-0 rounded-3 mb-4" style="background:rgba(37,211,102,0.12);color:#155724">
    <i class="bi bi-check-circle me-2"></i><strong>Cambios guardados.</strong>
</div>
<?php endif; ?>

<form method="POST" action="<?= $base ?>/dashboard/settings/save">
    <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">

    <div class="row g-4">

        <!-- ── Profile ──────────────────────────────────────────────────────── -->
        <div class="col-md-6">
            <div class="mc-table-card p-4 h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-circle me-2 text-primary"></i>Perfil de cuenta</h6>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Nombre de contacto</label>
                    <input type="text" name="contact_name" class="form-control"
                           value="<?= htmlspecialchars($client->contact_name) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Teléfono de contacto</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= htmlspecialchars($client->phone) ?>"
                           placeholder="+51 999 888 777">
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-semibold text-muted">Email de cuenta</label>
                    <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($client->email) ?>" disabled>
                    <div class="form-text">Para cambiar tu email escribe al soporte.</div>
                </div>
            </div>
        </div>

        <!-- ── Bot info ──────────────────────────────────────────────────────── -->
        <div class="col-md-6">
            <div class="mc-table-card p-4 h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-whatsapp me-2" style="color:#25d366"></i>Tu bot de Mia</h6>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Número de WhatsApp</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-light"
                               value="<?= htmlspecialchars($client->whatsapp_number ?: App::WHATSAPP) ?>"
                               id="wpNum" readonly>
                        <button type="button" class="btn btn-outline-secondary" onclick="copyWp()">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-semibold text-muted">Enlace directo (WhatsApp)</label>
                    <?php
                        $num = ltrim($client->whatsapp_number ?: App::WHATSAPP, '+');
                        $waLink = "https://wa.me/{$num}";
                    ?>
                    <a href="<?= htmlspecialchars($waLink) ?>" target="_blank"
                       class="d-block text-truncate small" style="color:#25d366">
                        <?= htmlspecialchars($waLink) ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- ── Notifications ────────────────────────────────────────────────── -->
        <div class="col-12">
            <div class="mc-table-card p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-bell me-2 text-warning"></i>Notificaciones</h6>

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold text-muted">Email para notificaciones</label>
                        <input type="email" name="notify_email" class="form-control"
                               value="<?= htmlspecialchars($client->notify_email ?? '') ?>"
                               placeholder="Deja vacío para usar email de cuenta">
                        <div class="form-text">Si lo dejas vacío, usamos tu email de cuenta.</div>
                    </div>

                    <div class="col-md-7 d-flex flex-column gap-3 justify-content-center ps-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   name="notify_on_capture" value="1" id="notifCapture"
                                   <?= $client->notify_on_capture ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="notifCapture">
                                Aviso por email cuando Mia captura un lead
                            </label>
                            <div class="form-text">Email instantáneo con nombre, teléfono y email del lead.</div>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   name="notify_daily_summary" value="1" id="notifDaily"
                                   <?= $client->notify_daily_summary ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="notifDaily">
                                Resumen diario de actividad
                            </label>
                            <div class="form-text">Email diario con leads nuevos y mensajes del día.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Save button ──────────────────────────────────────────────────── -->
        <div class="col-12">
            <button type="submit" class="btn btn-success px-4" style="background:#25d366;border-color:#25d366">
                <i class="bi bi-check-circle me-2"></i>Guardar cambios
            </button>
        </div>

    </div>
</form>

<script>
function copyWp() {
    const el = document.getElementById('wpNum');
    navigator.clipboard.writeText(el.value).then(() => {
        const btn = el.nextElementSibling;
        btn.innerHTML = '<i class="bi bi-check"></i>';
        setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i>', 1500);
    });
}
</script>

<?php require __DIR__ . '/_foot.php'; ?>
