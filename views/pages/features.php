<?php
/**
 * mia/views/pages/features.php — Features page (alternating sections)
 */

$base = App::basePath();
$pageTitle = 'Funciones — Mia by AiniTravel';
$waLink = 'https://wa.me/' . preg_replace('/[^0-9]/', '', App::WHATSAPP) . '?text=Hola%20Mia!';

ob_start();
?>

<!-- Page Hero -->
<section class="mia-feat-hero text-white text-center py-5">
    <div class="container py-4">
        <span class="badge bg-success bg-opacity-75 fs-6 px-3 py-2 mb-3 d-inline-block">Todo lo que Mia hace por ti</span>
        <h1 class="display-5 fw-bold mb-3">Tu equipo ya no puede más.<br><span class="text-success">Mia sí puede.</span></h1>
        <p class="lead text-light opacity-75 mx-auto" style="max-width:580px">
            Pon anuncios en Facebook, recibe 100 mensajes de WhatsApp y no pierdas ninguno.
            Mia trabaja 24/7, cierra ventas, habla 50+ idiomas y te avisa al instante.
        </p>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     SECTION 1 — Text LEFT · Visual RIGHT
     "Nunca pierdas un lead"
     ═══════════════════════════════════════════ -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Text -->
            <div class="col-lg-6">
                <span class="badge bg-success bg-opacity-10 text-success fw-semibold px-3 py-2 mb-3 d-inline-block">Captura de leads</span>
                <h2 class="fw-bold mb-3">Responde los 100 mensajes.<br>Sin perder ninguno.</h2>
                <p class="text-muted mb-4">
                    Tus anuncios de Facebook generan mensajes a las 2am, los domingos, en feriados.
                    Sin Mia, el 60% queda sin respuesta. Con Mia, cada mensaje recibe una respuesta
                    en menos de 5 segundos — siempre, sin excepción.
                </p>
                <ul class="list-unstyled">
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-success mt-1"></i><span>Respuesta automática en &lt;5 segundos</span></li>
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-success mt-1"></i><span>Activo 24 horas, 7 días, 365 días al año</span></li>
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-success mt-1"></i><span>Maneja 200 conversaciones simultáneas</span></li>
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-success mt-1"></i><span>Nunca olvida responder, nunca se cansa</span></li>
                </ul>
            </div>
            <!-- Visual: notification stack -->
            <div class="col-lg-6 d-flex justify-content-center">
                <div class="mia-feat-visual">
                    <div class="mia-notif-stack">
                        <div class="mia-notif mia-notif--1">
                            <div class="mia-notif-icon"><i class="bi bi-whatsapp text-white"></i></div>
                            <div class="mia-notif-body">
                                <div class="fw-semibold" style="font-size:.8rem">Carlos M.</div>
                                <div class="text-muted" style="font-size:.75rem">¿Tienen habitaciones para el 15 de abril?</div>
                            </div>
                            <span class="mia-notif-time">11:42 PM</span>
                        </div>
                        <div class="mia-notif mia-notif--2">
                            <div class="mia-notif-icon"><i class="bi bi-whatsapp text-white"></i></div>
                            <div class="mia-notif-body">
                                <div class="fw-semibold" style="font-size:.8rem">Ana Torres</div>
                                <div class="text-muted" style="font-size:.75rem">Hola vi su anuncio, ¿cuánto cuesta?</div>
                            </div>
                            <span class="mia-notif-time">2:17 AM</span>
                        </div>
                        <div class="mia-notif mia-notif--3">
                            <div class="mia-notif-icon"><i class="bi bi-whatsapp text-white"></i></div>
                            <div class="mia-notif-body">
                                <div class="fw-semibold" style="font-size:.8rem">John Smith</div>
                                <div class="text-muted" style="font-size:.75rem">Hi! Do you have availability?</div>
                            </div>
                            <span class="mia-notif-time">Sun 8:03 AM</span>
                        </div>
                        <div class="mia-notif mia-notif--4">
                            <div class="mia-notif-icon"><i class="bi bi-whatsapp text-white"></i></div>
                            <div class="mia-notif-body">
                                <div class="fw-semibold" style="font-size:.8rem">Lucía Pérez</div>
                                <div class="text-muted" style="font-size:.75rem">Quiero reservar para 2 personas...</div>
                            </div>
                            <span class="mia-notif-time">Holiday</span>
                        </div>
                        <div class="mia-feat-badge-green mt-3">
                            <i class="bi bi-lightning-charge-fill me-1"></i>Mia respondió a todos en &lt;5s
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     SECTION 2 — Visual LEFT · Text RIGHT
     "Cierra ventas en automático"
     ═══════════════════════════════════════════ -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center g-5 flex-lg-row-reverse">
            <!-- Text -->
            <div class="col-lg-6">
                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-3 py-2 mb-3 d-inline-block">Conversión de ventas</span>
                <h2 class="fw-bold mb-3">Califica al cliente.<br>Cierra el trato. Sola.</h2>
                <p class="text-muted mb-4">
                    Mia no solo saluda — hace las preguntas correctas, entiende lo que el cliente
                    necesita, presenta la oferta adecuada y guía hasta el pago. Como tu mejor
                    vendedor, pero disponible a cualquier hora y sin salario.
                </p>
                <ul class="list-unstyled">
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-primary mt-1"></i><span>Hace preguntas de calificación automáticamente</span></li>
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-primary mt-1"></i><span>Presenta opciones y precios personalizados</span></li>
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-primary mt-1"></i><span>Envía link de pago y confirma la reserva</span></li>
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-primary mt-1"></i><span>Tasa de conversión hasta 2x mayor</span></li>
                </ul>
            </div>
            <!-- Visual: mini chat closing deal -->
            <div class="col-lg-6 d-flex justify-content-center">
                <div class="mia-feat-visual">
                    <div class="mia-minichat">
                        <div class="mia-minichat-header">
                            <div class="mia-minichat-dot bg-danger me-1"></div>
                            <div class="mia-minichat-dot bg-warning me-1"></div>
                            <div class="mia-minichat-dot bg-success"></div>
                            <span class="ms-2 text-muted" style="font-size:.7rem">WhatsApp · Mia</span>
                        </div>
                        <div class="mia-minichat-body">
                            <div class="mia-msg mia-msg--in">Hola quiero info sobre paquetes a Cusco<span class="mia-time">9:40</span></div>
                            <div class="mia-msg mia-msg--out">¡Hola! ¿Para cuántas personas y qué fechas? 😊<span class="mia-time mia-time--out">9:40 <i class="bi bi-check2-all" style="color:#53bdeb"></i></span></div>
                            <div class="mia-msg mia-msg--in">2 personas, del 20 al 25 de abril<span class="mia-time">9:41</span></div>
                            <div class="mia-msg mia-msg--out">Perfecto! Tengo el <strong>Pack Cusco 5D</strong> a $420/persona. Incluye hotel + tours. ¿Lo reservamos?<span class="mia-time mia-time--out">9:41 <i class="bi bi-check2-all" style="color:#53bdeb"></i></span></div>
                            <div class="mia-msg mia-msg--in">Sí! ¿Cómo pago?<span class="mia-time">9:42</span></div>
                            <div class="mia-msg mia-msg--out">🎉 Aquí su link de pago: pay.mia.travel/abc123 — 30% para reservar. ¡Listo!<span class="mia-time mia-time--out">9:42 <i class="bi bi-check2-all" style="color:#53bdeb"></i></span></div>
                        </div>
                        <div class="mia-feat-badge-green mt-2 mx-2 mb-2">
                            <i class="bi bi-check-circle-fill me-1"></i>Venta cerrada en 2 minutos
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     SECTION 3 — Text LEFT · Visual RIGHT
     "50+ idiomas"
     ═══════════════════════════════════════════ -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Text -->
            <div class="col-lg-6">
                <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold px-3 py-2 mb-3 d-inline-block" style="color:#a07000!important">Multilingüe</span>
                <h2 class="fw-bold mb-3">Un cliente escribe en inglés.<br>Mia responde en inglés.</h2>
                <p class="text-muted mb-4">
                    Turistas de todo el mundo ven tus anuncios. Mia detecta automáticamente el idioma
                    del mensaje y responde en ese mismo idioma — sin configuración, sin traducción manual.
                    Español, inglés, portugués, francés, italiano, alemán y más de 50 idiomas.
                </p>
                <ul class="list-unstyled">
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-warning mt-1"></i><span>Detección automática de idioma</span></li>
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-warning mt-1"></i><span>Más de 50 idiomas disponibles</span></li>
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-warning mt-1"></i><span>Captura turistas internacionales que antes perdías</span></li>
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-warning mt-1"></i><span>Cierra tratos en cualquier idioma, sin esfuerzo</span></li>
                </ul>
            </div>
            <!-- Visual: language bubbles -->
            <div class="col-lg-6 d-flex justify-content-center">
                <div class="mia-feat-visual">
                    <div class="mia-lang-grid">
                        <div class="mia-lang-bubble mia-lang-bubble--in">
                            <span class="me-1">🇪🇸</span> Hola, ¿tienen disponibilidad?
                            <div class="mia-lang-label">Cliente · Español</div>
                        </div>
                        <div class="mia-lang-bubble mia-lang-bubble--out">
                            ¡Hola! Sí, tenemos habitaciones disponibles 😊
                            <div class="mia-lang-label mia-lang-label--out">Mia responde en español ✓</div>
                        </div>
                        <div class="mia-lang-bubble mia-lang-bubble--in">
                            <span class="me-1">🇺🇸</span> Hi! Do you have rooms for 2?
                            <div class="mia-lang-label">Cliente · English</div>
                        </div>
                        <div class="mia-lang-bubble mia-lang-bubble--out">
                            Hi! Yes, we have rooms for 2 available 😊
                            <div class="mia-lang-label mia-lang-label--out">Mia responds in English ✓</div>
                        </div>
                        <div class="mia-lang-bubble mia-lang-bubble--in">
                            <span class="me-1">🇧🇷</span> Olá! Vocês têm pacotes para abril?
                            <div class="mia-lang-label">Cliente · Português</div>
                        </div>
                        <div class="mia-lang-bubble mia-lang-bubble--out">
                            Olá! Sim, temos ótimos pacotes para abril! 😊
                            <div class="mia-lang-label mia-lang-label--out">Mia responde em português ✓</div>
                        </div>
                        <div class="text-center mt-3">
                            <span class="badge bg-dark text-light px-3 py-2">🌍 + 47 idiomas más</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     SECTION 4 — Visual LEFT · Text RIGHT
     "Tú siempre en control"
     ═══════════════════════════════════════════ -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center g-5 flex-lg-row-reverse">
            <!-- Text -->
            <div class="col-lg-6">
                <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold px-3 py-2 mb-3 d-inline-block">Control total</span>
                <h2 class="fw-bold mb-3">Mia trabaja.<br>Tú decides cuándo entrar.</h2>
                <p class="text-muted mb-4">
                    ¿Un cliente necesita atención especial? Simplemente escribe tú en el chat y Mia
                    se hace a un lado automáticamente. Cuando termines, Mia retoma sola.
                    Siempre sabes qué está pasando — notificaciones al instante en tu WhatsApp.
                </p>
                <ul class="list-unstyled">
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-danger mt-1"></i><span>Traspaso humano automático: tú escribes, Mia pausa</span></li>
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-danger mt-1"></i><span>Notificación inmediata de cada reserva confirmada</span></li>
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-danger mt-1"></i><span>Panel de administración con todos tus leads</span></li>
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-danger mt-1"></i><span>Historial completo de cada conversación</span></li>
                </ul>
            </div>
            <!-- Visual: handoff diagram -->
            <div class="col-lg-6 d-flex justify-content-center">
                <div class="mia-feat-visual">
                    <div class="mia-handoff">
                        <div class="mia-handoff-step mia-handoff-step--active">
                            <div class="mia-handoff-icon bg-success text-white">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div class="mia-handoff-text">
                                <div class="fw-bold" style="font-size:.85rem">Mia atiende</div>
                                <div class="text-muted" style="font-size:.75rem">Responde, califica, cotiza</div>
                            </div>
                            <span class="badge bg-success" style="font-size:.65rem">Automático</span>
                        </div>
                        <div class="mia-handoff-arrow text-muted">↓ Tú escribes en el chat</div>
                        <div class="mia-handoff-step">
                            <div class="mia-handoff-icon bg-primary text-white">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="mia-handoff-text">
                                <div class="fw-bold" style="font-size:.85rem">Tú tomas el control</div>
                                <div class="text-muted" style="font-size:.75rem">Mia se hace a un lado</div>
                            </div>
                            <span class="badge bg-primary" style="font-size:.65rem">Manual</span>
                        </div>
                        <div class="mia-handoff-arrow text-muted">↓ Cuando terminas</div>
                        <div class="mia-handoff-step">
                            <div class="mia-handoff-icon bg-success text-white">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div class="mia-handoff-text">
                                <div class="fw-bold" style="font-size:.85rem">Mia retoma sola</div>
                                <div class="text-muted" style="font-size:.75rem">Sin hacer nada extra</div>
                            </div>
                            <span class="badge bg-success" style="font-size:.65rem">Automático</span>
                        </div>
                        <div class="mia-feat-badge-green mt-3">
                            <i class="bi bi-bell-fill me-1"></i>Notificación instantánea en tu WhatsApp
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     SECTION 5 — Text LEFT · Visual RIGHT
     "Sin setup técnico"
     ═══════════════════════════════════════════ -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Text -->
            <div class="col-lg-6">
                <span class="badge bg-info bg-opacity-10 text-info fw-semibold px-3 py-2 mb-3 d-inline-block">Instalación</span>
                <h2 class="fw-bold mb-3">Listo en 48 horas.<br>Cero conocimiento técnico.</h2>
                <p class="text-muted mb-4">
                    No necesitas cambiar tu número de WhatsApp, descargar ninguna app ni
                    contratar a un programador. Nuestro equipo configura todo. Tú solo
                    nos dices cómo funciona tu negocio — el resto lo hacemos nosotros.
                </p>
                <ul class="list-unstyled">
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-info mt-1"></i><span>Se conecta a tu WhatsApp Business actual</span></li>
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-info mt-1"></i><span>Sin apps nuevas, sin cambiar tu número</span></li>
                    <li class="d-flex align-items-start gap-2 mb-2"><i class="bi bi-check-circle-fill text-info mt-1"></i><span>Configuración completa por nuestro equipo en 48h</span></li>
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-info mt-1"></i><span>Soporte incluido — te acompañamos siempre</span></li>
                </ul>
            </div>
            <!-- Visual: setup steps timeline -->
            <div class="col-lg-6 d-flex justify-content-center">
                <div class="mia-feat-visual">
                    <div class="mia-timeline">
                        <div class="mia-tl-step">
                            <div class="mia-tl-dot bg-success">1</div>
                            <div class="mia-tl-content">
                                <div class="fw-bold">Día 1 — Nos contactas</div>
                                <div class="text-muted small">Charlamos 15 min por WhatsApp sobre tu negocio</div>
                            </div>
                        </div>
                        <div class="mia-tl-line"></div>
                        <div class="mia-tl-step">
                            <div class="mia-tl-dot bg-primary">2</div>
                            <div class="mia-tl-content">
                                <div class="fw-bold">Día 1–2 — Configuramos</div>
                                <div class="text-muted small">Nuestro equipo programa Mia con tus precios y servicios</div>
                            </div>
                        </div>
                        <div class="mia-tl-line"></div>
                        <div class="mia-tl-step">
                            <div class="mia-tl-dot bg-warning">3</div>
                            <div class="mia-tl-content">
                                <div class="fw-bold">Día 2 — Prueba</div>
                                <div class="text-muted small">Probamos juntos hasta que todo funcione perfecto</div>
                            </div>
                        </div>
                        <div class="mia-tl-line"></div>
                        <div class="mia-tl-step">
                            <div class="mia-tl-dot" style="background:#25d366">4</div>
                            <div class="mia-tl-content">
                                <div class="fw-bold text-success">Día 2 — ¡Mia está viva! 🎉</div>
                                <div class="text-muted small">Tu WhatsApp empieza a cerrar ventas solo</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     Comparison Table
     ═══════════════════════════════════════════ -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <h2 class="text-center fw-bold mb-2">Mia vs. las alternativas</h2>
        <p class="text-center text-secondary mb-5">Por qué los negocios que más crecen eligen Mia</p>
        <div class="table-responsive">
            <table class="table table-dark table-bordered text-center align-middle">
                <thead>
                    <tr>
                        <th class="text-start py-3"></th>
                        <th class="py-3 text-secondary">Tu equipo solo</th>
                        <th class="py-3 text-secondary">Contratar más personal</th>
                        <th class="py-3 text-success bg-success bg-opacity-10">
                            <i class="bi bi-whatsapp me-1"></i>Mia
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-start fw-semibold">Disponible 24/7</td>
                        <td><i class="bi bi-x-lg text-danger"></i></td>
                        <td><span class="text-warning">~</span> <small>(costo extra)</small></td>
                        <td class="bg-success bg-opacity-10"><i class="bi bi-check-lg text-success fw-bold"></i></td>
                    </tr>
                    <tr>
                        <td class="text-start fw-semibold">Responde en &lt;5 segundos</td>
                        <td><i class="bi bi-x-lg text-danger"></i></td>
                        <td><i class="bi bi-x-lg text-danger"></i></td>
                        <td class="bg-success bg-opacity-10"><i class="bi bi-check-lg text-success fw-bold"></i></td>
                    </tr>
                    <tr>
                        <td class="text-start fw-semibold">Maneja 200 chats a la vez</td>
                        <td><i class="bi bi-x-lg text-danger"></i></td>
                        <td><i class="bi bi-x-lg text-danger"></i></td>
                        <td class="bg-success bg-opacity-10"><i class="bi bi-check-lg text-success fw-bold"></i></td>
                    </tr>
                    <tr>
                        <td class="text-start fw-semibold">50+ idiomas</td>
                        <td><i class="bi bi-x-lg text-danger"></i></td>
                        <td><span class="text-warning">~</span> <small>(muy caro)</small></td>
                        <td class="bg-success bg-opacity-10"><i class="bi bi-check-lg text-success fw-bold"></i></td>
                    </tr>
                    <tr>
                        <td class="text-start fw-semibold">Sin errores ni olvidos</td>
                        <td><i class="bi bi-x-lg text-danger"></i></td>
                        <td><i class="bi bi-x-lg text-danger"></i></td>
                        <td class="bg-success bg-opacity-10"><i class="bi bi-check-lg text-success fw-bold"></i></td>
                    </tr>
                    <tr>
                        <td class="text-start fw-semibold">Costo mensual</td>
                        <td class="text-secondary">Salarios + horas extra</td>
                        <td class="text-danger">S/3,000–S/6,000+</td>
                        <td class="bg-success bg-opacity-10 text-success fw-bold">Desde S/399</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     Industries
     ═══════════════════════════════════════════ -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center fw-bold mb-2">Diseñado para tu industria</h2>
        <p class="text-center text-muted mb-5">Mia se adapta a cómo funciona tu negocio</p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="text-center mb-3">
                        <div class="mia-industry-icon bg-success bg-opacity-10">
                            <i class="bi bi-building text-success" style="font-size:2rem"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-center mb-3">Hoteles</h5>
                    <ul class="list-unstyled text-muted small">
                        <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Muestra habitaciones, precios y disponibilidad</li>
                        <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Solicita foto de DNI / pasaporte</li>
                        <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Confirma reservas y envía voucher por email</li>
                        <li><i class="bi bi-check2 text-success me-2"></i>Check-in anticipado por WhatsApp</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4 border-success border-2">
                    <div class="text-center mb-3">
                        <div class="mia-industry-icon bg-primary bg-opacity-10">
                            <i class="bi bi-geo-alt text-primary" style="font-size:2rem"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-center mb-3">Agencias de Viaje</h5>
                    <ul class="list-unstyled text-muted small">
                        <li class="mb-2"><i class="bi bi-check2 text-primary me-2"></i>Cotiza paquetes al instante con precios actualizados</li>
                        <li class="mb-2"><i class="bi bi-check2 text-primary me-2"></i>Califica viajeros por presupuesto y destino</li>
                        <li class="mb-2"><i class="bi bi-check2 text-primary me-2"></i>Envía itinerarios y confirmaciones automáticamente</li>
                        <li><i class="bi bi-check2 text-primary me-2"></i>Seguimiento post-cotización para cerrar la venta</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="text-center mb-3">
                        <div class="mia-industry-icon bg-warning bg-opacity-10">
                            <i class="bi bi-compass text-warning" style="font-size:2rem"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-center mb-3">Operadores Turísticos</h5>
                    <ul class="list-unstyled text-muted small">
                        <li class="mb-2"><i class="bi bi-check2 text-warning me-2"></i>Informa fechas disponibles y capacidad de tours</li>
                        <li class="mb-2"><i class="bi bi-check2 text-warning me-2"></i>Gestiona grupos y reservas múltiples</li>
                        <li class="mb-2"><i class="bi bi-check2 text-warning me-2"></i>Envía recordatorios antes del tour</li>
                        <li><i class="bi bi-check2 text-warning me-2"></i>Recopila reviews automáticamente al final</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Bottom CTA -->
<section class="py-5 text-center mia-hero text-white">
    <div class="container">
        <h2 class="fw-bold mb-3">¿Listo para que Mia empiece a trabajar hoy?</h2>
        <p class="lead mb-4 opacity-75">7 días gratis, sin tarjeta de crédito, configuración incluida</p>
        <a href="<?= $waLink ?>" class="btn btn-success btn-lg px-5 fw-bold me-3" target="_blank">
            <i class="bi bi-whatsapp me-2"></i>Probar Gratis 7 Días
        </a>
        <a href="<?= $base ?>/pricing" class="btn btn-outline-light btn-lg px-5">
            Ver Precios
        </a>
    </div>
</section>

<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
