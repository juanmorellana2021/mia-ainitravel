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
            $fromLid        = trim((string) $data['from']);
            $contactName    = trim((string) ($data['contact_name'] ?? ''));
            $profilePicUrl  = trim((string) ($data['profile_pic_url'] ?? ''));
            $result  = $service->process($phone, $message, $fromLid, $contactName, $profilePicUrl);

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

    /** Returns leads missing a profile pic or contact name (for startup backfill) */
    public function leadsNeedingBackfill(): void
    {
        if (!$this->guardBotRequest()) return;
        $raw      = file_get_contents('php://input');
        $data     = json_decode($raw ?: '', true);
        $clientId = (int)($data['client_id'] ?? 0);
        if (!$clientId) { echo json_encode(['success' => false, 'error' => 'Missing client_id']); return; }

        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT id, phone FROM mia_client_leads
              WHERE client_id = ?
                AND (profile_pic IS NULL OR contact_name = '' OR contact_name IS NULL)
                AND phone NOT REGEXP '^[0-9]{14,16}$'
                AND phone != ''
              LIMIT 200"
        );
        $stmt->execute([$clientId]);
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'leads' => $leads], JSON_UNESCAPED_UNICODE);
    }

    /** Applies backfill: saves profile pics and contact names for existing leads */
    public function applyLeadBackfill(): void
    {
        if (!$this->guardBotRequest()) return;
        $raw      = file_get_contents('php://input');
        $data     = json_decode($raw ?: '', true);
        $clientId = (int)($data['client_id'] ?? 0);
        $updates  = $data['updates'] ?? [];
        if (!$clientId || !is_array($updates)) {
            echo json_encode(['success' => false, 'error' => 'Invalid payload']); return;
        }

        $pdo     = Database::get();
        $applied = 0;
        foreach ($updates as $u) {
            $leadId  = (int)($u['lead_id']         ?? 0);
            $name    = substr(trim((string)($u['contact_name']    ?? '')), 0, 255);
            $picUrl  = trim((string)($u['profile_pic_url'] ?? ''));
            if (!$leadId) continue;

            $savedPic = $picUrl ? $this->saveProfilePic($picUrl, $clientId, $leadId) : null;

            if ($name && $savedPic) {
                $pdo->prepare("UPDATE mia_client_leads SET contact_name = ?, profile_pic = ?
                                WHERE id = ? AND client_id = ?
                                  AND (contact_name = '' OR contact_name IS NULL)")
                    ->execute([$name, $savedPic, $leadId, $clientId]);
                // If contact_name was already set, still save the pic
                $pdo->prepare("UPDATE mia_client_leads SET profile_pic = ?
                                WHERE id = ? AND client_id = ? AND profile_pic IS NULL")
                    ->execute([$savedPic, $leadId, $clientId]);
            } elseif ($name) {
                $pdo->prepare("UPDATE mia_client_leads SET contact_name = ?
                                WHERE id = ? AND client_id = ?
                                  AND (contact_name = '' OR contact_name IS NULL)")
                    ->execute([$name, $leadId, $clientId]);
            } elseif ($savedPic) {
                $pdo->prepare("UPDATE mia_client_leads SET profile_pic = ?
                                WHERE id = ? AND client_id = ? AND profile_pic IS NULL")
                    ->execute([$savedPic, $leadId, $clientId]);
            }
            $applied++;
        }
        echo json_encode(['success' => true, 'applied' => $applied], JSON_UNESCAPED_UNICODE);
    }

    /** Downloads a profile pic URL and saves it to the avatars directory */
    private function saveProfilePic(string $url, int $clientId, int $leadId): ?string
    {
        $dir = __DIR__ . '/../assets/uploads/avatars';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $filename = 'lead_' . $clientId . '_' . $leadId . '.jpg';
        $path     = $dir . '/' . $filename;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $imgData = curl_exec($ch);
        $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($imgData && $code === 200 && strlen($imgData) > 500) {
            file_put_contents($path, $imgData);
            return 'assets/uploads/avatars/' . $filename;
        }
        return null;
    }


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

            "PUEDES RESALTAR ELEMENTOS Y NAVEGAR A OTRA PÁGINA. " .
            "Si la respuesta está en otra página, incluye 'navigate' con la URL (ej: /dashboard/settings). " .
            "Si el elemento está en la página ACTUAL, usa 'highlight'. Puedes usar ambos. " .
            "NUNCA menciones nombres de selectores CSS en el texto de 'reply' — son internos. " .
            "En el reply habla natural (ej: 'Ve a Configuración y conecta WhatsApp' — no '.bi-whatsapp'). " .

            "SELECTORES para el campo highlight (NUNCA en el reply): " .
            "Menú lateral (siempre visibles): 'a[href*=\"settings\"]', 'a[href*=\"leads\"]', 'a[href*=\"messages\"]', " .
            "'a[href*=\"analytics\"]', 'a[href*=\"broadcast\"]', 'a[href*=\"sequences\"]', 'a[href*=\"billing\"]'. " .
            "En /dashboard/leads: 'a.btn-outline-primary' (botón ver), '.mc-table-card' (tabla). " .
            "En /dashboard/settings: '.mc-table-card' (sección config). " .

            "REGLA CLAVE: si el elemento a destacar está en OTRA página, pon 'navigate' a esa página " .
            "Y en 'highlight' pon el ítem del menú lateral que lleva a ella. " .

            "FORMATO DE RESPUESTA: responde SIEMPRE con JSON válido así: " .
            "{\"reply\": \"texto natural\", \"highlight\": \"selector-opcional\", \"navigate\": \"/dashboard/ruta-opcional\"} " .
            "REGLAS ESTRICTAS: (1) El JSON empieza con { y termina con } — nada más. " .
            "(2) NUNCA texto, emojis ni selectores CSS fuera del JSON. " .
            "(3) Emojis solo dentro de 'reply'. Solo JSON puro.";

        $messages   = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($history as $h) {
            $messages[] = $h;
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $apiKey  = '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';
        $payload = json_encode([
            'model'           => 'llama-3.3-70b-versatile',
            'messages'        => $messages,
            'temperature'     => 0.5,
            'max_tokens'      => 200,
            'response_format' => ['type' => 'json_object'],  // force valid JSON output
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

        // response_format=json_object ensures valid JSON, but extract robustly anyway.
        $parsed = json_decode($raw, true);
        if (!is_array($parsed)) {
            // Model may have put text before/after the JSON block — find and extract it.
            if (preg_match('/\{[^{}]*\}/s', $raw, $m, PREG_OFFSET_CAPTURE)) {
                $jsonBlock  = $m[0][0];
                $jsonOffset = $m[0][1];
                $parsedBlock = json_decode($jsonBlock, true);
                if (is_array($parsedBlock)) {
                    $parsed = $parsedBlock;
                    // Use text before the { as reply if model didn't put it inside
                    if (empty($parsed['reply'])) {
                        $textBefore = trim(substr($raw, 0, $jsonOffset));
                        if ($textBefore !== '') $parsed['reply'] = $textBefore;
                    }
                }
            }
        }

        $reply     = trim((string)($parsed['reply'] ?? ''));
        $highlight = trim((string)($parsed['highlight'] ?? ''));
        $navigate  = trim((string)($parsed['navigate'] ?? ''));
        // Strip any trailing {...} JSON artifact from the reply text itself
        $reply = trim(preg_replace('/\s*\{[^{}]+\}\s*$/', '', $reply));
        if ($reply === '') {
            $reply = '¿En qué parte necesitas ayuda? 😊';
        }
        // Whitelist highlight selectors to prevent injection
        $allowedHighlight = '';
        if ($highlight !== '' && preg_match('/^[\w\s\[\]#.*=":\'>,\-\/]+$/', $highlight)) {
            $allowedHighlight = $highlight;
        }
        // Whitelist navigate to internal dashboard paths only
        $allowedNavigate = '';
        if ($navigate !== '' && preg_match('/^\/dashboard([\\/\w-]*)$/', $navigate)) {
            $allowedNavigate = $navigate;
        }

        $out = ['reply' => $reply];
        if ($allowedHighlight !== '') $out['highlight'] = $allowedHighlight;
        if ($allowedNavigate  !== '') $out['navigate']  = $allowedNavigate;
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
    }
}
