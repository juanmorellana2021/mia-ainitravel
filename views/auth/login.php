<?php
/**
 * mia/views/auth/login.php — Client login page
 */
$base = App::basePath();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — Mia</title>
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
        <div class="brand-icon"><i class="bi bi-whatsapp"></i></div>
        <h4 class="fw-bold mb-1">Mia by AiniTravel</h4>
        <p class="mb-0 opacity-75 small">Panel de gestión de leads</p>
    </div>
    <div class="auth-body">

        <?php if (!empty($_GET['reset'])): ?>
            <div class="alert alert-success small py-2 mb-3">
                <i class="bi bi-check-circle me-1"></i>Contraseña actualizada. Ya puedes iniciar sesión.
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small py-2 mb-3">
                <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $base ?>/login">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App::csrfToken()) ?>">

            <div class="mb-3">
                <label class="form-label fw-medium small">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="tu@empresa.com" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium small">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="remember_me" id="remember_me" value="1">
                    <label class="form-check-label small text-muted" for="remember_me">
                        Mantenerme conectado por 30 días
                    </label>
                </div>
                <a href="<?= $base ?>/forgot-password" class="small text-decoration-none" style="color:#6c757d">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>

            <button type="submit" class="btn btn-mia w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Entrar al Panel
            </button>
        </form>

        <hr class="my-3">
        <div class="d-flex justify-content-center align-items-center">
            <p class="text-muted small mb-0">
                ¿No tienes cuenta?
                <a href="<?= $base ?>/register" class="text-decoration-none fw-medium" style="color:#25d366">
                    Prueba gratis 7 días
                </a>
            </p>
        </div>
    </div>
</div>
<script src="<?= App::asset('js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
