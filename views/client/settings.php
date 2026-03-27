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
    'custom_type'   => '',
    'description'   => '',
    'services'      => '',
    'pricing'       => '',
    'hours'         => '',
    'faqs'          => '',
    'website'       => '',
    'location'      => '',
    'google_maps'   => '',
    'language'      => 'es',
    'tone'          => 'friendly',
    'char_skills'   => [],
    'hours_enabled' => false,
    'hours_config'  => [],
], $bc);
$activeSkills = (array)($bc['char_skills'] ?? []);

// All available character skills
$allSkills = [
    'humor'    => ['😄', 'Toque de humor',       'Agrega chispa con humor ligero cuando el contexto lo permite'],
    'empathy'  => ['💖', 'Muy empática',          'Reconoce los sentimientos del cliente antes de responder'],
    'stories'  => ['📖', 'Cuenta historias',      'Menciona casos de éxito similares para generar confianza'],
    'direct'   => ['⚡', 'Directa al grano',      'Sin rodeos — va al punto en la primera frase'],
    'scarcity' => ['⏰', 'Urgencia / escasez',    'Menciona disponibilidad limitada u ofertas por tiempo'],
    'patient'  => ['🙏', 'Nunca presiona',        'Paciente y sin presión — el cliente decide a su ritmo'],
    'usted'    => ['🎩', 'Trato formal (usted)',  'Usa "usted" siempre en lugar de "tú"'],
    'tips'     => ['💡', 'Da consejos de valor',  'Incluye tips extras útiles relacionados con la consulta'],
    'premium'  => ['✨', 'Voz premium',           'Vocabulario refinado — "inversión", "exclusivo", no "barato"'],
    'proactive'=> ['🔄', 'Proactiva',             'Ofrece alternativas y próximos pasos sin que se los pidan'],
];

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

