<?php
/**
 * mia/views/pages/pricing.php — Pricing page
 */

$base = App::basePath();
$pageTitle = 'Precios — Mia by AiniTravel';
$waLink = 'https://wa.me/' . preg_replace('/[^0-9]/', '', App::WHATSAPP) . '?text=Hola%20Mia!%20Me%20interesa%20el%20plan%20';

ob_start();
?>

<section class="py-5">
    <div class="container">
        <h1 class="text-center fw-bold mb-2">Planes y Precios</h1>
        <p class="text-center text-muted mb-5">Elige el plan que mejor se adapte a tu negocio. Todos incluyen 7 días gratis.</p>

        <div class="row g-4 justify-content-center">

            <!-- Basic -->
            <div class="col-lg-4">
                <div class="card h-100 border shadow-sm">
                    <div class="card-header bg-white text-center py-4">
                        <h5 class="fw-bold mb-1">Básico</h5>
                        <p class="text-muted small mb-0">Hostales y hoteles pequeños</p>
                    </div>
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold mb-1"><?= App::CURRENCY ?><?= number_format(App::PLAN_BASIC) ?></div>
                        <p class="text-muted">/ mes</p>
                        <hr>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Reservas automáticas 24/7</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Notificaciones WhatsApp + email</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Traspaso humano inteligente</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Verificación de identidad</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Hasta 200 conversaciones/mes</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Bilingüe (ES + EN)</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle me-2"></i>Panel web de control</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle me-2"></i>Consulta por WhatsApp</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center py-3">
                        <a href="<?= $base ?>/register?plan=basic" class="btn btn-outline-success w-100">
                            <i class="bi bi-rocket me-1"></i>Empezar Gratis
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pro (highlighted) -->
            <div class="col-lg-4">
                <div class="card h-100 border-success shadow">
                    <div class="card-header bg-success text-white text-center py-4">
                        <span class="badge bg-warning text-dark mb-2">Más Popular</span>
                        <h5 class="fw-bold mb-1">Pro</h5>
                        <p class="small mb-0 opacity-75">Hoteles medianos y agencias</p>
                    </div>
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold mb-1"><?= App::CURRENCY ?><?= number_format(App::PLAN_PRO) ?></div>
                        <p class="text-muted">/ mes</p>
                        <hr>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Todo lo del Básico</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Panel web de control completo</strong></li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Consulta datos por WhatsApp</strong></li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Conversaciones ilimitadas</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Reportes mensuales automáticos</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Email personalizado con tu logo</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Soporte prioritario</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center py-3">
                        <a href="<?= $base ?>/register?plan=pro" class="btn btn-success w-100">
                            <i class="bi bi-rocket me-1"></i>Empezar Gratis
                        </a>
                    </div>
                </div>
            </div>

            <!-- Enterprise -->
            <div class="col-lg-4">
                <div class="card h-100 border shadow-sm">
                    <div class="card-header bg-white text-center py-4">
                        <h5 class="fw-bold mb-1">Enterprise</h5>
                        <p class="text-muted small mb-0">Cadenas y agencias grandes</p>
                    </div>
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold mb-1"><?= App::CURRENCY ?><?= number_format(App::PLAN_ENTERPRISE) ?></div>
                        <p class="text-muted">/ mes</p>
                        <hr>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Todo lo del Pro</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Múltiples números WhatsApp</strong></li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Integración con tu PMS</strong></li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Personalización completa</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>API access</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Account manager dedicado</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>SLA 99.9% uptime</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center py-3">
                        <a href="<?= $base ?>/register?plan=enterprise" class="btn btn-outline-success w-100">
                            <i class="bi bi-rocket me-1"></i>Contactar Ventas
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Setup fee note -->
        <div class="text-center mt-5">
            <p class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Configuración única: <strong><?= App::CURRENCY ?><?= number_format(App::SETUP_FEE) ?></strong>
                (incluye personalización completa de tu asistente)
            </p>
            <p class="text-muted small">Todos los precios en Soles peruanos (PEN). IVA no incluido.</p>
        </div>
    </div>
</section>

<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
