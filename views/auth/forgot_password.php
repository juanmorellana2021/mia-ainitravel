<?php
/**
 * mia/views/auth/forgot_password.php — Request password reset link
 */
$base = App::basePath();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña — Mia</title>
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap-icons.min.css') ?>">
    <style>
        body { background: #f0f4f8; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .auth-card { width: 100%; max-width: 420px; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.10); }
        .auth-brand { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: #fff; border-radius: 16px 16px 0 0; padding: 28px 32px 24px; text-align: center; }
        .auth-brand .brand-icon { width: 56px; height: 56px; background: rgba(37,211,102,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 1.6rem; color: #25d366; }
        .auth-body { padding: 28px 32px 32px; background: #fff; border-radius: 0 0 16px 16px; }
        .form-control:focus { border-color: #25d366; box-shadow: 0 0 0 0.2rem rgba(37,211,102,0.2); }
        .btn-mia { background: #25d366; border: none; color: #fff; font-weight: 600; padding: 10px; }
        .btn-mia:hover { background: #1da851; color: #fff; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="auth-brand">
        <div class="brand-icon"><i class="bi bi-key"></i></div>
        <h4 class="fw-bold mb-1">Recuperar contraseña</h4>
        <p class="mb-0 opacity-75 small">Te enviaremos un enlace por email</p>
    </div>
    <div class="auth-body">

        <?php if (!empty($success)): ?>
            <div class="alert alert-success small py-2 mb-3">
                <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small py-2 mb-3">
                <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($success)): ?>
        <p class="text-muted small mb-3">Ingresa el email de tu cuenta y te enviaremos un enlace para crear una nueva contraseña.</p>

        <form method="POST" action="<?= $base ?>/forgot-password">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App::csrfToken()) ?>">

            <div class="mb-4">
                <label class="form-label fw-medium small">Email de tu cuenta</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="tu@empresa.com" required autofocus>
                </div>
            </div>

            <button type="submit" class="btn btn-mia w-100">
                <i class="bi bi-send me-2"></i>Enviar enlace de recuperación
            </button>
        </form>
        <?php endif; ?>

        <hr class="my-3">
        <p class="text-center text-muted small mb-0">
            <a href="<?= $base ?>/login" class="text-decoration-none fw-medium" style="color:#25d366">
                <i class="bi bi-arrow-left me-1"></i>Volver al login
            </a>
        </p>
    </div>
</div>
<script src="<?= App::asset('js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