<?php if ($onboarding): ?>
<!-- ── Onboarding wizard progress banner ───────────────────────────────────── -->
<?php
$_hasBotCfg   = !empty($bc['description']) || !empty($bc['services']);
$_hasWa       = $client->bot_wa_status === 'connected';
$_obStep      = $_hasWa ? 3 : ($_hasBotCfg ? 2 : 1);
?>
<div class="mc-table-card mb-4" style="border:2px solid rgba(37,211,102,0.35)">
    <div class="p-4">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div style="width:44px;height:44px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="bi bi-whatsapp text-white" style="font-size:1.3rem"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Bienvenido/a, <?= htmlspecialchars($client->contact_name) ?> 👋</h6>
                <p class="text-muted small mb-0">Sigue estos 3 pasos para activar tu Mia en WhatsApp.</p>
            </div>
            <a href="<?= $base ?>/dashboard" class="btn btn-sm btn-outline-secondary ms-auto d-none d-md-inline">Configurar después</a>
        </div>

        <div class="row g-3">
            <?php
            $steps = [
                ['Configura tu negocio',   'Cuéntale a Mia sobre tu empresa, servicios y precios.',        $_obStep >= 2],
                ['Conecta tu WhatsApp',    'Escanea el QR desde WhatsApp Business → Dispositivos vinculados.', $_obStep >= 3],
                ['¡Mia está lista! 🎉', 'Tu bot ya responde automáticamente en WhatsApp.',            $_obStep === 3],
            ];
            foreach ($steps as $i => [$title, $desc, $done]):
                $num     = $i + 1;
                $active  = ($num === $_obStep);
                $bgCard  = $done ? 'rgba(37,211,102,0.08)' : '#f8fafc';
                $bgBadge = $done ? '#25d366' : ($active ? 'rgba(37,211,102,0.45)' : '#dee2e6');
                $txtNum  = ($done || $active) ? '#fff' : '#495057';
            ?>
            <div class="col-md-4">
                <div class="d-flex align-items-start gap-3 p-3 rounded-3 h-100" style="background:<?= $bgCard ?>">
                    <div style="width:32px;height:32px;border-radius:50%;background:<?= $bgBadge ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <?php if ($done): ?>
                        <i class="bi bi-check-lg" style="color:#fff;font-size:.85rem"></i>
                        <?php else: ?>
                        <span style="font-size:.82rem;font-weight:700;color:<?= $txtNum ?>"><?= $num ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="fw-semibold small <?= $active ? 'text-success' : '' ?>"><?= $title ?></div>
                        <div class="text-muted" style="font-size:.74rem"><?= $desc ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

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

                    <!-- ── QR & Deep link generator ─────────────────────── -->
                    <hr class="my-3">
                    <p class="small fw-semibold mb-2"><i class="bi bi-qr-code me-1"></i>Tu enlace directo de WhatsApp</p>
                    <div id="waLinkBox">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="genWaLinkBtn">
                            <i class="bi bi-qr-code-scan me-1"></i>Generar QR y enlace
                        </button>
                    </div>
                    <div id="waLinkResult" class="d-none mt-3">
                        <div class="d-flex flex-wrap gap-4 align-items-start">
                            <img id="waQrImg" src="" alt="QR WhatsApp"
                                 style="width:150px;height:150px;border:1px solid #dee2e6;border-radius:8px">
                            <div>
                                <p class="text-muted small mb-1">Enlace directo (compártelo o ponlo en tu web):</p>
                                <div class="input-group input-group-sm" style="max-width:380px">
                                    <input type="text" id="waDeepLink" class="form-control" readonly>
                                    <button class="btn btn-outline-secondary" type="button" id="copyWaLinkBtn"
                                            title="Copiar enlace">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                                <div class="mt-2 d-flex gap-2">
                                    <a id="waQrDownload" href="#" download="qr-whatsapp.png"
                                       class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-download me-1"></i>Descargar QR
                                    </a>
                                    <a id="waLinkOpen" href="#" target="_blank"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Abrir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-3">
                        Conecta tu WhatsApp de negocio para que Mia responda automáticamente a tus clientes.
                    </p>
                    <!-- Step 1: connect button -->
                    <div id="waStep1">
                        <button type="button" class="btn btn-success w-100"
                                id="waConnectBtn"
                                style="background:#25d366;border-color:#25d366">
                            <i class="bi bi-whatsapp me-2"></i>Iniciar conexión WhatsApp
                        </button>
                    </div>
                    <!-- Step 2: QR box (hidden until connect clicked) -->
                    <div id="waStep2" class="d-none">
                        <div class="text-center p-3 border rounded-3 bg-light" id="waConnectBox">
                            <div class="spinner-border spinner-border-sm text-success mb-2" role="status"></div>
                            <div class="text-muted small">Iniciando sesión...</div>
                        </div>
                        <div class="form-text mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Abre WhatsApp → Dispositivos vinculados → Vincular dispositivo → escanea el QR.
                        </div>
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

                    <!-- ── AI Business Search ───────────────────────────── -->
                    <div class="col-12">
                        <div class="p-3 rounded-3" style="background:linear-gradient(135deg,rgba(37,211,102,0.08),rgba(99,102,241,0.06));border:1.5px dashed rgba(37,211,102,0.35)">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-search-heart text-success fs-5"></i>
                                <span class="fw-semibold small">Buscar mi negocio con IA</span>
                                <span class="badge bg-success bg-opacity-10 text-success" style="font-size:0.72rem">Nuevo</span>
                            </div>
                            <p class="text-muted mb-2" style="font-size:0.82rem">
                                Escribe el nombre de tu negocio (y ciudad si quieres). La IA generará un perfil completo que puedes editar.
                            </p>
                            <div class="d-flex gap-2">
                                <input type="text" id="bizSearchInput" class="form-control form-control-sm"
                                       placeholder="Ej: Hotel Las Orquídeas, Cusco  ·  Pizzería La Brasa, Lima  ·  Consultora López"
                                       style="max-width:480px">
                                <button type="button" id="bizSearchBtn" class="btn btn-sm btn-success px-3"
                                        style="background:#25d366;border-color:#25d366;white-space:nowrap">
                                    <i class="bi bi-stars me-1"></i>Generar perfil
                                </button>
                            </div>
                            <div id="bizSearchStatus" class="mt-2" style="font-size:0.82rem;display:none"></div>
                        </div>
                    </div>

                    <!-- Business type + tone + language (3 cols) -->
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">Tipo de negocio</label>
                        <select name="business_type" class="form-select" id="businessTypeSelect">
                            <?php
                            $types = [
                                'hotel'         => '🏨 Hotel / Hostal / Alojamiento',
                                'travel_agency' => '✈️ Agencia de viajes / Tours',
                                'restaurant'    => '🍽️ Restaurante / Café / Bar',
                                'retail'        => '🛍️ Tienda / Boutique / Retail',
                                'services'      => '💼 Servicios profesionales',
                                'health'        => '🏥 Salud / Clínica / Bienestar',
                                'education'     => '📚 Educación / Academia',
                                'other'         => '🏢 Otro tipo de negocio...',
                            ];
                            foreach ($types as $val => $label):
                            ?>
                            <option value="<?= $val ?>" <?= $bc['business_type'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Custom type — shown only when "other" is selected -->
                        <div id="customTypeWrap" style="display:<?= $bc['business_type'] === 'other' ? '' : 'none' ?>; margin-top:8px">
                            <input type="text" name="bot_custom_type" id="customTypeInput"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($bc['custom_type'] ?? '') ?>"
                                   placeholder="Describe el tipo: peluquería, ferretería, spa...">
                            <div class="form-text">Mia usará esto para adaptar sus respuestas.</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">Tono del bot</label>
                        <select name="bot_tone" class="form-select">
                            <option value="friendly"    <?= $bc['tone'] === 'friendly'    ? 'selected' : '' ?>>😊 Amigable y cercano</option>
                            <option value="professional"<?= $bc['tone'] === 'professional'? 'selected' : '' ?>>👔 Profesional y formal</option>
                            <option value="casual"      <?= $bc['tone'] === 'casual'      ? 'selected' : '' ?>>🤙 Casual y relajado</option>
                            <option value="luxury"      <?= $bc['tone'] === 'luxury'      ? 'selected' : '' ?>>✨ Exclusivo y de lujo</option>
                        </select>
                    </div>

                    <!-- Language -->
                    <div class="col-md-4">
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

                    <!-- ── Template banner (JS shows this on type change) ──── -->
                    <div class="col-12" id="templateBannerWrap" style="display:none">
                        <div class="d-flex align-items-center gap-3 px-3 py-2 rounded-3"
                             style="background:rgba(37,211,102,0.10);border:1.5px solid rgba(37,211,102,0.3)">
                            <i class="bi bi-magic text-success fs-5"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">Plantilla lista para <span id="templateTypeName">este negocio</span></div>
                                <div class="text-muted" style="font-size:0.79rem">Carga un ejemplo de partida y edítalo con tu información real — ahorra tiempo.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-success px-3"
                                    id="applyTemplateBtn"
                                    style="background:#25d366;border-color:#25d366;white-space:nowrap">
                                <i class="bi bi-lightning-charge me-1"></i>Cargar plantilla
                            </button>
                        </div>
                    </div>

                    <!-- Website + Location -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">
                            <i class="bi bi-globe2 me-1"></i>Sitio web / Facebook / Instagram
                        </label>
                        <input type="text" name="bot_website" class="form-control"
                               value="<?= htmlspecialchars($bc['website']) ?>"
                               placeholder="https://www.minegocio.com  o  https://instagram.com/minegocio">
                        <div class="form-text">Mia podrá indicar a los clientes dónde encontrar más información de tu negocio.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">
                            <i class="bi bi-geo-alt me-1"></i>Dirección completa
                        </label>
                        <input type="text" name="bot_location" class="form-control"
                               value="<?= htmlspecialchars($bc['location']) ?>"
                               placeholder="Av. Larco 234, Miraflores, Lima, Perú">
                        <div class="form-text">Dirección física de tu negocio. Mia la compartirá cuando pregunten dónde están ubicados.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">
                            <i class="bi bi-pin-map me-1"></i>Link de Google Maps
                        </label>
                        <input type="url" name="bot_google_maps" class="form-control"
                               value="<?= htmlspecialchars($bc['google_maps']) ?>"
                               placeholder="https://maps.google.com/?q=...  o  https://goo.gl/maps/...">
                        <div class="form-text">Pega aquí el enlace de Google Maps de tu negocio. Mia lo enviará cuando pregunten cómo llegar.</div>
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

                    <!-- Character Skills ──────────────────────────────────── -->
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-muted d-block mb-1">
                            <i class="bi bi-stars me-1 text-warning"></i>Habilidades de personalidad
                        </label>
                        <p class="text-muted small mb-3">
                            Selecciona los rasgos de carácter que quieres que Mia tenga al hablar con tus clientes.
                            Puedes combinar varios.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($allSkills as $key => [$emoji, $label, $desc]): ?>
                        <?php $isActive = in_array($key, $activeSkills); ?>
                        <label class="skill-pill <?= $isActive ? 'active' : '' ?>" title="<?= htmlspecialchars($desc) ?>">
                            <input type="checkbox" name="char_skills[]" value="<?= $key ?>" <?= $isActive ? 'checked' : '' ?> style="display:none">
                            <span><?= $emoji ?> <?= $label ?></span>
                        </label>
                        <?php endforeach; ?>
                        </div>
                        <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i>Pasa el mouse sobre cada habilidad para ver qué hace.</div>
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

        <!-- ── Business Hours ────────────────────────────────────────────── -->
        <div class="col-12">
            <div class="mc-table-card p-4">
                <div class="d-flex align-items-center gap-3 mb-1">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock me-2 text-info"></i>Horario de atención</h6>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch"
                               name="hours_enabled" value="1" id="hoursEnabled"
                               <?= !empty($bc['hours_enabled']) ? 'checked' : '' ?>
                               onchange="document.getElementById('hoursConfig').style.display=this.checked?'':'none'">
                        <label class="form-check-label small fw-semibold" for="hoursEnabled">Activar</label>
                    </div>
                </div>
                <p class="text-muted small mb-3">
                    Cuando estés fuera de horario, Mia responde con un mensaje de cierre en lugar de usar la IA.
                </p>

                <?php
                $hcData = $bc['hours_config'] ?? [];
                $hcTz   = $hcData['timezone'] ?? 'America/Lima';
                $hcSch  = $hcData['schedule'] ?? [];
                $hcMsg  = $hcData['closed_message'] ?? '';
                $days   = [
                    'mon' => 'Lunes',   'tue' => 'Martes',  'wed' => 'Miércoles',
                    'thu' => 'Jueves',  'fri' => 'Viernes', 'sat' => 'Sábado',
                    'sun' => 'Domingo',
                ];
                $defaultTimes = [
                    'mon'=>['open'=>'09:00','close'=>'18:00','enabled'=>true],
                    'tue'=>['open'=>'09:00','close'=>'18:00','enabled'=>true],
                    'wed'=>['open'=>'09:00','close'=>'18:00','enabled'=>true],
                    'thu'=>['open'=>'09:00','close'=>'18:00','enabled'=>true],
                    'fri'=>['open'=>'09:00','close'=>'17:00','enabled'=>true],
                    'sat'=>['open'=>'09:00','close'=>'13:00','enabled'=>false],
                    'sun'=>['open'=>'','close'=>'','enabled'=>false],
                ];
                $hcSch = array_merge($defaultTimes, $hcSch);
                ?>

                <div id="hoursConfig" <?= empty($bc['hours_enabled']) ? 'style="display:none"' : '' ?>>

                    <div class="mb-3" style="max-width:300px">
                        <label class="form-label small fw-semibold text-muted">Zona horaria</label>
                        <select name="hours_timezone" class="form-select form-select-sm">
                            <?php
                            $tzGroups = [
                                'América del Sur'  => ['America/Lima','America/Bogota','America/Santiago','America/Buenos_Aires','America/La_Paz','America/Caracas','America/Guayaquil'],
                                'América Central'  => ['America/Mexico_City','America/Guatemala','America/Costa_Rica','America/Panama'],
                                'América del Norte'=> ['America/New_York','America/Chicago','America/Denver','America/Los_Angeles'],
                                'Europa'           => ['Europe/Madrid','Europe/London','Europe/Paris'],
                            ];
                            foreach ($tzGroups as $grpLabel => $tzList):
                            ?>
                            <optgroup label="<?= htmlspecialchars($grpLabel) ?>">
                                <?php foreach ($tzList as $tz): ?>
                                <option value="<?= $tz ?>" <?= $hcTz === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle" style="max-width:520px">
                            <thead>
                                <tr class="text-muted small">
                                    <th style="width:110px">Día</th>
                                    <th style="width:60px" class="text-center">Activo</th>
                                    <th>Apertura</th>
                                    <th>Cierre</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($days as $slug => $label):
                                $d = $hcSch[$slug];
                            ?>
                            <tr>
                                <td class="fw-semibold small"><?= $label ?></td>
                                <td class="text-center">
                                    <input type="checkbox" name="hours_<?= $slug ?>_enabled" value="1"
                                           class="form-check-input hours-day-toggle"
                                           data-day="<?= $slug ?>"
                                           <?= !empty($d['enabled']) ? 'checked' : '' ?>>
                                </td>
                                <td>
                                    <input type="time" name="hours_<?= $slug ?>_open" class="form-control form-control-sm"
                                           style="max-width:110px"
                                           value="<?= htmlspecialchars($d['open'] ?? '09:00') ?>"
                                           <?= empty($d['enabled']) ? 'disabled' : '' ?>>
                                </td>
                                <td>
                                    <input type="time" name="hours_<?= $slug ?>_close" class="form-control form-control-sm"
                                           style="max-width:110px"
                                           value="<?= htmlspecialchars($d['close'] ?? '18:00') ?>"
                                           <?= empty($d['enabled']) ? 'disabled' : '' ?>>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="max-width:520px">
                        <label class="form-label small fw-semibold text-muted">Mensaje fuera de horario</label>
                        <textarea name="hours_closed_message" class="form-control" rows="3"
                                  maxlength="400"
                                  placeholder="Ej: Estamos cerrados en este momento. Nuestro horario es de lunes a viernes 9am–6pm. ¡Te respondemos cuando abramos! 🕐"><?= htmlspecialchars($hcMsg) ?></textarea>
                        <div class="form-text">Máx. 400 caracteres. Usa texto claro y amigable.</div>
                    </div>

                </div><!-- /hoursConfig -->
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
            <style>
            .skill-pill {
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                padding: 6px 14px;
                border-radius: 20px;
                border: 1.5px solid #dee2e6;
                background: #f8f9fa;
                color: #6c757d;
                font-size: 0.84rem;
                user-select: none;
                transition: all .15s ease;
            }
            .skill-pill:hover {
                border-color: #25d366;
                color: #198754;
                background: rgba(37,211,102,0.06);
            }
            .skill-pill.active {
                border-color: #25d366;
                background: rgba(37,211,102,0.12);
                color: #146c43;
                font-weight: 600;
            }
            </style>
            <script>
            document.querySelectorAll('.skill-pill').forEach(function(pill) {
                pill.addEventListener('click', function() {
                    this.classList.toggle('active');
                    var cb = this.querySelector('input[type=checkbox]');
                    cb.checked = !cb.checked;
                });
            });

            // ── Custom type visibility ────────────────────────────────────
            (function() {
                var sel   = document.getElementById('businessTypeSelect');
                var wrap  = document.getElementById('customTypeWrap');
                var input = document.getElementById('customTypeInput');
                if (!sel || !wrap) return;
                sel.addEventListener('change', function() {
                    wrap.style.display = this.value === 'other' ? '' : 'none';
                    if (this.value !== 'other') input.value = '';
                });
            })();

            // ── AI Business search ────────────────────────────────────────
            (function() {
                var btn      = document.getElementById('bizSearchBtn');
                var inputEl  = document.getElementById('bizSearchInput');
                var status   = document.getElementById('bizSearchStatus');
                var typesel  = document.getElementById('businessTypeSelect');
                if (!btn || !inputEl) return;

                var fieldMap = {
                    description: 'bot_description',
                    services:    'bot_services',
                    pricing:     'bot_pricing',
                    hours:       'bot_hours',
                    faqs:        'bot_faqs',
                    website:     'bot_website',
                    location:    'bot_location'
                };

                btn.addEventListener('click', function() {
                    var query = inputEl.value.trim();
                    if (query.length < 3) {
                        status.style.display = '';
                        status.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Escribe el nombre de tu negocio primero.</span>';
                        return;
                    }
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Buscando...';
                    status.style.display = '';
                    status.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>La IA está generando el perfil de tu negocio...</span>';

                    fetch('<?= App::basePath() ?>/dashboard/settings/search-business', {
                        method:  'POST',
                        headers: {'Content-Type': 'application/json'},
                        body:    JSON.stringify({
                            query: query,
                            type:  typesel ? typesel.value : 'other'
                        })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-stars me-1"></i>Generar perfil';
                        if (!data.ok || !data.fields) {
                            status.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>' + (data.error || 'Error al generar. Intenta con otro nombre.') + '</span>';
                            return;
                        }
                        // Fill all form fields
                        Object.keys(fieldMap).forEach(function(key) {
                            var el = document.querySelector('[name="' + fieldMap[key] + '"]');
                            if (el && data.fields[key] !== undefined) el.value = data.fields[key];
                        });
                        status.innerHTML = '<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>¡Perfil generado! Revisa y edita los campos con tu información real, luego guarda.</span>';
                        // Scroll to description
                        var desc = document.querySelector('[name="bot_description"]');
                        if (desc) desc.scrollIntoView({behavior:'smooth', block:'center'});
                    })
                    .catch(function() {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-stars me-1"></i>Generar perfil';
                        status.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Error de conexión. Intenta de nuevo.</span>';
                    });
                });

                // Also trigger on Enter key in the search input
                inputEl.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') { e.preventDefault(); btn.click(); }
                });
            })();

            // ── Business-type preset templates ────────────────────────────
            (function() {
                var T = {
                    hotel: {
                        description: 'Somos [Nombre del hotel] ubicado en [Ciudad, País]. Contamos con [N] habitaciones confortables, WiFi, desayuno incluido y atención en recepción 24/7. Atendemos a viajeros nacionales e internacionales.',
                        services:    '- Habitación Simple: cama matrimonial, TV, WiFi, baño privado\n- Habitación Doble: 2 camas, TV, WiFi, baño privado\n- Suite: sala + dormitorio, jacuzzi, vista especial\n- Desayuno buffet incluido\n- Transfer aeropuerto disponible',
                        pricing:     '- Habitación Simple: S/150 por noche\n- Habitación Doble: S/220 por noche\n- Suite: S/350 por noche\n- Transfer aeropuerto: S/80 por viaje\n- Descuento 4+ noches: 10%',
                        hours:       '- Recepción: 24 horas / 7 días\n- Desayuno: 7:00am - 10:00am\n- Check-in: desde 3:00pm\n- Check-out: hasta 12:00pm',
                        faqs:        '¿Aceptan mascotas? No, lamentablemente no permitimos mascotas.\n¿Hay estacionamiento? Sí, gratuito para huéspedes.\n¿Aceptan tarjeta? Sí, Visa y Mastercard.\n¿Aceptan dólares? Sí, soles y dólares.'
                    },
                    travel_agency: {
                        description: 'Somos [Nombre de agencia] en [Ciudad], operadores de tours y paquetes turísticos. Llevamos a viajeros nacionales e internacionales a descubrir los mejores destinos.',
                        services:    '- Tours locales de medio día y día completo\n- Paquetes Machu Picchu, Valle Sagrado, Lago Titicaca\n- Viajes personalizados y luna de miel\n- Transfers y traslados\n- Paquetes internacionales',
                        pricing:     '- Tour local medio día: S/80 por persona\n- Tour Valle Sagrado día completo: S/150 por persona\n- Tour Machu Picchu: S/350 (incluye tren y guía)\n- Paquete 3 días Cusco: desde S/890 por persona\n- Grupos de 8+: 12% descuento',
                        hours:       '- Lunes a Sábado: 8:00am - 7:00pm\n- Domingos: 9:00am - 2:00pm\n- WhatsApp disponible 24/7 para consultas urgentes',
                        faqs:        '¿Los tours incluyen guía? Sí, guía bilingüe en todos los tours.\n¿Qué incluye el precio? Transporte, guía y entradas. Almuerzo opcional.\n¿Se puede pagar en cuotas? Sí, con 50% de adelanto.'
                    },
                    restaurant: {
                        description: 'Somos [Nombre del restaurante] en [Ciudad], ofrecemos cocina [tipo: criolla / italiana / fusión] con ingredientes frescos y preparados al momento. El lugar ideal para almuerzos familiares, reuniones de trabajo y eventos.',
                        services:    '- Platos a la carta para almuerzo y cena\n- Menú del día de lunes a viernes\n- Delivery a domicilio (radio 5km)\n- Reservas para grupos y eventos especiales\n- Opciones vegetarianas y sin gluten disponibles',
                        pricing:     '- Menú del día: S/18 (entrada + fondo + refresco)\n- Platos a la carta: S/28 - S/65\n- Costo de delivery: S/5\n- Bebidas y jugos: S/8 - S/22',
                        hours:       '- Lunes a Viernes: 12:00pm - 10:00pm\n- Sábados: 12:00pm - 11:00pm\n- Domingos: 12:00pm - 6:00pm\n- Delivery hasta las 9:30pm',
                        faqs:        '¿Hacen reservas? Sí, recomendado para fines de semana con 1 día de anticipación.\n¿Tienen salón privado? Sí, para hasta 30 personas.\n¿Hay estacionamiento? Sí, en el edificio sin costo.'
                    },
                    retail: {
                        description: 'Somos [Nombre de tienda] en [Ciudad], especialistas en [ropa / accesorios / artesanía / etc.] para [público objetivo]. Ofrecemos productos de calidad con atención personalizada y envíos a todo el país.',
                        services:    '- Ropa casual, formal y sport\n- Accesorios: bolsos, cinturones, joyería\n- Asesoría de imagen sin costo\n- Envíos nacionales e internacionales\n- Cambios y devoluciones dentro de 15 días',
                        pricing:     '- Blusas y tops: S/60 - S/150\n- Vestidos y enterizos: S/120 - S/280\n- Pantalones y jeans: S/90 - S/200\n- Accesorios: desde S/30\n- Rebajas de temporada: hasta 40% off',
                        hours:       '- Lunes a Sábado: 10:00am - 8:00pm\n- Domingos: 11:00am - 6:00pm\n- WhatsApp: 9:00am - 9:00pm todos los días',
                        faqs:        '¿Hacen envíos a provincias? Sí, a todo el Perú.\n¿Tienen tallas grandes? Sí, contamos de S hasta 3XL.\n¿Puedo devolver una compra? Sí, 15 días con etiqueta y sin uso.'
                    },
                    services: {
                        description: 'Somos [Nombre de empresa] en [Ciudad], especializados en [tipo de servicio: contabilidad / marketing / tecnología / consultoría]. Trabajamos con empresas y personas naturales para [resolver X problema] de forma rápida y profesional.',
                        services:    '- Consultoría y asesoría personalizada\n- Implementación y configuración\n- Soporte técnico y mantenimiento continuo\n- Capacitaciones presenciales y online\n- Diagnósticos y auditorías',
                        pricing:     '- Consulta inicial: GRATIS (30 minutos)\n- Servicio básico: desde S/200\n- Paquete mensual: desde S/500/mes\n- Proyectos a medida: cotización personalizada\n- Múltiples métodos de pago aceptados',
                        hours:       '- Lunes a Viernes: 9:00am - 6:00pm\n- Sábados: 9:00am - 1:00pm\n- Respuesta por WhatsApp: máx. 2 horas en horario laboral',
                        faqs:        '¿Trabajan con pequeñas empresas? Sí, tenemos paquetes para todo tamaño.\n¿Cuánto demora el servicio? Lo estimamos en la consulta inicial gratuita.\n¿Firman contrato? Sí, siempre trabajamos con contrato y confidencialidad.'
                    },
                    health: {
                        description: 'Somos [Nombre de clínica / spa / centro de bienestar] en [Ciudad], especializados en [área: dental, fisioterapia, nutrición, estética, psicología]. Atención personalizada con profesionales certificados.',
                        services:    '- Consultas presenciales y virtuales\n- Tratamientos especializados en [área]\n- Sesiones de terapia y rehabilitación\n- Análisis de laboratorio y diagnósticos\n- Control y seguimiento de tratamientos',
                        pricing:     '- Consulta inicial: S/80\n- Consulta de seguimiento: S/60\n- Sesión de tratamiento: S/120\n- Paquete de 5 sesiones: S/500 (ahorra S/100)\n- Análisis básico de laboratorio: desde S/150',
                        hours:       '- Lunes a Viernes: 8:00am - 7:00pm\n- Sábados: 8:00am - 1:00pm\n- Atención solo con cita previa',
                        faqs:        '¿Necesito cita previa? Sí, es indispensable agendar.\n¿Aceptan seguros médicos? Sí, trabajamos con las principales aseguradoras.\n¿Hay estacionamiento? Sí, disponible para pacientes.'
                    },
                    education: {
                        description: 'Somos [Nombre de academia / instituto] en [Ciudad], especializados en [área educativa: idiomas, tecnología, negocios, arte]. Formamos a estudiantes y profesionales con metodología práctica y docentes certificados.',
                        services:    '- Cursos presenciales y online en [área]\n- Capacitaciones corporativas in-company\n- Diplomados y programas de especialización\n- Clases particulares personalizadas\n- Material de estudio y certificados incluidos',
                        pricing:     '- Curso básico (1 mes): S/350\n- Curso avanzado (2 meses): S/650\n- Diplomado (4 meses): S/1,200\n- Clase particular por hora: S/80\n- Grupos corporativos: cotización especial',
                        hours:       '- Clases presenciales: según cronograma del aula\n- Plataforma online: acceso 24/7\n- Oficina administrativa: Lunes a Viernes 9am - 6pm\n- Sábados: 9:00am - 1:00pm',
                        faqs:        '¿Dan certificado oficial? Sí, digital y físico en todos los cursos.\n¿Las clases quedan grabadas? Sí, disponibles en la plataforma.\n¿Aceptan pago en cuotas? Sí, en 2 o 3 cuotas sin interés.'
                    },
                    other: {
                        description: 'Somos [Nombre de tu negocio] en [Ciudad, País]. [Describe qué haces, a quién ayudas y qué te hace especial — cuanto más detallas, mejor responde Mia.]',
                        services:    '[Lista tus servicios o productos principales, uno por línea. Incluye las características más relevantes para tus clientes.]',
                        pricing:     '[Escribe tus precios o rangos de precios para cada servicio o producto. Sé específico — ayuda a Mia a responder consultas de tarifas con exactitud.]',
                        hours:       '[Indica días y horarios de atención. Ej: Lunes a Viernes 9am-6pm / Sábados 9am-1pm]',
                        faqs:        '[Escribe las 3-5 preguntas que más te hacen tus clientes y sus respuestas exactas.]'
                    }
                };
                var typeLabels = {
                    hotel: 'Hotel / Hostal', travel_agency: 'Agencia de Viajes',
                    restaurant: 'Restaurante / Café', retail: 'Tienda / Boutique',
                    services: 'Servicios Profesionales', health: 'Salud y Bienestar',
                    education: 'Academia / Instituto', other: 'Negocio General'
                };
                var sel    = document.getElementById('businessTypeSelect');
                var banner = document.getElementById('templateBannerWrap');
                var tname  = document.getElementById('templateTypeName');
                var applyBtn = document.getElementById('applyTemplateBtn');

                function showBanner(val) {
                    if (!T[val] || !banner) return;
                    tname.textContent = typeLabels[val] || val;
                    banner.style.display = '';
                    // Reset in case it was replaced with success text
                    applyBtn.style.display = '';
                }

                if (sel) {
                    sel.addEventListener('change', function() { showBanner(this.value); });
                    // Also show on load if fields are mostly empty (new user)
                    var descField = document.querySelector('[name="bot_description"]');
                    if (descField && !descField.value.trim()) showBanner(sel.value);
                }

                if (applyBtn) {
                    applyBtn.addEventListener('click', function() {
                        var t = T[sel.value];
                        if (!t) return;
                        var map = {
                            bot_description: t.description,
                            bot_services:    t.services,
                            bot_pricing:     t.pricing,
                            bot_hours:       t.hours,
                            bot_faqs:        t.faqs
                        };
                        Object.keys(map).forEach(function(n) {
                            var el = document.querySelector('[name="' + n + '"]');
                            if (el) el.value = map[n];
                        });
                        banner.innerHTML = '<div class="px-3 py-2 rounded-3 text-success small fw-semibold" style="background:rgba(37,211,102,0.10);border:1.5px solid rgba(37,211,102,0.3)"><i class="bi bi-check-circle-fill me-2"></i>Plantilla aplicada — edita los campos con los datos reales de tu negocio y guarda los cambios.</div>';
                    });
                }
            })();

            // ── Business hours: toggle time inputs when day checkbox changes ─
            (function() {
                document.querySelectorAll('.hours-day-toggle').forEach(function(cb) {
                    cb.addEventListener('change', function() {
                        var day  = this.dataset.day;
                        var row  = this.closest('tr');
                        var inputs = row.querySelectorAll('input[type=time]');
                        inputs.forEach(function(inp) { inp.disabled = !cb.checked; });
                    });
                });
            })();

            // ── WA QR & Link generator ────────────────────────────────────
            (function() {
                var genBtn  = document.getElementById('genWaLinkBtn');
                var result  = document.getElementById('waLinkResult');
                var qrImg   = document.getElementById('waQrImg');
                var linkInp = document.getElementById('waDeepLink');
                var copyBtn = document.getElementById('copyWaLinkBtn');
                var dlBtn   = document.getElementById('waQrDownload');
                var openBtn = document.getElementById('waLinkOpen');
                if (!genBtn) return;

                genBtn.addEventListener('click', function() {
                    genBtn.disabled = true;
                    genBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generando...';
                    fetch('<?= App::basePath() ?>/dashboard/settings/wa-link')
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            genBtn.disabled = false;
                            genBtn.innerHTML = '<i class="bi bi-qr-code-scan me-1"></i>Generar QR y enlace';
                            if (data.error) { alert(data.error); return; }
                            qrImg.src       = data.qr_url;
                            linkInp.value   = data.link;
                            dlBtn.href      = data.qr_url;
                            openBtn.href    = data.link;
                            result.classList.remove('d-none');
                        })
                        .catch(function() {
                            genBtn.disabled = false;
                            genBtn.innerHTML = '<i class="bi bi-qr-code-scan me-1"></i>Generar QR y enlace';
                        });
                });

                if (copyBtn) {
                    copyBtn.addEventListener('click', function() {
                        if (linkInp.value) {
                            navigator.clipboard.writeText(linkInp.value).then(function() {
                                copyBtn.innerHTML = '<i class="bi bi-check2"></i>';
                                setTimeout(function() { copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 2000);
                            });
                        }
                    });
                }
            })();
            </script>
            <button type="submit" class="btn btn-success px-4"
                    style="background:#25d366;border-color:#25d366">
                <i class="bi bi-check-circle me-2"></i>Guardar cambios
            </button>
        </div>

    </div>
