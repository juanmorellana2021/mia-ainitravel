<?php
/**
 * mia/views/auth/reset_password.php — Set new password via reset token
 */
$base = App::basePath();
$token = htmlspecialchars($_GET['token'] ?? $_POST['token'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña — Mia</title>
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
        .password-strength { height: 4px; border-radius: 2px; transition: width .3s, background .3s; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="auth-brand">
        <div class="brand-icon"><i class="bi bi-shield-lock"></i></div>
        <h4 class="fw-bold mb-1">Nueva contraseña</h4>
        <p class="mb-0 opacity-75 small">Ingresa tu nueva contraseña</p>
    </div>
    <div class="auth-body">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small py-2 mb-3">
                <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
                <?php if (str_contains($error, 'expirado') || str_contains($error, 'usado')): ?>
                    <div class="mt-2">
                        <a href="<?= $base ?>/forgot-password" class="alert-link">Solicitar nuevo enlace →</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $base ?>/reset-password">
            <input type="hidden" name="_csrf"  value="<?= htmlspecialchars(App::csrfToken()) ?>">
            <input type="hidden" name="token"  value="<?= $token ?>">

            <div class="mb-3">
                <label class="form-label fw-medium small">Nueva contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control"
                           placeholder="Mínimo 8 caracteres" required autofocus minlength="8">
                </div>
                <div class="mt-1 bg-light rounded" style="height:4px">
                    <div id="strengthBar" class="password-strength bg-danger" style="width:0%"></div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-medium small">Confirmar contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="password2" id="password2" class="form-control"
                           placeholder="Repite la contraseña" required>
                </div>
                <div id="matchHint" class="form-text mt-1"></div>
            </div>

            <button type="submit" class="btn btn-mia w-100">
                <i class="bi bi-check-circle me-2"></i>Guardar nueva contraseña
            </button>
        </form>

        <hr class="my-3">
        <p class="text-center text-muted small mb-0">
            <a href="<?= $base ?>/login" class="text-decoration-none fw-medium" style="color:#25d366">
                <i class="bi bi-arrow-left me-1"></i>Volver al login
            </a>
        </p>
    </div>
</div>
<script src="<?= App::asset('js/bootstrap.bundle.min.js') ?>"></script>
<script>
const pw  = document.getElementById('password');
const pw2 = document.getElementById('password2');
const bar = document.getElementById('strengthBar');
const hint = document.getElementById('matchHint');

pw.addEventListener('input', () => {
    const len = pw.value.length;
    let pct = 0, color = '#dc3545';
    if (len >= 8)  { pct = 33; color = '#ffc107'; }
    if (len >= 12) { pct = 66; color = '#fd7e14'; }
    if (len >= 16) { pct = 100; color = '#25d366'; }
    if (/[0-9]/.test(pw.value) && /[^a-zA-Z0-9]/.test(pw.value)) pct = Math.min(pct + 15, 100);
    bar.style.width = pct + '%';
    bar.style.background = color;
});

pw2.addEventListener('input', () => {
    if (!pw2.value) { hint.textContent = ''; return; }
    if (pw.value === pw2.value) {
        hint.innerHTML = '<span class="text-success"><i class="bi bi-check2"></i> Las contraseñas coinciden</span>';
    } else {
        hint.innerHTML = '<span class="text-danger"><i class="bi bi-x"></i> No coinciden</span>';
    }
});
</script>
</body>
</html>
