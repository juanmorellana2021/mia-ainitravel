<?php
/**
 * mia/views/pages/landing.php — Homepage / Landing
 */

$base = App::basePath();
$pageTitle = 'Mia — Convierte mensajes de WhatsApp en clientes automáticamente 24/7';
$pageDescription = 'MIA responde, califica y cierra clientes por ti — para restaurantes, hoteles, clínicas y cualquier negocio. Prueba gratis 15 días. Sin riesgo. Configuración en minutos.';
$pageCanonical = App::URL . '/';
$waLink = 'https://wa.me/' . preg_replace('/[^0-9]/', '', App::WHATSAPP) . '?text=Hola%20Mia!';

ob_start();
?>

<!-- JSON-LD Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Mia by AiniTravel",
  "description": "<?= htmlspecialchars($pageDescription) ?>",
  "url": "https://mia-whatsapp.com",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "Web",
  "offers": {
    "@type": "AggregateOffer",
    "priceCurrency": "PEN",
    "lowPrice": "<?= App::PLAN_STARTER ?>",
    "highPrice": "<?= App::PLAN_PRO ?>",
    "offerCount": "3"
  },
  "provider": {
    "@type": "Organization",
    "name": "AiniTravel",
    "url": "https://mia-whatsapp.com",
    "contactPoint": {
      "@type": "ContactPoint",
      "telephone": "<?= App::WHATSAPP ?>",
      "contactType": "sales",
      "availableLanguage": ["Spanish", "English"]
    }
  }
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "¿Cómo funciona Mia?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "El cliente ve tu anuncio en Facebook y escribe a tu WhatsApp. Mia responde al instante, cotiza precios, califica al cliente y cierra la venta. Tú recibes al cliente listo para pagar."
      }
    },
    {
      "@type": "Question",
      "name": "¿Cuánto cuesta Mia?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Planes desde S/84 al mes con 15 días de prueba gratis. Sin tarjeta de crédito. Configuración incluida en 48 horas."
      }
    },
    {
      "@type": "Question",
      "name": "¿En qué idiomas funciona Mia?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Mia habla más de 50 idiomas. Detecta automáticamente el idioma del cliente y responde en ese mismo idioma."
      }
    }
  ]
}
</script>

