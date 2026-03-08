<?php
/**
 * mia/views/pages/landing.php — Homepage / Landing
 */

$base = App::basePath();
$pageTitle = 'Mia — Responde todos tus leads de WhatsApp, automáticamente';
$waLink = 'https://wa.me/' . preg_replace('/[^0-9]/', '', App::WHATSAPP) . '?text=Hola%20Mia!';

ob_start();
?>

<!-- Hero -->
<section class="mia-hero text-white text-center py-5">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">
                    Pones anuncios en Facebook.<br>
                    <span class="text-success">¿Quién responde los 100 mensajes de WhatsApp?</span>
                </h1>
                <p class="lead mb-4">
                    Hoteles, agencias de viaje y operadores turísticos gastan miles en publicidad
                    y pierden hasta el <strong>60% de sus leads</strong> porque no pueden responder a tiempo.
                    Mia es tu asistente de WhatsApp — responde al instante, califica al cliente,
                    toma la reserva o cotización, y confirma el pago. Las 24 horas. Los 7 días. Sin perder ninguno.
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="<?= $waLink ?>" class="btn btn-success btn-lg px-4" target="_blank">
                        <i class="bi bi-whatsapp me-2"></i>Prueba Gratis — 7 días
                    </a>
                    <a href="<?= $base ?>/demo" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-play-circle me-2"></i>Ver Demo
                    </a>
                </div>
                <p class="mt-3 small text-light opacity-75">Sin tarjeta de crédito · Configuración en 48h · Cancela cuando quieras</p>
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

<!-- ROI Section -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-3">Los números no mienten</h2>
                <p class="lead">Si gastas S/1,000 al mes en Facebook Ads y el 60% de tus leads quedan sin responder, estás tirando S/600 a la basura cada mes.</p>
                <p>Con Mia respondiendo al instante, podrías <strong>convertir el doble de leads</strong> sin contratar más personal — eso puede significar <strong>S/3,000–S/8,000 en ventas adicionales</strong> por solo S/399/mes.</p>
                <p class="fw-bold text-success fs-5">Por cada S/1 que inviertes → recuperas S/8 o más</p>
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
        <a href="<?= $waLink ?>" class="btn btn-light btn-lg px-5 fw-bold" target="_blank">
            <i class="bi bi-whatsapp me-2 text-success"></i>Hablar con Mia
        </a>
    </div>
</section>

<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
