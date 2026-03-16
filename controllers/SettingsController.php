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

    /**
     * GET /dashboard/settings/wa-link
     * Returns JSON with the wa.me deep link and a Google Charts QR image URL.
     */
    public function waLink(): void
    {
        header('Content-Type: application/json');
        $client = $this->requireClient();

        if (!$client->whatsapp_number) {
            http_response_code(400);
            echo json_encode(['error' => 'WhatsApp no conectado']);
            return;
        }

        $num  = ltrim($client->whatsapp_number, '+');
        // Only digits allowed after stripping the +
        $num  = preg_replace('/[^0-9]/', '', $num);
        $text = urlencode('Hola, me interesa saber más sobre ' . $client->business_name);
        $link = "https://wa.me/{$num}?text={$text}";
        // Google Charts QR — safe external URL for generating QR codes
        $qrUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=250x250&chld=M|1&chl=' . urlencode($link);

        echo json_encode(['link' => $link, 'qr_url' => $qrUrl]);
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
        $client     = $this->requireClient();
        $saved      = isset($_GET['saved']);
        $onboarding = isset($_GET['onboarding']) && $client->onboarding_done === 0;
        require __DIR__ . '/../views/client/settings.php';
    }

    public function finishOnboarding(): void
    {
        App::csrfVerify();
        $client = $this->requireClient();
        (new ClientService())->markOnboardingDone($client->id);
        $_SESSION['mia_client']['onboarding_done'] = 1;
        header('Location: ' . App::basePath() . '/dashboard');
        exit;
    }

    public function save(): void
    {
        App::csrfVerify();
        $client = $this->requireClient();

        // Validate char_skills against allowed values
        $allowedSkills = ['humor','empathy','stories','direct','scarcity','patient','usted','tips','premium','proactive'];
        $rawSkills = $_POST['char_skills'] ?? [];
        $charSkills = array_values(array_intersect((array)$rawSkills, $allowedSkills));

        // Build hours_config from posted day schedule
        $allowedDays  = ['mon','tue','wed','thu','fri','sat','sun'];
        $allowedTz    = timezone_identifiers_list();
        $postedTz     = $_POST['hours_timezone'] ?? 'America/Lima';
        $hoursTimezone = in_array($postedTz, $allowedTz) ? $postedTz : 'America/Lima';

        $schedule = [];
        foreach ($allowedDays as $d) {
            $schedule[$d] = [
                'enabled' => !empty($_POST["hours_{$d}_enabled"]),
                'open'    => preg_replace('/[^0-9:]/', '', $_POST["hours_{$d}_open"]  ?? ''),
                'close'   => preg_replace('/[^0-9:]/', '', $_POST["hours_{$d}_close"] ?? ''),
            ];
        }

        $hoursConfig = [
            'timezone'       => $hoursTimezone,
            'schedule'       => $schedule,
            'closed_message' => substr(trim($_POST['hours_closed_message'] ?? ''), 0, 400),
        ];

        (new ClientService())->updateSettings($client->id, [
            'contact_name'        => $_POST['contact_name']        ?? '',
            'phone'               => $_POST['phone']               ?? '',
            'business_type'       => $_POST['business_type']       ?? 'other',
            'bot_custom_type'     => $_POST['bot_custom_type']     ?? '',
            'bot_description'     => $_POST['bot_description']     ?? '',
            'bot_services'        => $_POST['bot_services']        ?? '',
            'bot_pricing'         => $_POST['bot_pricing']         ?? '',
            'bot_hours'           => $_POST['bot_hours']           ?? '',
            'bot_faqs'            => $_POST['bot_faqs']            ?? '',
            'bot_website'         => $_POST['bot_website']         ?? '',
            'bot_location'        => $_POST['bot_location']        ?? '',
            'bot_language'        => $_POST['bot_language']        ?? 'es',
            'bot_tone'            => $_POST['bot_tone']            ?? 'friendly',
            'char_skills'         => $charSkills,
            'notify_email'        => $_POST['notify_email']        ?? '',
            'notify_on_capture'   => $_POST['notify_on_capture']   ?? 0,
            'notify_daily_summary'=> $_POST['notify_daily_summary']?? 0,
            'hours_enabled'       => $_POST['hours_enabled']       ?? 0,
            'hours_config'        => $hoursConfig,
        ]);

        header('Location: ' . App::basePath() . '/dashboard/settings?saved=1');
        exit;
    }

    /**
     * POST /dashboard/settings/search-business
     * Body: { query: "Hotel El Sol, Cusco", type: "hotel" }
     * Returns JSON with description/services/pricing/hours/faqs/website/location fields.
     */
    public function searchBusiness(): void
    {
        header('Content-Type: application/json');
        $this->requireClient();

        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $query = trim($body['query'] ?? '');
        $type  = trim($body['type']  ?? 'other');

        if (strlen($query) < 3) {
            http_response_code(400);
            echo json_encode(['error' => 'Escribe al menos el nombre del negocio.']);
            return;
        }

        $apiKey  = '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';
        $payload = json_encode([
            'model'       => 'llama-3.3-70b-versatile',
            'temperature' => 0.4,
            'max_tokens'  => 700,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' =>
                        'Eres un asistente que genera perfiles de negocio para configurar un chatbot de WhatsApp. ' .
                        'Cuando el usuario te dé el nombre (y opcionalmente ciudad/rubro) de un negocio, responde ÚNICAMENTE con un objeto JSON válido que tenga exactamente estas claves: ' .
                        '"description", "services", "pricing", "hours", "faqs", "website", "location". ' .
                        'Cada valor es un string. Usa saltos de línea \n dentro de los strings para listas. ' .
                        'Haz los textos realistas, concretos y editables — el usuario los ajustará con sus datos reales. ' .
                        'Si deduces el país/ciudad del nombre, úsalo; si no, pon [Ciudad, País]. ' .
                        'Para "website" y "location" pon string vacío si no puedes deducirlos. ' .
                        'NUNCA incluyas explicación fuera del JSON. Solo el JSON.'
                ],
                [
                    'role'    => 'user',
                    'content' => "Genera el perfil para: \"{$query}\" (tipo de negocio: {$type})"
                ]
            ]
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
        ]);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            http_response_code(503);
            echo json_encode(['error' => 'Error al conectar con la IA. Intenta de nuevo.']);
            return;
        }

        $data = json_decode($response, true);
        $text = trim($data['choices'][0]['message']['content'] ?? '');

        // Extract JSON from response (strip any markdown fences the AI might add)
        if (preg_match('/```json\s*([\s\S]+?)```/i', $text, $m)) $text = trim($m[1]);
        elseif (preg_match('/```([\s\S]+?)```/i', $text, $m))      $text = trim($m[1]);

        $fields = json_decode($text, true);
        if (!is_array($fields)) {
            http_response_code(422);
            echo json_encode(['error' => 'No se pudo generar el perfil. Intenta con un nombre más específico.']);
            return;
        }

        // Whitelist keys — never expose anything unexpected
        $safe = [];
        foreach (['description','services','pricing','hours','faqs','website','location'] as $k) {
            $safe[$k] = isset($fields[$k]) ? (string)$fields[$k] : '';
        }
        echo json_encode(['ok' => true, 'fields' => $safe]);
    }
}