</form>

<?php if ($client->bot_wa_status !== 'connected'): ?>
<script>
(function() {
    const btn   = document.getElementById('waConnectBtn');
    const step1 = document.getElementById('waStep1');
    const step2 = document.getElementById('waStep2');
    const box   = document.getElementById('waConnectBox');
    if (!btn) return;

    let polling = false;

    function pollQr() {
        if (!polling) return;
        fetch('<?= $base ?>/dashboard/settings/wa-qr')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'connected') {
                    box.innerHTML = '<div class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>¡WhatsApp conectado! Recargando...</div>';
                    polling = false;
                    setTimeout(() => location.reload(), 1500);
                } else if (data.qr_image) {
                    box.innerHTML = '<img src="' + data.qr_image + '" class="img-fluid" style="max-width:220px" alt="QR Code">' +
                        '<div class="text-muted small mt-2">Escanea con WhatsApp → Dispositivos vinculados</div>';
                    setTimeout(pollQr, 20000); // QR refreshes every 20s
                } else {
                    box.innerHTML = '<div class="spinner-border spinner-border-sm text-success mb-2" role="status"></div>' +
                        '<div class="text-muted small mt-1">Preparando QR...</div>';
                    setTimeout(pollQr, 4000);
                }
            })
            .catch(() => {
                box.innerHTML = '<div class="text-muted small">No se pudo cargar el QR. <a href="" onclick="location.reload();return false;">Recargar</a></div>';
            });
    }

    btn.addEventListener('click', function() {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Iniciando...';

        fetch('<?= $base ?>/dashboard/settings/wa-connect', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    '{}',
        })
        .then(r => r.json())
        .then(data => {
            if (data.success === false) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-whatsapp me-2"></i>Reintentar conexión';
                box.innerHTML = '<div class="text-danger small">' + (data.error || 'Error al iniciar') + '</div>';
                step1.classList.remove('d-none');
                step2.classList.add('d-none');
                return;
            }
            // Session started — show QR box and begin polling
            step1.classList.add('d-none');
            step2.classList.remove('d-none');
            polling = true;
            pollQr();
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-whatsapp me-2"></i>Reintentar conexión';
            alert('No se pudo conectar con el servidor del bot.');
        });
    });
})();
</script>
<?php endif; ?>

<?php if ($onboarding): ?>
<div class="mc-table-card mt-4 p-4" style="border:2px solid rgba(37,211,102,0.35)">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h6 class="fw-bold mb-1">¿Listo para empezar?</h6>
            <p class="text-muted small mb-0">Puedes ajustar cualquier cosa en cualquier momento desde Configuración.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="<?= $base ?>/dashboard" class="btn btn-outline-secondary btn-sm">
                Configurar después
            </a>
            <form method="POST" action="<?= $base ?>/dashboard/settings/finish-onboarding">
                <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">
                <button type="submit" class="btn btn-success px-4"
                        style="background:#25d366;border-color:#25d366">
                    <i class="bi bi-rocket-takeoff me-2"></i>¡Ir al Dashboard!
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_foot.php'; ?>
