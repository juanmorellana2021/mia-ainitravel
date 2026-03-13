<?php
/**
 * mia/services/ClientBotService.php
 *
 * AI bot service for a Mia client's WhatsApp number.
 * Reads the client's bot_config to build a custom system prompt,
 * then calls Groq to answer the guest's message.
 *
 * This is SEPARATE from MiaSalesService (which sells Mia to new businesses).
 * This service IS Mia running on behalf of a subscribed client's business.
 */

declare(strict_types=1);

class ClientBotService
{
    private PDO    $pdo;
    private Client $client;
    private array  $cfg;

    // Plan-based feature flags
    private bool $canCaptureLead;
    private bool $canHandoff;

    private const GROQ_KEY   = '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';
    private const GROQ_MODEL = 'llama-3.3-70b-versatile';
    private const MAX_HISTORY = 10; // message pairs

    // Monthly conversation limits per plan (0 = unlimited)
    public const CONV_LIMITS = [
        'trial'      => 0,    // trial = full Pro experience, unlimited
        'starter'    => 500,
        'basic'      => 1000,
        'pro'        => 0,
        'enterprise' => 0,
    ];

    public function __construct(Client $client)
    {
        $this->pdo    = Database::get();
        $this->client = $client;
        $this->cfg    = json_decode($client->bot_config ?? '{}', true) ?: [];

        $planCaps = [
            'trial'      => ['handoff', 'leads'], // trial = full Pro experience so users see everything
            'starter'    => [],
            'basic'      => ['handoff'],
            'pro'        => ['handoff', 'leads'],
            'enterprise' => ['handoff', 'leads'],
        ];
        $caps = $planCaps[$client->plan] ?? [];
        $this->canHandoff     = in_array('handoff', $caps);
        $this->canCaptureLead = in_array('leads', $caps);

        $this->ensureTable();
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Process an incoming WhatsApp message from a guest.
     * Returns ['reply' => string].
     */
    public function process(string $guestPhone, string $message): array
    {
        $guestPhone = $this->normalizePhone($guestPhone);
        $msg        = trim($message);

        if (empty($msg)) {
            return ['reply' => ''];
        }

        // ── Monthly conversation limit check ─────────────────────────────────
        $limit = self::CONV_LIMITS[$this->client->plan] ?? 0;
        if ($limit > 0) {
            // Check if this phone already has a message this month (existing convo)
            $stmtExist = $this->pdo->prepare(
                "SELECT COUNT(*) FROM mia_client_messages
                 WHERE client_id = ? AND phone = ? AND direction = 'inbound'
                   AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())"
            );
            $stmtExist->execute([$this->client->id, $guestPhone]);
            $isNewConvo = ((int)$stmtExist->fetchColumn() === 0);

            if ($isNewConvo) {
                $stmtCount = $this->pdo->prepare(
                    "SELECT COUNT(DISTINCT phone) FROM mia_client_messages
                     WHERE client_id = ? AND direction = 'inbound'
                       AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())"
                );
                $stmtCount->execute([$this->client->id]);
                $used = (int)$stmtCount->fetchColumn();

                if ($used >= $limit) {
                    error_log("[ClientBot:{$this->client->id}] Conv limit reached ({$used}/{$limit}) — blocking {$guestPhone}");
                    return ['reply' => '']; // silent block; owner should upgrade
                }
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        // Human handoff request — hand back to owner
        if ($this->canHandoff &&
            preg_match('/\bhumano|agente|persona|hablar con|speak to|una persona\b/i', $msg)) {
            $reply = $this->handoffReply();
            $this->log($guestPhone, 'user', $msg);
            $this->log($guestPhone, 'assistant', $reply);
            return ['reply' => $reply];
        }

        $history  = $this->loadHistory($guestPhone);
        $messages = array_merge(
            [['role' => 'system', 'content' => $this->buildSystemPrompt()]],
            $history,
            [['role' => 'user',   'content' => $msg]]
        );

        $reply = $this->callGroq($messages);
        $this->log($guestPhone, 'user', $msg);
        $this->log($guestPhone, 'assistant', $reply);

        // Lead capture: if Pro+ plan and reply includes asking for contact info,
        // we let the AI handle it naturally via the system prompt; the client's
        // dashboard leads are populated via the guest's WhatsApp number itself.

        return ['reply' => $reply];
    }

    // ── System prompt ─────────────────────────────────────────────────────────

    private function buildSystemPrompt(): string
    {
        $bizName    = $this->client->business_name;
        $rawType    = $this->cfg['business_type']  ?? $this->client->business_type ?? 'negocio';
        $customType = $this->cfg['custom_type']    ?? '';
        $bizType    = ($rawType === 'other' && $customType !== '') ? $customType : $rawType;
        $desc       = $this->cfg['description']    ?? '';
        $services   = $this->cfg['services']       ?? '';
        $pricing    = $this->cfg['pricing']        ?? '';
        $hours      = $this->cfg['hours']          ?? '';
        $faqs       = $this->cfg['faqs']           ?? '';
        $website    = $this->cfg['website']        ?? '';
        $location   = $this->cfg['location']       ?? '';
        $tone       = $this->cfg['tone']        ?? 'friendly';
        $language   = $this->cfg['language']     ?? 'es';
        $charSkills = (array)($this->cfg['char_skills'] ?? []);

        // Build skill-specific prompt injections
        $skillMap = [
            'humor'    => 'Uso de humor ligero y apropiado: incluye una broma corta o comentario ingenioso cuando el momento lo permita de forma natural.',
            'empathy'  => 'Empatía activa: reconoce el sentimiento del cliente antes de dar información ("Entiendo que puede ser frustrante...", "Me alegra que preguntes eso").',
            'stories'  => 'Cuenta micro-historias de éxito: cuando sea relevante menciona brevemente un cliente similar que tuvo un buen resultado con el negocio.',
            'direct'   => 'Estilo ultra-directo: sin frases de relleno, sin saludos largos — la primera frase ya da la respuesta.',
            'scarcity' => 'Usa escasez y urgencia cuando sea apropiado: disponibilidad limitada, temporada alta, oferta por tiempo limitado.',
            'patient'  => 'Nunca presiones al cliente. Si no está listo, respeta su ritmo. Solo una pregunta de seguimiento por turno, nunca dos.',
            'usted'    => 'Usa SIEMPRE "usted" y nunca "tú". Trato formal en todo momento.',
            'tips'     => 'Proactivo con valor extra: cuando sea natural, da 1 consejo útil adicional relacionado con lo que preguntaron.',
            'premium'  => 'Voz de marca premium: usa vocabulario refinado — "inversión" no "costo", "exclusivo" no "barato", "seleccionado" no "disponible".',
            'proactive'=> 'Siempre ofrece un próximo paso claro o alternativa sin que te lo pidan. No dejes la conversación sin dirección.',
        ];
        $activeSkillLines = [];
        foreach ($charSkills as $sk) {
            if (isset($skillMap[$sk])) $activeSkillLines[] = '- ' . $skillMap[$sk];
        }
        $skillsBlock = !empty($activeSkillLines)
            ? "\nHABILIDADES DE PERSONALIDAD ACTIVAS:\n" . implode("\n", $activeSkillLines)
            : '';

        $toneDesc = match ($tone) {
            'professional' => 'Formal y profesional. Respuestas precisas y bien estructuradas.',
            'casual'       => 'Casual y relajado. Como hablar con un amigo que conoce el negocio.',
            'luxury'       => 'Exclusivo y refinado. Al nivel de un servicio de lujo de cinco estrellas.',
            default        => 'Amigable y cercano. Cálido, directo y genuinamente útil.',
        };

        $languageRule = match ($language) {
            'en'   => 'Always respond in English.',
            'auto' => 'Detect the language of the customer\'s message and respond in the same language.',
            default => 'Responde siempre en español.',
        };

        $handoffBlock = $this->canHandoff
            ? "- Si el cliente pide hablar con una persona real, dile que avisarás al equipo y que alguien lo contactará pronto."
            : "- Si el cliente pide hablar con una persona, dile que puede comunicarse directamente con el negocio.";

        $leadBlock = $this->canCaptureLead
            ? "- Si el cliente muestra interés en comprar/reservar algo específico, pide su nombre y número/email de forma natural para que el equipo lo contacte."
            : '';

        $servicesBlock = $services ? "SERVICIOS Y PRODUCTOS:\n{$services}" : '';
        $pricingBlock  = $pricing  ? "PRECIOS:\n{$pricing}"               : '';
        $hoursBlock    = $hours    ? "HORARIOS:\n{$hours}"                 : '';
        $faqsBlock     = $faqs     ? "PREGUNTAS FRECUENTES:\n{$faqs}"      : '';
        $descBlock     = $desc     ? "SOBRE EL NEGOCIO:\n{$desc}"          : '';
        $websiteBlock  = $website  ? "SITIO WEB / REDES SOCIALES: {$website}" : '';
        $locationBlock = $location ? "UBICACIÓN / DIRECCIÓN: {$location}"    : '';

        return <<<PROMPT
Eres el asistente virtual de WhatsApp de *{$bizName}*, un negocio de tipo {$bizType}.

TONO: {$toneDesc}
IDIOMA: {$languageRule}

{$descBlock}

{$locationBlock}

{$websiteBlock}

{$servicesBlock}

{$pricingBlock}

{$hoursBlock}

{$faqsBlock}

REGLAS DE COMPORTAMIENTO:
- Responde SOLO sobre este negocio. No inventes información que no esté aquí.
- Si no sabes la respuesta, di que consultarás con el equipo y lo confirmarás.
- Mensajes cortos y concretos — máximo 3 líneas para respuestas simples.
- No uses listas largas. Solo si el cliente pide ver todos los servicios/precios.
- 1 emoji máximo por mensaje, solo si suma.
- NUNCA digas que eres una IA a menos que te pregunten directamente.
{$handoffBlock}
{$leadBlock}{$skillsBlock}
PROMPT;
    }

    // ── Groq call ─────────────────────────────────────────────────────────────

    private function callGroq(array $messages): string
    {
        $payload = json_encode([
            'model'       => self::GROQ_MODEL,
            'messages'    => $messages,
            'temperature' => 0.6,
            'max_tokens'  => 150,
            'top_p'       => 0.9,
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::GROQ_KEY,
            ],
        ]);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("[ClientBot:{$this->client->id}] Groq error: $err");
            return 'Un momento, estoy teniendo un pequeño problema técnico. Intenta de nuevo en un instante 🙏';
        }

