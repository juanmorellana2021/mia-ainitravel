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
        <p class="text-center text-muted mb-5">3 planes simples. Sin permanencia. Todos incluyen 7 días gratis y configuración sin costo.</p>

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
                        <div class="mb-3 p-2 rounded-2 d-flex align-items-center gap-2" style="background:rgba(108,117,125,0.08);border:1px solid rgba(108,117,125,0.2)">
                            <i class="bi bi-headset" style="color:#6c757d;font-size:1.25rem;flex-shrink:0"></i>
                            <div class="text-start">
                                <div class="fw-bold" style="font-size:.85rem;color:#495057">Mia Soporte</div>
                                <div class="text-muted" style="font-size:.75rem">IA de soporte 24/7 · FAQs · 50+ idiomas</div>
                            </div>
                        </div>
                        <hr>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Bot IA 24/7 en WhatsApp</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Configuración con tus servicios, precios y FAQs</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Autocompletar configuración con IA</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>10 habilidades de personalidad del bot</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>50+ idiomas automáticos</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Panel CRM (leads, estados, pipeline)</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Analíticas y reportes</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Difusión masiva a leads por WhatsApp</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Chat directo desde el panel (Mia pausa al instante)</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Notificaciones al dueño</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Horario de atención configurable (bot respeta tu horario)</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Enlace directo y código QR de WhatsApp</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle me-2"></i>Traspaso humano por WhatsApp</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle me-2"></i>Captura de leads automática</li>
                            <li class="mb-2 text-muted"><i class="bi bi-x-circle me-2"></i>Agenda de citas con recordatorios</li>
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
                <div class="card h-100 border-success shadow">
                    <div class="card-header bg-success text-white text-center py-4">
                        <span class="badge bg-warning text-dark mb-2">Más Popular</span>
                        <h5 class="fw-bold mb-1">Pro</h5>
                        <p class="small mb-0 opacity-75">Atención automática + traspaso + captura de leads</p>
                    </div>
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold mb-1"><?= App::CURRENCY ?><?= number_format(App::PLAN_BASIC) ?></div>
                        <p class="text-muted">/ mes</p>
                        <div class="mb-3 p-2 rounded-2 d-flex align-items-center gap-2" style="background:rgba(13,110,253,0.07);border:1px solid rgba(13,110,253,0.18)">
                            <i class="bi bi-graph-up-arrow" style="color:#0d6efd;font-size:1.25rem;flex-shrink:0"></i>
                            <div class="text-start">
                                <div class="fw-bold" style="font-size:.85rem;color:#0d6efd">Mia Ventas</div>
                                <div class="text-muted" style="font-size:.75rem">IA de ventas · captura leads · traspaso humano inteligente</div>
                            </div>
                        </div>
                        <hr>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Todo lo del Starter</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Traspaso humano por WhatsApp</strong> <small class="text-muted">(Mia reconoce "quiero hablar con una persona" y pausa)</small></li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Captura de leads automática</strong> <small class="text-muted">(Mia pide nombre y contacto y los guarda en tu CRM)</small></li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Horario de atención configurable</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Enlace directo y código QR de WhatsApp</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Agenda de citas</strong> <small class="text-muted">(Mia agenda, confirma y envía recordatorios automáticos)</small></li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center py-3">
                        <a href="<?= $base ?>/register?plan=basic" class="btn btn-success w-100">
                            <i class="bi bi-rocket me-1"></i>Empezar Gratis
                        </a>
                    </div>
                </div>
            </div>

            <!-- Business (highlighted) -->
            <div class="col-lg-4">
                <div class="card h-100 border shadow-sm">
                    <div class="card-header bg-dark text-white text-center py-4">
                        <h5 class="fw-bold mb-1">Business</h5>
                        <p class="small mb-0 opacity-75">Suite completa + múltiples canales + soporte dedicado</p>
                    </div>
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold mb-1"><?= App::CURRENCY ?><?= number_format(App::PLAN_PRO) ?></div>
                        <p class="text-muted">/ mes</p>
                        <div class="mb-3 p-2 rounded-2 d-flex align-items-center gap-2" style="background:rgba(37,211,102,0.09);border:1px solid rgba(37,211,102,0.22)">
                            <i class="bi bi-buildings" style="color:#25d366;font-size:1.25rem;flex-shrink:0"></i>
                            <div class="text-start">
                                <div class="fw-bold" style="font-size:.85rem;color:#25d366">Mia Business</div>
                                <div class="text-muted" style="font-size:.75rem">Suite completa · citas · difusión · secuencias · ilimitado</div>
                            </div>
                        </div>
                        <hr>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Todo lo del Pro</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Múltiples números WhatsApp</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Horario de atención configurable</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Enlace directo y código QR de WhatsApp</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Agenda de citas</strong> <small class="text-muted">(Mia agenda, confirma y envía recordatorios automáticos)</small></li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Onboarding dedicado en 48h</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>SLA 99.9% uptime garantizado</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Account manager dedicado</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center py-3">
                        <a href="<?= $base ?>/register?plan=pro" class="btn btn-dark w-100">
                            <i class="bi bi-rocket me-1"></i>Empezar Gratis
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
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-secondary me-2"></i><strong>2 números WhatsApp independientes</strong></li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-secondary me-2"></i>Onboarding en 48h</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-secondary me-2"></i>SLA 99.9% uptime</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-secondary me-2"></i>Soporte prioritario</li>
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
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i><strong>Hasta 5 números WhatsApp independientes</strong></li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Onboarding prioritario (24h)</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>SLA 99.9% + soporte 24/7</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Account manager dedicado</li>
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
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-dark me-2"></i><strong>Números WhatsApp ilimitados</strong></li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-dark me-2"></i>Infraestructura dedicada</li>
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
