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

        header('Location: ' . App::basePath() . '/dashboard');
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

            header('Location: ' . App::basePath() . '/dashboard?welcome=1');
            exit;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
            require __DIR__ . '/../views/auth/register.php';
        }
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): void
    {
        unset($_SESSION['mia_client_id'], $_SESSION['mia_client']);
        header('Location: ' . App::basePath() . '/login');
        exit;
    }
}