<!-- Hero -->
<section class="mia-hero text-white py-5">
    <div class="container py-4">
        <div class="row align-items-center g-5">

            <!-- Left: copy -->
            <div class="col-lg-6 text-center text-lg-start">
                <h1 class="display-4 fw-bold mb-3">
                    Convierte mensajes de WhatsApp en clientes <span class="text-success">automáticamente 24/7</span>
                </h1>
                <p class="lead mb-4">
                    MIA responde, califica y cierra clientes por ti — para restaurantes, hoteles, clínicas y cualquier negocio.
                </p>
                <div class="d-flex gap-3 justify-content-center justify-content-lg-start flex-wrap">
                    <a href="<?= $base ?>/register" class="btn btn-success btn-lg px-4">
                        <i class="bi bi-whatsapp me-2"></i>Activar prueba gratis en mi WhatsApp
                    </a>
                    <a href="<?= $base ?>/demo" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-play-circle me-2"></i>Ver Demo
                    </a>
                </div>
                <p class="mt-3 small text-light opacity-75">Sin riesgo. Configuración en minutos.</p>
                <div class="mt-3 d-flex gap-2 justify-content-center justify-content-lg-start flex-wrap">
                    <span class="badge bg-success bg-opacity-75 fs-6 px-3 py-2"><i class="bi bi-translate me-1"></i>Habla más de 50 idiomas</span>
                    <span class="badge bg-light text-dark fs-6 px-3 py-2"><i class="bi bi-clock me-1"></i>Responde en &lt;5 segundos</span>
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2"><i class="bi bi-whatsapp me-1"></i>100% en WhatsApp</span>
                </div>
            </div>

            <!-- Right: phone mockup -->
            <div class="col-lg-6 d-flex justify-content-center">
                <div class="mia-phone-wrap">
                    <!-- Phone device frame -->
                    <div class="mia-device">
                        <div class="mia-device-btn-vol"></div>
                        <div class="mia-device-btn-power"></div>
                        <div class="mia-device-island"></div>
                        <div class="mia-device-screen">
                        <!-- Status bar -->
                        <div class="mia-statusbar">
                            <span>9:41</span>
                            <div class="d-flex gap-1 align-items-center">
                                <i class="bi bi-reception-4" style="font-size:.6rem"></i>
                                <i class="bi bi-wifi" style="font-size:.6rem"></i>
                                <i class="bi bi-battery-half" style="font-size:.6rem"></i>
                            </div>
                        </div>
                        <!-- WhatsApp header -->
                        <div class="mia-phone-header">
                            <div class="d-flex align-items-center gap-2">
                                <div class="mia-avatar">
                                    <i class="bi bi-robot text-white" style="font-size:1.1rem"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-white" style="font-size:.85rem;line-height:1.1">Mia • AiniTravel</div>
                                    <div style="font-size:.7rem;color:#a8e6c0">en línea</div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 ms-auto">
                                <i class="bi bi-camera-video text-white opacity-75"></i>
                                <i class="bi bi-telephone text-white opacity-75"></i>
                            </div>
                        </div>
                        <!-- Chat body -->
                        <div class="mia-phone-body">
                            <div class="mia-date-divider">hoy</div>

                            <div class="mia-msg mia-msg--in">
                                Hola! Vi su anuncio en Facebook, ¿tienen paquetes para Cusco en abril? 🙏
                                <span class="mia-time">9:43 PM</span>
                            </div>

                            <div class="mia-msg mia-msg--out">
                                ¡Hola! Soy Mia 😊 Sí, tenemos paquetes increíbles para Cusco en abril. ¿Cuántas personas viajan?
                                <span class="mia-time mia-time--out">9:43 PM <i class="bi bi-check2-all" style="color:#53bdeb"></i></span>
                            </div>

                            <div class="mia-msg mia-msg--in">
                                Somos 2 adultos y 1 niño
                                <span class="mia-time">9:44 PM</span>
                            </div>

                            <div class="mia-msg mia-msg--out">
                                Perfecto! Para 2 adultos + 1 niño tengo el paquete <strong>Cusco Mágico 5D/4N</strong> en $480 por persona. Incluye vuelos, hotel 3★ y tours. ¿Le interesa reservar?
                                <span class="mia-time mia-time--out">9:44 PM <i class="bi bi-check2-all" style="color:#53bdeb"></i></span>
                            </div>

                            <div class="mia-msg mia-msg--in">
                                ¡Sí! ¿Cómo pago?
                                <span class="mia-time">9:45 PM</span>
                            </div>

                            <div class="mia-msg mia-msg--out">
                                🎉 ¡Excelente! Puede reservar con solo el 30% de adelanto. Le envío el link de pago ahora mismo. Su agente le confirmará los detalles en minutos.
                                <span class="mia-time mia-time--out">9:45 PM <i class="bi bi-check2-all" style="color:#53bdeb"></i></span>
                            </div>

                            <div class="mia-typing">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                        <!-- Input bar -->
                        <div class="mia-phone-input">
                            <div class="mia-input-bar">
                                <i class="bi bi-emoji-smile text-secondary"></i>
                                <span class="text-secondary flex-grow-1" style="font-size:.8rem">Escribe un mensaje</span>
                                <i class="bi bi-mic text-secondary"></i>
                            </div>
                        </div>
                        </div><!-- /mia-device-screen -->
                        <div class="mia-device-home"></div>
                    </div><!-- /mia-device -->
                    <!-- "Deal closed" badge floating -->
                    <div class="mia-deal-badge">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        <span>¡Trato cerrado en 2 min!</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Problem / Pain Points -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center fw-bold mb-2">¿Te suena familiar?</h2>
        <p class="text-center text-muted mb-5">El problema número uno de negocios de viaje que hacen publicidad en Facebook</p>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <i class="bi bi-megaphone text-primary fs-1 mb-3"></i>
                    <h6 class="fw-bold">Gastas en ads, pierdes leads</h6>
                    <p class="text-muted small">Tu anuncio genera 80 mensajes. Solo respondes 20. Los otros 60 compran en la competencia.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <i class="bi bi-moon-stars text-warning fs-1 mb-3"></i>
                    <h6 class="fw-bold">Los clientes escriben fuera de horario</h6>
                    <p class="text-muted small">A las 11pm, los domingos, en feriados — sin respuesta, el cliente ya fue.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <i class="bi bi-x-circle text-danger fs-1 mb-3"></i>
                    <h6 class="fw-bold">Preguntan pero no compran</h6>
                    <p class="text-muted small">El 70% de consultas no se convierten sin seguimiento inmediato. La velocidad gana la venta.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <i class="bi bi-people text-success fs-1 mb-3"></i>
                    <h6 class="fw-bold">No puedes contratar más gente</h6>
                    <p class="text-muted small">Un equipo de 2 personas no puede manejar 200 chats al día. Mia sí puede.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center fw-bold mb-2">¿Cómo funciona?</h2>
        <p class="text-center text-muted mb-5">3 pasos simples — para hoteles, agencias, operadores turísticos y más.</p>
        <div class="row g-4 align-items-center">
            <div class="col-md-4 text-center">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px">
                    <span class="fs-2 fw-bold text-success">1</span>
                </div>
                <h5 class="fw-bold">El cliente te escribe</h5>
                <p class="text-muted">Ve tu anuncio en Facebook o Instagram y hace clic en "Enviar mensaje" directo a tu WhatsApp.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px">
                    <span class="fs-2 fw-bold text-success">2</span>
                </div>
                <h5 class="fw-bold">Mia atiende al instante</h5>
                <p class="text-muted">Responde preguntas, muestra opciones, cotiza precios, califica al cliente y cierra la venta o reserva.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px">
                    <span class="fs-2 fw-bold text-success">3</span>
                </div>
                <h5 class="fw-bold">Tú recibes al cliente listo</h5>
                <p class="text-muted">Notificación instantánea con todos los datos. El cliente ya confirmó y está listo para pagar.</p>
            </div>
        </div>
    </div>