        $data = json_decode($response, true);
        $text = trim($data['choices'][0]['message']['content'] ?? '');

        if (empty($text)) {
            error_log("[ClientBot:{$this->client->id}] Groq empty: $response");
            return 'Un momento, estoy teniendo un pequeño problema técnico. Intenta de nuevo en un instante 🙏';
        }

        // Keep first paragraph only
        $break = strpos($text, "\n\n");
        if ($break !== false) {
            $text = trim(substr($text, 0, $break));
        }
        return $text;
    }

    // ── Conversation history ──────────────────────────────────────────────────

    private function loadHistory(string $phone): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT conv_history FROM mia_client_bot_sessions
              WHERE client_id = ? AND guest_phone = ? LIMIT 1'
        );
        $stmt->execute([$this->client->id, $phone]);
        $raw = $stmt->fetchColumn();
        if (!$raw) return [];
        $history = json_decode($raw, true) ?? [];
        return array_slice($history, -(self::MAX_HISTORY * 2));
    }

    private function log(string $phone, string $role, string $content): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, conv_history FROM mia_client_bot_sessions
              WHERE client_id = ? AND guest_phone = ? LIMIT 1'
        );
        $stmt->execute([$this->client->id, $phone]);
        $row = $stmt->fetch();

        $history   = $row ? (json_decode($row['conv_history'] ?? '[]', true) ?? []) : [];
        $history[] = ['role' => $role, 'content' => $content];
        $json      = json_encode(array_slice($history, -(self::MAX_HISTORY * 2)), JSON_UNESCAPED_UNICODE);

        if ($row) {
            $u = $this->pdo->prepare(
                'UPDATE mia_client_bot_sessions SET conv_history = ?, updated_at = NOW()
                  WHERE client_id = ? AND guest_phone = ?'
            );
            $u->execute([$json, $this->client->id, $phone]);
        } else {
            $i = $this->pdo->prepare(
                'INSERT INTO mia_client_bot_sessions (client_id, guest_phone, conv_history)
                 VALUES (?, ?, ?)'
            );
            $i->execute([$this->client->id, $phone, $json]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function handoffReply(): string
    {
        $bizName = $this->client->business_name;
        return "Claro, aviso al equipo de {$bizName} que quieres hablar con alguien. " .
               "Te contactarán en breve 👋";
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9@]/', '', $phone);
    }

    private function ensureTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS mia_client_bot_sessions (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                client_id    INT NOT NULL,
                guest_phone  VARCHAR(50) NOT NULL,
                conv_history MEDIUMTEXT NULL,
                created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_client_guest (client_id, guest_phone),
                INDEX idx_client (client_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
