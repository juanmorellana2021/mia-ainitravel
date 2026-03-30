<?php
/**
 * mia/views/client/sales_config.php — Sales behaviour configuration (Pro / Business)
 */
$base         = App::basePath();
$pageTitle    = 'Config. de Ventas — Mia';
$pageTopTitle = 'Configuración de Ventas';
$activeNav    = 'sales_config';

// Decode stored sales config
$bc      = json_decode($client->bot_config ?? '{}', true) ?: [];
$sc      = $bc['sales'] ?? [];
$sc = array_merge([
    'approach'            => 'friendly',
    'cta_text'            => '',
    'cta_link'            => '',
    'deposit_text'        => '',
    'qualifier_questions' => '',
    'qualifier_info'      => '',
    'handoff_triggers'    => '',
    'handoff_message'     => '',
    'handoff_phone'       => '',
    'followup_template'   => '',
    'special_offer'       => '',
    'yape_phone'          => '',
    'plin_phone'          => '',
    'bank_info'           => '',
    'payment_qr'          => '',
], $sc);

// Plan check for followup-gated section
$followupPlans = ['pro', 'enterprise', 'enterprise_duo', 'enterprise_chain', 'enterprise_corp', 'trial'];
$hasFollowup   = in_array($client->plan, $followupPlans);

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>
<!-- ── Main content ──────────────────────────────────────────────────────────── -->
<div class="mc-main-content">
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title"><?= $pageTopTitle ?></h1>
            <p class="mc-page-subtitle">Personaliza cómo Mia vende, califica leads y cierra negocios para ti.</p>
        </div>
    </div>

    <?php if ($saved): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <span>Configuración de ventas guardada correctamente.</span>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= $base ?>/dashboard/sales-config/save">
        <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">

        <!-- ── 1. Modo de ventas ─────────────────────────────────────────────── -->
        <div class="mc-table-card mb-4">
            <div class="p-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Modo de ventas</h5>
                <p class="text-muted small mb-4">Define el estilo que Mia usa para guiar a los clientes hacia la compra.</p>

                <div class="row g-3">
                    <?php
                    $approaches = [
                        'friendly' => [
                            'icon'  => 'bi-heart',
                            'label' => 'Consultivo',
                            'desc'  => 'Mia ayuda al cliente a encontrar lo que necesita sin presión. Ideal para servicios de alto valor o clientes indecisos.',
                        ],
                        'direct'   => [
                            'icon'  => 'bi-lightning-fill',
                            'label' => 'Directo al cierre',
                            'desc'  => 'Mia guía activamente la conversación hacia la compra o reserva. Hace preguntas de cierre, propone opciones concretas.',
                        ],
                        'urgency'  => [
                            'icon'  => 'bi-alarm',
                            'label' => 'Urgencia y escasez',
                            'desc'  => 'Mia menciona disponibilidad limitada, plazos o ventajas de decidir ahora mismo. Potente para promos y temporadas.',
                        ],
                    ];
                    foreach ($approaches as $val => $ap): ?>
                    <div class="col-md-4">
                        <label class="d-block h-100" style="cursor:pointer">
                            <input type="radio" name="sales_approach" value="<?= $val ?>"
                                   <?= $sc['approach'] === $val ? 'checked' : '' ?>
                                   style="display:none"
                                   onchange="document.querySelectorAll('.approach-card').forEach(c=>c.classList.remove('selected'));this.closest('label').querySelector('.approach-card').classList.add('selected')">
                            <div class="approach-card p-3 rounded-3 h-100 <?= $sc['approach'] === $val ? 'selected' : '' ?>"
                                 style="border:2px solid <?= $sc['approach'] === $val ? '#25d366' : 'rgba(255,255,255,.12)' ?>;background:rgba(255,255,255,.04);transition:border-color .2s"
                                 onclick="document.querySelectorAll('.approach-card').forEach(c=>{c.style.borderColor='rgba(255,255,255,.12)';});this.style.borderColor='#25d366';">
                                <div class="mb-2"><i class="bi <?= $ap['icon'] ?> fs-4 text-success"></i></div>
                                <div class="fw-bold mb-1"><?= $ap['label'] ?></div>
                                <small class="text-muted"><?= $ap['desc'] ?></small>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── 2. Llamada a la acción ────────────────────────────────────────── -->
        <div class="mc-table-card mb-4">
            <div class="p-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-cursor-fill me-2 text-success"></i>Cierre y llamada a la acción</h5>
                <p class="text-muted small mb-4">Qué quieres que Mia invite a hacer cuando el cliente está listo para comprar.</p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Texto del CTA principal</label>
                        <input type="text" class="form-control mc-form-control" name="cta_text"
                               placeholder="ej: ¡Reserva tu lugar ahora!"
                               maxlength="200"
                               value="<?= htmlspecialchars($sc['cta_text']) ?>">
                        <div class="form-text">Frase que Mia dirá para invitar al cliente a tomar acción.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Enlace de pago / reserva</label>
                        <input type="url" class="form-control mc-form-control" name="cta_link"
                               placeholder="https://pay.ejemplo.com/checkout"
                               maxlength="500"
                               value="<?= htmlspecialchars($sc['cta_link']) ?>">
                        <div class="form-text">URL que Mia compartirá para concretar la compra o reserva.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Anticipo / seña (opcional)</label>
                        <input type="text" class="form-control mc-form-control" name="deposit_text"
                               placeholder="ej: Se requiere 30% de anticipo para confirmar la reserva."
                               maxlength="200"
                               value="<?= htmlspecialchars($sc['deposit_text']) ?>">
                        <div class="form-text">Si manejas anticipos, Mia lo mencionará al momento de cerrar.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── 3. Calificación de leads ──────────────────────────────────────── -->
        <div class="mc-table-card mb-4">
            <div class="p-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-funnel me-2 text-success"></i>Calificación de leads</h5>
                <p class="text-muted small mb-4">Preguntas que Mia hace de manera natural para conocer el perfil y necesidades del cliente.</p>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Preguntas de calificación</label>
                        <textarea class="form-control mc-form-control" name="qualifier_questions"
                                  rows="5" maxlength="1000"
                                  placeholder="¿Para cuántas personas es el evento?&#10;¿Tienes alguna fecha en mente?&#10;¿Cuál es tu presupuesto aproximado?&#10;¿Ya visitaste alguno de nuestros locales antes?"><?= htmlspecialchars($sc['qualifier_questions']) ?></textarea>
                        <div class="form-text">Una pregunta por línea. Mia las incorporará de forma natural en la conversación, no como un formulario.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Información mínima a recopilar</label>
                        <input type="text" class="form-control mc-form-control" name="qualifier_info"
                               placeholder="ej: nombre, fecha deseada, número de personas, presupuesto"
                               maxlength="300"
                               value="<?= htmlspecialchars($sc['qualifier_info']) ?>">
                        <div class="form-text">Los datos sin los cuales no se puede avanzar al cierre. Mia los pedirá si el cliente no los dio.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── 4. Traspaso humano ────────────────────────────────────────────── -->
        <div class="mc-table-card mb-4">
            <div class="p-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-person-lines-fill me-2 text-success"></i>Traspaso a humano</h5>
                <p class="text-muted small mb-4">Cuándo y cómo Mia debe transferir la conversación a tu equipo.</p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Frases que activan el traspaso</label>
                        <textarea class="form-control mc-form-control" name="handoff_triggers"
                                  rows="3" maxlength="300"
                                  placeholder="quiero hablar con alguien&#10;necesito un ejecutivo&#10;hablar con el manager"><?= htmlspecialchars($sc['handoff_triggers']) ?></textarea>
                        <div class="form-text">Una por línea. Mia activará el traspaso si detecta alguna de estas.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Mensaje al activar traspaso</label>
                        <textarea class="form-control mc-form-control" name="handoff_message"
                                  rows="3" maxlength="400"
                                  placeholder="¡Entendido! Aviso al equipo ahora mismo. Alguien te contactará muy pronto 👋"><?= htmlspecialchars($sc['handoff_message']) ?></textarea>
                        <div class="form-text">Lo que Mia responderá al cliente al hacer el traspaso.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Teléfono del equipo (notificación)</label>
                        <input type="text" class="form-control mc-form-control" name="handoff_phone"
                               placeholder="+51 987 654 321"
                               maxlength="30"
                               value="<?= htmlspecialchars($sc['handoff_phone']) ?>">
                        <div class="form-text">Número al que Mia enviará un aviso cuando active un traspaso.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── 5. Oferta especial ────────────────────────────────────────────── -->
        <div class="mc-table-card mb-4">
            <div class="p-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-tag-fill me-2 text-success"></i>Oferta especial activa</h5>
                <p class="text-muted small mb-4">Una promoción o beneficio que Mia puede mencionar en el momento adecuado para impulsar la decisión.</p>

                <div class="col-12">
                    <textarea class="form-control mc-form-control" name="special_offer"
                              rows="3" maxlength="300"
                              placeholder="ej: Esta semana tenemos 15% de descuento en reservas para grupos de más de 10 personas."><?= htmlspecialchars($sc['special_offer']) ?></textarea>
                    <div class="form-text mt-2">Déjalo vacío si no hay ninguna oferta activa en este momento.</div>
                </div>
            </div>
        </div>

        <!-- ── 6. Métodos de pago ─────────────────────────────────────────── -->
        <div class="mc-table-card mb-4">
            <div class="p-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-wallet2 me-2 text-success"></i>Métodos de pago</h5>
                <p class="text-muted small mb-4">Configura tus métodos de pago para que Mia pueda indicarle al cliente cómo pagar.</p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold"><i class="bi bi-phone me-1"></i>Número de Yape</label>
                        <input type="text" class="form-control mc-form-control" name="yape_phone"
                               placeholder="ej: 987 654 321"
                               maxlength="30"
                               value="<?= htmlspecialchars($sc['yape_phone']) ?>">
                        <div class="form-text">Mia compartirá este número cuando el cliente quiera pagar con Yape.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold"><i class="bi bi-phone me-1"></i>Número de Plin</label>
                        <input type="text" class="form-control mc-form-control" name="plin_phone"
                               placeholder="ej: 987 654 321"
                               maxlength="30"
                               value="<?= htmlspecialchars($sc['plin_phone']) ?>">
                        <div class="form-text">Mia compartirá este número cuando el cliente quiera pagar con Plin.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold"><i class="bi bi-bank me-1"></i>Datos de transferencia bancaria (opcional)</label>
                        <textarea class="form-control mc-form-control" name="bank_info"
                                  rows="2" maxlength="500"
                                  placeholder="ej: BCP Cta. Ahorros 123-456789-0-12 — Razón social: Mi Empresa SAC"><?= htmlspecialchars($sc['bank_info']) ?></textarea>
                        <div class="form-text">Si aceptas transferencias bancarias, Mia dará estos datos cuando el cliente lo pida.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold"><i class="bi bi-qr-code me-1"></i>Código QR de pago (Yape/Plin)</label>
                        <div class="d-flex align-items-start gap-3">
                            <?php if (!empty($sc['payment_qr'])): ?>
                            <div id="qrPreview" class="position-relative" style="width:150px;flex-shrink:0">
                                <img src="<?= htmlspecialchars($sc['payment_qr']) ?>" class="img-fluid rounded border" alt="QR de pago">
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" id="qrDeleteBtn" style="font-size:.7rem;padding:2px 6px" title="Eliminar QR">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <input type="file" class="form-control mc-form-control" id="qrFileInput" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">Sube una imagen de tu código QR de Yape o Plin. Mia la enviará cuando el cliente quiera pagar.</div>
                                <div id="qrUploadStatus" class="small mt-1"></div>
                            </div>
                        </div>
                        <input type="hidden" name="payment_qr" id="paymentQrValue" value="<?= htmlspecialchars($sc['payment_qr']) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ── 7. Seguimiento (Pro+) ─────────────────────────────────────────── -->
        <div class="mc-table-card mb-4 <?= !$hasFollowup ? 'opacity-50' : '' ?>" style="position:relative">
            <?php if (!$hasFollowup): ?>
            <div style="position:absolute;inset:0;z-index:2;display:flex;align-items:center;justify-content:center;border-radius:inherit">
                <a href="<?= $base ?>/dashboard/billing?upgrade=followup"
                   class="btn btn-warning fw-bold px-4">
                    <i class="bi bi-lightning-fill me-1"></i> Disponible en Plan Pro — Actualizar
                </a>
            </div>
            <?php endif; ?>
            <div class="p-4">
                <h5 class="fw-bold mb-1">
                    <i class="bi bi-reply-all me-2 text-success"></i>Mensaje de seguimiento
                    <?php if (!$hasFollowup): ?>
                    <span class="badge bg-warning text-dark ms-2" style="font-size:.65rem">Pro</span>
                    <?php endif; ?>
                </h5>
                <p class="text-muted small mb-4">Plantilla que Mia puede usar para retomar contacto con leads que no respondieron.</p>

                <textarea class="form-control mc-form-control" name="followup_template"
                          rows="3" maxlength="500"
                          <?= !$hasFollowup ? 'disabled' : '' ?>
                          placeholder="ej: ¡Hola! Solo quería ver si pudiste revisar la información que te envié. ¿Tienes alguna duda que pueda resolver? 😊"><?= htmlspecialchars($sc['followup_template']) ?></textarea>
                <div class="form-text mt-2">Esta plantilla se usa en secuencias de seguimiento automáticas.</div>
            </div>
        </div>

        <!-- ── Save ─────────────────────────────────────────────────────────── -->
        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="<?= $base ?>/dashboard" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-success px-4 fw-semibold">
                <i class="bi bi-check-circle me-1"></i> Guardar configuración de ventas
            </button>
        </div>

    </form>