</section>

<!-- Product Showcase -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center fw-bold mb-2">Tu panel de control completo</h2>
        <p class="text-center text-muted mb-4">Todo lo que necesitas para gestionar tus ventas por WhatsApp — en un solo lugar</p>

        <!-- Tab navigation -->
        <ul class="nav nav-pills justify-content-center flex-wrap gap-2 mb-4" id="showcaseTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-dashboard" data-bs-toggle="pill" data-bs-target="#pane-dashboard" type="button" role="tab">
                    <i class="bi bi-grid me-1"></i>Dashboard
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-analytics" data-bs-toggle="pill" data-bs-target="#pane-analytics" type="button" role="tab">
                    <i class="bi bi-bar-chart me-1"></i>Analíticas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-chat" data-bs-toggle="pill" data-bs-target="#pane-chat" type="button" role="tab">
                    <i class="bi bi-chat-dots me-1"></i>Chat en vivo
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-leads" data-bs-toggle="pill" data-bs-target="#pane-leads" type="button" role="tab">
                    <i class="bi bi-people me-1"></i>Leads
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-messages" data-bs-toggle="pill" data-bs-target="#pane-messages" type="button" role="tab">
                    <i class="bi bi-envelope me-1"></i>Mensajes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-calendar" data-bs-toggle="pill" data-bs-target="#pane-calendar" type="button" role="tab">
                    <i class="bi bi-calendar-check me-1"></i>Citas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-sales" data-bs-toggle="pill" data-bs-target="#pane-sales" type="button" role="tab">
                    <i class="bi bi-gear me-1"></i>Ventas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-automations" data-bs-toggle="pill" data-bs-target="#pane-automations" type="button" role="tab">
                    <i class="bi bi-arrow-repeat me-1"></i>Automatizaciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-broadcast" data-bs-toggle="pill" data-bs-target="#pane-broadcast" type="button" role="tab">
                    <i class="bi bi-megaphone me-1"></i>Seguimientos
                </button>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content" id="showcaseContent">
            <div class="tab-pane fade show active" id="pane-dashboard" role="tabpanel">
                <div class="text-center">
                    <p class="text-muted mb-3">Tu centro de control: leads, conversiones, plan activo y actividad reciente en un vistazo.</p>
                    <img src="<?= App::asset('img/screenshots/dashboardmia2.png') ?>" alt="Dashboard principal de Mia — resumen de leads y métricas" class="img-fluid rounded shadow" style="max-height:520px" loading="lazy">
                </div>
            </div>
            <div class="tab-pane fade" id="pane-analytics" role="tabpanel">
                <div class="text-center">
                    <p class="text-muted mb-3">Mira en tiempo real cuántos leads llegan, tu tasa de conversión y mensajes por día.</p>
                    <img src="<?= App::asset('img/screenshots/analytics.png') ?>" alt="Panel de analíticas de Mia — leads, conversiones y mensajes" class="img-fluid rounded shadow" style="max-height:520px" loading="lazy">
                </div>
            </div>
            <div class="tab-pane fade" id="pane-chat" role="tabpanel">
                <div class="text-center">
                    <p class="text-muted mb-3">Mia conversa con tus clientes por WhatsApp como si fuera parte de tu equipo.</p>
                    <img src="<?= App::asset('img/screenshots/chatmia2.png') ?>" alt="Chat de WhatsApp con Mia respondiendo automáticamente" class="img-fluid rounded shadow" style="max-height:520px" loading="lazy">
                </div>
            </div>
            <div class="tab-pane fade" id="pane-leads" role="tabpanel">
                <div class="text-center">
                    <p class="text-muted mb-3">Todos tus leads organizados con estado, fuente, valor estimado y acciones rápidas.</p>
                    <img src="<?= App::asset('img/screenshots/leadsmia2.png') ?>" alt="CRM de leads de Mia — gestión de contactos de WhatsApp" class="img-fluid rounded shadow" style="max-height:520px" loading="lazy">
                </div>
            </div>
            <div class="tab-pane fade" id="pane-messages" role="tabpanel">
                <div class="text-center">
                    <p class="text-muted mb-3">Bandeja de mensajes completa — ve todas las conversaciones de entrada y salida con cada lead.</p>
                    <img src="<?= App::asset('img/screenshots/messegemia2.png') ?>" alt="Bandeja de mensajes de WhatsApp en Mia" class="img-fluid rounded shadow" style="max-height:520px" loading="lazy">
                </div>
            </div>
            <div class="tab-pane fade" id="pane-calendar" role="tabpanel">
                <div class="text-center">
                    <p class="text-muted mb-3">Mia agenda citas directamente desde WhatsApp. Tú solo ves tu calendario.</p>
                    <img src="<?= App::asset('img/screenshots/calendar.png') ?>" alt="Calendario de citas agendadas por WhatsApp" class="img-fluid rounded shadow" style="max-height:520px" loading="lazy">
                </div>
            </div>
            <div class="tab-pane fade" id="pane-sales" role="tabpanel">
                <div class="text-center">
                    <p class="text-muted mb-3">Configura cómo Mia vende: modo consultivo, directo al cierre o urgencia. Tú decides.</p>
                    <img src="<?= App::asset('img/screenshots/sales-config.png') ?>" alt="Configuración de ventas de Mia — modo de ventas y CTA" class="img-fluid rounded shadow" style="max-height:520px" loading="lazy">
                </div>
            </div>
            <div class="tab-pane fade" id="pane-automations" role="tabpanel">
                <div class="text-center">
                    <p class="text-muted mb-3">Crea secuencias de seguimiento automáticas. Mia envía mensajes en los tiempos que tú definas.</p>
                    <img src="<?= App::asset('img/screenshots/automations.png') ?>" alt="Automatizaciones de seguimiento de leads en Mia" class="img-fluid rounded shadow" style="max-height:520px" loading="lazy">
                </div>
            </div>
            <div class="tab-pane fade" id="pane-broadcast" role="tabpanel">
                <div class="text-center">
                    <p class="text-muted mb-3">Envía mensajes masivos a tus leads seleccionados — perfecto para promos y recordatorios.</p>
                    <img src="<?= App::asset('img/screenshots/defusionleads.png') ?>" alt="Seguimientos y difusión masiva a leads de WhatsApp" class="img-fluid rounded shadow" style="max-height:520px" loading="lazy">
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="<?= $base ?>/register" class="btn btn-success btn-lg px-4">
                <i class="bi bi-rocket me-2"></i>Prueba todo esto gratis — 7 días
            </a>
        </div>
    </div>
