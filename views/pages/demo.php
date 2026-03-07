<?php
/**
 * mia/views/pages/demo.php — Live demo simulation page
 */

$base = App::basePath();
$pageTitle = 'Demo en Vivo — Mia by AiniTravel';
$waLink = 'https://wa.me/' . preg_replace('/[^0-9]/', '', App::WHATSAPP) . '?text=Hola%20Mia!%20Quiero%20ver%20la%20demo';

ob_start();
?>

<section class="py-5">
    <div class="container">
        <h1 class="text-center fw-bold mb-2">Míralo en Acción</h1>
        <p class="text-center text-muted mb-5">Así es como tu huésped reservaría una habitación por WhatsApp</p>

        <div class="row justify-content-center">
            <div class="col-lg-5">
                <!-- WhatsApp mockup -->
                <div class="card shadow border-0" style="max-width:400px; margin:0 auto;">
                    <div class="card-header bg-success text-white d-flex align-items-center py-3">
                        <i class="bi bi-arrow-left me-3 fs-5"></i>
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:36px;height:36px">
                            <span class="fw-bold text-success" style="font-size:14px">M</span>
                        </div>
                        <div>
                            <div class="fw-bold small">Hotel Sol de Cusco</div>
                            <div class="small opacity-75">en línea</div>
                        </div>
                    </div>
                    <div class="card-body p-3" id="demoChat" style="background:#e5ddd5; min-height:450px; overflow-y:auto">
                        <!-- Messages injected by JS -->
                    </div>
                    <div class="card-footer bg-white p-2">
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm border-0" placeholder="Escribe un mensaje..." disabled>
                            <button class="btn btn-success btn-sm" id="btnNextStep" type="button">
                                <i class="bi bi-play-fill"></i> Siguiente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-flex align-items-center mt-4 mt-lg-0">
                <div>
                    <h4 class="fw-bold mb-3">Lo que estás viendo:</h4>
                    <div id="demoExplanation">
                        <p class="text-muted">Presiona <strong>"Siguiente"</strong> para ver cada paso de la conversación.</p>
                    </div>

                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="fw-bold"><i class="bi bi-lightbulb text-warning me-2"></i>¿Sabías que...?</h6>
                        <p class="small text-muted mb-0">El 85% de viajeros contactan hoteles por WhatsApp antes de reservar. Si no respondes en 5 minutos, se van al siguiente hotel.</p>
                    </div>

                    <div class="mt-4">
                        <a href="<?= $waLink ?>" class="btn btn-success btn-lg w-100" target="_blank">
                            <i class="bi bi-whatsapp me-2"></i>Pruébalo Tú Mismo
                        </a>
                        <p class="small text-muted text-center mt-2">Habla con Mia directamente por WhatsApp</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
ob_start();
?>
<script src="<?= App::asset('js/demo.js') ?>"></script>
<?php
$pageScripts = ob_get_clean();

$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
