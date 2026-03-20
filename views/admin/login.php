<?php
/**
 * mia/views/admin/login.php
 */

$base = App::basePath();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Mia</title>
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap-icons.min.css') ?>">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="card shadow-sm border-0" style="width:100%;max-width:400px">
        <div class="card-body p-4">
            <h4 class="text-center fw-bold mb-1"><i class="bi bi-whatsapp text-success me-2"></i>Mia Admin</h4>
            <p class="text-center text-muted small mb-4">Panel de gestión de leads</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger small py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= $base ?>/admin/login">
                <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">
                <div class="mb-3">
                    <label class="form-label small">Usuario</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                </button>
            </form>
        </div>
    </div>
<script src="<?= App::asset('js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
