<?php
/**
 * mia/controllers/AuthController.php
 *
 * Registration, login, and logout for Mia business clients.
 */

declare(strict_types=1);

class AuthController
{
    // ── Login ─────────────────────────────────────────────────────────────────

    public function loginForm(): void
    {
        if (!empty($_SESSION['mia_client_id'])) {
            header('Location: ' . App::basePath() . '/dashboard');
            exit;
        }
        $error = '';
        require __DIR__ . '/../views/auth/login.php';
    }

    public function loginSubmit(): void
    {
        App::csrfVerify();

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $error    = '';

        if (!$email || !$password) {
            $error = 'Por favor ingresa tu email y contraseña.';
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        $service = new ClientService();
        $client  = $service->authenticate($email, $password);

        if (!$client) {
            $error = 'Email o contraseña incorrectos.';
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        session_regenerate_id(true);
        $_SESSION['mia_client_id'] = $client->id;
        $_SESSION['mia_client']    = (new BillingService())->clientToSession($client);

        // Remember Me — write a hashed token in DB + send a 30-day cookie
        if (!empty($_POST['remember_me'])) {
            $rmToken   = bin2hex(random_bytes(32));
            $rmHash    = hash('sha256', $rmToken);
            $rmTtl     = App::CLIENT_REMEMBER_TTL;
            Database::get()->prepare(
                'INSERT INTO mia_remember_tokens (client_id, token_hash, expires_at) VALUES (?,?,?)'
            )->execute([$client->id, $rmHash, date('Y-m-d H:i:s', time() + $rmTtl)]);
            setcookie('mia_remember', $rmToken, [
                'expires'  => time() + $rmTtl,
                'path'     => App::basePath() ?: '/',
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        // First-time users go to the setup wizard; returning users go to dashboard
        $dest = $client->onboarding_done === 0
            ? App::basePath() . '/dashboard/settings?onboarding=1'
            : App::basePath() . '/dashboard';
        header('Location: ' . $dest);
        exit;
    }

    // ── Register ──────────────────────────────────────────────────────────────

    public function registerForm(): void
    {
        if (!empty($_SESSION['mia_client_id'])) {
            header('Location: ' . App::basePath() . '/dashboard');
            exit;
        }
        $error = '';
        $old   = [];
        require __DIR__ . '/../views/auth/register.php';
    }

    public function registerSubmit(): void
    {
        App::csrfVerify();

        $old = [
            'business_name' => trim($_POST['business_name'] ?? ''),
            'contact_name'  => trim($_POST['contact_name']  ?? ''),
            'email'         => trim($_POST['email']         ?? ''),
            'phone'         => trim($_POST['phone']         ?? ''),
            'business_type' => $_POST['business_type']      ?? 'other',
        ];
        $password  = $_POST['password']  ?? '';
        $password2 = $_POST['password2'] ?? '';
        $error     = '';

        // Validation
        if (!$old['business_name'] || !$old['contact_name'] || !$old['email'] || !$password) {
            $error = 'Todos los campos con * son obligatorios.';
        } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Email inválido.';
        } elseif (strlen($password) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($password !== $password2) {
            $error = 'Las contraseñas no coinciden.';
        }

        if ($error) {
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        try {
            $service = new ClientService();
            $client  = $service->register(array_merge($old, ['password' => $password]));

            session_regenerate_id(true);
            $_SESSION['mia_client_id'] = $client->id;
            $_SESSION['mia_client']    = (new BillingService())->clientToSession($client);

            header('Location: ' . App::basePath() . '/dashboard/settings?onboarding=1');
            exit;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
            require __DIR__ . '/../views/auth/register.php';
        }
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): void
    {
        // Revoke persistent remember-me token if one exists
        if (!empty($_COOKIE['mia_remember'])) {
            $rmHash = hash('sha256', $_COOKIE['mia_remember']);
            Database::get()->prepare('DELETE FROM mia_remember_tokens WHERE token_hash = ?')
                          ->execute([$rmHash]);
            setcookie('mia_remember', '', [
                'expires'  => 1,
                'path'     => App::basePath() ?: '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        unset($_SESSION['mia_client_id'], $_SESSION['mia_client']);
        header('Location: ' . App::basePath() . '/login');
        exit;
    }

    // ── Forgot Password ───────────────────────────────────────────────────────

    public function forgotForm(): void
    {
        if (!empty($_SESSION['mia_client_id'])) {
            header('Location: ' . App::basePath() . '/dashboard');
            exit;
        }
        $success = $error = '';
        require __DIR__ . '/../views/auth/forgot_password.php';
    }

    public function forgotSubmit(): void
    {
        App::csrfVerify();

        $email   = trim(strtolower($_POST['email'] ?? ''));
        $success = $error = '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ingresa un email válido.';
            require __DIR__ . '/../views/auth/forgot_password.php';
            return;
        }

        $db   = Database::get();
        $stmt = $db->prepare('SELECT id FROM mia_clients WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $client = $stmt->fetch();

        if ($client) {
            // Invalidate any existing tokens for this email
            $db->prepare('DELETE FROM mia_password_resets WHERE email = ?')->execute([$email]);

            $token     = bin2hex(random_bytes(32)); // 64-char hex token
            $tokenHash = hash('sha256', $token);
            $expires   = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            $db->prepare(
                'INSERT INTO mia_password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)'
            )->execute([$email, $tokenHash, $expires]);

            $resetUrl = App::URL . App::basePath() . '/reset-password?token=' . urlencode($token);
            $this->sendResetEmail($email, $resetUrl);
        }

        // Always show same message — don't reveal if email exists (security)
        $success = 'Si ese email está registrado, recibirás el enlace en unos minutos.';
        require __DIR__ . '/../views/auth/forgot_password.php';
    }

    // ── Reset Password ────────────────────────────────────────────────────────

    public function resetForm(): void
    {
        $token = $_GET['token'] ?? '';
        $error = '';

        if (!$token) {
            header('Location: ' . App::basePath() . '/forgot-password');
            exit;
        }

        require __DIR__ . '/../views/auth/reset_password.php';
    }

    public function resetSubmit(): void
    {
        App::csrfVerify();

        $token     = $_POST['token']     ?? '';
        $password  = $_POST['password']  ?? '';
        $password2 = $_POST['password2'] ?? '';
        $error     = '';

        if (!$token) {
            header('Location: ' . App::basePath() . '/forgot-password');
            exit;
        }

        if (strlen($password) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
            require __DIR__ . '/../views/auth/reset_password.php';
            return;
        }

        if ($password !== $password2) {
            $error = 'Las contraseñas no coinciden.';
            require __DIR__ . '/../views/auth/reset_password.php';
            return;
        }

        $db        = Database::get();
        $tokenHash = hash('sha256', $token);
        $stmt      = $db->prepare(
            'SELECT * FROM mia_password_resets
             WHERE token_hash = ? AND used = 0 AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();

        if (!$row) {
            $error = 'Este enlace ha expirado o ya fue usado. Solicita uno nuevo.';
            require __DIR__ . '/../views/auth/reset_password.php';
            return;
        }

        // Update password and mark token used atomically
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare('UPDATE mia_clients SET password_hash = ?, updated_at = NOW() WHERE email = ?')
           ->execute([$hash, $row['email']]);
        $db->prepare('UPDATE mia_password_resets SET used = 1 WHERE id = ?')
           ->execute([$row['id']]);

        // Redirect to login with success flag
        header('Location: ' . App::basePath() . '/login?reset=1');
        exit;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function sendResetEmail(string $to, string $resetUrl): void
    {
        $subject = 'Restablece tu contraseña — Mia';
        $time    = date('d/m/Y H:i');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f0f4f8;margin:0;padding:20px">
<div style="background:#fff;border-radius:12px;max-width:480px;margin:0 auto;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)">
  <div style="background:#1a1a2e;color:#fff;padding:28px 24px;text-align:center">
    <div style="font-size:2.4rem">&#x1F510;</div>
    <h2 style="margin:8px 0 4px;color:#25d366;font-size:20px">Restablecer contrase&ntilde;a</h2>
    <p style="margin:0;color:#ccc;font-size:13px">{$time}</p>
  </div>
  <div style="padding:28px 24px">
    <p style="color:#495057;line-height:1.6">Recibimos una solicitud para restablecer la contrase&ntilde;a de tu cuenta Mia.</p>
    <div style="text-align:center;margin:24px 0">
      <a href="{$resetUrl}" style="display:inline-block;background:#25d366;color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px">Restablecer contrase&ntilde;a &rarr;</a>
    </div>
    <p style="color:#6c757d;font-size:13px">Este enlace expira en <strong>30 minutos</strong>.<br>Si no solicitaste este cambio, ignora este correo.</p>
  </div>
  <div style="background:#f8f9fa;padding:14px 24px;text-align:center;font-size:12px;color:#adb5bd">Mia by AiniTravel &middot; noreply@ainitravel.com</div>
</div></body></html>
HTML;

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Mia <noreply@ainitravel.com>\r\n";
        @mail($to, $subject, $html, $headers);
        error_log("[Mia] Password reset email sent to {$to}");
    }
}
