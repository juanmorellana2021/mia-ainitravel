<?php
/**
 * mia/views/pages/404.php
 */

$pageTitle = 'Página no encontrada — Mia';

ob_start();
?>
<section class="py-5 text-center">
    <div class="container py-5">
        <h1 class="display-1 fw-bold text-muted">404</h1>
        <p class="lead">Lo sentimos, esta página no existe.</p>
        <a href="<?= App::basePath() ?>/" class="btn btn-success mt-3">
            <i class="bi bi-house me-2"></i>Volver al inicio
        </a>
    </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
