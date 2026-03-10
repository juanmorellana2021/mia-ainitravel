<?php
/**
 * mia/services/MiaSalesService.php
 *
 * AI-powered WhatsApp B2B sales assistant — "Mia"
 *
 * Uses Groq LLM (llama-3.3-70b) for natural conversational responses.
 * State machine tracks what has been collected (size, method, pain, name, email).
 * Full conversation history is stored per-phone and passed to the AI for context.
 *
 * Flow: new → intro → qualifying_size → qualifying_method → qualifying_pain
 *       → roi_pitch → demo → benefits → closing → collecting_name
 *       → collecting_email → captured
 */

declare(strict_types=1);

class MiaSalesService
{
    private PDO $pdo;

    // ── AI config (Ollama on AI VPS, same as Sofia) ────────────────────────
    private const OLLAMA_URL   = 'http://72.60.1.16:11434/api/chat';
    private const OLLAMA_MODEL = 'qwen2.5:7b';
    private const AI_TIMEOUT   = 60;

    // How many recent messages to pass as context to the AI
    private const MAX_HISTORY = 14;

    public function __construct()
    {
        $this->pdo = Database::get();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS mia_sales_sessions (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                phone           VARCHAR(50) NOT NULL,
                state           VARCHAR(30) DEFAULT 'new',
                business_name   VARCHAR(255) NULL,
                contact_name    VARCHAR(255) NULL,
                email           VARCHAR(255) NULL,
                business_type   VARCHAR(50)  NULL,
                room_count      INT          NULL,
                current_method  VARCHAR(50)  NULL,
                pain_point      VARCHAR(100) NULL,
                conv_history    MEDIUMTEXT   NULL,
                created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_phone (phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        // Add conv_history column to existing tables (ignore if already exists)
        try {
            $this->pdo->exec("ALTER TABLE mia_sales_sessions ADD COLUMN conv_history MEDIUMTEXT NULL");
        } catch (\Throwable $e) { /* already exists */ }
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PUBLIC API
    // ════════════════════════════════════════════════════════════════════════

    public function process(string $phone, string $message): array
    {
        $phone   = $this->normalizePhone($phone);
        $session = $this->getOrCreateSession($phone);
        $msg     = mb_strtolower(trim($message));

        // Reset command
        if (in_array($msg, ['reset', 'reiniciar', 'empezar de nuevo'], true)) {
            $this->resetSession($phone);
            $session = $this->getOrCreateSession($phone);
        }

        // Human handoff shortcut — any state
        if (preg_match('/\bhumano|agente|persona|hablar con|speak to\b/i', $msg)) {
            $this->appendHistory($phone, 'user', $message);
            $reply = "Por supuesto, te conecto con nuestro equipo ahora mismo 🙋\n\n" .
                     "Puedes escribirnos en *mia.ainitravel.com* o al WhatsApp de soporte — alguien te atiende en minutos.\n\n" .
                     "¡También puedo seguir ayudándote aquí si prefieres! 😊";
            $this->appendHistory($phone, 'assistant', $reply);
            return ['reply' => $reply];
        }

        $state = $session['state'] ?? 'new';

        return match ($state) {
            'new'               => $this->handleNew($phone, $session, $message),
            'intro'             => $this->handleIntro($phone, $session, $message),
            'qualifying_size'   => $this->handleQualifySize($phone, $session, $message),
            'qualifying_method' => $this->handleQualifyMethod($phone, $session, $message),
            'qualifying_pain'   => $this->handleQualifyPain($phone, $session, $message),
            'roi_pitch'         => $this->handleRoiPitch($phone, $session, $message),
            'demo'              => $this->handleDemo($phone, $session, $message),
            'benefits'          => $this->handleBenefits($phone, $session, $message),
            'closing'           => $this->handleClosing($phone, $session, $message),
            'collecting_name'   => $this->handleCollectName($phone, $session, $message),
            'collecting_email'  => $this->handleCollectEmail($phone, $session, $message),
            'captured'          => $this->handleCaptured($phone, $session, $message),
            default             => $this->handleNew($phone, $session, $message),
        };
    }

    // ════════════════════════════════════════════════════════════════════════
    //  STATE HANDLERS
    //
    //  Pattern: extract data from user message → update session state →
    //           call aiReply() with goal instructions for this turn
    // ════════════════════════════════════════════════════════════════════════

    private function handleNew(string $phone, array $session, string $message): array
    {
        $this->updateSession($phone, ['state' => 'intro']);
        $session['state'] = 'intro';
        return $this->aiReply($phone, $session, $message,
            "Primera vez que escribe. Saludo MUY corto y directo — máximo 3 líneas en total. " .
            "Preséntate: Mia de AiniDesk. Una frase de qué hacemos (WhatsApp automático para negocios). " .
            "Termina con UNA sola pregunta: ¿qué tipo de negocio tienes? " .
            "NADA de stats, NADA de casos de éxito aún, NADA de listas. " .
            "Tono: persona real que acaba de conocerte, no vendedor. Breve, cálido, curioso."
        );
    }

    private function handleIntro(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        if (preg_match('/\bno\b|no gracias|not interested|no me interesa|no necesito/i', $msg)) {
            return $this->aiReply($phone, $session, $message,
                "El usuario no está interesado por ahora. Despídete con genuina calidez y sin presión. " .
                "Deja la puerta abierta para el futuro. Una respuesta corta y humana."
            );
        }

        // Capture business type from their reply if mentioned
        $bizType = 'business';
        if (preg_match('/\bagencia|agencia de viajes|travel agency\b/i', $msg)) {
            $bizType = 'agency';
        } elseif (preg_match('/\bhotel|hostal|hostel|lodge|resort\b/i', $msg)) {
            $bizType = 'hotel';
        } elseif (preg_match('/\brestaurante|restaurant|cafe|cafetería\b/i', $msg)) {
            $bizType = 'restaurant';
        } elseif (preg_match('/\btienda|shop|boutique|store\b/i', $msg)) {
            $bizType = 'retail';
        } elseif (preg_match('/\bconsultora|consultora|servicios|services\b/i', $msg)) {
            $bizType = 'services';
        }

        $this->updateSession($phone, ['state' => 'qualifying_size', 'business_type' => $bizType]);
        $session = array_merge($session, ['state' => 'qualifying_size', 'business_type' => $bizType]);
        return $this->aiReply($phone, $session, $message,
            "Acaba de decirte su tipo de negocio ({$bizType}). Reacciona con interés genuino — demuestra que " .
            "CONOCES ese tipo de negocio y sus desafíos. No repitas lo que dijeron. " .
            "Luego haz la pregunta de tamaño con maestría: NO preguntes 'cuántas habitaciones tienes' — " .
            "pregunta algo que haga pensar: por ejemplo para hotel: '¿cuántas noches al mes se te van sin reservar?', " .
            "para restaurante: '¿cuántos pedidos por WhatsApp manejan en una semana normal?', " .
            "para agencia: '¿cuántas consultas de viaje les llegan al día que no pueden atender a tiempo?'. " .
            "La pregunta debe hacer que empiecen a calcular su propia pérdida. Una sola pregunta."
        );
    }

    private function handleQualifySize(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        // Extract number (consultations/clients/rooms per month)
        $roomCount = 30; // default
        if (preg_match('/(\d+)\s*(hab|room|cuarto)/i', $msg, $m)) {
            $roomCount = (int) $m[1];
        } elseif (preg_match('/(\d+)/', $msg, $m)) {
            $roomCount = (int) $m[1];
        } elseif (preg_match('/\bpoco|chico|pequeño|small|menos de 10\b/i', $msg)) {
            $roomCount = 10;
        } elseif (preg_match('/\bgrande|muchos|large|cientos\b/i', $msg)) {
            $roomCount = 100;
        }

        // Also detect biz type if not already set
        $bizType = $session['business_type'] ?? 'business';
        if ($bizType === 'business') {
            if (preg_match('/\bagencia|travel\b/i', $msg)) $bizType = 'agency';
            elseif (preg_match('/\bhotel|hostal|hostel\b/i', $msg)) $bizType = 'hotel';
            elseif (preg_match('/\brestaurante|restaurant\b/i', $msg)) $bizType = 'restaurant';
        }

        $this->updateSession($phone, ['state' => 'qualifying_method', 'room_count' => $roomCount, 'business_type' => $bizType]);
        $session = array_merge($session, ['state' => 'qualifying_method', 'room_count' => $roomCount, 'business_type' => $bizType]);

        return $this->aiReply($phone, $session, $message,
            "Tienes su volumen (~{$roomCount} unidades). Usa esto para implicar pérdida (SPIN — pregunta de implicación): " .
            "brevemente saca una cuenta mental visible para ellos. Ej: 'Con eso, si pierdes solo el 10% de consultas " .
            "sin respuesta, son X clientes al mes.' Hazlos calcular su propia pérdida. " .
            "Luego pregunta cómo manejan WhatsApp HOY — una persona, un sistema, o nadie lo gestiona. " .
            "El objetivo de esta pregunta es revelar que no tienen un sistema formal (la mayoría no lo tiene). " .
            "Adapta el lenguaje a su tipo de negocio ({$bizType})."
        );
    }

    private function handleQualifyMethod(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        $method = 'mixed';
        if (preg_match('/\bmanual|llamada|llamadas|whatsapp\b/i', $msg) || str_contains($msg, '1')) {
            $method = 'manual';
        } elseif (preg_match('/\bformulario|web|página\b/i', $msg) || str_contains($msg, '2')) {
            $method = 'web_form';
        } elseif (preg_match('/\bbooking|airbnb|expedia|ota\b/i', $msg) || str_contains($msg, '3')) {
            $method = 'otas';
        }

        $this->updateSession($phone, ['state' => 'qualifying_pain', 'current_method' => $method]);
        $session = array_merge($session, ['state' => 'qualifying_pain', 'current_method' => $method]);

        $bizType = $session['business_type'] ?? 'business';
        return $this->aiReply($phone, $session, $message,
            "Sabes cómo atienden WhatsApp (método: {$method}). Ahora ejecuta la pregunta de dolor más poderosa que tienes. " .
            "No des opciones como un formulario — haz UNA pregunta abierta que los haga SENTIR el problema: " .
            "Ej: '¿Cuándo fue la última vez que un cliente te escribió y no pudiste responder a tiempo?' " .
            "O: '¿Qué crees que pasa con los clientes que te escriben a las 11pm y no reciben respuesta hasta el día siguiente?' " .
            "Adapta a su negocio ({$bizType}) y método actual ({$method}). " .
            "El objetivo: que ELLOS digan el dolor con sus propias palabras — eso vale más que cualquier argumento tuyo. " .
            "Hazlo sentir como una conversación genuina entre expertos, no como un cuestionario."
        );
    }

    private function handleQualifyPain(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        $pain = 'general';
        if (preg_match('/\bhorario|noche|fuera de\b/i', $msg) || str_contains($msg, '1')) {
            $pain = 'after_hours';
        } elseif (preg_match('/\btiempo|lento|demora|tardo|rápido\b/i', $msg) || str_contains($msg, '2')) {
            $pain = 'slow_replies';
        } elseif (preg_match('/\bconfirman|no reservan|nunca compran\b/i', $msg) || str_contains($msg, '3')) {
            $pain = 'no_confirm';
        } elseif (preg_match('/\bcomision|comisión|booking\.com|porcentaje\b/i', $msg) || str_contains($msg, '4')) {
            $pain = 'high_commissions';
        }

        $this->updateSession($phone, ['state' => 'roi_pitch', 'pain_point' => $pain]);
        $session = array_merge($session, ['state' => 'roi_pitch', 'pain_point' => $pain]);

        // Build ROI numbers for the context
        $rooms        = (int) ($session['room_count'] ?? 25);
        $lostPerNight = max(2, (int) ($rooms * 0.15));
        $monthlyLost  = $lostPerNight * 180 * 30;
        $captured     = (int) ($monthlyLost * 0.30);
        $roi          = max(2, (int) ($captured / 399));

        $bizType = $session['business_type'] ?? 'business';

        $painContextMap = [
            'after_hours'      => 'pierden clientes/ventas cuando escriben fuera del horario de atención',
            'slow_replies'     => 'no pueden responder rápido a todas las consultas y pierden ventas por velocidad de respuesta',
            'no_confirm'       => 'los clientes preguntan por WhatsApp pero no concretan la compra porque el seguimiento es lento o manual',
            'high_commissions' => 'dependen de plataformas de terceros con comisiones altas y quieren vender directamente',
            'general'          => 'tienen consultas sin atender en WhatsApp que representan ventas perdidas',
        ];

        return $this->aiReply($phone, $session, $message,
            "MOMENTO DE VERDAD — el prospecto acaba de articular su dolor. Ahora ejecuta el pitch de ROI perfecto. " .
            "Negocio: {$bizType} | Volumen: ~{$rooms} | Dolor: {$painContextMap[$pain]}. " .
            "PASO 1 — Valida su dolor con empatía real, no corporativa. Demuestra que entiendes exactamente QUÉ les cuesta. " .
            "PASO 2 — Ponle número a su pérdida: 'Con {$rooms} clientes/mes y un 15% sin respuesta, " .
            "estás dejando ir ~S/" . number_format($monthlyLost) . " al mes — no porque no quieras atenderlos, " .
            "sino porque físicamente no puedes estar las 24h.' Haz que SIENTAN ese número. " .
            "PASO 3 — El contraste: Mia cuesta S/399/mes. Si solo captura 1 de cada 3 clientes perdidos, " .
            "tienes S/{$roi} de retorno por cada sol invertido. No es un gasto — es la inversión más obvia del año. " .
            "PASO 4 — Usa el caso de éxito más relevante para SU tipo de negocio del system prompt. " .
            "PASO 5 — Cierra este turno con UNA pregunta de micro-compromiso: '¿Quieres que te muestre exactamente " .
            "cómo funciona para un negocio como el tuyo en 2 minutos?' — espera su respuesta."
        );
    }

    private function handleRoiPitch(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        if (preg_match('/\bdemo|ver|muestra|show\b/i', $msg) ||
            preg_match('/\bsí\b|\bsi\b|\byes\b|\bok\b|\bdale\b|\bclaro\b|\bbueno\b|\binteresa\b|\bquiero\b/i', $msg)) {

            $this->updateSession($phone, ['state' => 'demo']);
            $session['state'] = 'demo';

            $bizType = $session['business_type'] ?? 'business';
            return $this->aiReply($phone, $session, $message,
                "DEMO TIME — haz esto cinematográfico, no un manual de instrucciones. " .
                "Pon contexto: 'Son las 11:30pm. El dueño de {$bizType} está dormido. Un cliente escribe...' " .
                "Luego muestra la conversación REAL entre cliente y Mia — con nombres inventados pero realistas, " .
                "mensajes naturales, respuestas rápidas e inteligentes de Mia. " .
                "Para hotel/agencia: cliente consulta disponibilidad → Mia pregunta fechas → confirma precio → reserva hecha. " .
                "Para restaurante: cliente pide delivery → Mia toma pedido → confirma tiempo de entrega. " .
                "Para retail/servicios: cliente pregunta precio → Mia responde + ofrece variante → cliente compra. " .
                "Al final de la demo: 'Y mientras eso pasaba, {$bizType} recibió esta notificación: " .
                "[muestra el WhatsApp de alerta al dueño con nombre, pedido y datos del cliente].' " .
                "Pausa dramática. Luego: '¿Qué te pareció?' — espera su reacción."
            );
        }

        // Objection or hesitation
        return $this->aiReply($phone, $session, $message,
            "El prospecto tiene dudas o no respondió con un sí claro. Técnica Feel/Felt/Found: " .
            "1. 'Entiendo cómo te sientes — [parafrasea su duda específica]' " .
            "2. 'Otros dueños de {$bizType} sentían lo mismo cuando los conocí' " .
            "3. 'Lo que encontraron fue...' [usa un caso de éxito específico del system prompt]. " .
            "NO repitas números ya mencionados. NO lances más features. " .
            "PRIMERO pregunta qué es exactamente lo que le genera dudas — puede que sea algo simple. " .
            "Escucha la objeción real antes de responder. Termina con UNA pregunta suave de avance."
        );
    }

    private function handleDemo(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        if (preg_match('/\bbenefi|incluye|incluido|feature|funcional\b/i', $msg)) {
            $this->updateSession($phone, ['state' => 'benefits']);
            $session['state'] = 'benefits';
            return $this->aiReply($phone, $session, $message,
                "El usuario quiere saber todo lo que incluye Mia. Presenta los beneficios clave con entusiasmo: " .
                "reservas 24/7, bilingüe automático, traspaso humano inteligente, notificaciones instantáneas, " .
                "email de confirmación con logo, verificación de identidad, panel web, sin comisiones por reserva, " .
                "configuración en 48h. Termina con mención de la prueba gratis de 7 días."
            );
        }

        $this->updateSession($phone, ['state' => 'closing']);
        $session['state'] = 'closing';
        $bizType = $session['business_type'] ?? 'negocio';
        $rooms   = (int)($session['room_count'] ?? 30);
        return $this->aiReply($phone, $session, $message,
            "Acaban de ver la demo. Capitaliza el momento emocional — están en su pico de interés AHORA. " .
            "NO presentes los 3 planes como lista genérica. Recomienda UNO basado en lo que sabes de su negocio: " .
            "si tienen volumen alto o son agencia/hotel → Pro S/699. Si son pequeños o acaban de arrancar → Básico S/399. " .
            "Di algo como: 'Para un {$bizType} de tu tamaño, el plan Pro tiene más sentido porque...' " .
            "Menciona los 7 días gratis como eliminador de riesgo: 'No arriesgas nada — pruébalo gratis 7 días " .
            "y si no ves resultados, cancelas con un mensaje.' " .
            "Cierre de elección (no sí/no): '¿Empezamos con el Pro o prefieres el Básico para la prueba?' " .
            "La primera persona que habla después de esa pregunta, pierde."
        );
    }

    private function handleBenefits(string $phone, array $session, string $message): array
    {
        $this->updateSession($phone, ['state' => 'closing']);
        $session['state'] = 'closing';
        return $this->aiReply($phone, $session, $message,
            "Después de mostrar los beneficios, es momento de cerrar. " .
            "Presenta los planes (Básico S/399, Pro S/699, Enterprise S/1,199) de forma concisa. " .
            "Destaca la prueba de 7 días gratis sin compromiso. " .
            "Basándote en lo que sabes de su negocio, sugiere cuál plan le encajaría mejor."
        );
    }

    private function handleClosing(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        if (preg_match('/\bempezar|activar|prueba|quiero|lo quiero|start|trial|básico|pro|enterprise\b/i', $msg) ||
            preg_match('/\b1\b|\b2\b|\b3\b/', $msg)) {

            $this->updateSession($phone, ['state' => 'collecting_name']);
            $session['state'] = 'collecting_name';
            return $this->aiReply($phone, $session, $message,
                "El usuario quiere empezar. Exprésate con entusiasmo genuino — tomó una buena decisión. " .
                "Para activar la prueba necesitas el nombre de su hotel o agencia. " .
                "Pídelo de forma cálida y natural, como si fuera el primer paso de algo emocionante."
            );
        }

        // Objection or hesitation at closing
        $bizType = $session['business_type'] ?? 'negocio';
        return $this->aiReply($phone, $session, $message,
            "Objeción en fase de cierre — momento más crítico de la venta. NO des lista de objeciones genéricas. " .
            "PRIMERO: diagnostica qué tipo de objeción es basándote en lo que dijeron: " .
            "¿precio? ¿tiempo? ¿incertidumbre? ¿necesitan convencer a su socio/esposo/a? " .
            "Luego aplica Find/Felt/Found + elimina el riesgo específico: " .
            "• Precio → '¿Cuánto cobra Booking.com por una reserva? S/399 al mes es menos que 1 comisión.' " .
            "• Tiempo/técnico → 'No tocas nada — el equipo lo monta en 48h mientras tú sigues con tu negocio.' " .
            "• Incertidumbre → '7 días gratis, sin tarjeta. Si en una semana no ves 1 cliente extra, " .
            "cancelas con un WhatsApp y punto.' " .
            "• Debo hablarlo → 'Claro. ¿Qué información necesitas para presentárselo a [él/ella]? Te lo preparo.' " .
            "Siempre termina con UNA pregunta de cierre suave — elección, no sí/no."
        );
    }

    private function handleCollectName(string $phone, array $session, string $message): array
    {
        $name = trim($message);

        if (strlen($name) < 2) {
            return $this->aiReply($phone, $session, $message,
                "No pudo capturar el nombre del negocio. Pide de nuevo el nombre de su empresa/negocio, de forma amigable."
            );
        }

        $this->updateSession($phone, ['state' => 'collecting_email', 'business_name' => $name]);
        $session = array_merge($session, ['state' => 'collecting_email', 'business_name' => $name]);

        $bizType = $session['business_type'] ?? 'negocio';
        return $this->aiReply($phone, $session, $message,
            "Tienes el nombre del negocio: {$name} ({$bizType}). Celebra brevemente — hazlos sentir que tomaron " .
            "una buena decisión. Crea anticipación: menciona que en 48h el equipo los contactará para configurar todo. " .
            "Pide el email de forma natural: es para enviarles los accesos + un resumen de lo que conversaron. " .
            "Hazlo sentir como el primer paso de algo importante, no como llenar un formulario."
        );
    }

    private function handleCollectEmail(string $phone, array $session, string $message): array
    {
        if (!preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $message, $m)) {
            return $this->aiReply($phone, $session, $message,
                "No detectaste un email válido. Pide de nuevo el email de contacto amigablemente — " .
                "es para enviarle los accesos a la prueba gratuita de 7 días."
            );
        }

        $email = $m[0];
        $this->updateSession($phone, ['state' => 'captured', 'email' => $email]);
        $session = array_merge($session, ['state' => 'captured', 'email' => $email]);

        try {
            $leadService = new LeadService();
            $s = SalesSession::fromRow($this->getSession($phone));
            $leadService->createFromSession($s);
        } catch (\Throwable $e) {
            error_log('[Mia] Lead creation error: ' . $e->getMessage());
        }

        $bizName = $session['business_name'] ?? 'tu negocio';
        $bizType = $session['business_type'] ?? 'negocio';
        return $this->aiReply($phone, $session, $message,
            "¡CIERRE EXITOSO! Datos completos: {$bizName} ({$bizType}), email: {$email}. " .
            "Momento final más importante de toda la conversación — hazlo memorable. " .
            "1. Confirma con energía genuina — no exagerada, real. Ellos acaban de tomar una buena decisión. " .
            "2. Pinta el futuro en 48h: 'Mañana el equipo te escribe para definir cómo suena Mia para {$bizName}. " .
            "Pasado mañana, Mia ya está respondiendo tus clientes mientras tú duermes.' " .
            "3. Dales un insight final de regalo — algo que puedan hacer ya: " .
            "'Mientras tanto, anota las 5 preguntas que más te hacen tus clientes por WhatsApp. " .
            "Eso ayudará al equipo a configurar Mia perfectamente para ti.' " .
            "4. Cierra con calidez, brevedad y confianza. Tú sabes que tomaron la decisión correcta."
        );
    }

    private function handleCaptured(string $phone, array $session, string $message): array
    {
        return $this->aiReply($phone, $session, $message,
            "El cliente ya está registrado y esperando ser contactado por el equipo de AiniDesk. " .
            "Responde a su mensaje de forma útil y amigable. Si tiene preguntas sobre el producto, " .
            "respóndelas con precisión. Si quiere hablar con alguien ya mismo, indica mia.ainitravel.com. " .
            "Sé su asistente personal mientras llega el equipo."
        );
    }

    // ════════════════════════════════════════════════════════════════════════
    //  AI ENGINE — Groq LLM with conversation history
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Generate an AI reply using full conversation history as context.
     * Appends user message to history before calling, appends AI reply after.
     */
    private function aiReply(
        string $phone,
        array  $session,
        string $userMessage,
        string $turnGoal = ''
    ): array {
        $this->appendHistory($phone, 'user', $userMessage);

        $history  = $this->loadHistory($phone);
        $messages = array_merge(
            [['role' => 'system', 'content' => $this->buildSystemPrompt($session, $turnGoal)]],
            $history
        );

        $reply = $this->callGroq($messages);
        $this->appendHistory($phone, 'assistant', $reply);

        return ['reply' => $reply];
    }

    private function buildSystemPrompt(array $session, string $turnGoal): string
    {
        $state   = $session['state']          ?? 'new';
        $volume  = $session['room_count']     ? "{$session['room_count']} unidades/clientes/mes" : 'desconocido';
        $method  = $session['current_method'] ? $this->methodLabel($session['current_method']) : 'desconocido';
        $pain    = $session['pain_point']     ? $this->painLabel($session['pain_point']) : 'desconocido';
        $bizName = $session['business_name']  ?? 'desconocido';
        $email   = $session['email']          ?? 'pendiente';
        $bizType = $session['business_type']  ?? 'negocio';

        $goalBlock = $turnGoal
            ? "\n\n═══ TU MISIÓN EN ESTE TURNO ═══\n{$turnGoal}"
            : '';

        return <<<PROMPT
Eres *Mia*, la mejor consultora de ventas de AiniDesk — y las mejores vendedoras hablan MENOS, no más.

Tu superpoder es la precisión. Un mensaje corto y elegido con cuidado cierra más ventas que tres párrafos. Como dijo Pascal: "Hubiera escrito una carta más corta, pero no tuve el tiempo." Tú SÍ tienes el tiempo — y la inteligencia para elegir la única cosa que importa decir ahora.

ASÍ ESCRIBES TÚ (WhatsApp humano, no email corporativo):
✅ "Hola! Soy Mia 😊 ¿Qué tipo de negocio tienes?"
✅ "Entiendo. Y cuando no hay nadie — ¿cuántos clientes crees que se van sin respuesta?"
✅ "Perfecto. ¿Quieres probarlo gratis 7 días, sin compromiso?"

ASÍ NUNCA ESCRIBES:
❌ Dos o más párrafos separados por una línea en blanco
❌ Listas con viñetas para responder una pregunta simple
❌ "¡Hola! Me alegra que hayas escrito. Soy Mia de AiniDesk y me especializo en..."
❌ Explicar tu razonamiento — solo da el resultado

REGLA DE ORO: Si escribiste más de 3 líneas, borra y elige solo lo más importante. Eso es pensar, no escribir más.

═══ TU FILOSOFÍA DE VENTAS (INTERIORIZA ESTO) ═══
• *Diagnostica antes de recetar*: Haz preguntas inteligentes. Un buen médico no receta sin escuchar.
• *Amplifica la consecuencia*: No solo describes el problema — haces que sientan lo que les CUESTA cada día sin solución. "¿Cuántos clientes crees que se fueron porque respondiste 4 horas tarde?" golpea más que cualquier feature.
• *Enseña antes de vender* (Challenger Sale): Comparte un insight que no habían considerado. Ej: "El 67% de los clientes por WhatsApp no vuelven a escribir si no responden en 5 minutos."
• *Micro-compromisos* (Sí progresivos): Consigue pequeños "sí" antes del gran "sí". "¿Te pasa eso?" → "¿Cuánto crees que pierdes?" → "¿Querrías ver cómo lo resolvemos?"
• *Pérdidas antes que ganancias*: La pérdida duele 2x más que la ganancia. No digas "gana más" — di "deja de perder X al mes".
• *Historias reales, no features*: "Un restaurante en Lima que tenía el mismo problema que tú ahora recibe 23 pedidos extra al mes por WhatsApp" vende más que cualquier lista de funciones.
• *Objeciones = preguntas disfrazadas*: Si dicen "está caro" realmente preguntan "¿vale la pena?". Si dicen "lo pensaré" realmente dicen "no me convencí aún". Responde a lo que NO dijeron.
• *Un paso a la vez*: Nunca intentes cerrar antes de tiempo. Tu única tarea en cada turno es llevarlos al SIGUIENTE paso, no al final.
• *Silencio después del cierre*: Cuando hagas la pregunta de cierre, quédate callada. La primera persona que habla pierde.

═══ PRODUCTO: MIA POR AINIDESK ═══
Mia es un asistente de WhatsApp con IA configurable para CUALQUIER negocio:
• Responde clientes 24/7 — incluso a las 2am cuando el dueño duerme
• Maneja preguntas frecuentes, muestra catálogo/servicios/precios, toma pedidos y reservas
• Bilingüe automático (español/inglés sin configuración)
• *Traspaso inteligente*: cuando el dueño toma el control, el bot se aparta solo — natural y sin fricción
• Notificaciones al instante: cada venta/reserva/pedido llega por WhatsApp y email
• *Seguimiento automático*: persigue a los que preguntaron y no compraron (recupera el 30% de leads perdidos)
• Panel web: historial de conversaciones, ingresos, estadísticas en tiempo real
• Sin comisiones por venta — tarifa fija mensual predecible
• Configuración completa en 48h — el equipo lo hace todo, el cliente no toca nada técnico

═══ CASOS DE ÉXITO REALES (usa estos en conversación) ═══
• *Hotel Cusco* (40 hab): Pasó de 12 a 17 reservas directas semanales en el primer mes. Ahorra S/2,800/mes en comisiones de Booking.com. ROI: 700%.
• *Agencia de viajes Lima*: Mia atiende 180 consultas/mes fuera de horario. Cierra 22% de esas consultas sin intervención humana.
• *Restaurante Miraflores*: 31 pedidos adicionales/mes por WhatsApp que antes se perdían porque no había quien respondiera a tiempo.
• *Consultora de servicios*: Agenda 14 citas automáticamente al mes que antes se caían por respuesta lenta.

═══ PLANES ═══
• *Básico S/399/mes* — hasta 200 conversaciones/mes. Ideal para empezar.
• *Pro S/699/mes* — conversaciones ilimitadas + panel completo + reportes automáticos. El más popular.
• *Enterprise S/1,199/mes* — múltiples números WhatsApp, integraciones personalizadas, soporte VIP.
• Configuración: S/500 pago único (incluye toda la personalización)
• 🎁 *7 días GRATIS* — sin tarjeta, sin compromiso, cancela cuando quieras.

═══ OBJECIONES FRECUENTES Y CÓMO MANEJARLAS ═══
• "Está caro" → "Entiendo. ¿Cuánto cuesta hoy una sola comisión de Booking.com o perder UN cliente grande? S/399 al mes es menos de S/14 al día. ¿Cuánto vale para ti atender 1 cliente extra por semana?"
• "Lo voy a pensar" → "Claro, es una decisión importante. Solo quiero asegurarme de haberte dado toda la información — ¿hay algo específico que te genera duda? Prefiero resolver eso ahora."
• "No tengo tiempo para configurarlo" → "Por eso lo hacemos nosotros. Tú no tocas nada — en 48h está listo y funcionando."
• "Ya tenemos alguien respondiendo WhatsApp" → "Genial. ¿Esa persona responde a las 2am? ¿Los domingos? ¿En menos de 60 segundos siempre? Mia no reemplaza a tu equipo — lo libera para las conversaciones que sí necesitan un humano."
• "No sé si funcionará para mi negocio" → "Por eso existe la prueba de 7 días — para que lo veas funcionando en TU negocio, con TUS clientes, antes de comprometer un sol."

═══ CONTEXTO ACTUAL DEL PROSPECTO ═══
Etapa: {$state} | Tipo de negocio: {$bizType}
Volumen: {$volume} | Método actual: {$method} | Dolor principal: {$pain}
Nombre del negocio: {$bizName} | Email: {$email}

═══ REGLAS DE COMUNICACIÓN ═══
• Español natural; inglés si el usuario escribe en inglés
• 1-2 emojis máximo, solo si suman
• Termina con UNA sola pregunta o acción — nunca dos
• NUNCA repitas lo que ya dijiste en el historial — avanza
• NUNCA suenes a script corporativo. Cada mensaje fresco, como un humano real
• Si no sabes algo, ofrece conectarlos con el equipo: *mia.ainitravel.com*
• Si dicen que no les interesa, respeta su decisión con elegancia y cierra bien
• Listas con viñetas: SOLO para mostrar planes/precios cuando el cliente lo pide{$goalBlock}
PROMPT;
    }

    private function callGroq(array $messages): string
    {
        // Use the shared AI gateway (Ollama 3s → Groq fallback) just like Sofia
        require_once '/var/www/html/ainitravel.com/ai_gateway.php';

        $result = ai_chat($messages, [
            'temperature' => 0.72,
            'num_predict' => 100,   // hard cap: ~75 words = physically one short paragraph
            'max_tokens'  => 100,   // for Groq side
            'top_p'       => 0.9,
        ]);

        if (!empty($result['response'])) {
            error_log("[Mia] AI response via {$result['source']}");
            // Hard-enforce single paragraph: strip everything after first blank line
            $text = trim($result['response']);
            $firstBreak = strpos($text, "\n\n");
            if ($firstBreak !== false) {
                $text = trim(substr($text, 0, $firstBreak));
            }
            return $text;
        }

        error_log("[Mia] Both Ollama and Groq failed");
        return "Lo siento, tuve un pequeño problema técnico. Intenta de nuevo en un momento 🙏";
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CONVERSATION HISTORY
    // ════════════════════════════════════════════════════════════════════════

    private function loadHistory(string $phone): array
    {
        $stmt = $this->pdo->prepare("SELECT conv_history FROM mia_sales_sessions WHERE phone = ?");
        $stmt->execute([$phone]);
        $data = $stmt->fetchColumn();
        if (!$data) {
            return [];
        }
        $history = json_decode($data, true) ?? [];
        return array_slice($history, -self::MAX_HISTORY);
    }

    private function appendHistory(string $phone, string $role, string $content): void
    {
        $stmt = $this->pdo->prepare("SELECT conv_history FROM mia_sales_sessions WHERE phone = ?");
        $stmt->execute([$phone]);
        $data    = $stmt->fetchColumn();
        $history = $data ? (json_decode($data, true) ?? []) : [];

        $history[] = ['role' => $role, 'content' => $content];
        $history   = array_slice($history, -30); // keep last 30 entries (15 turns)

        $this->pdo->prepare("UPDATE mia_sales_sessions SET conv_history = ? WHERE phone = ?")
            ->execute([json_encode($history, JSON_UNESCAPED_UNICODE), $phone]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════════════════════════

    private function methodLabel(string $method): string
    {
        return match ($method) {
            'manual'   => 'llamadas/WhatsApp manual',
            'web_form' => 'formulario web',
            'otas'     => 'OTAs (Booking/Airbnb)',
            default    => 'múltiples canales',
        };
    }

    private function painLabel(string $pain): string
    {
        return match ($pain) {
            'after_hours'      => 'pierde clientes fuera de horario',
            'slow_replies'     => 'respuestas lentas',
            'no_confirm'       => 'huéspedes que no confirman reserva',
            'high_commissions' => 'comisiones altas en OTAs',
            default            => 'problemas generales de reservas',
        };
    }

    // ════════════════════════════════════════════════════════════════════════
    //  SESSION MANAGEMENT
    // ════════════════════════════════════════════════════════════════════════

    private function getOrCreateSession(string $phone): array
    {
        $session = $this->getSession($phone);
        if ($session) {
            return $session;
        }
        $this->pdo->prepare("INSERT INTO mia_sales_sessions (phone, state) VALUES (?, 'new')")
            ->execute([$phone]);
        return $this->getSession($phone);
    }

    private function getSession(string $phone): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM mia_sales_sessions WHERE phone = ?");
        $stmt->execute([$phone]);
        return $stmt->fetch() ?: null;
    }

    private function updateSession(string $phone, array $fields): void
    {
        $sets   = [];
        $values = [];
        foreach ($fields as $col => $val) {
            $sets[]   = "$col = ?";
            $values[] = $val;
        }
        $values[] = $phone;
        $sql = "UPDATE mia_sales_sessions SET " . implode(', ', $sets) . " WHERE phone = ?";
        $this->pdo->prepare($sql)->execute($values);
    }

    private function resetSession(string $phone): void
    {
        $this->pdo->prepare("DELETE FROM mia_sales_sessions WHERE phone = ?")->execute([$phone]);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}