</div>

<style>
.approach-card:hover { border-color: rgba(37,211,102,.5) !important; }
.approach-card.selected { border-color: #25d366 !important; background: rgba(37,211,102,.08) !important; }
</style>

<script>
(function(){
    const BASE = '<?= App::basePath() ?>';
    const csrf = '<?= App::csrfToken() ?>';
    const fileInput = document.getElementById('qrFileInput');
    const hiddenVal = document.getElementById('paymentQrValue');
    const statusEl  = document.getElementById('qrUploadStatus');

    if (fileInput) {
        fileInput.addEventListener('change', function(){
            const file = this.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                statusEl.innerHTML = '<span class="text-danger">El archivo es muy grande (máx 2 MB)</span>';
                return;
            }
            statusEl.innerHTML = '<span class="text-muted"><i class="bi bi-arrow-repeat spin"></i> Subiendo...</span>';
            const fd = new FormData();
            fd.append('qr_image', file);
            fd.append('_csrf', csrf);
            fetch(BASE + '/dashboard/sales-config/upload-qr', { method:'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.url) {
                        hiddenVal.value = data.url;
                        statusEl.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> QR subido. Recuerda guardar la configuración.</span>';
                        // Show preview
                        let preview = document.getElementById('qrPreview');
                        if (!preview) {
                            preview = document.createElement('div');
                            preview.id = 'qrPreview';
                            preview.className = 'position-relative';
                            preview.style.cssText = 'width:150px;flex-shrink:0';
                            fileInput.closest('.d-flex').prepend(preview);
                        }
                        preview.innerHTML = '<img src="' + data.url + '" class="img-fluid rounded border" alt="QR de pago">';
                    } else {
                        statusEl.innerHTML = '<span class="text-danger">' + (data.error || 'Error al subir') + '</span>';
                    }
                })
                .catch(() => { statusEl.innerHTML = '<span class="text-danger">Error de conexión</span>'; });
        });
    }

    const delBtn = document.getElementById('qrDeleteBtn');
    if (delBtn) {
        delBtn.addEventListener('click', function(){
            if (!confirm('¿Eliminar el código QR?')) return;
            hiddenVal.value = '';
            const preview = document.getElementById('qrPreview');
            if (preview) preview.remove();
        });
    }
})();
</script>

<?php require __DIR__ . '/_foot.php'; ?>
