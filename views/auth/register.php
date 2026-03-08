<?php
/**
 * mia/views/auth/register.php — Client registration page
 */
$base = App::basePath();
$old  = $old ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta — Mia</title>
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap-icons.min.css') ?>">
    <style>
        body { background: #f0f4f8; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 0; }
        .auth-card { width: 100%; max-width: 480px; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.10); }
        .auth-brand { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: #fff; border-radius: 16px 16px 0 0; padding: 24px 32px 20px; text-align: center; }
        .auth-body { padding: 28px 32px 32px; background: #fff; border-radius: 0 0 16px 16px; }
        .form-control:focus, .form-select:focus { border-color: #25d366; box-shadow: 0 0 0 0.2rem rgba(37,211,102,0.2); }
        .btn-mia { background: #25d366; border: none; color: #fff; font-weight: 600; padding: 10px; }
        .btn-mia:hover { background: #1da851; color: #fff; }
        .trial-badge { background: rgba(37,211,102,0.12); border: 1px solid rgba(37,211,102,0.3); border-radius: 8px; padding: 10px 14px; font-size: 0.83rem; color: #155724; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="auth-brand">
        <h5 class="fw-bold mb-1"><i class="bi bi-whatsapp text-success me-2"></i>Mia by AiniTravel</h5>
        <p class="mb-0 opacity-75 small">Crea tu cuenta — <?= App::FREE_TRIAL_DAYS ?> días gratis sin tarjeta</p>
    </div>
    <div class="auth-body">

        <div class="trial-badge mb-4">
            <i class="bi bi-gift me-2"></i><strong><?= App::FREE_TRIAL_DAYS ?> días de prueba gratuita.</strong>
            Sin tarjeta de crédito. Cancela cuando quieras.
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small py-2 mb-3">
                <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $base ?>/register">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App::csrfToken()) ?>">

            <div class="row g-3 mb-3">
                <div class="col-12">
                    <label class="form-label fw-medium small">Nombre de tu negocio *</label>
                    <input type="text" name="business_name" class="form-control"
                           value="<?= htmlspecialchars($old['business_name'] ?? '') ?>"
                           placeholder="Hotel El Sol, Agencia Aventura..." required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small">Tu nombre *</label>
                    <input type="text" name="contact_name" class="form-control"
                           value="<?= htmlspecialchars($old['contact_name'] ?? '') ?>"
                           placeholder="Juan García" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small">Tipo de negocio *</label>
                    <select name="business_type" class="form-select" required>
                        <option value="hotel"    <?= ($old['business_type'] ?? '') === 'hotel'    ? 'selected' : '' ?>>Hotel / Hostal</option>
                        <option value="agency"   <?= ($old['business_type'] ?? '') === 'agency'   ? 'selected' : '' ?>>Agencia de Viajes</option>
                        <option value="operator" <?= ($old['business_type'] ?? '') === 'operator' ? 'selected' : '' ?>>Operador Turístico</option>
                        <option value="other"    <?= ($old['business_type'] ?? 'other') === 'other' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label fw-medium small">Email *</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                           placeholder="tu@empresa.com" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-medium small">WhatsApp / Teléfono</label>
                    <input type="tel" name="phone" class="form-control"
                           value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                           placeholder="+51 999 999 999">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small">Contraseña *</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Mínimo 8 caracteres" required minlength="8">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small">Confirmar contraseña *</label>
                    <input type="password" name="password2" class="form-control"
                           placeholder="Repite la contraseña" required>
                </div>
            </div>

            <button type="submit" class="btn btn-mia w-100 mb-3">
                <i class="bi bi-rocket me-2"></i>Crear cuenta gratis
            </button>

            <p class="text-muted small text-center mb-0">
                Al registrarte aceptas nuestros términos de servicio.
            </p>
        </form>

        <hr class="my-3">
        <p class="text-center text-muted small mb-0">
            ¿Ya tienes cuenta?
            <a href="<?= $base ?>/login" class="text-decoration-none fw-medium" style="color:#25d366">Iniciar sesión</a>
        </p>
    </div>
</div>
<script src="<?= App::asset('js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
