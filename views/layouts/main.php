<?php
/**
 * mia/views/layouts/main.php
 *
 * Master layout template — Bootstrap 5 (local files, zero CDN).
 * Pages set $pageTitle and $pageContent before including this.
 */

$base = App::basePath();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Mia by AiniTravel') ?></title>
    <meta name="description" content="<?= htmlspecialchars(App::TAGLINE) ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/mia.css') ?>">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $base ?>/">
            <i class="bi bi-whatsapp text-success me-2"></i>Mia
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>/">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>/features">Funciones</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>/pricing">Precios</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>/demo">Demo</a></li>
                <?php if (!empty($_SESSION['mia_client_id'])): ?>
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm ms-lg-2 mt-2 mt-lg-0" href="<?= $base ?>/dashboard">
                        <i class="bi bi-grid me-1"></i>Mi Panel
                    </a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm ms-lg-2 mt-2 mt-lg-0" href="<?= $base ?>/login">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Ingresar
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="btn btn-success btn-sm ms-2 mt-2 mt-lg-0" href="<?= $base ?>/register">
                        <i class="bi bi-rocket me-1"></i>Prueba Gratis
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page content -->
<main>
<?php if (isset($pageContent)) echo $pageContent; ?>
</main>

<!-- Footer -->
<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="fw-bold"><i class="bi bi-whatsapp text-success me-2"></i>Mia by AiniTravel</h5>
                <p class="text-secondary"><?= htmlspecialchars(App::TAGLINE) ?></p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Enlaces</h6>
                <ul class="list-unstyled">
                    <li><a href="<?= $base ?>/features" class="text-secondary text-decoration-none">Funciones</a></li>
                    <li><a href="<?= $base ?>/pricing" class="text-secondary text-decoration-none">Precios</a></li>
                    <li><a href="<?= $base ?>/demo" class="text-secondary text-decoration-none">Demo en Vivo</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Contacto</h6>
                <p class="text-secondary mb-1"><i class="bi bi-whatsapp me-2"></i><?= App::WHATSAPP ?></p>
                <p class="text-secondary mb-1"><i class="bi bi-envelope me-2"></i>hola@ainitravel.com</p>
                <p class="text-secondary"><i class="bi bi-globe me-2"></i>mia.ainitravel.com</p>
            </div>
        </div>
        <hr class="border-secondary">
        <p class="text-center text-secondary mb-0">&copy; <?= date('Y') ?> AiniTravel. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="<?= App::asset('js/bootstrap.bundle.min.js') ?>"></script>
<?php if (!empty($pageScripts)) echo $pageScripts; ?>
</body>
</html>
