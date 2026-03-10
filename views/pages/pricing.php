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
        <p class="text-center text-muted mb-5">Elige entre Soporte, Ventas + Soporte, o Suite completa con gestión e integraciones. Todos incluyen 7 días gratis.</p>

        <div class="row g-4 justify-content-center">

            <!-- Starter -->
            <div class="col-lg-3">
                <div class="card h-100 border shadow-sm">
                    <div class="card-header bg-white text-center py-4">
                        <h5 class="fw-bold mb-1">Starter</h5>
                        <p class="text-muted small mb-0">Para probar y empezar sin riesgo</p>
                    </div>
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold mb-1"><?= App::CURRENCY ?><?= number_format(App::PLAN_STARTER) ?></div>
                        <p class="text-muted">/ mes</p>
                        <hr>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Módulo Soporte 24/7</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Respuestas automáticas por WhatsApp</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Hasta 200 conversaciones/mes</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>1 usuario incluido</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Multilingüe (50+ idiomas)</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle me-2"></i>Traspaso humano inteligente</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle me-2"></i>Módulo Ventas</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle me-2"></i>Módulo Gestión</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center py-3">
                        <a href="<?= $base ?>/register?plan=starter" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-rocket me-1"></i>Empezar Gratis
                        </a>
                    </div>
                </div>
            </div>

            <!-- Basic -->
            <div class="col-lg-4">
                <div class="card h-100 border shadow-sm">
                    <div class="card-header bg-white text-center py-4">
                        <h5 class="fw-bold mb-1">Soporte Básico</h5>
                        <p class="text-muted small mb-0">Atención automática para negocios en inicio</p>
                    </div>
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold mb-1"><?= App::CURRENCY ?><?= number_format(App::PLAN_BASIC) ?></div>
                        <p class="text-muted">/ mes</p>
                        <hr>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Módulo Soporte 24/7</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Respuestas automáticas por WhatsApp</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Traspaso humano inteligente</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Hasta 1,000 conversaciones/mes</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>1 usuario incluido</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Multilingüe (ES · EN · PT · FR · DE · IT + 50 idiomas más)</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle me-2"></i>Módulo Ventas</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle me-2"></i>Módulo Gestión de negocio</li>
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
                        <p class="small mb-0 opacity-75">Ventas + Soporte para equipos en crecimiento</p>
                    </div>
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold mb-1"><?= App::CURRENCY ?><?= number_format(App::PLAN_PRO) ?></div>
                        <p class="text-muted">/ mes</p>
                        <hr>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Todo lo del Soporte Básico</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Módulo Ventas (captura y calificación de leads)</strong></li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Conversaciones ilimitadas</strong></li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>1 usuario incluido</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Asientos adicionales por equipo:</li>
                            <li class="mb-2 ms-4"><i class="bi bi-dot me-1"></i>Asientos 2-3: S/300 c/u</li>
                            <li class="mb-2 ms-4"><i class="bi bi-dot me-1"></i>Asientos 4-6: S/200 c/u</li>
                            <li class="mb-2 ms-4"><i class="bi bi-dot me-1"></i>Asientos 7+: S/120 c/u</li>
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
                        <p class="text-muted small mb-0">Suite completa: Ventas + Soporte + Gestión</p>
                    </div>
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold mb-1"><?= App::CURRENCY ?><?= number_format(App::PLAN_ENTERPRISE) ?></div>
                        <p class="text-muted">/ mes</p>
                        <hr>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Todo lo del Pro</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Módulo Gestión de negocio completo</strong></li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Usuarios de equipo ilimitados</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Múltiples números WhatsApp</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Integraciones estándar (API, webhooks, Zapier/Make)</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Integraciones personalizadas bajo evaluación técnica</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Account manager dedicado + SLA 99.9% uptime</li>
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

        <!-- Footer notes -->
        <div class="text-center mt-5">
            <p class="text-muted">
                <i class="bi bi-check2-circle me-1 text-success"></i>
                <strong>Sin costo de configuración</strong> — onboarding y personalización inicial incluidos en todos los planes.
            </p>
            <p class="text-muted small">Integraciones personalizadas (por ejemplo, PMS/CRM privados) se cotizan según alcance y acceso técnico.</p>
            <p class="text-muted small">Todos los precios en Soles peruanos (PEN) · Sin FX · Soporte local en español · IVA no incluido.</p>
            <div class="d-inline-flex gap-3 flex-wrap justify-content-center mt-3">
                <span class="badge bg-light text-dark border"><i class="bi bi-flag me-1"></i>Hecho en Perú</span>
                <span class="badge bg-light text-dark border"><i class="bi bi-chat-dots me-1"></i>Soporte en español</span>
                <span class="badge bg-light text-dark border"><i class="bi bi-currency-exchange me-1"></i>Precios en Soles (PEN)</span>
                <span class="badge bg-light text-dark border"><i class="bi bi-headset me-1"></i>Sin call centers en India</span>
            </div>
        </div>
    </div>
</section>

<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
