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
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Hasta 500 conversaciones/mes</li>
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

        <!-- Corporate tiers -->
        <div class="mt-5 pt-4 border-top">
            <h3 class="text-center fw-bold mb-2">¿Cadena, franquicia o corporación?</h3>
            <p class="text-center text-muted mb-4">Planes diseñados para operar múltiples sedes, alto volumen y equipos grandes.</p>
            <div class="row g-4 justify-content-center">

                <!-- Duo -->
                <div class="col-lg-4">
                    <div class="card h-100 border-secondary shadow-sm">
                        <div class="card-header bg-secondary text-white text-center py-4">
                            <h5 class="fw-bold mb-1">Enterprise Duo</h5>
                            <p class="small mb-0 opacity-75">2 sedes: hoteles boutique, agencias con sucursal, parejas de negocios</p>
                        </div>
                        <div class="card-body text-center">
                            <div class="display-5 fw-bold mb-1"><?= App::CURRENCY ?><?= number_format(App::PLAN_ENTERPRISE_DUO) ?></div>
                            <p class="text-muted">/ mes &middot; 2 sedes</p>
                            <hr>
                            <ul class="list-unstyled text-start">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-secondary me-2"></i>Todo lo del Enterprise</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-secondary me-2"></i><strong>2 sedes / n&uacute;meros WhatsApp</strong></li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-secondary me-2"></i>15,000 conversaciones/mes incluidas</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-secondary me-2"></i>Dashboard unificado por sede</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-secondary me-2"></i>Onboarding en 48h</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-secondary me-2"></i>SLA 99.9% uptime</li>
                            </ul>
                        </div>
                        <div class="card-footer bg-white text-center py-3">
                            <a href="<?= $base ?>/register?plan=enterprise-duo" class="btn btn-secondary w-100">
                                <i class="bi bi-building me-1"></i>Contactar Ventas
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Cadena -->
                <div class="col-lg-5">
                    <div class="card h-100 border-primary shadow">
                        <div class="card-header bg-primary text-white text-center py-4">
                            <h5 class="fw-bold mb-1">Enterprise Cadena</h5>
                            <p class="small mb-0 opacity-75">Cadenas hoteleras, agencias con franquicias, grupos de negocios</p>
                        </div>
                        <div class="card-body text-center">
                            <div class="display-5 fw-bold mb-1"><?= App::CURRENCY ?><?= number_format(App::PLAN_ENTERPRISE_CHAIN) ?></div>
                            <p class="text-muted">/ mes · hasta 5 sedes</p>
                            <hr>
                            <ul class="list-unstyled text-start">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Todo lo del Enterprise</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i><strong>Hasta 5 sedes / números WhatsApp</strong></li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>30,000 conversaciones/mes incluidas</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Dashboard unificado multi-sede</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Reportes consolidados por sede</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Onboarding prioritario (24h)</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>SLA 99.9% + soporte 24/7</li>
                            </ul>
                        </div>
                        <div class="card-footer bg-white text-center py-3">
                            <a href="<?= $base ?>/register?plan=enterprise-chain" class="btn btn-primary w-100">
                                <i class="bi bi-building me-1"></i>Contactar Ventas
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Corporativo -->
                <div class="col-lg-5">
                    <div class="card h-100 border-dark shadow">
                        <div class="card-header bg-dark text-white text-center py-4">
                            <span class="badge bg-warning text-dark mb-2">Alto Volumen</span>
                            <h5 class="fw-bold mb-1">Enterprise Corporativo</h5>
                            <p class="small mb-0 opacity-75">Grandes corporaciones, cadenas nacionales, operadores turísticos</p>
                        </div>
                        <div class="card-body text-center">
                            <div class="display-5 fw-bold mb-1"><?= App::CURRENCY ?><?= number_format(App::PLAN_ENTERPRISE_CORP) ?></div>
                            <p class="text-muted">/ mes · sedes ilimitadas</p>
                            <hr>
                            <ul class="list-unstyled text-start">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-dark me-2"></i>Todo lo del Enterprise Cadena</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-dark me-2"></i><strong>Sedes y números WhatsApp ilimitados</strong></li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-dark me-2"></i><strong>Conversaciones ilimitadas</strong></li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-dark me-2"></i>Infraestructura dedicada</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-dark me-2"></i>Integraciones con PMS/CRM propietario</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-dark me-2"></i>Account manager exclusivo</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-dark me-2"></i>SLA personalizado + contrato anual</li>
                            </ul>
                        </div>
                        <div class="card-footer bg-white text-center py-3">
                            <a href="<?= $base ?>/register?plan=enterprise-corp" class="btn btn-dark w-100">
                                <i class="bi bi-buildings me-1"></i>Contactar Ventas
                            </a>
                        </div>
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
