<?php
/**
 * mia/controllers/DashboardController.php
 *
 * Client-facing dashboard: overview, leads, messages.
 */

declare(strict_types=1);

class DashboardController
{
    // ── Auth guard ────────────────────────────────────────────────────────────

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
        // Keep session fresh
        $_SESSION['mia_client'] = (new BillingService())->clientToSession($client);
        return $client;
    }

    // ── Dashboard overview ────────────────────────────────────────────────────

    public function index(): void
    {
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();

        $stats            = $leadService->stats($client->id);
        $recentLeads      = $leadService->recent($client->id, 8);
        $todayMessages    = $leadService->todayMessages($client->id);
        $monthConvos      = $leadService->monthlyConversations($client->id);
        $convLimit        = ClientBotService::CONV_LIMITS[$client->plan] ?? 0;
        $welcome          = !empty($_GET['welcome']);
        $qrSetup          = !empty($_GET['qr_setup']);

        require __DIR__ . '/../views/client/dashboard.php';
    }

    // ── Leads list ────────────────────────────────────────────────────────────

    public function leads(): void
    {
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();

        $filter = $_GET['status'] ?? '';
        $leads  = $leadService->allForClient($client->id, $filter);
        $stats  = $leadService->stats($client->id);

        require __DIR__ . '/../views/client/leads.php';
    }

    // ── Lead detail ───────────────────────────────────────────────────────────

    public function leadDetail(int $id): void
    {
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();

        $lead = $leadService->findById($id, $client->id);
        if (!$lead) {
            http_response_code(404);
            require __DIR__ . '/../views/pages/404.php';
            return;
        }

        $messages        = $leadService->messagesForLead($lead->id, $client->id);
        $seqService      = new SequenceService();
        $sequences       = $seqService->all($client->id);
        $leadEnrollments = $seqService->enrollmentsForLead($lead->id, $client->id);

        require __DIR__ . '/../views/client/lead_detail.php';
    }

    // ── Lead add (manual contact) ─────────────────────────────────────────────

    public function leadAdd(): void
    {
        App::csrfVerify();
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();

        // Sanitise phone: keep digits only, must be 7-15 digits
        $rawPhone = trim($_POST['phone'] ?? '');
        $phone    = preg_replace('/\D/', '', $rawPhone);
        if (strlen($phone) < 7 || strlen($phone) > 15) {
            $_SESSION['lead_add_error'] = 'Número de teléfono inválido. Ingresa solo dígitos (7–15), incluyendo código de país.';
            header('Location: ' . App::basePath() . '/dashboard/leads');
            exit;
        }

        $contactName    = trim($_POST['contact_name'] ?? '');
        $initialMessage = trim($_POST['initial_message'] ?? '');

        // If lead already exists for this client redirect to it
        $existing = $leadService->findByPhoneForClient($phone, $client->id);
        if ($existing) {
            header('Location: ' . App::basePath() . '/dashboard/leads/' . $existing->id . '?already=1');
            exit;
        }

        $lead = $leadService->create($client->id, [
            'contact_name'   => $contactName,
            'phone'          => $phone,
            'source'         => 'manual',
            'status'         => 'new',
            'value_estimate' => 0,
            'notes'          => '',
        ]);

        if ($initialMessage !== '') {
            $leadService->saveMessage($client->id, $lead->id, $phone, $initialMessage, 'outbound', 'human');
            $this->sendViaBot($client->id, $phone, $initialMessage);
        }

        header('Location: ' . App::basePath() . '/dashboard/leads/' . $lead->id . '?added=1');
        exit;
    }

    // ── Lead bulk import ─────────────────────────────────────────────────────

    public function leadImport(): void
    {
        App::csrfVerify();
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();

        header('Content-Type: application/json');

        if (empty($_FILES['csv_file']['tmp_name'])) {
            echo json_encode(['error' => 'No se recibió ningún archivo.']);
            exit;
        }

        $tmpPath = $_FILES['csv_file']['tmp_name'];
        $handle  = fopen($tmpPath, 'r');
        if (!$handle) {
            echo json_encode(['error' => 'No se pudo leer el archivo.']);
            exit;
        }

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $lineNum  = 0;

        while (($row = fgetcsv($handle, 200, ',')) !== false) {
            $lineNum++;

            // Skip empty rows
            if (empty(array_filter($row))) continue;

            $rawPhone = trim($row[0] ?? '');
            $name     = trim($row[1] ?? '');

            // Skip header row if first cell looks like text
            if ($lineNum === 1 && !is_numeric(preg_replace('/\D/', '', $rawPhone))) continue;

            $phone = preg_replace('/\D/', '', $rawPhone);

            if (strlen($phone) < 7 || strlen($phone) > 15) {
                $errors[] = "Línea {$lineNum}: teléfono inválido ({$rawPhone})";
                continue;
            }

            $existing = $leadService->findByPhoneForClient($phone, $client->id);
            if ($existing) {
                $skipped++;
                continue;
            }

            $leadService->create($client->id, [
                'contact_name'   => $name,
                'phone'          => $phone,
                'source'         => 'import',
                'status'         => 'new',
                'value_estimate' => 0,
                'notes'          => '',
            ]);
            $imported++;
        }

        fclose($handle);

        echo json_encode([
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => array_slice($errors, 0, 10), // cap at 10 error messages
        ]);
        exit;
    }

    // ── Lead update ───────────────────────────────────────────────────────────

    public function leadUpdate(int $id): void
    {
        App::csrfVerify();
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();

        $lead = $leadService->findById($id, $client->id);
        if (!$lead) {
            http_response_code(404);
            return;
        }

        $leadService->update($id, $client->id, [
            'status'         => $_POST['status']         ?? $lead->status,
            'contact_name'   => $_POST['contact_name']   ?? $lead->contact_name,
            'notes'          => $_POST['notes']          ?? $lead->notes,
            'value_estimate' => $_POST['value_estimate'] ?? $lead->value_estimate,
        ]);

        if (isset($_POST['contact_type'])) {
            $leadService->updateContactType($id, $client->id, $_POST['contact_type']);
        }

        header('Location: ' . App::basePath() . '/dashboard/leads/' . $id . '?saved=1');
        exit;
    }

    // ── Lead chat: AJAX endpoints ─────────────────────────────────────────────

    public function leadMessages(int $id): void
    {
        header('Content-Type: application/json');
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();
        $lead        = $leadService->findById($id, $client->id);
        if (!$lead) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            return;
        }
        $messages = $leadService->messagesForLead($lead->id, $client->id);
        echo json_encode(array_map(fn($m) => [
            'id'         => $m->id,
            'direction'  => $m->direction,
            'message'    => $m->message,
            'handled_by' => $m->handled_by,
            'created_at' => $m->created_at,
        ], $messages));
    }

    public function leadGallery(): void
    {
        header('Content-Type: application/json');
        $client = $this->requireClient();
        $stmt = Database::get()->prepare(
            'SELECT filename, photo_name, description, price
             FROM mia_client_photos
             WHERE client_id = ?
             ORDER BY sort_order ASC, id ASC
             LIMIT 60'
        );
        $stmt->execute([$client->id]);
        $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $base   = rtrim(App::URL, '/') . '/assets/uploads/photos/' . $client->id . '/';
        $photos = array_map(fn($r) => [
            'url'   => $base . rawurlencode($r['filename']),
            'name'  => $r['photo_name'] ?: $r['description'] ?: 'Foto',
            'price' => $r['price'] ?? '',
        ], $rows);
        echo json_encode(['photos' => $photos]);
    }

    public function leadSend(int $id): void
    {
        header('Content-Type: application/json');
        App::csrfVerify();
        $client      = $this->requireClient();
        $leadService = new ClientLeadService();
        $lead        = $leadService->findById($id, $client->id);
        if (!$lead) {
            http_response_code(404);
            echo json_encode(['error' => 'Lead not found']);
            return;
        }
        $text = trim($_POST['message'] ?? '');
        if (empty($text)) {
            http_response_code(400);
            echo json_encode(['error' => 'Empty message']);
            return;
        }
        // For LID-only leads (no resolved phone yet), use the LID as the address.
        // The bot worker converts bare digits to NUMBER@lid when length >= 14.
        $sendTo = $lead->phone !== '' ? $lead->phone : ($lead->lid ?? '');
        if (empty($sendTo)) {
            http_response_code(422);
            echo json_encode(['error' => 'No WhatsApp address for this lead yet']);
            return;
        }
        $leadService->saveMessage($client->id, $lead->id, $sendTo, $text, 'outbound', 'human');
        $delivered = $this->sendViaBot($client->id, $sendTo, $text);
        echo json_encode(['success' => true, 'delivered' => $delivered]);
    }

    public function leadTranslate(int $id): void
    {
        header('Content-Type: application/json');
        $client      = $this->requireClient();

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);

        // CSRF check — token comes in JSON body for this endpoint
        $token = $data['_csrf'] ?? '';
        if (!isset($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $leadService = new ClientLeadService();
        $lead        = $leadService->findById($id, $client->id);
        if (!$lead) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            return;
        }
        $texts = array_values(array_filter(array_map('strval', (array)($data['texts'] ?? []))));
        if (empty($texts)) {
            echo json_encode(['translations' => []]);
            return;
        }

        // Ask Groq to return a JSON array — far more reliable than numbered lists
        $prompt = "You are a professional translator. Your task: translate each message from ANY language to Spanish.\n"
                . "RULES:\n"
                . "- Translate ALL text to Spanish, including Hebrew, Arabic, English, or any other language.\n"
                . "- If a message is already in Spanish, copy it unchanged.\n"
                . "- Preserve emojis and URLs as-is.\n"
                . "- Return ONLY a valid JSON array of translated strings. Same count, same order.\n"
                . "- No explanations, no markdown, no extra text.\n\n"
                . "Input:\n" . json_encode($texts, JSON_UNESCAPED_UNICODE);

        $groqKey = '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';

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $groqKey],
            CURLOPT_POSTFIELDS     => json_encode([
                'model'       => 'llama-3.3-70b-versatile',
                'messages'    => [
                    ['role' => 'system', 'content' => 'You are a translation API. You only output valid JSON arrays of translated strings. Translate everything to Spanish.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens'  => 4000,
                'temperature' => 0.1,
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        $json   = json_decode($resp ?: '', true);
        $output = trim($json['choices'][0]['message']['content'] ?? '');

        // Extract JSON array — try the whole output first, then look for [...] inside it
        $arr = json_decode($output, true);
        if (!is_array($arr)) {
            if (preg_match('/\[.*\]/s', $output, $m)) {
                $arr = json_decode($m[0], true);
            }
        }

        // Apply positionally; fallback to original per-item if something is missing
        $translations = [];
        foreach ($texts as $i => $orig) {
            $t = isset($arr[$i]) ? strval($arr[$i]) : null;
            $translations[] = ($t !== null && $t !== '') ? $t : $orig;
        }

        // If nothing actually changed, flag it so JS can show a warning instead of false success
        $changed = false;
        foreach ($texts as $i => $orig) {
            if ($translations[$i] !== $orig) { $changed = true; break; }
        }

        echo json_encode(['translations' => $translations, 'changed' => $changed]);
    }

    private function sendViaBot(int $clientId, string $phone, string $message): bool
    {
        $payload = json_encode([
            'client_id' => $clientId,
            'to'        => preg_replace('/[^0-9+]/', '', $phone),
            'message'   => $message,
        ]);
        $ctx = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
            'content'       => $payload,
            'timeout'       => 8,
            'ignore_errors' => true,
        ]]);
        $result = @file_get_contents('http://localhost:3001/send-client', false, $ctx);
        if ($result === false) return false;
        $data = json_decode($result, true);
        return !empty($data['success']);
    }

    // ── Analytics ─────────────────────────────────────────────────────────────

    public function analytics(): void
    {
        $client = $this->requireClient();
        $data   = (new ClientLeadService())->analyticsData($client->id);
        require __DIR__ . '/../views/client/analytics.php';
    }

    // ── Messages inbox ────────────────────────────────────────────────────────

    public function messages(): void
    {
        $client = $this->requireClient();

        $filter   = $_GET['handled_by'] ?? '';
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = 30;
        $offset   = ($page - 1) * $perPage;

        $db = Database::get();

        if ($filter) {
            $stmt = $db->prepare(
                'SELECT m.*, l.contact_name AS lead_name
                 FROM mia_client_messages m
                 LEFT JOIN mia_client_leads l ON l.id = m.lead_id
                 WHERE m.client_id = ? AND m.handled_by = ?
                 ORDER BY m.created_at DESC
                 LIMIT ? OFFSET ?'
            );
            $stmt->execute([$client->id, $filter, $perPage, $offset]);
        } else {
            $stmt = $db->prepare(
                'SELECT m.*, l.contact_name AS lead_name
                 FROM mia_client_messages m
                 LEFT JOIN mia_client_leads l ON l.id = m.lead_id
                 WHERE m.client_id = ?
                 ORDER BY m.created_at DESC
                 LIMIT ? OFFSET ?'
            );
            $stmt->execute([$client->id, $perPage, $offset]);
        }

        $messages = $stmt->fetchAll();

        require __DIR__ . '/../views/client/messages.php';
    }
}
