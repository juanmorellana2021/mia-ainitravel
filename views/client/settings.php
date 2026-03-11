<?php
/**
 * mia/views/client/settings.php — Account & bot configuration settings
 */
$base         = App::basePath();
$pageTitle    = 'Configuración — Mia';
$pageTopTitle = 'Configuración';
$activeNav    = 'settings';

// Decode stored bot_config JSON, fall back to empty defaults
$bc = json_decode($client->bot_config ?? '{}', true) ?: [];
$bc = array_merge([
    'business_type' => $client->business_type ?: 'other',
    'description'   => '',
    'services'      => '',
    'pricing'       => '',
    'hours'         => '',
    'faqs'          => '',
    'language'      => 'es',
    'tone'          => 'friendly',
], $bc);

// Plan capabilities
$planCaps = [
    'trial'      => ['basic'],
    'starter'    => ['basic'],
    'basic'      => ['basic', 'handoff'],
    'pro'        => ['basic', 'handoff', 'leads', 'followup'],
    'enterprise' => ['basic', 'handoff', 'leads', 'followup', 'multilang'],
];
$caps = $planCaps[$client->plan] ?? ['basic'];

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

        <!-- ── Profile ────────────────────────────────────────────────────── -->
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
                    <input type="email" class="form-control bg-light"
                           value="<?= htmlspecialchars($client->email) ?>" disabled>
                    <div class="form-text">Para cambiar tu email escríbenos al soporte.</div>
                </div>
            </div>
        </div>

        <!-- ── WhatsApp status ────────────────────────────────────────────── -->
        <div class="col-md-6">
            <div class="mc-table-card p-4 h-100">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-whatsapp me-2" style="color:#25d366"></i>Conectar WhatsApp
                </h6>

                <?php if ($client->bot_wa_status === 'connected' && $client->whatsapp_number): ?>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-success fs-6 px-3 py-2">
                            <i class="bi bi-check-circle me-1"></i>Conectado
                        </span>
                        <span class="text-muted small"><?= htmlspecialchars($client->whatsapp_number) ?></span>
                    </div>
                    <p class="text-muted small mb-3">
                        Tu WhatsApp está activo. Mia ya responde a tus clientes automáticamente.
                    </p>
                    <?php
                        $num = ltrim($client->whatsapp_number, '+');
                        $waLink = "https://wa.me/{$num}";
                    ?>
                    <a href="<?= htmlspecialchars($waLink) ?>" target="_blank"
                       class="btn btn-outline-success btn-sm">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Probar enlace
                    </a>
                <?php else: ?>
                    <p class="text-muted small mb-3">
                        Escanea el código QR con tu WhatsApp para conectarlo a Mia.
                        Una vez conectado, Mia responderá automáticamente a tus clientes.
                    </p>
                    <div class="text-center p-3 border rounded-3 bg-light" id="waConnectBox">
                        <div class="spinner-border spinner-border-sm text-success mb-2" role="status"></div>
                        <div class="text-muted small">Cargando código QR...</div>
                    </div>
                    <div class="form-text mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Abre WhatsApp → Dispositivos vinculados → Vincular dispositivo → escanea el QR.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
             BOT CONFIGURATION
             ══════════════════════════════════════════════════════════════ -->
        <div class="col-12">
            <div class="mc-table-card p-4">
                <h6 class="fw-bold mb-1">
                    <i class="bi bi-robot me-2 text-success"></i>Configura tu Mia
                </h6>
                <p class="text-muted small mb-4">
                    Cuéntale a Mia sobre tu negocio para que responda exactamente como tú lo harías.
                </p>

                <div class="row g-4">

                    <!-- Business type + tone -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Tipo de negocio</label>
                        <select name="business_type" class="form-select">
                            <?php
                            $types = [
                                'hotel'         => '🏨 Hotel / Hostal / Alojamiento',
                                'travel_agency' => '✈️ Agencia de viajes / Tours',
                                'restaurant'    => '🍽️ Restaurante / Café / Bar',
                                'retail'        => '🛍️ Tienda / Boutique / Retail',
                                'services'      => '💼 Servicios profesionales',
                                'health'        => '🏥 Salud / Clínica / Bienestar',
                                'education'     => '📚 Educación / Academia',
                                'other'         => '🏢 Otro negocio',
                            ];
                            foreach ($types as $val => $label):
                            ?>
                            <option value="<?= $val ?>" <?= $bc['business_type'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Tono del bot</label>
                        <select name="bot_tone" class="form-select">
                            <option value="friendly"    <?= $bc['tone'] === 'friendly'    ? 'selected' : '' ?>>😊 Amigable y cercano</option>
                            <option value="professional"<?= $bc['tone'] === 'professional'? 'selected' : '' ?>>👔 Profesional y formal</option>
                            <option value="casual"      <?= $bc['tone'] === 'casual'      ? 'selected' : '' ?>>🤙 Casual y relajado</option>
                            <option value="luxury"      <?= $bc['tone'] === 'luxury'      ? 'selected' : '' ?>>✨ Exclusivo y de lujo</option>
                        </select>
                    </div>

                    <!-- Language -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">
                            Idioma principal
                            <?php if (!in_array('multilang', $caps)): ?>
                            <span class="badge bg-warning text-dark ms-1" title="Plan Pro o superior">Pro+</span>
                            <?php endif; ?>
                        </label>
                        <select name="bot_language" class="form-select" <?= !in_array('multilang', $caps) ? 'disabled' : '' ?>>
                            <option value="es" <?= $bc['language'] === 'es' ? 'selected' : '' ?>>🇵🇪 Español</option>
                            <option value="en" <?= $bc['language'] === 'en' ? 'selected' : '' ?>>🇺🇸 English</option>
                            <option value="auto" <?= $bc['language'] === 'auto' ? 'selected' : '' ?>>🌐 Automático (detecta el idioma del cliente)</option>
                        </select>
                        <?php if (!in_array('multilang', $caps)): ?>
                        <input type="hidden" name="bot_language" value="<?= htmlspecialchars($bc['language']) ?>">
                        <div class="form-text text-warning">Multilingüe disponible en Plan Pro o superior.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-muted">
                            Descripción de tu negocio
                        </label>
                        <textarea name="bot_description" class="form-control" rows="3"
                                  placeholder="Ej: Somos el Hotel Las Orquídeas en Cusco, Perú. Tenemos 25 habitaciones con vista al Templo del Sol. Atendemos a viajeros nacionales e internacionales...">
<?= htmlspecialchars($bc['description']) ?></textarea>
                        <div class="form-text">Cuanto más detallada, mejor responde Mia. Incluye ubicación, especialidad y tipo de cliente.</div>
                    </div>

                    <!-- Services -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Servicios / Productos</label>
                        <textarea name="bot_services" class="form-control" rows="4"
                                  placeholder="Ej:&#10;- Habitación Simple: cama matrimonial, baño privado, WiFi, desayuno incluido&#10;- Suite Junior: sala + dormitorio, vista al jardín&#10;- Tour Machu Picchu: salida 6am, guía incluido, almuerzo...">
<?= htmlspecialchars($bc['services']) ?></textarea>
                        <div class="form-text">Lista tus servicios/productos principales con sus características.</div>
                    </div>

                    <!-- Pricing -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Precios</label>
                        <textarea name="bot_pricing" class="form-control" rows="4"
                                  placeholder="Ej:&#10;- Habitación Simple: S/180/noche&#10;- Suite Junior: S/280/noche&#10;- Tour Machu Picchu: S/350 por persona&#10;- Descuento grupos 5+: 15% off">
<?= htmlspecialchars($bc['pricing']) ?></textarea>
                        <div class="form-text">Mia usará estos precios para responder consultas de tarifas.</div>
                    </div>

                    <!-- Hours -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Horarios de atención</label>
                        <textarea name="bot_hours" class="form-control" rows="3"
                                  placeholder="Ej:&#10;- Recepción: 24 horas&#10;- Restaurante: 7am - 10pm&#10;- Check-in: 3pm / Check-out: 12pm&#10;- Tours: salida diaria 6am">
<?= htmlspecialchars($bc['hours']) ?></textarea>
                    </div>

                    <!-- FAQs -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Preguntas frecuentes (FAQ)</label>
                        <textarea name="bot_faqs" class="form-control" rows="3"
                                  placeholder="Ej:&#10;¿Aceptan mascotas? Sí, solo en habitaciones de primer piso.&#10;¿Tiene estacionamiento? Sí, gratuito para huéspedes.&#10;¿Aceptan pago con tarjeta? Sí, Visa y Mastercard.">
<?= htmlspecialchars($bc['faqs']) ?></textarea>
                        <div class="form-text">Mia responderá estas preguntas automáticamente.</div>
                    </div>

                    <!-- Plan-gated features notice -->
                    <?php if (in_array('leads', $caps)): ?>
                    <div class="col-12">
                        <div class="alert border-0 rounded-3 py-2 px-3" style="background:rgba(37,211,102,0.08)">
                            <i class="bi bi-lightning-charge-fill text-success me-1"></i>
                            <strong>Tu plan incluye captura de leads:</strong>
                            Mia pedirá nombre, email y teléfono a los clientes interesados y los guardará en tu panel.
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="alert border-0 rounded-3 py-2 px-3 bg-light text-muted small">
                            <i class="bi bi-lock me-1"></i>
                            <strong>Captura de leads y seguimiento automático</strong> disponibles en Plan Basic o superior.
                            <a href="<?= $base ?>/dashboard/billing" class="ms-1">Ver planes →</a>
                        </div>
                    </div>
                    <?php endif; ?>

                </div><!-- /row -->
            </div>
        </div>

        <!-- ── Notifications ──────────────────────────────────────────────── -->
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

        <!-- ── Save ───────────────────────────────────────────────────────── -->
        <div class="col-12">
            <button type="submit" class="btn btn-success px-4"
                    style="background:#25d366;border-color:#25d366">
                <i class="bi bi-check-circle me-2"></i>Guardar cambios
            </button>
        </div>

    </div>
</form>

<?php if ($client->bot_wa_status !== 'connected'): ?>
<script>
// Poll bot server for QR code (via PHP proxy)
(function() {
    const box = document.getElementById('waConnectBox');
    if (!box) return;

    function loadQr() {
        fetch('<?= $base ?>/dashboard/settings/wa-qr')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'connected') {
                    box.innerHTML = '<div class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>¡WhatsApp conectado! Recarga la página.</div>';
                    setTimeout(() => location.reload(), 1500);
                } else if (data.qr_image) {
                    box.innerHTML = '<img src="' + data.qr_image + '" class="img-fluid" style="max-width:220px" alt="QR Code"><div class="text-muted small mt-2">Escanea con WhatsApp → Dispositivos vinculados</div>';
                    setTimeout(loadQr, 20000); // refresh every 20s (QR expires)
                } else {
                    box.innerHTML = '<div class="text-muted small"><i class="bi bi-hourglass-split me-1"></i>Preparando QR... <a href="" onclick="location.reload();return false;">recargar</a></div>';
                    setTimeout(loadQr, 5000);
                }
            })
            .catch(() => {
                box.innerHTML = '<div class="text-muted small">No se pudo cargar el QR. <a href="" onclick="location.reload();return false;">Intentar de nuevo</a></div>';
            });
    }
    loadQr();
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/_foot.php'; ?>
