<?php
/**
 * mia/controllers/BroadcastController.php
 *
 * Send a WhatsApp message to all captured leads.
 * Uses POST → Redirect → GET pattern to prevent double-submit.
 */

declare(strict_types=1);

class BroadcastController
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
        $client  = $this->requireClient();
        $service = new BroadcastService();

        $leads   = $service->leadsWithPhone($client->id);
        $history = $service->history($client->id);

        require __DIR__ . '/../views/client/broadcast.php';
    }

    public function send(): void
    {
        App::csrfVerify();
        $client  = $this->requireClient();
        $message = trim($_POST['message'] ?? '');

        if ($message === '') {
            header('Location: ' . App::basePath() . '/dashboard/broadcast?error=empty');
            exit;
        }

        $result = (new BroadcastService())->send($client->id, $message);

        header(
            'Location: ' . App::basePath()
            . '/dashboard/broadcast?sent=' . $result['sent']
            . '&failed=' . $result['failed']
            . '&total=' . $result['total']
        );
        exit;
    }
}
