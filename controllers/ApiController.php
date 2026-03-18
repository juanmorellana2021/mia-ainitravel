<?php
/**
 * mia/controllers/ApiController.php
 *
 * Two endpoints:
 *  POST /api/chat         — Mia sales bot (sells Mia to new businesses)
 *  POST /api/client-chat  — Mia running on behalf of a subscribed client's WA number
 *
 * Both are localhost-only (called by the bot server), authenticated with X-Mia-Bot-Key.
 */

declare(strict_types=1);

class ApiController
{
    private function guardBotRequest(): bool
    {
        header('Content-Type: application/json');
        $caller   = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $botKey   = $_SERVER['HTTP_X_MIA_BOT_KEY'] ?? '';
        $validKey = ($botKey === 'mia-bot-secret-2026');
        $local    = in_array($caller, ['127.0.0.1', '::1', 'localhost'], true);
        if (!$local && !$validKey) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return false;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
            return false;
        }
        return true;
    }

    // ── Mia sales bot (Juan's number — sells Mia to new hotel owners) ─────────
    public function chat(): void
    {
        if (!$this->guardBotRequest()) return;

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);

        if (!is_array($data) || empty($data['from'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Missing from']);
            return;
        }

        $phone    = trim((string) $data['from']);
        $message  = trim((string) ($data['message'] ?? ''));

        // Media resolution: convert voice notes / images to text before the service sees them
        $mediaData = $data['media_data'] ?? null;
        $mediaMime = $data['media_mime'] ?? null;
        $mediaType = $data['media_type'] ?? null;
        if ($mediaData && $mediaMime) {
            require_once __DIR__ . '/../services/MediaService.php';
            $mediaSvc = new MediaService();
            if (in_array($mediaType, ['ptt', 'audio'], true)) {
                $transcript = $mediaSvc->transcribeAudio($mediaData, $mediaMime);
                $message    = $transcript ? '[Nota de voz]: ' . $transcript : '[El usuario envió una nota de voz]';
                error_log('[Mia] Voice transcribed: ' . substr($transcript ?: '', 0, 80));
            } elseif ($mediaType === 'image') {
                $desc    = $mediaSvc->describeImage($mediaData, $mediaMime, $message);
                $message = $desc ? ($message ? "[Imagen: {$desc}] {$message}" : "[Imagen]: {$desc}") : ($message ?: '[El usuario envió una imagen]');
            }
        }

        if (empty($message)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Empty message after media processing']);
            return;
        }

        try {
            $service = new MiaSalesService();
            $result  = $service->process($phone, $message);

            echo json_encode([
                'success' => true,
                'reply'   => $result['reply'] ?? '',
                'from'    => $phone,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('[Mia ApiController] Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
                'reply'   => 'Lo siento, estoy teniendo problemas técnicos. Intenta de nuevo en un momento. 🙏',
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ── Client bot (answers guests on behalf of a subscribed client) ──────────
    // Called by bot server with: { from, message, client_id }
    public function clientChat(): void
    {
        if (!$this->guardBotRequest()) return;

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);

        if (!is_array($data) || empty($data['from']) || empty($data['client_id'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Missing from or client_id']);
            return;
        }

        // Prefer real phone number over LID/internal WA ID if worker resolved it
        $phone     = trim((string) ($data['phone'] ?? $data['from']));
        if (empty($phone)) $phone = trim((string) $data['from']);
        $message   = trim((string) ($data['message'] ?? ''));
        $clientId  = (int) $data['client_id'];

        // Media resolution: convert voice notes / images to text before the service sees them
        $mediaData = $data['media_data'] ?? null;
        $mediaMime = $data['media_mime'] ?? null;
        $mediaType = $data['media_type'] ?? null;
        if ($mediaData && $mediaMime) {
            require_once __DIR__ . '/../services/MediaService.php';
            $mediaSvc = new MediaService();
            if (in_array($mediaType, ['ptt', 'audio'], true)) {
                $transcript = $mediaSvc->transcribeAudio($mediaData, $mediaMime);
                $message    = $transcript ? '[Nota de voz]: ' . $transcript : '[El usuario envió una nota de voz]';
                error_log("[ClientBot:{$clientId}] Voice transcribed: " . substr($transcript ?: '', 0, 80));
            } elseif ($mediaType === 'image') {
                $desc    = $mediaSvc->describeImage($mediaData, $mediaMime, $message);
                $message = $desc ? ($message ? "[Imagen: {$desc}] {$message}" : "[Imagen]: {$desc}") : ($message ?: '[El usuario envió una imagen]');
            }
        }

        // No reply needed for media we couldn't extract (e.g. stickers)
        if (empty($message)) {
            echo json_encode(['success' => true, 'reply' => '']);
            return;
        }

        $client = (new ClientService())->findById($clientId);
        if (!$client) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Client not found']);
            return;
        }

        if (!$client->isActive()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Client subscription inactive']);
            return;
        }

        try {
            $service = new ClientBotService($client);
            // Pass the raw LID 'from' so the service can migrate old LID-stored leads
            $fromLid = trim((string) $data['from']);
            $result  = $service->process($phone, $message, $fromLid);

            echo json_encode([
                'success' => true,
                'reply'   => $result['reply'] ?? '',
                'from'    => $phone,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("[ClientBot:{$clientId}] Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
                'reply'   => 'Un momento, estoy teniendo un pequeño problema técnico 🙏',
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ── Public page-event tracking pixel ─────────────────────────────────────

    // ── LID resolution helpers (called by bot worker on startup) ─────────────

    /** Returns all LID-format phone numbers stored for this client */
    public function resolveLids(): void
    {
        if (!$this->guardBotRequest()) return;
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        $clientId = (int)($data['client_id'] ?? 0);
        if (!$clientId) { echo json_encode(['success' => false, 'error' => 'Missing client_id']); return; }

        $pdo = Database::get();
        // LID-format numbers are exactly 15 digits (real phones are 10-13)
        $stmt = $pdo->prepare(
            "SELECT DISTINCT phone FROM mia_client_leads
             WHERE client_id = ? AND phone REGEXP '^[0-9]{14,16}$'
             UNION
             SELECT DISTINCT phone FROM mia_client_messages
             WHERE client_id = ? AND phone REGEXP '^[0-9]{14,16}$'"
        );
        $stmt->execute([$clientId, $clientId]);
        $lids = array_column($stmt->fetchAll(), 'phone');
        echo json_encode(['success' => true, 'lids' => $lids], JSON_UNESCAPED_UNICODE);
    }

    /** Applies resolved LID→phone mappings, updating leads and messages */
    public function applyLidResolutions(): void
    {
        if (!$this->guardBotRequest()) return;
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        $clientId = (int)($data['client_id'] ?? 0);
        $resolved = $data['resolved'] ?? [];
        if (!$clientId || !is_array($resolved)) {
            echo json_encode(['success' => false, 'error' => 'Invalid payload']); return;
        }

        $pdo = Database::get();
        $updated = 0;
        foreach ($resolved as $r) {
            $lid   = preg_replace('/[^0-9]/', '', (string)($r['lid']   ?? ''));
            $phone = preg_replace('/[^0-9]/', '', (string)($r['phone'] ?? ''));
            if (!$lid || !$phone || $lid === $phone) continue;

            $pdo->prepare("UPDATE mia_client_leads    SET phone=? WHERE client_id=? AND phone=?")->execute([$phone, $clientId, $lid]);
            $pdo->prepare("UPDATE mia_client_messages SET phone=? WHERE client_id=? AND phone=?")->execute([$phone, $clientId, $lid]);
            error_log("[LIDmigration] client={$clientId} {$lid} → {$phone}");
            $updated++;
        }
        echo json_encode(['success' => true, 'updated' => $updated], JSON_UNESCAPED_UNICODE);
    }


    // GET/POST /api/track
    // Params (query-string or JSON body):
    //   event      string  pageview | pageleave | cta_click
    //   session_id string  client-generated UUID (persisted in localStorage)
    //   page       string  page path / URL
    //   referrer   string  document.referrer
    //   utm_*      string  UTM campaign parameters
    //   device     string  mobile | desktop | tablet
    //   duration   int     milliseconds on page (pageleave only)
    public function track(): void
    {
        // Accept both GET params and JSON body
        $raw   = file_get_contents('php://input');
        $body  = $raw ? (json_decode($raw, true) ?: []) : [];
        $p     = array_merge($_GET, $body);

        $allowedEvents = ['pageview', 'pageleave', 'cta_click'];
        $event         = in_array($p['event'] ?? '', $allowedEvents, true) ? $p['event'] : 'pageview';
        $sessionId     = substr(preg_replace('/[^a-zA-Z0-9\-_]/', '', (string)($p['session_id'] ?? '')), 0, 64);
        $page          = substr(strip_tags((string)($p['page']     ?? '')), 0, 255);
        $referrer      = substr(strip_tags((string)($p['referrer'] ?? '')), 0, 500);
        $utmSource     = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($p['utm_source']   ?? '')), 0, 100);
        $utmMedium     = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($p['utm_medium']   ?? '')), 0, 100);
        $utmCampaign   = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($p['utm_campaign'] ?? '')), 0, 100);
        $allowedDevices = ['mobile', 'desktop', 'tablet'];
        $device        = in_array($p['device'] ?? '', $allowedDevices, true) ? $p['device'] : '';
        $durationMs    = max(0, (int)($p['duration'] ?? 0));
        $ipHash        = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');

        $db = Database::get();
        $db->exec("
            CREATE TABLE IF NOT EXISTS `mia_page_events` (
                `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `event`        VARCHAR(30)     NOT NULL DEFAULT '',
                `session_id`   VARCHAR(64)     NOT NULL DEFAULT '',
                `ip_hash`      VARCHAR(64)     NOT NULL DEFAULT '',
                `page`         VARCHAR(255)    NOT NULL DEFAULT '',
                `referrer`     VARCHAR(500)    NOT NULL DEFAULT '',
                `utm_source`   VARCHAR(100)    NOT NULL DEFAULT '',
                `utm_medium`   VARCHAR(100)    NOT NULL DEFAULT '',
                `utm_campaign` VARCHAR(100)    NOT NULL DEFAULT '',
                `device`       VARCHAR(20)     NOT NULL DEFAULT '',
                `duration_ms`  INT UNSIGNED    NOT NULL DEFAULT 0,
                `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_event_date` (`event`, `created_at`),
                KEY `idx_created`    (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->prepare("
            INSERT INTO mia_page_events
                (event, session_id, ip_hash, page, referrer, utm_source, utm_medium, utm_campaign, device, duration_ms)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$event, $sessionId, $ipHash, $page, $referrer, $utmSource, $utmMedium, $utmCampaign, $device, $durationMs]);

        // Return a 1×1 transparent GIF so it can be used as an <img> src pixel
        header('Content-Type: image/gif');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }

    // ── WA status callback (called by bot.js when client session connects/disconnects) ─
    // POST /api/client-status  { client_id, status, phone? }
    public function clientStatus(): void
    {
        if (!$this->guardBotRequest()) return;

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);

        if (!is_array($data) || empty($data['client_id']) || empty($data['status'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Missing client_id or status']);
            return;
        }

        $clientId = (int) $data['client_id'];
        $status   = trim((string) $data['status']);   // 'connected' | 'disconnected'
        $phone    = isset($data['phone']) ? trim((string) $data['phone']) : null;

        (new ClientService())->updateWaStatus($clientId, $status, $phone ?: null);

        // Alert the client by email when their bot drops
        if ($status === 'disconnected') {
            try {
                require_once __DIR__ . '/../services/NotificationService.php';
                (new NotificationService())->sendDisconnectAlert($clientId);
            } catch (\Throwable $e) {
                error_log('[Mia] Disconnect alert error: ' . $e->getMessage());
            }
        }

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    // ── Onboarding help chat (authenticated clients only) ─────────────────────
    // POST /api/onboarding-help  { message: string, history: [{role,content},...] }
    public function onboardingHelp(): void
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['mia_client_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado']);
            return;
        }

        // Rate-limit: max 100 messages per PHP session to prevent API abuse
        $_SESSION['ob_help_count'] = ($_SESSION['ob_help_count'] ?? 0) + 1;
        if ($_SESSION['ob_help_count'] > 100) {
            echo json_encode(['reply' => 'Has alcanzado el límite de mensajes por sesión. Recarga la página para continuar. 😊']);
            return;
        }

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true) ?: [];

        $userMessage = substr(strip_tags(trim((string)($data['message'] ?? ''))), 0, 500);
        if ($userMessage === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Mensaje vacío']);
            return;
        }

        // Sanitize and limit history to last 6 turns
        $history = [];
        foreach (array_slice((array)($data['history'] ?? []), -6) as $turn) {
            $role    = ($turn['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $content = substr(strip_tags((string)($turn['content'] ?? '')), 0, 300);
            if ($content !== '') {
                $history[] = ['role' => $role, 'content' => $content];
            }
        }

        $bizName    = htmlspecialchars($_SESSION['mia_client']['business_name'] ?? 'tu negocio', ENT_QUOTES);
        $currentPage = htmlspecialchars(trim(parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH), '/'), ENT_QUOTES);

        $systemPrompt =
            "Eres Mia, la asistente inteligente integrada en el panel de 'Mia by AiniTravel'. " .
            "Estás ayudando a {$bizName}. La página actual del cliente es: '{$currentPage}'. " .
            "Responde SIEMPRE en español. Sé concisa (máx 3 oraciones de respuesta). Usa máximo 1 emoji. Sé directa y amigable. " .

            "CONOCES TODO EL PANEL. Secciones disponibles: " .
            "DASHBOARD (/dashboard) – resumen de métricas, leads nuevos, mensajes recientes. " .
            "LEADS (/dashboard/leads) – lista de contactos capturados por el bot; cada lead tiene estado (Nuevo, Interesado, Ganado, Perdido) y estimado de valor; puedes hacer clic en un lead para ver su conversación completa. " .
            "MENSAJES (/dashboard/messages) – historial de conversaciones de WhatsApp con todos los contactos. " .
            "ANALÍTICAS (/dashboard/analytics) – gráficas de rendimiento del bot, tasa de respuesta, leads por día. " .
            "DIFUSIÓN (/dashboard/broadcast) – envío masivo de mensajes a listas de contactos. Requiere plan Pro+. " .
            "AUTOMATIZACIONES (/dashboard/sequences) – secuencias de mensajes programados (follow-ups automáticos). Requiere plan Pro+. " .
            "CITAS (/dashboard/appointments) – sistema para que el bot agende citas. Requiere plan Pro+. " .
            "SUSCRIPCIÓN (/dashboard/billing) – manejo del plan, facturas, upgrades. " .
            "CONFIGURACIÓN (/dashboard/settings) – personalidad del bot, idioma, horarios de atención, mensaje de bienvenida, conectar WhatsApp (escanear QR con WhatsApp Business → Dispositivos vinculados → Vincular dispositivo). " .

            "PUEDES RESALTAR ELEMENTOS DE LA PANTALLA. Si el usuario pregunta dónde está algo o necesitas señalar un elemento concreto, " .
            "incluye en tu respuesta JSON un campo 'highlight' con un selector CSS del elemento a resaltar. " .
            "USA SIEMPRE EL SELECTOR MÁS ESPECÍFICO PARA EL CONTEXTO. Selectores disponibles: " .

            "MENÚ LATERAL (siempre visibles): " .
            "'a[href*=\"/dashboard\"]' (menú Dashboard), " .
            "'a[href*=\"leads\"]' (menú Leads en sidebar — NO la tabla), " .
            "'a[href*=\"messages\"]' (menú Mensajes), " .
            "'a[href*=\"analytics\"]' (menú Analíticas), " .
            "'a[href*=\"broadcast\"]' (menú Difusión), " .
            "'a[href*=\"sequences\"]' (menú Automatizaciones), " .
            "'a[href*=\"billing\"]' (menú Suscripción), " .
            "'a[href*=\"settings\"]' (menú Configuración), " .
            "'#mia-help-nav-btn' (este botón de ayuda). " .

            "PÁGINA LEADS (/dashboard/leads) — para señalar el botón de ver detalle de un lead: " .
            "'a.btn-outline-primary' (botón ojo/ver de cada fila), " .
            "'.btn.chat-open-btn' (botón de chat de cada fila), " .
            "'.mc-table-card' (la tabla completa de leads). " .

            "PÁGINA CONFIGURACIÓN (/dashboard/settings) — " .
            "'.mc-table-card' (sección principal de configuración), " .
            "'.bi-whatsapp' (icono para conectar WhatsApp). " .

            "PÁGINA MENSAJES (/dashboard/messages) — " .
            "'.mc-table-card' (lista de conversaciones). " .

            "Si no hay nada relevante que resaltar, omite el campo 'highlight'. " .
            "IMPORTANTE: cuando el usuario pida ver, mostrar o señalar algo en pantalla, usa SIEMPRE el campo highlight.";

            "FORMATO DE RESPUESTA: responde SIEMPRE con JSON válido así: " .
            "{\"reply\": \"tu respuesta aquí\", \"highlight\": \"selector-css-opcional\"} " .
            "REGLAS ESTRICTAS: (1) El JSON empieza con { y termina con } — nada más. " .
            "(2) NUNCA pongas texto, emojis ni caracteres FUERA del objeto JSON. " .
            "(3) Los emojis van únicamente DENTRO del valor de 'reply'. Solo JSON puro.";

        $messages   = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($history as $h) {
            $messages[] = $h;
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $apiKey  = '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';
        $payload = json_encode([
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => $messages,
            'temperature' => 0.5,
            'max_tokens'  => 180,
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
        ]);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            echo json_encode(['reply' => 'Tuve un problema técnico. Intenta de nuevo. 🙏'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result  = json_decode($response, true);
        $raw     = trim($result['choices'][0]['message']['content'] ?? '');

        // Model should return pure JSON. Extract the {} block robustly in case
        // it adds trailing emojis or text outside the JSON object.
        $parsed = json_decode($raw, true);
        if (!is_array($parsed)) {
            // Find the first complete { ... } block in the output
            if (preg_match('/\{.*\}/s', $raw, $m)) {
                $parsed = json_decode($m[0], true);
            }
        }

        $reply     = trim((string)($parsed['reply'] ?? $raw));
        $highlight = trim((string)($parsed['highlight'] ?? ''));
        // If reply still looks like raw JSON (total parse failure), return fallback
        if ($reply === '' || (str_starts_with($reply, '{') && str_contains($reply, '"reply"'))) {
            $reply = '¿En qué parte necesitas ayuda? 😊';
        }
        // Whitelist highlight selectors to prevent injection
        $allowedHighlight = '';
        if ($highlight !== '' && preg_match('/^[\w\s\[\]#.*=":\'>,\-\/]+$/', $highlight)) {
            $allowedHighlight = $highlight;
        }

        $out = ['reply' => $reply];
        if ($allowedHighlight !== '') $out['highlight'] = $allowedHighlight;
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
    }
}
