<?php
/**
 * mia/views/pages/landing.php — Homepage / Landing
 */

$base = App::basePath();
$pageTitle = 'Mia — Reservas Automáticas por WhatsApp para Hoteles';
$waLink = 'https://wa.me/' . preg_replace('/[^0-9]/', '', App::WHATSAPP) . '?text=Hola%20Mia!';

ob_start();
?>

<!-- Hero -->
<section class="mia-hero text-white text-center py-5">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">
                    Tu hotel tomando reservas<br>
                    <span class="text-success">por WhatsApp, 24/7</span>
                </h1>
                <p class="lead mb-4">
                    Mia es tu asistente inteligente que atiende huéspedes,
                    muestra habitaciones, cobra y confirma reservas —
                    incluso a las 3 de la mañana.
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
        <p class="text-center text-muted mb-5">Estos son los problemas más comunes de hoteles que aún no usan Mia</p>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <i class="bi bi-moon-stars text-primary fs-1 mb-3"></i>
                    <h6 class="fw-bold">Pierdes reservas de noche</h6>
                    <p class="text-muted small">Los huéspedes envían mensajes a las 11pm. Sin respuesta, reservan en otro lugar.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <i class="bi bi-hourglass-split text-warning fs-1 mb-3"></i>
                    <h6 class="fw-bold">Responder toma mucho tiempo</h6>
                    <p class="text-muted small">Copiar precios, verificar disponibilidad, enviar fotos... todo manual.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <i class="bi bi-x-circle text-danger fs-1 mb-3"></i>
                    <h6 class="fw-bold">Preguntan pero no reservan</h6>
                    <p class="text-muted small">El 70% de consultas no se convierten porque falta seguimiento inmediato.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <i class="bi bi-cash-stack text-success fs-1 mb-3"></i>
                    <h6 class="fw-bold">Comisiones altísimas</h6>
                    <p class="text-muted small">Booking.com cobra 15-25%. Eso podría ser tu ganancia.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center fw-bold mb-2">¿Cómo funciona?</h2>
        <p class="text-center text-muted mb-5">3 pasos simples. Tu hotel con reservas automáticas por WhatsApp.</p>
        <div class="row g-4 align-items-center">
            <div class="col-md-4 text-center">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px">
                    <span class="fs-2 fw-bold text-success">1</span>
                </div>
                <h5 class="fw-bold">El huésped escribe</h5>
                <p class="text-muted">Ve tu anuncio en Facebook o Google, y te escribe por WhatsApp.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px">
                    <span class="fs-2 fw-bold text-success">2</span>
                </div>
                <h5 class="fw-bold">Mia atiende todo</h5>
                <p class="text-muted">Muestra habitaciones, precio, fechas, pide ID, y confirma la reserva.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px">
                    <span class="fs-2 fw-bold text-success">3</span>
                </div>
                <h5 class="fw-bold">Tú recibes la reserva</h5>
                <p class="text-muted">Notificación instantánea con todos los datos del huésped y la reserva.</p>
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
                <p class="lead">Un hotel de 20 habitaciones pierde en promedio S/2,400 al mes en reservas no atendidas fuera de horario.</p>
                <p>Con Mia, podrías recuperar el <strong>30% de esas reservas perdidas</strong> — eso es <strong>~S/720/mes en ingresos adicionales</strong> por solo S/399.</p>
                <p class="fw-bold text-success fs-5">Por cada S/1 que inviertes → recuperas S/6</p>
            </div>
            <div class="col-lg-6">
                <div class="card bg-dark border-success">
                    <div class="card-body text-center">
                        <div class="row g-3">
                            <div class="col-6">
                                <h3 class="text-success fw-bold">24/7</h3>
                                <small class="text-secondary">Atención sin parar</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-success fw-bold">&lt;5s</h3>
                                <small class="text-secondary">Tiempo de respuesta</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-success fw-bold">0%</h3>
                                <small class="text-secondary">Comisión por reserva</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-success fw-bold">40%</h3>
                                <small class="text-secondary">Más reservas directas</small>
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
        <h2 class="fw-bold mb-3">¿Listo para que tu hotel nunca pierda una reserva?</h2>
        <p class="lead mb-4">Empieza tu prueba gratis de 7 días — sin compromiso</p>
        <a href="<?= $waLink ?>" class="btn btn-light btn-lg px-5 fw-bold" target="_blank">
            <i class="bi bi-whatsapp me-2 text-success"></i>Hablar con Mia
        </a>
    </div>
</section>

<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
