<?php
/**
 * mia/views/pages/features.php — Features page
 */

$base = App::basePath();
$pageTitle = 'Funciones — Mia by AiniTravel';
$waLink = 'https://wa.me/' . preg_replace('/[^0-9]/', '', App::WHATSAPP) . '?text=Hola%20Mia!';

ob_start();
?>

<section class="py-5 bg-light">
    <div class="container">
        <h1 class="text-center fw-bold mb-2">Todo lo que Mia hace por tu hotel</h1>
        <p class="text-center text-muted mb-5">Un asistente completo que trabaja para ti sin descanso</p>

        <div class="row g-4">
            <!-- Feature 1 -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:50px;height:50px">
                            <i class="bi bi-clock-fill text-success fs-5"></i>
                        </div>
                        <h5 class="fw-bold">Reservas 24/7</h5>
                        <p class="text-muted">Tu WhatsApp atiende huéspedes a cualquier hora — noches, fines de semana, feriados. Nunca más pierdas una reserva.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:50px;height:50px">
                            <i class="bi bi-chat-dots-fill text-primary fs-5"></i>
                        </div>
                        <h5 class="fw-bold">Conversación Natural</h5>
                        <p class="text-muted">Mia habla como una persona real. Responde preguntas, sugiere habitaciones, y guía al huésped hasta confirmar.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:50px;height:50px">
                            <i class="bi bi-translate text-warning fs-5"></i>
                        </div>
                        <h5 class="fw-bold">Bilingüe Automático</h5>
                        <p class="text-muted">Detecta si el huésped habla español o inglés y responde en su idioma. Perfecto para turismo internacional.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 4 -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:50px;height:50px">
                            <i class="bi bi-person-badge-fill text-danger fs-5"></i>
                        </div>
                        <h5 class="fw-bold">Verificación de Identidad</h5>
                        <p class="text-muted">Pide foto de DNI/pasaporte antes de confirmar. Sabes quién llega a tu hotel antes de que llegue.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 5 -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:50px;height:50px">
                            <i class="bi bi-bell-fill text-info fs-5"></i>
                        </div>
                        <h5 class="fw-bold">Notificaciones Instantáneas</h5>
                        <p class="text-muted">Cuando hay una nueva reserva, recibes un WhatsApp y email al instante con todos los detalles.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 6 -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:50px;height:50px">
                            <i class="bi bi-people-fill text-success fs-5"></i>
                        </div>
                        <h5 class="fw-bold">Traspaso Humano</h5>
                        <p class="text-muted">Si tú respondes un mensaje, Mia se detiene automáticamente y te deja hablar. Retoma cuando tú quieras.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 7 -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:50px;height:50px">
                            <i class="bi bi-envelope-fill text-primary fs-5"></i>
                        </div>
                        <h5 class="fw-bold">Emails Automáticos</h5>
                        <p class="text-muted">El huésped recibe confirmación por email con los detalles de su reserva. Profesional y automático.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 8 -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:50px;height:50px">
                            <i class="bi bi-graph-up text-warning fs-5"></i>
                        </div>
                        <h5 class="fw-bold">Panel Web de Control</h5>
                        <p class="text-muted">Ve todas tus reservas, ingresos, y estadísticas en un dashboard online. También accesible por WhatsApp (Plan Pro).</p>
                    </div>
                </div>
            </div>

            <!-- Feature 9 -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:50px;height:50px">
                            <i class="bi bi-shield-check text-danger fs-5"></i>
                        </div>
                        <h5 class="fw-bold">Sin Comisiones</h5>
                        <p class="text-muted">Tarifa fija mensual. No importa si haces 5 o 500 reservas — siempre pagas lo mismo. Adiós Booking.com.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="<?= $waLink ?>" class="btn btn-success btn-lg px-5" target="_blank">
                <i class="bi bi-whatsapp me-2"></i>Empezar Prueba Gratis
            </a>
        </div>
    </div>
</section>

<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
