<?php
/**
 * mia/services/LeadMemoryService.php
 *
 * Extracts and stores persistent facts about contacts using AI.
 * Keyed by phone number — the persistent identifier in WhatsApp.
 * Lead IDs can change if a contact is deleted/recreated, but the phone stays.
 *
 * FLOW:
 *   1. After Mia replies, the bot service calls extractAndStore().
 *   2. This sends the latest exchange to Groq with a special "extractor" prompt.
 *   3. Groq returns key facts (or "NINGUNO" if nothing new).
 *   4. New facts are saved to mia_lead_memories, deduplicating against existing ones.
 *   5. Before the next conversation, getMemoryBlock() returns a formatted string
 *      that gets injected into the system prompt.
 *
 * LIMITS:
 *   - Max 20 facts per phone (oldest are pruned when exceeded).
 *   - Extraction uses max_tokens=100 to keep costs minimal.
 *   - Only runs for 'lead' contact_type (not staff/friend/proveedor).
 */

declare(strict_types=1);

class LeadMemoryService
{
    private PDO $pdo;
    private const MAX_FACTS_PER_PHONE = 20;

    private const GROQ_KEY   = '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';
    private const GROQ_MODEL = 'llama-3.3-70b-versatile';

    public function __construct()
    {
        $this->pdo = Database::get();
        $this->ensureTable();
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Load remembered facts for a contact by phone, formatted for the system prompt.
     * Returns empty string if no memories exist.
     */
    public function getMemoryBlock(int $clientId, string $phone): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT fact FROM mia_lead_memories
             WHERE client_id = ? AND phone = ?
             ORDER BY created_at ASC
             LIMIT ?'
        );
        $stmt->execute([$clientId, $phone, self::MAX_FACTS_PER_PHONE]);
        $facts = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($facts)) {
            return '';
        }

        $lines = array_map(fn(string $f) => "- {$f}", $facts);

        return "MEMORIA DEL CONTACTO (lo que sabes de conversaciones anteriores):\n"
             . implode("\n", $lines) . "\n"
             . "Usa esta información de forma natural en la conversación. No repitas los datos textualmente ni digas 'según mis registros'. "
             . "Simplemente recuerda estos detalles como si fueras un buen vendedor que conoce a su cliente.";
    }

    /**
     * After a conversation exchange, extract new facts and store them.
     * $userMsg  = what the lead just said
     * $botReply = what Mia just replied
     */
    public function extractAndStore(int $clientId, string $phone, string $userMsg, string $botReply): void
    {
        // Load existing facts to send to the extractor for dedup
        $stmt = $this->pdo->prepare(
            'SELECT fact FROM mia_lead_memories WHERE client_id = ? AND phone = ? ORDER BY created_at ASC'
        );
        $stmt->execute([$clientId, $phone]);
        $existing = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $existingBlock = !empty($existing)
            ? "DATOS YA GUARDADOS (no repetir):\n" . implode("\n", array_map(fn($f) => "- {$f}", $existing))
            : "No hay datos previos guardados.";

        $extractPrompt = <<<PROMPT
Eres un extractor de datos. Analiza este intercambio de WhatsApp y extrae SOLO datos concretos y nuevos sobre el cliente.

{$existingBlock}

INTERCAMBIO:
Cliente: {$userMsg}
Asistente: {$botReply}

REGLAS:
- Extrae solo HECHOS CONCRETOS: nombre, fechas, preferencias, número de personas, presupuesto, lo que buscan, quejas, etc.
- NO extraigas saludos, despedidas, ni información genérica.
- NO repitas datos que ya están guardados arriba.
- Cada hecho en una línea separada, sin guiones ni números.
- Si no hay información nueva relevante, responde EXACTAMENTE: NINGUNO
- Máximo 3 hechos por intercambio.
- Escribe cada hecho como una frase corta y directa (ej: "Se llama María", "Busca habitación doble para 2 noches", "Prefiere check-in tardío").
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $extractPrompt],
            ['role' => 'user',   'content' => 'Extrae los datos nuevos.'],
        ];

        $response = $this->callGroq($messages, 100);

        if (empty($response) || str_contains(strtoupper($response), 'NINGUNO')) {
            return;
        }

        // Parse lines into individual facts
        $lines = preg_split('/\r?\n/', trim($response));
        $newFacts = [];
        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B-•*");
            if (strlen($line) >= 5 && strlen($line) <= 500) {
                $newFacts[] = $line;
            }
        }

        if (empty($newFacts)) {
            return;
        }

        // Insert new facts
        $insertStmt = $this->pdo->prepare(
            'INSERT INTO mia_lead_memories (client_id, phone, fact, source) VALUES (?, ?, ?, ?)'
        );
        foreach (array_slice($newFacts, 0, 3) as $fact) {
            $insertStmt->execute([$clientId, $phone, $fact, 'ai']);
        }

        // Prune if over limit — keep newest
        $this->pruneOldFacts($clientId, $phone);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function pruneOldFacts(int $clientId, string $phone): void
    {
        $countStmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM mia_lead_memories WHERE client_id = ? AND phone = ?'
        );
        $countStmt->execute([$clientId, $phone]);
        $total = (int) $countStmt->fetchColumn();

        if ($total <= self::MAX_FACTS_PER_PHONE) {
            return;
        }

        $excess = $total - self::MAX_FACTS_PER_PHONE;
        $delStmt = $this->pdo->prepare(
            'DELETE FROM mia_lead_memories
             WHERE client_id = ? AND phone = ?
             ORDER BY created_at ASC
             LIMIT ?'
        );
        $delStmt->execute([$clientId, $phone, $excess]);
    }

    private function callGroq(array $messages, int $maxTokens): string
    {
        $payload = json_encode([
            'model'       => self::GROQ_MODEL,
            'messages'    => $messages,
            'temperature' => 0.3,
            'max_tokens'  => $maxTokens,
            'top_p'       => 0.9,
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::GROQ_KEY,
            ],
        ]);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("[LeadMemory] Groq error: {$err}");
            return '';
        }

        $data = json_decode($response, true);
        return trim($data['choices'][0]['message']['content'] ?? '');
    }

    private function ensureTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS mia_lead_memories (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                client_id   INT NOT NULL,
                phone       VARCHAR(50) NOT NULL,
                fact        VARCHAR(500) NOT NULL,
                source      ENUM('ai','manual') NOT NULL DEFAULT 'ai',
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_client_phone (client_id, phone),
                INDEX idx_phone (phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
