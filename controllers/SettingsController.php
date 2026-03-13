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

    public function waQr(): void
    {
        header('Content-Type: application/json');
        $client = $this->requireClient();

        $ctx      = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
        $response = @file_get_contents('http://127.0.0.1:3001/qr/' . $client->id, false, $ctx);

        if ($response === false) {
            echo json_encode(['status' => 'unavailable']);
            return;
        }
        echo $response; // bot server returns {status, qr_image} JSON
    }

    public function waConnect(): void
    {
        header('Content-Type: application/json');
        $client = $this->requireClient();

        $ctx = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\nContent-Length: 2",
            'content'       => '{}',
            'timeout'       => 10,
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents('http://127.0.0.1:3001/connect/' . $client->id, false, $ctx);

        if ($response === false) {
            http_response_code(503);
            echo json_encode(['success' => false, 'error' => 'Bot server unavailable']);
            return;
        }
        echo $response;
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

        // Validate char_skills against allowed values
        $allowedSkills = ['humor','empathy','stories','direct','scarcity','patient','usted','tips','premium','proactive'];
        $rawSkills = $_POST['char_skills'] ?? [];
        $charSkills = array_values(array_intersect((array)$rawSkills, $allowedSkills));

        (new ClientService())->updateSettings($client->id, [
            'contact_name'        => $_POST['contact_name']        ?? '',
            'phone'               => $_POST['phone']               ?? '',
            'business_type'       => $_POST['business_type']       ?? 'other',
            'bot_description'     => $_POST['bot_description']     ?? '',
            'bot_services'        => $_POST['bot_services']        ?? '',
            'bot_pricing'         => $_POST['bot_pricing']         ?? '',
            'bot_hours'           => $_POST['bot_hours']           ?? '',
            'bot_faqs'            => $_POST['bot_faqs']            ?? '',
            'bot_language'        => $_POST['bot_language']        ?? 'es',
            'bot_tone'            => $_POST['bot_tone']            ?? 'friendly',
            'char_skills'         => $charSkills,
            'notify_email'        => $_POST['notify_email']        ?? '',
            'notify_on_capture'   => $_POST['notify_on_capture']   ?? 0,
            'notify_daily_summary'=> $_POST['notify_daily_summary']?? 0,
        ]);

        header('Location: ' . App::basePath() . '/dashboard/settings?saved=1');
        exit;
    }
}