</section>

<!-- Why Mia? -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center fw-bold mb-2">¿Por qué elegir Mia?</h2>
        <p class="text-center text-muted mb-5">Razones reales por las que negocios como el tuyo están cambiando la forma de vender</p>
        <div class="row g-4">

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                            <i class="bi bi-translate text-success fs-3"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Cierra tratos en cualquier idioma</h6>
                    </div>
                    <p class="text-muted small mb-0">Español, inglés, portugués, francés, italiano, alemán y más de 50 idiomas. Mia detecta el idioma del cliente y responde automáticamente. Captura turistas internacionales que tu equipo no podría atender.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                            <i class="bi bi-funnel text-primary fs-3"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Filtra leads automáticamente</h6>
                    </div>
                    <p class="text-muted small mb-0">No todos los mensajes son clientes serios. Mia hace las preguntas clave, descarta curiosos y solo te pasa los leads calificados listos para comprar. Tu equipo solo habla con quien de verdad quiere cerrar.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning bg-opacity-10 rounded p-2 me-3">
                            <i class="bi bi-lightning-charge text-warning fs-3"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Velocidad = ventas</h6>
                    </div>
                    <p class="text-muted small mb-0">El cliente que escribe a las 9pm quiere respuesta ahora, no mañana. El negocio que responde primero gana el 78% de las veces. Con Mia respondiendo en menos de 5 segundos, siempre serás el primero.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-danger bg-opacity-10 rounded p-2 me-3">
                            <i class="bi bi-shield-check text-danger fs-3"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Sin errores humanos</h6>
                    </div>
                    <p class="text-muted small mb-0">Olvidar responder, dar el precio equivocado, duplicar una reserva — errores que cuestan caro. Mia siempre da la información correcta, consistente y profesional en cada conversación.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info bg-opacity-10 rounded p-2 me-3">
                            <i class="bi bi-graph-up-arrow text-info fs-3"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Escala tu negocio sin escalar costos</h6>
                    </div>
                    <p class="text-muted small mb-0">Contrata más personal y tus costos se duplican. Con Mia, puedes triplicar tus campañas de Facebook Ads y manejar el triple de mensajes por el mismo precio mensual fijo.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                            <i class="bi bi-gear text-success fs-3"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Listo en 48 horas, sin técnicos</h6>
                    </div>
                    <p class="text-muted small mb-0">No necesitas cambiar nada de tu negocio. Mia se conecta a tu WhatsApp existente. Nuestro equipo lo configura todo en 48 horas. Tú solo empiezas a recibir más clientes confirmados.</p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ROI Section -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-3">Los números no mienten</h2>
                <p class="lead">Si gastas S/1,000 al mes en Facebook Ads y el 60% de tus leads quedan sin responder, estás tirando S/600 a la basura cada mes.</p>
                <p>Con Mia respondiendo al instante, podrías <strong>convertir el doble de leads</strong> sin contratar más personal — eso puede significar <strong>S/3,000–S/8,000 en ventas adicionales</strong> por solo <?= App::CURRENCY ?><?= number_format(App::PLAN_STARTER) ?>/mes.</p>
                <p class="fw-bold text-success fs-5">Por cada S/1 que inviertes → recuperas S/8 o más</p>
                <div class="alert alert-success border-0 mt-3 py-3">
                    <i class="bi bi-lightbulb-fill me-2"></i>
                    <strong>Por el costo de un empleado, puedes cerrar 10 veces más negocios.</strong>
                    Mia no se cansa, no toma descanso y atiende 200 conversaciones a la vez.
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card bg-dark border-success">
                    <div class="card-body text-center">
                        <div class="row g-3">
                            <div class="col-6">
                                <h3 class="text-success fw-bold">24/7</h3>
                                <small class="text-secondary">Responde sin parar</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-success fw-bold">&lt;5s</h3>
                                <small class="text-secondary">Tiempo de respuesta</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-success fw-bold">100%</h3>
                                <small class="text-secondary">Leads atendidos</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-success fw-bold">2x</h3>
                                <small class="text-secondary">Más conversiones</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-5 text-center bg-success bg-gradient text-white">
    <div class="container">
        <h2 class="fw-bold mb-3">¿Listo para no perder más leads de WhatsApp?</h2>
        <p class="lead mb-4">Empieza tu prueba gratis de 7 días — para hoteles, agencias de viaje y operadores turísticos</p>
        <a href="<?= $base ?>/register" class="btn btn-light btn-lg px-5 fw-bold">
            <i class="bi bi-rocket me-2 text-success"></i>Empezar prueba gratis
        </a>
    </div>
</section>

<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
