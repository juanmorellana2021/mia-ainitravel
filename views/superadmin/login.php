<?php
/**
 * mia/views/superadmin/login.php
 */
$base = App::basePath();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin — Mia</title>
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap-icons.min.css') ?>">
    <style>
        body { background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { background: #1e293b; border-radius: 16px; padding: 40px 36px; width: 100%; max-width: 380px; border: 1px solid rgba(255,255,255,0.07); }
        .login-box h1 { font-size: 1.3rem; font-weight: 700; color: #f1f5f9; margin-bottom: 4px; }
        .login-box .sub { font-size: 0.82rem; color: #64748b; margin-bottom: 28px; }
        .form-label { color: #94a3b8; font-size: 0.82rem; margin-bottom: 4px; }
        .form-control { background: #0f172a; border: 1px solid #334155; color: #f1f5f9; border-radius: 8px; }
        .form-control:focus { background: #0f172a; border-color: #6366f1; color: #f1f5f9; box-shadow: 0 0 0 3px rgba(99,102,241,0.2); }
        .btn-sa { background: #6366f1; border: none; color: #fff; width: 100%; padding: 11px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; }
        .btn-sa:hover { background: #4f46e5; color: #fff; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-whatsapp" style="color:#6366f1;font-size:1.5rem;"></i>
        <span style="color:#f1f5f9;font-weight:700;font-size:1.1rem;">Mia</span>
        <span style="font-size:0.65rem;background:#6366f1;color:#fff;border-radius:4px;padding:2px 6px;font-weight:700;text-transform:uppercase;">Super</span>
    </div>
    <h1>Panel de administración</h1>
    <p class="sub">Solo para uso interno</p>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:0.85rem;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;border-radius:8px;">
        <i class="bi bi-exclamation-triangle me-1"></i> Credenciales incorrectas
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= $base ?>/superadmin/login">
        <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">
        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-sa">Entrar</button>
    </form>
</div>
</body>
</html>
