<?php
/**
 * mia/controllers/SettingsController.php
 *
 * Client account settings: profile, notification preferences, bot info.
 */

declare(strict_types=1);

class SettingsController
{
    private function requireClient(): Client
    {
        if (empty($_SESSION['mia_client_id'])) {
            header('Location: ' . App::basePath() . '/login');
            exit;
        }
        $client = (new ClientService())->findById((int)$_SESSION['mia_client_id']);
        if (!$client) {
            session_destroy();
            header('Location: ' . App::basePath() . '/login');
            exit;
        }
        $_SESSION['mia_client'] = (new BillingService())->clientToSession($client);
        return $client;
    }

    public function index(): void
    {
        $client = $this->requireClient();
        $saved  = isset($_GET['saved']);
        require __DIR__ . '/../views/client/settings.php';
    }

    public function save(): void
    {
        App::csrfVerify();
        $client = $this->requireClient();

        (new ClientService())->updateSettings($client->id, [
            'contact_name'        => $_POST['contact_name']        ?? '',
            'phone'               => $_POST['phone']               ?? '',
            'notify_email'        => $_POST['notify_email']        ?? '',
            'notify_on_capture'   => $_POST['notify_on_capture']   ?? 0,
            'notify_daily_summary'=> $_POST['notify_daily_summary']?? 0,
        ]);

        header('Location: ' . App::basePath() . '/dashboard/settings?saved=1');
        exit;
    }
}
